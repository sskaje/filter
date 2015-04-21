#include <errno.h>
#include <string.h>
#include <stddef.h>
#include <unistd.h>
#include <stdlib.h>

#include "log.h"
#include "list.h"
#include "worker.h"
#include "engine.h"
#include "common.h"
#include "smm/actypes.h"
#include "protocol.h"

static Engine* get_match_engine(WorkerContext* context) {
	if (context->newer->serial > context->current->serial) {
		engine_destroy(context->current);
		context->current = context->newer;
	}
	return context->current;
}

static void send_match_result(int client, List* result) {
	log_debug("Sending results");

	PROTOCOL_HEADER resp;
	resp.Version = PROTOCOL_VERSION;
	resp.Command = CMD_RESULT;
	resp.Length  = 0;

	char buffer[MAX_DATA_LENGTH+1] = {'\0'};
	RESULT_PAIR rp;

	int offset = 0;
	while (offset + sizeof(RESULT_PAIR) < (int)sizeof(buffer)
			&& result != NULL) {

		rp.Length = FLT_LIST_GET(result, int)[1];
		rp.StartPos = FLT_LIST_GET(result, int)[0] - rp.Length;
		rp.Flag = FLT_LIST_GET(result, int)[2];

		memcpy(buffer+offset, &rp, sizeof(rp));

		offset += sizeof(rp);
		result = result->next;
	}
	resp.Length = offset;
	log_debug("Result data length=%d", resp.Length);

	write(client, &resp, sizeof(resp));
	write(client, buffer, resp.Length);
}

static void send_error(int client, char* message, int length)
{
	PROTOCOL_HEADER resp;
	resp.Version = PROTOCOL_VERSION;
	resp.Command = CMD_ERROR;
	resp.Length  = length;

	write(client, &resp, sizeof(resp));
	write(client, message, length);
}

static void send_pong(int client, int length, int flag)
{
	PROTOCOL_HEADER resp;
	resp.Version = PROTOCOL_VERSION;
	resp.Command = CMD_PONG;
	resp.Length  = length;
	resp.Flag    = flag;

	char buffer[length+1];

	if (length > 0) {
		memset(buffer, '\0', sizeof(buffer));
		read(client, buffer, length);
	}

	write(client, &resp, sizeof(resp));

	if (length > 0) {
		write(client, buffer, length);
	}
}

static void read_main_text(int client, int length, Engine* engine) {
	char buffer[1024];
	int remain = length;
	while (remain > 0) {
		int max = sizeof(buffer);
		if (remain < max) {
			max = remain;
		}
		int n = read(client, buffer, max);
		if (n < 0) {
			log_warning("read() failed, %s", strerror(errno));
			continue;
		}
		if (n == 0) {
			log_error("client closed connection unexpectedly");
			return;
		}
		engine_feed_text(engine, buffer, n);
		remain -= n;
	}
}

static void serve_client(int client, WorkerContext* context) {
	Engine* engine = get_match_engine(context);
	PROTOCOL_HEADER req;
	for (;;) {
		int n = read(client, &req, sizeof(req));
		if (n < 0) {
			log_error("failed to read data from client: %s",
					strerror(errno));
			break;
		}
		if (n == 0) {
			break;
		}
		log_info("Client requests received. Command=%d, Length=%d, Flags=%d",
			req.Command, req.Length, req.Flag);

		if (req.Command == CMD_MATCH) {
			// reset engine
			read_main_text(client, req.Length, engine);
			List* result = engine_get_result(engine);
			engine_reset(engine);
			send_match_result(client, result);
		} else if (req.Command == CMD_PING) {
			send_pong(client, req.Length, req.Flag);
		} else {
			send_error(client, "Bad command!", strlen("Bad command!"));
		}
	}
}

void* match_thread(void* p) {
	WorkerContext* context = p;
	while (1) {
		int client;
		read(context->client_pipe[0], &client, sizeof(client));
		serve_client(client, context);
		close(client);
		++context->served_count;
		mpool_reset(context->pool);
	}
	return NULL;
}

void worker_context_init(WorkerContext* context) {
	context->pool = mpool_create();
	context->newer = engine_init(context->pool);
	context->current = context->newer;
	context->served_count = 0;
	context->waiting_count = 0;
	pipe(context->client_pipe);
}

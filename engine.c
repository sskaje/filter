#include <stdlib.h>
#include <string.h>
#include <unistd.h>

#include "list.h"
#include "log.h"
#include "engine.h"

Engine* engine_init(MemoryPool* pool) {
	Engine* engine = malloc(sizeof(Engine));
	engine->automata = ac_automata_init();
	engine->pool = pool;
	engine->serial = 0;
	engine_reset(engine);
	return engine;
}

void engine_reset(Engine* engine) {
	engine->fresh = 1;
	engine->match = NULL;
	engine->tail = &engine->match;
}

void engine_destroy(Engine* engine) {
	ac_automata_release(engine->automata);
	free(engine);
}

void engine_add_pattern(Engine* engine, const AC_ALPHABET_t* pattern,
		int length) {

	int flag = 0;
	// find the last : and split
	int c = 0;
	int c_max = 5;
	for (int i = length - 1; i >=0; i--) {
		if ((pattern[i] < '0' || pattern[i] > '9') && pattern[i] != ':') {
			break;
		}

		++c;
		if (c >= c_max) {
			break;
		}
		if (pattern[i] == ':') {
			char flag_tmp[c_max];
			memcpy(flag_tmp, pattern + i + 1, c);
			flag_tmp[c+1] = '\0';
			flag = atoi(flag_tmp);
			length = i;
			break;
		}
	}

	if (length < 1) {
		return;
	}

	log_debug("add pattern: %s(len=%d), flag=%d", pattern, length, flag);

	AC_PATTERN_t ptn = {
			pattern, length, flag,
			{NULL}
	};
	ac_automata_add(engine->automata, &ptn);
}

static int handle_match(AC_MATCH_t* match, void* p) {
	Engine* engine = p;
	List* node = mpool_alloc(engine->pool, sizeof(List) + sizeof(int) * 3);
	FLT_LIST_GET(node, int)[0] = match->position;
	FLT_LIST_GET(node, int)[1] = match->patterns[0].length;
	FLT_LIST_GET(node, int)[2] = match->patterns[0].flag;

	*engine->tail = node;
	engine->tail = &node->next;
	return 0;
}

void engine_feed_text(Engine* engine, const AC_ALPHABET_t* text,
		int length) {
	AC_TEXT_t t = {
			text, length
	};

	log_debug("Keep = %d", !engine->fresh);

	ac_automata_search(engine->automata, &t, !engine->fresh, &handle_match,
			engine);
	if (engine->fresh) {
		engine->fresh = 0;
	}
}

List* engine_get_result(Engine* engine) {
	*engine->tail = NULL;
	engine->fresh = 1;
	return engine->match;
}

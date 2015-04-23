<?php

/**
 * 关键词匹配服务PHP API
 *
 * @author sskaje (http://sskaje.me)
 */
class filter
{
    const CMD_NONE = 0;
    const CMD_MATCH = 1;
    const CMD_RESULT = 2;
    const CMD_ADD = 3;
    const CMD_DELETE = 4;
    const CMD_PING = 253;
    const CMD_PONG = 254;
    const CMD_ERROR=255;

    const VERSION = 1;
    const HEADER_LENGTH = 6;

    const RESULT_PAIR_LENGTH = 6;

    protected $host;
    protected $port;

    protected $fp;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = intval($port);

        $this->connect();
    }

    public function __destruct()
    {
        $this->close();
    }

    public function connect()
    {
        if (empty($this->host)) {
            throw new filterException("host can not be empty", 100001);
        }
        if (empty($this->port) || $this->port < 1 || $this->port > 65535) {
            throw new filterException("Invalid port", 100002);
        }

        $ctx = stream_context_create();
        $connect_flag = STREAM_CLIENT_CONNECT | ~STREAM_CLIENT_PERSISTENT;

        $fp = stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $error,
            100,
            $connect_flag,
            $ctx
        );

        if (!$fp) {
            throw new filterException("Connection failed. {$error}#{$errno}", 100003);
        }

        stream_set_blocking($fp, 1);

        stream_set_write_buffer($fp, 0);

        $this->fp = $fp;
    }

    public function close()
    {
        fclose($this->fp);
    }

    /**
     * 匹配文本
     *
     * @param string $text             输入文本，UTF-8
     * @param array $result_pairs      返回结果集数组
     * @return int                     匹配条数
     * @throws filterException
     */
    public function match($text, &$result_pairs = array())
    {
        if (isset($text[0x10000]) || empty($text)) {
            throw new filterException("bad length", 200001);
        }

        $packet = pack('CC', self::VERSION, self::CMD_MATCH);
        $packet .= pack('v', 0);                # flag
        $packet .= pack('v', strlen($text));    # big endian
        $packet .= $text;

        # socket write
        $r = fwrite($this->fp, $packet);

        if (!$r) {
            throw new filterException("write failed", 200002);
        }
        #echo "{$r} bytes written\n";

        $s = fread($this->fp, self::HEADER_LENGTH);
        $p = unpack("Cversion/Ccommand/vflag/vlen", $s);
        #echo "Command = {$p['command']} Length={$p['len']} Flags={$p['flag']}\n";

        if ($p['command'] != self::CMD_RESULT) {
            if ($p['command'] == self::CMD_ERROR) {
                if ($p['len']) {
                    $result = fread($this->fp, $p['len']);
                    echo "Error: $result\n";
                    return false;
                }
            }
            throw new filterException("bad response", 200003);
        }
        if ($p['len']) {
            $result = fread($this->fp, $p['len']);

            $pair_length = self::RESULT_PAIR_LENGTH;
            $start_pos = 0;

            $count = 0;
            do {
                $rr = unpack("vpos/vlen/vflag", substr($result, $start_pos, $pair_length));
                $rr['word'] = substr($text, $rr['pos'], $rr['len']);
                $result_pairs[] = $rr;
                $start_pos += $pair_length;
                ++$count;
            } while(isset($result[$start_pos]));

            return $count;
        }

        return 0;
    }

    /**
     * 过滤文本
     *
     * @param string $text            待过滤文本，UTF-8
     * @param string $replace_with    替换字符，暂时只接受ASCII，且长度为1，默认为'*'
     * @param bool $word_replace      是否按宽字符替换，如果启用则UTF-8中文单个汉字被替换后为**，否则为UTF-8字节数
     * @return string
     * @throws filterException
     */
    public function filter($text, $replace_with='*', $word_replace=false)
    {
        $result = $this->match($text, $result_pairs);
        if (!$result) {
            return $text;
        }

        if (empty($replace_with)) {
            $replace_with = '*';
        }

        if (!$word_replace) {
            foreach ($result_pairs as $t) {
                $this->ascii_replace($text, $t['pos'], $t['len'], $replace_with[0]);
            }
        } else {
            foreach ($result_pairs as $t) {
                $slice = substr($text, $t['pos'], $t['len']);
                if (($width = mb_strwidth($slice, 'UTF-8')) != $t['len']) {
                    $text = substr($text, 0, $t['pos']) . str_repeat($replace_with[0], $width) . substr($text, $t['pos'] + $t['len']);
                } else {
                    $this->ascii_replace($text, $t['pos'], $t['len'], $replace_with[0]);
                }
            }
        }

        return $text;
    }

    /**
     * 按 ASCII 逐个字符替换
     *
     * @param string & $text
     * @param int $start
     * @param int $length
     * @param string $replace_char
     */
    protected function ascii_replace(&$text, $start, $length, $replace_char)
    {
        for ($i = 0; $i<$length; $i++) {
            $text[$start + $i] = $replace_char;
        }
    }

    /**
     * PING
     *
     * @param string $payload
     * @return bool
     * @throws filterException
     */
    public function ping($payload='1234567890abcdef')
    {
        $input_flag = mt_rand(0, 65535);

        $packet = pack('CC', self::VERSION, self::CMD_PING);
        $packet .= pack('v', $input_flag);         # flag
        $packet .= pack('v', strlen($payload));    # big endian
        $packet .= $payload;

        # socket write
        $r = fwrite($this->fp, $packet);

        if (!$r) {
            throw new filterException("write failed", 200002);
        }

        $s = fread($this->fp, self::HEADER_LENGTH);
        $p = unpack("Cversion/Ccommand/vflag/vlen", $s);

        if ($p['command'] != self::CMD_PONG) {
            if ($p['command'] == self::CMD_ERROR) {
                if ($p['len']) {
                    $result = fread($this->fp, $p['len']);
                    echo "Error: $result\n";
                    return false;
                }
            }
            throw new filterException("bad response", 200003);
        }

        if ($p['len']) {
            $result = fread($this->fp, $p['len']);
            if ($p['flag'] == $input_flag && $result == $payload) {
                return true;
            }
            return false;
        }
        return true;
    }
}

class filterException extends Exception {}

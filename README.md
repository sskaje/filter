关键词匹配服务
======
Author: torshie([https://github.com/torshie] (https://github.com/torshie)), sskaje ([http://sskaje.me](http://sskaje.me))

# Build
Run script build.sh to build the project. Note .c files under directory
"test" will be ignored.

# Command line options:
* "--listen" Address to listen to. It could to a UNIX socket path or an
Internet address. Internet address must be given in the format of
"a.b.c.d:port". Domain names aren't supported. This option can be
specified more than once.
* "--pattern" path to the file containing the patterns should be censored
* "--thread" number of worker threads to start, optional, default to 10

# Request & response
See doc/protocol.md

# Online pattern database reloading
Send signal SIGUSR1 (kill -SIGUSR1 <pid>) to the process to reload the
pattern database.

# Change text encoding
Modify source code to redefine type AC_ALPHABET_t to the type you like,
rebuild the program, then encode all characters (pattern database and
text to be checked) into sequences of AC_ALPHABET_t.

# Pattern database format
文本数据库，一行一个关键词。关键词支持额外的flag参数，结构如下:

```
word1:1
word2:4
word3:8
word3:2
```

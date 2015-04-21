Protocol
======

#Header
V C F L D

* Version  8-bit     1
* Command  8-bit
* Flag     16-bit    Currently useless
* Length   16-bit
* Data     64K-1 bytes maximum

**Multiple-byte fields are all little endian.**

##Commands
```

enum {
    CMD_NONE,
    CMD_MATCH,
    CMD_RESULT,
    CMD_ADD,
    CMD_DELETE,
    CMD_PING=253,
    CMD_PONG=254,
    CMD_ERROR=255,
};

```

#Data

P L F

* start Position       16-bit
* Length               16-bit
* Flag                 16-bit



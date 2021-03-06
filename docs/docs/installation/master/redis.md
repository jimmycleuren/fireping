---
title: "Redis - Installation"
permalink: /docs/installation/master/redis/
key: docs-installation-master-redis
---

# Installation

Redis is used to cache certain API results and alert patterns.

## Debian

Install Redis.

```bash
$ sudo apt-get update
$ sudo apt-get install -y redis-server
```

Make sure that redis is running.

```bash
$ sudo service redis-server status
[FAIL] redis-server is not running ... failed!
$ sudo service redis-server start
Starting redis-server: redis-server.
```

Verify that the installation is running and working with the `PING` command.

```bash
$ redis-cli 
127.0.0.1:6379> PING
PONG
```
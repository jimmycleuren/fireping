---
title: "Database - Installation"
permalink: /docs/getting-started/database/
key: docs-getting-started-database
---

# Installation

In this section we'll talk about the installation and configuration of a MariaDB database server.

This documentation was tested against a MariaDB 10.1 installation.

## Debian 9

```bash
$ sudo apt-get update
$ sudo apt-get install -y mariadb-server
``` 

Make sure it's installed and running:

```bash
$ service mysql status
[info] MariaDB is stopped..
$ service mysql start
[ ok ] Starting MariaDB database server: mysqld.
$ service mysql status
[info] /usr/bin/mysqladmin  Ver 9.1 Distrib 10.1.44-MariaDB, for debian-linux-gnu on x86_64
Copyright (c) 2000, 2018, Oracle, MariaDB Corporation Ab and others.

Server version          10.1.44-MariaDB-0+deb9u1
Protocol version        10
Connection              Localhost via UNIX socket
UNIX socket             /var/run/mysqld/mysqld.sock
Uptime:                 15 sec

Threads: 1  Questions: 61  Slow queries: 0  Opens: 32  Flush tables: 1  Open tables: 26  Queries per second avg: 4.066.
```

After the installation has completed, connect to it:

```bash
$ sudo mariadb
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 228204187
Server version: 10.1.26-MariaDB-0+deb9u1 Debian 9.1

Copyright (c) 2000, 2017, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [(none)]> 
```

Then execute the following:

```bash
MariaDB [(none)]> CREATE DATABASE fireping;
Query OK, 1 row affected (0.00 sec)

MariaDB [(none)]> CREATE USER 'fireping'@'localhost' IDENTIFIED BY 'a12dd17b75817cefc00fa8d3016cdc066cfb9134';
Query OK, 0 rows affected (0.00 sec)

MariaDB [(none)]> GRANT ALL PRIVILEGES ON fireping.* TO 'fireping'@'localhost';
Query OK, 0 rows affected (0.00 sec)

MariaDB [(none)]> FLUSH PRIVILEGES;
Query OK, 0 rows affected (0.00 sec)

MariaDB [(none)]>
```

Remember the database name, user and password for later.

**WARNING**: DO NOT use the password from the example. Generate a random password.
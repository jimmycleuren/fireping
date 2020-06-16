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
$ service mysql start
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
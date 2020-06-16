---
title: "Getting Started"
permalink: /docs/getting-started/reverse-proxy/
key: docs-getting-started-reverse-proxy
---

# NGINX Installation

In this section we'll talk about the installation and configuration of NGINX to act as a reverse proxy to our application.

This documentation was tested against a MariaDB 10.1 installation.

## Debian 9

```bash
$ sudo apt-get update
$ sudo apt-get install -y mariadb-server
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

```sql
CREATE DATABASE fireping;
CREATE USER 'fireping'@'localhost' IDENTIFIED BY 'PASSWORD_CHANGE_ME';
GRANT ALL PRIVILEGES ON fireping.* TO 'fireping'@'localhost';
FLUSH PRIVILEGES;
```
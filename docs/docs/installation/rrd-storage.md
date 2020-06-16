---
title: "Getting Started"
permalink: /docs/getting-started/rrd-storage/
key: docs-getting-started-rrd-storage
---

# Installation

In this section we'll cover the different types of supported storage and how to make a decision on which type to use.

We have three supported storage modes: rrd, rrdcached and rrddistributed.

| type | filesystem | batch processing | description |
| :--- | :--------- | :--------------- | :---------- |
| rrd | local | no | store all rrd files on the application server and process each update sequentially. |
| rrdcached | local | yes | store all rrd files on the application server and process updates in batches. |
| rrddistributed | distributed | yes | store all files over various storage nodes and process their updates in batches. |



## Debian 9

```bash
$ sudo apt-get update
$ sudo apt-get install -y rrdtool
$ sudo apt-get install -y rrdcached
```

## Types

### RRD Storage

#### Debian 9

```bash
$ sudo apt-get update
$ sudo apt-get install -y rrdtool```

This was the first and simplest storage type we implemented. It's designed to read/write data to and from the local filesystem using rrdtool. You can use this for small setups that aren't expected to grow much, or as an initial starting point.

### RRD Cached Storage

As the number of devices in your setup grows you might begin to notice IO performance problems. This happens because with regular RRD Storage, every update needs to be sent to and processed by rrdtool for a given rrd file.
RRD Cached Storage uses rrdcached under the hood to batch a number of updates together before confirming the changes. This happens after a set number of updates, or after 

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
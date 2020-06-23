---
title: "RRD Storage - Installation"
permalink: /docs/installation/master/rrd-storage/
key: docs-installation-master-rrd-storage
---

# Information

In this section we'll cover the different types of supported storage and how to make a decision on which type to use.

We have three supported storage modes: rrd, rrdcached and rrddistributed.

| type | filesystem | batch processing | description |
| :--- | :--------- | :--------------- | :---------- |
| rrd | local | no | sequentially write updates to rrd files on the local filesystem. |
| rrdcached | local | yes | accept updates for a set amount of time or until a number of updates have been received before committing to rrd files. |
| rrddistributed | distributed | yes | behaves like rrdcached but distributes files over multiple storage nodes. |

All three modes allow running the storage node on localhost. Distributed will spread files equally over all configured storage nodes.

# rrdtools

## Debian

This needs to be installed on any server that will be interacting with RRD files.

```bash
$ sudo apt-get update
$ sudo apt-get install -y rrdtool
```

# rrdcached

## Debian

This needs to be installed on any server that will be running `rrdcached`.
This is only relevant for scenarios where your storage driver is `rrdcached` or `rrddistributed`.

```bash
$ sudo apt-get update
$ sudo apt-get install -y rrdcached
```

After the installation has completed, configure it in `/etc/default/rrdcached`.

## Configuration

### BASE_PATH

It's recommended to use a dedicated partition on a separate data disk instead. RRD files can take up a surprisingly large amount of space very quickly as the amount of devices you're targeting grows.

Example:

```bash
BASE_PATH=/var/lib/rrdcached/db/ # This is the default.
```

### SOCKGROUP

It's recommended to use a dedicated fireping system user/group to run rrdcached.

```bash
SOCKGROUP=fireping
```

### NETWORK_OPTIONS

Use `-L` to allow the rrdcached process to open sockets on default port on all available interfaces. This will make your rrdcached process reachable over network connections.
This is only required if you need this storage node to be remotely accessible by your Fireping master instance.

This is only relevant for `rrddistributed` storage setups.

Example:

```bash
NETWORK_OPTIONS="-L"
```

### BASE_OPTIONS

You must set these. 

```bash
BASE_OPTIONS="-B -F -R"
```

- `-B` will restrict access to paths within the specified `BASE_PATH`.
- `-F` will always flush all updates at shutdown.
- `-R` will allow recursive directory creation within the specified `BASE_PATH`.

## Service Status

After making your configuration changes, ensure that the rrdcached process is running.

```bash
$ sudo service rrdcached status
[FAIL] rrdcached is not running ... failed!
$ sudo service rrdcached start
[ ok ] rrdcached started.
```
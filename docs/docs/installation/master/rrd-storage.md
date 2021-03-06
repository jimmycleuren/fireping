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

# RRDCached

See [the storage installation](/docs/installation/storage/getting-started/) on how to install rrdcached. This can be done on your application server (STORAGE=rrdcached).
---
title: "Getting Started"
permalink: /docs/installation/storage/getting-started/
key: docs-installation-storage-getting-started
---

## Getting Started

This section explains how to install and configure rrdcached. You don't *have* to install this on a separate server.

## Hardware Requirements

Realistically most things will work. This machine will be accepting TCP connections over the rrdcached process and writing a lot of IO. We have a setup of two storage nodes with these specs:

- CPU: Intel(R) Xeon(R) CPU E5-2650 v4 @ 2.20GHz
- Memory: 8GB
- Storage: 1TB disk (~300 GB used)

This should be pretty safe for most setups, and you can always expand relatively easily as your needs grow.
You can definitely do with less. Try with what you have, see what works, and report back to us. We'd love to hear about it.

## Software Requirements

Storage nodes have been tested on Debian 9 and Debian 10. Whatever you use, it must support running [rrdcached](https://oss.oetiker.ch/rrdtool/doc/rrdcached.en.html).

## Installation

```bash
$ sudo apt-get install rrdcached rrdtool
```

## Add user

```bash
$ sudo addgroup --system fireping
Adding group `fireping' (GID 101) ...
Done.
```

```bash
$ sudo adduser --system --home /opt/fireping --shell /bin/bash --ingroup fireping --disabled-password --disabled-login fireping
Adding system user `fireping' (UID 101) ...
Adding new user `fireping' (UID 101) with group `fireping' ...
Creating home directory `/opt/fireping' ...
$ su - fireping
$ whoami
fireping
```

## Configuration

You can then configure the daemon to your liking. On Debian, the configuration file is located at `/etc/default/rrdcached`.

Have a look at the settings, it's a fairly simple configuration file. 

This is what it can look like:

```ini
$ cat /etc/default/rrdcached 
# /etc/default file for RRD cache daemon

# Full path to daemon
DAEMON=/usr/bin/rrdcached

# Optional override flush interval, in seconds.
#WRITE_TIMEOUT=300

# Optional override maximum write delay, in seconds.
#WRITE_JITTER=0

# Optional override number of write_threads
#WRITE_THREADS=4

# Where database files are placed.  If left unset, the default /tmp will
# be used.  NB: The daemon will reject a directory that has symlinks as
# components.  NB: You may want to have -B in BASE_OPTS.
BASE_PATH=/opt/fireping/var/rrd

# Where journal files are placed.  If left unset, journaling will
# be disabled.
JOURNAL_PATH=/var/lib/rrdcached/journal/

# FHS standard placement for process ID file.
PIDFILE=/var/run/rrdcached.pid

# FHS standard placement for local control socket.
#SOCKFILE=/var/run/rrdcached.sock

# Optional override group that should own/access the local control
# socket
SOCKGROUP=fireping

# Optional override access mode of local control socket.
#SOCKMODE=0660

# Optional unprivileged group to run under when daemon.  If unset
# retains invocation group privileges.
DAEMON_GROUP=fireping

# Optional unprivileged user to run under when daemon.  If unset
# retains invocation user privileges.
DAEMON_USER=fireping

# Network socket address requests.  Use in conjunction with SOCKFILE to
# also listen on INET domain sockets.  The option is a lower-case ell
# ASCII 108 = 0x6c, and should be repeated for each address.  The
# parameter is an optional IP address, followed by an optional port with
# a colon separating it from the address.  The empty string is
# interpreted as "open sockets on the default port on all available
# interfaces", but generally does not pass through init script functions
# so use -L with no parameters for that configuration.
# Simply comment this line if you don't need to accept TCP connections.
NETWORK_OPTIONS="-L"

# Any other options not specifically supported by the script (-P, -f,
# -F, -B).
BASE_OPTIONS="-B -F -R"
```

It is recommended to set `BASE_PATH` to a dedicated partition so that a large influx of data cannot make your system unusable.

Using this configuration file assumse you have a fireping user and group. It does the following.

- Enables TCP listeners. (`-L`)
- Prevents access to directories outside of the base directory. (`-B`)
- Ensures that all updates are flushed at shutdown. (`-F`)
- Enables recursive directory creation within the base directory. (`-R`)

## Starting RRDCached

After changing your configuration ensure that the daemon is running.

```bash
$ sudo service rrdcached status
[FAIL] rrdcached is not running ... failed!
$ sudo service rrdcached start
[ ok ] rrdcached started.
```

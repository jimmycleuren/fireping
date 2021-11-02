---
title: "Troubleshooting"
permalink: /docs/troubleshooting/
key: docs-troubleshooting-index
---

# Master

## Troubleshooting Logs

Depending on how you setup your Fireping instance errors may appears in
different places. It's important to note that Fireping outputs data to
`stderr`. In a standard Debian setup with PHP-FPM running via systemd, you
can read the logs with `journalctl -f -u php7.4-fpm.service`.

Note that in Docker, you can simply use `docker logs <container_name>` to
read the container's logs.

## Website doesn't load anymore

This usually happens because you tried upgrading a running master instance. As the new cache rebuilds, any user connections will also trigger a cache warmup. This could lead to data from the previous release being warmed into the cache.

As such: avoid live upgrades. Either take your master instance offline before upgrading, or install the upgrade side-by-side to the running version. After you've successfully downloaded and configured the latest release, symlink the "current" release to the latest release. This also allows for easier rollbacks.

Suggested fixes:

- `php bin/console cache:clear`
- `php bin/console cache:warmup`

## Some links do not work (for example deleting an object)

Make sure you have set the TRUSTED_PROXIES variable if you are using a reverse proxy in front of nginx.

If your reverse proxy also does http to https redirection, make sure the X-Forwarded-Proto header is passed correctly.

Example for apache:

- `RequestHeader set X-Forwarded-Proto "https"`

# Slave

## Troubleshooting Logs

It depends on how you start your dispatcher. For example, with `supervisord`
you can redirect stderr to wherever you want. Refer to the supervisord
documentation for more information.

Note that in Docker, you can simply use `docker logs <container_name>` to
read the container's logs.

## Connectivity but no ping results

Make sure that the fping binary has the setuid bit set or that the user with which you are running the slave is root.

```bash
$ whoami
fireping
$ fping 8.8.8.8
Can't create raw socket (need to run as root?).
```

You can fix this as root by setting the setuid bit on the fping binary.

```bash
$ which fping
/usr/bin/fping
$ sudo chmod u+s /usr/bin/fping
$ fping 8.8.8.8
8.8.8.8 is alive
```

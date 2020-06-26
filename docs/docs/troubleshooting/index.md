---
title: "Troubleshooting"
permalink: /docs/troubleshooting/
key: docs-troubleshooting-index
---

# Troubleshooting

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

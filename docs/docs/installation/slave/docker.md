---
title: "Docker"
permalink: /docs/installation/slave/docker/
key: docs-installation-slave-docker
---

# Requirements

Ensure that you have a working Fireping master installation before proceeding.

# Installation

This is part of an express install. If you've already done the prior manual steps, skip this page.

First, make sure that [Docker is installed](https://docs.docker.com/engine/install/debian/).

Then, run the following command, replacing the variables SLAVE_NAME, SLAVE_PASSWORD and SLAVE_URL with those matching your setup.

```bash
sudo docker run -d \
    -e SLAVE_NAME="slave" \
    -e SLAVE_PASSWORD="password" \
    -e SLAVE_URL="http://fireping.example" \
    -v /tmp/logs:/app/var/log \
    --restart=unless-stopped \
    --name fireping-slave \
    jimmycleuren/fireping
```

## Custom Certificates

You might have to load additional certificates into your slave container if your master runs on a certificate for which the CAs are not available by default.

```bash
sudo docker run -d \
    -e SLAVE_NAME="slave" \
    -e SLAVE_PASSWORD="password" \
    -e SLAVE_URL="http://fireping.example" \
    -v /tmp/logs:/app/var/log \
    -v /path/to/certificates/:/usr/local/share/ca-certificates/ \
    --restart=unless-stopped \
    --name fireping-slave \
    jimmycleuren/fireping
```

## Registration

Lastly, ensure that you have [registered your slave](/docs/installation/master/fireping/#slave-registration) in your master instance, or it will not be able to fetch any configuration.
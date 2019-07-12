---
layout: doc
title: "Installation Using Docker"
nav_order: 1
parent: Installation guide for the Slaves
grand_parent: Getting started
permalink: /getting-started/slaves/docker
---

First make sure you have [docker](http://www.docker.com) installed.

Then run
```bash
sudo docker run -d \
    -e SLAVE_NAME="myslave" \
    -e SLAVE_PASSWORD="password" \
    -e SLAVE_URL="http://fireping.myserver.com" \
    -v /tmp/logs:/app/var/logs \
    --restart=unless-stopped \
    --name fireping \
    jimmycleuren/fireping
```

Now open your fireping dashboard, go to `Admin > User` and create a new user with these credentials, `role` as `role_api` and check the `enabled` box.

![Screenshot of adding a Slave](../../assets/img/adding_slave_user.png)
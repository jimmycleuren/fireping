---
title: "Installation using Docker"
permalink: /getting-started/slaves/docker
key: docs-getting-started-slaves-docker
---

First make sure you have [Docker](http://www.docker.com) installed.

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

![Screenshot of adding a Slave](/fireping/assets/images/adding_slave_user.png)

You should now see the slave if you go back to `fireping.develop/slaves`. (it can take a few seconds)

![Screenshot of adding a Slave](/fireping/assets/images/slaves_added_list.png)
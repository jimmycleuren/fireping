---
title: "Installation using Docker"
permalink: /docs/getting-started/slaves/docker
key: docs-getting-started-slaves-docker
---

First make sure you have [Docker](http://www.docker.com) installed.

Then run
```bash
sudo docker run -d \
    -e slave_NAME="myslave" \
    -e slave_PASSWORD="password" \
    -e slave_URL="http://fireping.myserver.com" \
    -v /tmp/logs:/app/var/log \
    --restart=unless-stopped \
    --name fireping \
    jimmycleuren/fireping
```

Now open your fireping dashboard, go to `Admin > User` and create a new user with these credentials, `role` as `role_api` and check the `enabled` box.

![Screenshot of adding a slave](/assets/images/adding_slave_user.png)

You should now see the slave if you go back to `fireping.develop/slaves`. (it can take a few seconds)

![Screenshot of adding a slave](/assets/images/slaves_added_list.png)

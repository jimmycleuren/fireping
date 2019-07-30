---
title: "Installing master and slave(s) on the same host"
permalink: /docs/getting-started/same-host
key: docs-getting-started-same-host
---

# Using a Docker slave
The easiest way to install a master and slave(s) on the same host is by following the [master Setup](/fireping/docs/getting-started/master) and the [Docker slave Setup](/fireping/docs/getting-started/slaves/docker)

# Using a manually installed slave
The installation can also be done manually; however, this is not recommended.
Start by cloning the repositry twice. We can't install both to `/opt/fireping`, as this will cause interference. Instead, we install the master to `/opt/fireping-master` and the slave to `/opt/fireping-slave`.

Next, follow the instructions for the [master Setup](/fireping/docs/getting-started/master) but when cloning the repository use
```bash
sudo git clone https://github.com/jimmycleuren/fireping.git fireping-master
```

Same goes for the [slave Setup](/fireping/docs/getting-started/slaves/manual), when cloning use
```bash
sudo git clone https://github.com/jimmycleuren/fireping.git fireping-slave
```
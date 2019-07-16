---
layout: doc
title: "Installing Master and Slave(s) on the same host"
nav_order: 3
parent: Getting started
permalink: /getting-started/same-host
---

# Using a Docker Slave
The easiest way to install a Master and Slave(s) on the same host is by following the [Master Setup](/fireping/getting-started/master) and the [Docker Slave Setup](/fireping/getting-started/slaves/docker)

# Using a manually installed Slave
The installation can also be done manually; however, this is not recommended.
The problem with doing this manually is that you have to clone the repositry twice, while they will install on the same directory. To solve this, we install the master to `/opt/fireping-master` and the slave to `/opt/fireping-slave`.

Next, follow the instructions for the [Master Setup](/fireping/getting-started/master) but on cloning the repository use
```bash
sudo git clone https://github.com/jimmycleuren/fireping.git fireping-master
```

Same goes for the [Slave Setup](/fireping/getting-started/slaves/manual), on cloning use
```bash
sudo git clone https://github.com/jimmycleuren/fireping.git fireping-slave
```
---
layout: doc
title: "Installing Master and Slave(s) on the same host"
nav_order: 3
parent: Getting started
permalink: /getting-started/same-host
---

# Using a Docker Slave
Just follow the [Master Setup](/getting-started/master) and the [Docker Slave Setup](/getting-started/slaves/docker)

# Using a manually installed Slave
The problem with doing this manually is that you have to clone the repo twice and they want to install to the same place, so instead of installing the folders to `/opt/fireping`, we install the master to `/opt/fireping-master` and the slave to `/opt/fireping-slave`.

Thus Folow the instructions for the [Master Setup](/getting-started/master) but on cloning the repository use
```bash
sudo git clone https://github.com/jimmycleuren/fireping.git fireping-master
```

Same goes for the [Slave Setup](/getting-started/slaves/manual), on cloning use
```bash
sudo git clone https://github.com/jimmycleuren/fireping.git fireping-slave
```
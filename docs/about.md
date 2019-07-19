---
layout: article
title: "About"
key: page-about
permalink: /about
sidebar:
    nav: side-nav
---

# What does it do?
Fireping is a program that lets you gather ping, traceroute and HTTP data from servers. 
This data is then visualized on a graph.
Fireping is very easy to configure and has a built in alerting system to let you know when a server is unreachable or expriencing packet loss.

# How does it work?
Fireping uses a **Master/Slave** architecture. This has two parts

- The **Master** which will host the dashboard that presents the data and lets you control/config all the Slave-nodes and much more.
- The **Slaves** which gather the data and send it to the master to present it on the dashboard.

You can setup multiple Slaves for a single Master but only one master per Slave.
This allows for efficient data gathering for a lot of servers with as few as possible slaves.
For collecting data on big datacenters, you can add a Storage node to handle the large amounts of data.

![Master/Slave](/fireping/assets/images/master_slave.png)

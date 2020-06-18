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
Fireping uses a **master/slave** architecture. This has two parts

- The **master** which will host the dashboard that presents the data and lets you control/config all the Slave-nodes and much more.
- The **slaves** which gather the data and forward it to the master.

You can setup multiple slaves for a single master but only one master per slave.
This allows for efficient data gathering about a lot of servers with as few as possible slaves.
For collecting data on big datacenters, you can add a storage node to handle the large amounts of data.

![Master/Slave](/assets/images/master_slave.png)

---
layout: doc
title: "Admin Guide"
nav_order: 2
has_children: true
parent: How to use Fireping
permalink: /how-to/admin
has_toc: false
---

# the Admin dashboard
on the Admin dashboard you can configure

| Option                                                | Short Description       
|:------------------------------------------------------|:------------------
| [Domain](/how-to/admin/domain)                        | Will act as a group for the settings and devices
| [Device](/how-to/admin/device)                        | A device from which to gather data
| [Alert](/how-to/admin/alert)                          | List of alerts
| [AlertRule](/how-to/admin/alert-rule)                 | A rule to express when to give an alert
| [AlertDestination](/how-to/admin/alert-destination)   | The destination on which the alert will be recieved (ie. mail, slack, ...)
| [Probe](/how-to/admin/probe)                          | A probe to be executed by a slave that will gather data (ping, traceroute)
| [ProbeArchive](/how-to/admin/probe-archive)           | Settings to define how long data is stored
| [Slave](/how-to/admin/slave)                          | Remote server that will execute the probes
| [SlaveGroup](/how-to/admin/slave-group)               | A group of slaves over which the workload is equaly distributed
| [StorageNode](/how-to/admin/storage-node)             | An extra server to cope with the storage requirements
| [User](/how-to/admin/user)                            | Settings to add/remove/alter users
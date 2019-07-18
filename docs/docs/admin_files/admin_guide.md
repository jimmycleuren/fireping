---
title: "Admin Guide"
permalink: /docs/how-to/admin
key: docs-how-to-admin
---

# The admin dashboard
![Devices listed in admin](\fireping\assets\images\devices_setings_list.png)
On the Admin dashboard you can configure all settings, remove slaves, add or remove users and more.
To understand what all these options do, you can read these documents.

| Option                                                | Short Description       
|:------------------------------------------------------|:------------------
| [Domain](/fireping/docs/how-to/admin/domain)                        | Will act as a group for the settings and devices
| [Device](/fireping/docs/how-to/admin/device)                        | A device from which to gather data
| [Alert](/fireping/docs/how-to/admin/alert)                          | List of alerts
| [AlertRule](/fireping/docs/how-to/admin/alert-rule)                 | A rule to express when to give an alert
| [AlertDestination](/fireping/docs/how-to/admin/alert-destination)   | The destination on which the alert will be recieved (ie. mail, slack, ...)
| [Probe](/fireping/docs/how-to/admin/probe)                          | A probe to be executed by a slave that will gather data (ping, traceroute)
| [ProbeArchive](/fireping/docs/how-to/admin/probe-archive)           | Settings to define how long data is stored
| [Slave](/fireping/docs/how-to/admin/slave)                          | Remote server that will execute the probes
| [SlaveGroup](/fireping/docs/how-to/admin/slave-group)               | A group of slaves over which the workload is equaly distributed
| [StorageNode](/fireping/docs/how-to/admin/storage-node)             | An extra server to cope with the storage requirements
| [User](/fireping/docs/how-to/admin/user)                            | Settings to add/remove/alter users
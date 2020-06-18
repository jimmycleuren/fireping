---
title: "Device Settings"
permalink: /docs/how-to/admin/device
key: docs-how-to-admin-device
---

![Settings for device](/assets/images/settings_device.png)

# Short Description
A Device is the device from which data will be gathered, thus the slaves wil gather response data from this device.

# Required fields
- a `Name`.
- a `Domain` that contains all the `probes`/`slaves` acting on the `Device`.
- an `Ip` address of the actual device from which to gather data.

# Optional fields
- `SlaveGroups`, the `Probes` will be executed on the `slaves` in these `SlaveGroup`s.
- `Probes`, these are the probes that will be executed to gather data.
- `Alert rules`, are the rules describing when to alert.
- `Alert destinations`, are the destination where the alert will be shown in case one is triggered. 

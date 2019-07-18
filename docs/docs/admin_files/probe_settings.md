---
title: "Probe Settings"
permalink: /docs/how-to/admin/probe
key: docs-how-to-admin-probe
---

![Settings for device](/fireping/assets/images/probe_settings.png)

# Short Description
A probe will be executed by the slaves to gather data about a device. This data will be kept in a [Probe Archive](/fireping/how-to/admin/probe-archive).

# Required fields
- `Name` of the probe.
- `Type` of the probe (`ping`, `http` or `traceroute`)
- `Step` how many seconds to wait for each execution.
- `Samples` how many samples to take per execution.
- `Arguments`, almost everytime the value will be `{}`.

# Optional fields
- `Archives` are the archives where to store the data.

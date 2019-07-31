---
title: "Domain Settings"
permalink: /docs/how-to/admin/domain
key: docs-how-to-admin-domain
---

![Settings for domain with parent](/fireping/assets/images/settings_domain_with_parent.png)

# Short description
A Domain is a container that connects `slaveGroups`, `Probes`, `Alert rules` and `Alert destinations`. 

# Required fields
- a `Name`

# Optional fields
- a `Parent`, the domain wil be listed under the parent in the dashboard.

![Domain with parent example](/fireping/assets/images/domain_with_parent.png)

- `slaveGroups`, the `Probes` will be executed on the `slaves` in these `slaveGroup`s.
- `Probes`, these are the probes that will be executed to gather data.
- `Alert rules`, are the rules describing when to alert.
- `Alert destinations`, are the destination where the alert will be shown in case one is triggered. 

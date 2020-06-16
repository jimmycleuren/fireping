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

- `Parent` defines the parent domain from which this domain and its devices will inherit their settings.
- `slaveGroups`, the `Probes` will be executed on the `slaves` in these slave groups.
- `Probes`, these are the probes that will be executed to gather data.
- `Alert rules`, are the rules describing when to alert.
- `Alert destinations`, are the destination where the alert will be shown in case one is triggered. 

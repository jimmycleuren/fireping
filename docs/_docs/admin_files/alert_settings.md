---
layout: doc
title: "Alert Settings"
nav_order: 3
parent: Admin Guide
grand_parent: How to use Fireping
permalink: /how-to/admin/alert
---

![Settings for device](/fireping/assets/images/alert_settings.png)

# Short Description
Allows you to edit/remove the alert information.

# Required fields
- `Active` is 1 when the alert is active, 0 if it is inactive.
- `Firstseen` is the date/hour when the alert was first triggered.
- `Lastseen` is the date/hour when the alert was last active.

# Optional fields
- `Device` is the device where the alert was triggered.
- `Alert rule` is the rule that triggered the alert.
- `Slave group` is the group of slaves that triggered the alert.
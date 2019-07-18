---
title: "Alert Rule Settings"
permalink: /docs/how-to/admin/alert-rule
key: docs-how-to-admin-alert-rule
---

![Settings for device](/fireping/assets/images/alert_rule_settings.png)

# Short Description
Describes when to trigger an alert. Alerts can be sent to different destinations using [Alert Destinations](/fireping/how-to/admin/alert-destination).

# Required fields
- `Name` of the rule
- `Datasource` that will be captured (ie. median, loss, ...)
- `Pattern` the patter to match such that an alert is triggered (for example `<100,<100,<100,>100,>100,>100`, this will trigger when the datasource is lower than 100 for 3 [steps](/fireping/how-to/admin/probe) and then higher than 100 for 3 [steps](/fireping/how-to/admin/probe))
- `Message up`, the message to display when the alert is triggered
- `Message down`, the message to display when the alert is cleared
- `Probe`, the probe to monitor

# Optional fields
- `Parent`, the alert will not be triggered if the parent already has been, for example: if you have an alert for ping `median` `>50` and one for if it is down, the `>50` will be triggered unless you make the alert for down it's parent.
- `Children` the same as parent but now you specify the children to not alert when the given rule is met.
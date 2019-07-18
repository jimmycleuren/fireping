---
layout: doc
title: "Probe Archive Settings"
nav_order: 7
parent: Admin Guide
grand_parent: How to use Fireping
permalink: /how-to/admin/probe-archive
---

![Settings for device](/fireping/assets/images/probe_archive_settings.png)

# Short Description
A Probe Archive will hold the data gathered by probes for a specified amount of time.

# Required fields
- `Function`, the function which data it will host (`AVERAGE` for the average, `MIN` for the minimum, `MAX` for the maximum)
- `Steps`, the amount of steps to take the `Function` over. this wil be stored as 1 `row`.
- `Rows`, the amount of rows to save, if you have you probe step set to 60 (1 minute) and the steps in the archive is 1, you map 1 minute to every row thus 1440 rows is a day.
- `Probe`, the probe from which to hold the data.
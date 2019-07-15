---
layout: doc
title: "User Settings"
nav_order: 11
parent: Admin Guide
grand_parent: How to use Fireping
permalink: /how-to/admin/user
---

![Settings for device](/fireping/assets/img/user_settings.png)

# Short Description
Create/edit user credetials, roles.

# Required fields
- `Username` is the username for the user.
- `Email` is the email address for the user.
- `Enabled`, whether the account is enabled or not.
- `Last Login`, whether the last login is recorded or left empty.
- `Roles`, the role for the user (`ROLE_ADMIN` makes it an admin account, able to make changes. `ROLE_API` is set for slave nodes, it gives them access to the API).

# Optional fields
- `Password` is the password for the user.

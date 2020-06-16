---
title: "User Settings"
permalink: /docs/how-to/admin/user
key: docs-how-to-admin-user
---

![Settings for device](/assets/images/user_settings.png)

# Short Description
Allows you to create/edit user credetials and roles.

# Required fields
- `Username` is the username for the user.
- `Email` is the email address for the user.
- `Enabled`, whether the account is enabled or not.
- `Last Login`, whether the last login is recorded or left empty.
- `Roles`, the role for the user (`ROLE_ADMIN` makes it an admin account, able to make changes. `ROLE_API` is set for slave nodes, it gives them access to the API).

# Optional fields
- `Password` is the password for the user.

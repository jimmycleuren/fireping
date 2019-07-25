---
title: "Deploy slaves using ansible"
permalink: /docs/getting-started/slaves/deploy-with-ansible
key: docs-getting-started-slave-ansible
---

# Install ansible

If you are new to ansible and/or haven't installed it yet, I recommend watching this tutorial.

<div>{%- include extensions/youtube.html id='icR-df2Olm8' -%}</div>

# Download the role

The role is located in `/opt/fireping/ansible/roles`.
To copy it to your playbook's folder, run the following command

```bash
cp -R /opt/fireping/ansible/roles my-ansiblebook-folder
```

where `my-ansiblebook-folder` is replaced with the actual folder where the role needs to be.

Then add all your slaves in the hosts file in your **playbook folder** either by ip-address or by hostname.
For example
```
slave-1
slave-2
slave-3
```

which could also be abbreviated to

```
slave-[1:3]
```

In your `playbook.yml` file, add the following role
```yaml
- role: fireping_slave
  become: true
  become_user: username
  slave_name: myslave
  slave_password: password
  slave_url: http://fireping.develop
```

where 
- **become_user** is followed by the username to log in to the server
- **slave_myslave** is followed the username for the slave
- **slave_password** is followed the password for the slave
- **slave_url** is followed by the url of the fireping master

Executing this role will install the docker image for the slave.
If you have not installed docker on your machines yet, you can use the [ansible-docker role by nickjj](https://github.com/nickjj/ansible-docker) to install it using ansible.

Now you only have to run your playbook by executing

```bash
ansible-playbook -K playbook.yml
```

and the the slaves will be set up on the hosts.

Now all that is left is to create an account for the slaves.
Open your fireping dashboard, go to `Admin > User` and create a new user with these credentials, `role` as `role_api` and check the `enabled` box.

![Screenshot of adding a Slave](/fireping/assets/images/adding_slave_user.png)

You should now see the slave if you go back to `fireping.develop/slaves`. (it can take a few seconds)

![Screenshot of adding a Slave](/fireping/assets/images/slaves_added_list.png)

You only have to make one account as the slaves you installed all share the same user credentials.
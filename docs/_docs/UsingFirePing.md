---
title: "using fireping"
---

# How to use FirePing
## Testing
After setting up a Master adding a Slave you can add a test domain.

- Goto `fireping.develop` or however you named it, the dashboard wil be located here.

- Login with the admin account you created while setting up the slave.

- Now lick on the `Admin` tab, here you can configure all Probes and such (Explanation follows)

- and add a new Domain.
![img](media/docs/domain.png)

- Make a new `SlaveGroup` and set the Domain and add your Slave(s). A `SlaveGroup` is what it says... a group of Slaves

- Then you can add 2 probes, 1 for `ping` and one for `trace route`, make step `60`, samples `15` and arguments `{}`. These `Probes` will collect the data, in this case one will be collecting `ping` data and the other will collect `traceroute` data.
![img](media/docs/probes.png)

- Then make a ProbeArchive with `steps` `1` and select a probe with function `AVERAGE`. The ProbeArchive will store the data that probes collect and show it on the graph
![img](media/docs/probe_archive.png)

- Add a device named `Google` and assign it to the domain and set the `ip` to `8.8.8.8`. The device is the server/device to be pinged/tracerouted/...

- Go back, edit the domain and add the SlaveGroup and the probes.
![img](media/docs/domain_2.png)

- Last go back to `fireping.develop` and click on the new link in the side bar, Wait a few minutes and you should start to see a graph. You can click on it to expand the graph into the `ping` graph and `traceroute` graph.

## Domain
A Domain is a container that connects `SlaveGroups`, `Probes`, `Alert rules` and `Alert destinations`. 
It requires
- a `Name`

And optionally
- a `Parent`, the `Parent` will contain the new Domain.
![img](media/docs/domain_with_parent.png)
- `SlaveGroups`, the `Probes` will be executed on the `Slaves` in these `SlaveGroup`s.
- `Probes`, these are the probes that will be executed to gather data.
- `Alert rules`, are the rules describing when to alert.
- `Alert destinations`, are the destination where the alert will be shown in case one is triggered. 

![img](media/docs/settings_domain_with_parent.png)

## Device
A Device is the device from which data will be gathered, thus the slaves wil ping, or traceroute this device.
It requires
- a `Name`.
- a `Domain` that contains all the `probes`/`slaves` acting on the `Device`.
- an `Ip` address of the actual device from which to gather data.

And optionally
- `SlaveGroups`, the `Probes` will be executed on the `Slaves` in these `SlaveGroup`s.
- `Probes`, these are the probes that will be executed to gather data.
- `Alert rules`, are the rules describing when to alert.
- `Alert destinations`, are the destination where the alert will be shown in case one is triggered. 




































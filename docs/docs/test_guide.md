---
layout: doc
title: "Test Guide"
nav_order: 1
parent: How to use Fireping
permalink: /how-to/test
---

## Testing the setup
After setting up a Master and adding a Slave you can add a test domain.

1. Go to `fireping.develop` or whatever you named it, the dashboard wil be located here.

2. Login with the admin account you created while setting up the Master by clicking on the `Login` tab.

3. Now click on the `Admin` tab. Here you can configure all Probes and more (All explained in the [Admin Guide](/fireping/how-to/admin)).

4. Add a new Domain. (click on `Domain > Add Domain`).
![Domain settings](/fireping/assets/img/domain.png)

5. Make a new `SlaveGroup`, set the Domain and add your Slave(s). A `SlaveGroup` is what it says... a group of Slaves.

6. Add two probes, one for `ping` and one for `traceroute`. Make step `60`, samples `15` and arguments `{}`. These `Probes` will collect the data. In this case one will be collecting `ping` data and the other will collect `traceroute` data.
![Probes settings](/fireping/assets/img/probes.png)

7. Make a ProbeArchive with `steps` `1` and select a probe with function `AVERAGE` and Rows `1440` (Amount of minutes in a day, you can make it longer if you want to). The ProbeArchive will store the data that probes collect and show it on the graph.
![Probe Archives](/fireping/assets/img/probe_archive.png)

8. Add a device named `Google`, assign it to the domain and set the `ip` to `8.8.8.8`. The device is the server/device to be pinged/tracerouted/...

9. Go back, edit the domain and add the SlaveGroup and the probes
![Domain settings](/fireping/assets/img/domain_2.png)

10. Last go back to `fireping.develop` and click on the new link in the side bar. Wait a few minutes and you should start to see a graph. You can click on it to expand the graph into the `ping` graph and `traceroute` graph.
![Ping & Traceroute graph](/fireping/assets/img/dashboard_ping_traceroute_graph.png)
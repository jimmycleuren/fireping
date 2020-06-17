---
title: "Getting Started"
permalink: /docs/installation/slave/getting-started/
key: docs-installation-master-getting-started
---

# Hardware Requirements

Again, this also depends on your needs, but considering these servers will only be:

- sending out icmp echo-request
- sending out http(s) requests
- keeping data in memory

You can do a lot with very little. We have one server running in production with 4GB RAM, 2 vCPUs and 10 GB disk currently responsible for about 10K targets.

# Application Stack

- php7.4
- supervisord
- fping
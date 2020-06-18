---
title: "Reverse Proxy - Installation"
permalink: /docs/installation/master/reverse-proxy/
key: docs-installation-master-reverse-proxy
---

# NGINX Installation

In this section we'll talk about the installation and configuration of NGINX to act as a reverse proxy to our application.

## Debian

Install NGINX:

```bash
$ sudo apt-get update
$ sudo apt-get install -y nginx
``` 

Add configuration to `/etc/nginx/sites-available/fireping.conf`

```nginx
server {
    listen 443 ssl;
    server_name fireping.corp.example;
    ssl_certificate     /etc/nginx/ssl/fireping.corp.example.crt;
    ssl_certificate_key /etc/nginx/ssl/fireping.corp.example.key;
    ssl_protocols       TLSv1 TLSv1.1 TLSv1.2;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    root /opt/fireping/public;

    location / {
        # try to serve file directly, fallback to index.php
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;

        internal;
        fastcgi_read_timeout 300;
    }

    location ~ \.php$ {
        return 404;
    }


    error_log /var/log/nginx/fireping_error.log;
    access_log /var/log/nginx/fireping_access.log;
}
```

Then, enable the configuration.

```bash
sudo ln -s /etc/nginx/sites-available/fireping.conf /etc/nginx/sites-enabled/fireping.conf
```

And reload the NGINX configuration.

```bash
sudo nginx -s reload
```
[Animurecs](https://animurecs.com)
==================================

Animurecs is a PHP / MySQL social network aimed at making enjoying anime a communal experience. It uses `memcached` to store and retrieve frequently-queried data.

To get started (after installing the above dependencies), you should do the following:

1. `git clone` Animurecs
2. Modify `global/config.php.example` to work with your setup and rename it to `global/config.php`
3. Modify `dbv/config.php.sample` with the authentication setup of your choice and rename it to `dbv/config.php`
4. Open up `dbv/index.php` in your browser and push all the disk schema to your database
5. Open up `scripts/config.example.txt`, copy it to `scripts/config.txt` and change the parameters to fit your installation

After that, you'll want to set up your webserver. Point your server's document root to the public folder, and then create a rewrite rule to route:

- All requests to /api to /public/api.php
- All other requests to /public/index.html

For nginx it'd look like this:

    # Rewrite all API traffic to api.php
    location /api {
      rewrite  ^/api/?([a-zA-Z0-9\_]+)?(/(.+?))?(/([0-9A-Za-z\_]+))?/?$ /api.php?controller=$1&id=$3&action=$5 last;
    }

    # Rewrite all other traffic to its destination, or index.html
    location / {
      try_files $uri $uri/ /index.html;
    }

You should be all set up to go after that!

&copy; 2012 Charles Guo
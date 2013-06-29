[Animurecs](https://animurecs.com)
==================================

Animurecs is a PHP / MySQL social network aimed at making enjoying anime a communal experience. It uses `memcached` to store and retrieve frequently-queried data.

To get started (after installing the above dependencies), you should do the following:

1. `git clone` Animurecs
2. Modify `global/config.php.example` to work with your setup and rename it to `global/config.php`
3. Modify `dbv/config.php.sample` with the authentication setup of your choice and rename it to `dbv/config.php`
4. Open up `dbv/index.php` in your browser and push all the disk schema to your database

After that, you'll want to write a rewrite rule for your webserver so you can have pretty URLs. For nginx, it'd look something like this (inside your server block):

    if (!-e $request_filename) {
        rewrite  ^/([a-zA-Z0-9\_]+)?(/(.+?))?(/([0-9A-Za-z\_]+))?/?(\.([a-zA-Z]+))?/?$ /index.php?model=$1&id=$3&action=$5&format=$7 last;
        break;
    }

You should be all set up to go after that!

&copy; 2012 Charles Guo
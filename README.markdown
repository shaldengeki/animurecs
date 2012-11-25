Animurecs
=========

Animurecs is a PHP / MySQL social network aimed at making enjoying anime a communal experience. It uses `memcached` to store and retrieve frequently-queried data.

To get started (after installing the above dependencies), you should do the following:

1. `git clone` Animurecs
2. Modify `global/config.php.example` to work with your setup and rename it to `global/config.php`
3. Open up `dbv/index.php` in your browser and push all the disk schema to your database

After that, you'll want to write some rewrite rules for your webserver so you can have pretty URLs. For nginx, they'd look something like this:

    location /users/ {
        rewrite ^/users/?([a-zA-Z0-9\_]+)?/?([0-9A-Za-z\_]+)?/?$ /user.php?action=$2&id=$1 last;
    }

    location /anime/ {
        rewrite ^/anime/?([a-zA-Z0-9\_]+)?/?([0-9A-Za-z\_]+)?/?$ /anime.php?action=$2&id=$1 last;
    }

    location /discover/ {
        rewrite ^/discover/?([a-zA-Z0-9\_]+)?/?$ /discover.php?type=$1 last;
    }

    location /anime_lists/ {
        rewrite ^/anime_lists/?([a-zA-Z0-9\_]+)?/?([0-9A-Za-z\_]+)?/?$ /anime_list.php?action=$2&id=$1 last;
    }

    location /tag_types/ {
        rewrite ^/tag_types/?([a-zA-Z0-9\_]+)?/?([0-9A-Za-z\_]+)?/?$ /tag_type.php?action=$2&id=$1 last;
    }

    location /tags/ {
        rewrite ^/tags/?([a-zA-Z0-9\_]+)?/?([0-9A-Za-z\_]+)?/?$ /tag.php?action=$2&id=$1 last;
    }

    location /comments/ {
        rewrite ^/comments/?([a-zA-Z0-9\_]+)?/?([0-9A-Za-z\_]+)?/?$ /comment.php?action=$2&id=$1 last;
    }

You should be all set up to go after that!
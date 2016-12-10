# jonnybarnes.uk

This is the code that runs my website, [jonnybarnes.uk](https://jonnybarnes.uk).

In theory this is usable by other now :D

Set up the database, this software needs [PostgreSQL](https://wwwpostgresql.org)
with the [PostGIS](http://postgis.net) plugin. After installing these:

```shell
$ createdb -E utf8 db_name
$ psql -d db_name -c 'CREATE EXTENSION postgis'
```

First get the code, and make sure you’re on the `master` branch. This branch will
only have tagged releases:

```shell
$ git clone https://github.com/jonnybarnes/jonnybarnes.uk mysite.com
$ cd mysite.com
$ git checkout master
```

Then we need to set up the environment variables that the app will use.

```shell
$ cp .env.example .env
$ vim .env
```

Fill in the various variables. Then we can set up the app:

```shell
$ composer install
$ php artisan key:generate
$ php artisan migrate
```

Now we need to edit some config values. In `config/app.php` edit `name`, and in
`config/syndication.php` edit it to the appropriate values or set targets to `null`.

Some other things that should be changed. Go to `resources/views/master.blade.php`,
you may not want to link to a projects page. Also in the `<head>` the two last links
are to my profile pic and pgp key, ammend/remove as desired.

Now point your server to `public/index.php` and viola. Essentially this is a
Laravel app so debugging things shouldn’t be too hard.

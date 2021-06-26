MTR Database for PHP
====================

MTR Batch processing storing in database with dashboard 

[![Latest Stable Version](https://poser.pugx.org/yidas/mtr-database/v/stable?format=flat-square)](https://packagist.org/packages/yidas/mtr-database)
[![License](https://poser.pugx.org/yidas/mtr-database/license?format=flat-square)](https://packagist.org/packages/yidas/mtr-database)

OUTLINE
-------

- [Demonstration](#demonstration)
- [Requirements](#requirements)
- [Installation](#installation)
    - [Download](#download) 
    - [Startup](#startup)
- [Usage](#usage)
    - [Launch](#launch)
    - [Purge](#purge) 
    - [Dashboard](#Dashboard)
- [References](#references)

---

DEMONSTRATION
-------------

<img src="https://raw.githubusercontent.com/yidas/mtr-database-php/main/img/demo-dashboard-overview.png" height="300" /><img src="https://raw.githubusercontent.com/yidas/mtr-database-php/main/img/demo-dashboard-table-details.png" height="300" />

---

REQUIREMENTS
------------
This library requires the following:

- MTR library (CLI) 0.9+
- PHP 5.4.0+\|7.0+

---

INSTALLATION
------------

### Download

#### Composer Installation

Using Composer to install is the easiest way with auto-installer:

```shell
composer create-project --prefer-dist yidas/mtr-database
```

#### Wget Installation

You could see [Release](https://github.com/yidas/mtr-database-php/releases) for picking up the package with version, for example:
    
```shell
$ wget https://github.com/yidas/mtr-database-php/archive/master.tar.gz -O mtr-database-phpi.tar.gz
```

After download, uncompress the package:

```shell
$ tar -zxvf mtr-database-php.tar.gz
```

### Startup

After the download, you could start to set up the `config.inc.php` with your database connection, and run `install.php` which will help you to install the database & table:

```shell
$ php install.php
Installation completed
```

After the installation, enjoy to run or set `launch.php` with your prefered arguments in crontab:

```shell
$php launch.php
Process success
```

Set crontab into `/etc/cron.d/mtr-database`:

```shell
# Launch and record MTR every 10 miniutes by default
*/10 * * * * root php /var/www/html/mtr-database/launch.php >/dev/null 2>&1

# Purge data before 90 days by default (Optional)
00 00 * * * root php /var/www/html/mtr-database/purge.php >/dev/null 2>&1
```

> The default batch period is configured to be 10 minutes, so we can set the batch to be executed every 10 minutes.

---

USAGE
-----

### Launch

The configuration file `config.inc.php` allows you to set the default settings for MTR launching. However, you can also specify parameters immediately in the command to be run.

#### Host

Host parameter allows you to specify the target host to be tracked with `-h --host` parameter:

```shell
php launch.php --host="yourhost.local"
```

#### Period

Period argument allocate the number of minutes between crontab intervals with `-p --period` parameter:

```shell
# Launch and record MTR every 5 miniutes
*/5 * * * * root php /var/www/html/mtr-database/launch.php --period=5 >/dev/null 2>&1
```

In addition, Count argument will distribute the sending count between the interval according to the Period setting with `-c --report-cycles`  parameter:

```shell
# Launch and record MTR every 1 miniutes, and each report will send 10 count (send every 6 seconds)
*/1 * * * * root php /var/www/html/mtr-database/launch.php --period=1 --report-cycles=10 >/dev/null 2>&1
```

#### TCP

TCP argument allows you to use MTR TCP mode with specified port, `-T --tcp` for enabling TCP mode and `-P --port` for setting port:

```shell
php launch.php --tcp -port=443
```

### Purge

Running `purge.php` will delete old records older than the given number of days. You can use the `-d --days` parameter to set with (default is 90 days):

```shell
00 00 * * * root php /var/www/html/mtr-database/purge.php --days=30 >/dev/null 2>&1
```

### Dashboard

The endpoint of MTR Dashboard is `/index.php` and it's enabled by default, you could place the project in the web path such as `/var/www/html/mtr-database/index.php`.

For the authentication, you could easily set the username and password in `config.ini.php`:

```php
...
    'dashboard' => [
        'enable' => true,
        'username' => '',
        'password' => '',
        'categories' => [''],   // Category list for selection
    ],
...
```

> `categories` will enable you to query specific data by category settings or query all data with default blank values.


---

REFERENCES
----------

- [MTR - Github](https://github.com/traviscross/mtr)




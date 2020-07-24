# A simple demo how to use docker to serve a laravel application

1. Clone this repository
2. Run `composer install` to install PHP dependencies
3. Run `cp .env.sample .env` to prepare Laravel
4. Run `php artisan key:generate` to prepare Laravel
5. Run `docker-compose up` to spin up the docker containers

## Containers

The containers and the images they are based on are defined in `docker-compose.yaml`.

2 web servers are included to show that you can choose to run either Apache or Nginx+FPM

### apache - runs apache2 with PHP 7.2, PDO, pdo_mysql
Runs apache on port 80 internally. Externally available at port 8080. This is configured using the attached `httpd-vhosts.conf`
 
This can be access on the browser at http://localhost:8080/

Other test pages

* Homepage with param - http://localhost:8080/?abc=123
* PHP file with param - http://localhost:8080/index.php?abc=123
* Static file - http://localhost:8080/static/images/hello.html
* Static with param - http://localhost:8080/static/images/hello.html?v=123

### nginx + nginx-fpm

Test pages

* Homepage with param - http://localhost:8000/?abc=123
* PHP file with param - http://localhost:8000/index.php?abc=123
* Static file - http://localhost:8000/static/images/hello.html
* Static with param - http://localhost:8000/static/images/hello.html?v=123

#### nginx 
Runs nginx at port 80 internally. Externally available at port 8000. This is configured using the attached `nginx.conf`.

This serves static files. By default, it serves `index.html` which does not exist. This is intentional so it does not 
serve `index.php` as a static file. 

For all URLs, it will check if the path exists in public, otherwise, it will rewrite 
it to `index.php`. The configuration checks if the URL is *.php and if so, passes the request to the FPM handler at port 9000
identified by the line in nginx.conf `fastcgi_pass  nginx-fpm:9000;`

#### nginx-fpm - Runs PHP 7.2 with PDO, pdo_mysql
Runs php-fpm at port 9000 internally. This handles php requests. This cannot be accessed from outside the containers

### database
Runs mariadb at port 3306 internally, 4306 externally. This uses a directory `../docker_volume/mysql` to persist the 
mysql database files (otherwise the databases will be gone every restart).

This can be accessed using any db client
```
host: 127.0.0.1
port: 4306
username: root
password: root
```

The root password and access is configured using `MYSQL_ROOT_PASSWORD` and  `MYSQL_ROOT_HOST` (`grant all privileges to 'root'@'%''`).
This is only valid on first boot. When you change the root password in `docker-compose.yaml`, it will not reflect because
the mysql users table have already been initialized.

### mailcatcher

SMTP server running at port 1025 used to test email sending. Access the emails using its builtin webmail client at
http://localhost:1080/


## Testing

### DB setup

Login to mysql and create the database `laravel`

```
$ mysql -u root --password --port 4306 

> CREATE DATABASE laravel
```

Here's the tricky part, .env is setup so that laravel inside the container can access the DB in another container.
So the hostname is set to `database` and port to `3306`, database's insternal port.

You have to change this to `127.0.0.1` and `4306` to be able run artisan commands because this is done *outside* the containers.

```
# Temporarily set this in .env to run artisan commands
DB_HOST=127.0.0.1
DB_PORT=4306
```

Initialize database

```
# Install the migrations table
php artisan migrate:install

# Run migrations
php artisan migrate
```

### Run it

Restore .env
```
# Restore so laravel can access the DB from inside the container
DB_HOST=database
DB_PORT=3306
```

A sample controller included that demos db and mail connection.


Go to http://localhost:8080/mail or http://localhost:8000/mail to generate a mail with content fetched from the database.

<img src="https://raw.githubusercontent.com/kedomingo/simple-docker-laravel/master/mail.png" />


Then to go http://localhost:1080/ to check your mailcatcher inbox

<img src="https://raw.githubusercontent.com/kedomingo/simple-docker-laravel/master/mailcatcher.png" />

<hr />
-Kyle


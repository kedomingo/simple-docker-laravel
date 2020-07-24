# A simple demo how to use docker to serve a laravel application

1. Clone this repository
2. Run `composer install` to install PHP dependencies
3. Run `cp .env.sample .env` to prepare Laravel
4. Run `php artisan key:generate` to prepare Laravel
5. Run `docker-compose up` to spin up the docker containers

## Containers

The containers and the images they are based on are defined in `docker-compose.yaml`.

There are 2 ways to assign docker images to to the containers: 

* Use them as is. In this example, `nginx` is using the default image from dockerhub. This is done in docker-compose.yaml as


      image: nginx:latest

* Build one from a default image. This is done using a Dockerfile. In this example `apache` is using the base PHP image `php:7.2-apache` and installs additional things to it like PDO, pdo_mysql, and the rewrite apache module.  This is done in docker-compose.yaml as


        build:
          context: .
          dockerfile: ./docker/web/Dockerfile

`context` is the directory that the Dockerfile "sees". In this example, the directories in the Dockerfile are all relative to the current working directory
If ever you have a Dockerfile with a line that says `COPY somefile.txt /etc/somefile.txt`, it will attempt to look for the file `somefile.txt` in the current working directory, wherever this Dockerfile is located, because of the context definition.


2 web servers are included to show that you can choose to run either Apache or Nginx+FPM.

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

You have to change this to `127.0.0.1` and `4306` to be able run artisan commands because this is done *outside* the containers. (This can be solved by having another container with the sole purpose of running `php artisan` but let's leave it like this for now)

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

A sample controller is included that demos db and mail connection.


Go to http://localhost:8080/mail or http://localhost:8000/mail to generate a mail with content fetched from the database.

<img src="https://raw.githubusercontent.com/kedomingo/simple-docker-laravel/master/mail.png" />


Then to go http://localhost:1080/ to check your mailcatcher inbox

<img src="https://raw.githubusercontent.com/kedomingo/simple-docker-laravel/master/mailcatcher.png" />



### Going inside the containers

Run `docker ps` to see the running containers

```
kd558w kd558w % docker ps
CONTAINER ID        IMAGE                    COMMAND                  CREATED              STATUS              PORTS                              NAMES
332ac79c18e6        simpledocker_apache      "docker-php-entrypoi…"   About a minute ago   Up About a minute   0.0.0.0:8080->80/tcp               simpledocker_apache_1
0e0732bff136        nginx:latest             "/docker-entrypoint.…"   About a minute ago   Up About a minute   0.0.0.0:8000->80/tcp               simpledocker_nginx_1
0cc162f8cad5        mariadb/server:10.4      "docker-entrypoint.s…"   About a minute ago   Up About a minute   0.0.0.0:4306->3306/tcp             simpledocker_database_1
b658cf847e1f        simpledocker_nginx-fpm   "docker-php-entrypoi…"   About a minute ago   Up About a minute   9000/tcp                           simpledocker_nginx-fpm_1
7430ea213554        schickling/mailcatcher   "mailcatcher --no-qu…"   About a minute ago   Up About a minute   1025/tcp, 0.0.0.0:1080->1080/tcp   simpledocker_mailcatcher_1
```

You can "ssh" into one of these either by using the container id or the container name. Let's go inside the apache container. The command is `$ docker exec -it -u root <container> bash`

```
docker exec -it -u root 332ac79c18e6 bash

OR

docker exec -it -u root simpledocker_apache_1 bash
```

Remember the `somefile.txt` file that was copied in `docker/web/Dockerfile`? It has been copied as `/etc/somefile.html`

```
kd558w kd558w % docker exec -it -u root 332ac79c18e6 bash
root@332ac79c18e6:/var/www/html# ls -la /etc/somefile.html
-rw-r--r-- 1 root root 5 Jul 24 11:13 /etc/somefile.html
```

### Rebuilding the containers

Container images based on dockerfiles need to be rebuild when you make changes to their docker-compose.yaml or Dockerfile configuration. This can be done using either `docker-compose build <container>` or `docker-compose up --build` (to build all containers and up them afterwards)


<hr />
-Kyle


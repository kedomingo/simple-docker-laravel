## A simple demo how to use docker to serve a laravel application

1. Clone this repository
2. Run `composer install` to install PHP dependencies
3. Run `cp .env.sample .env` to prepare Laravel
4. Run `php artisan key:geenrate` to prepare Laravel
5. Run `docker-compose up` to spin up the docker containers

### Containers

#### apache
Runs apache on port 80 internally. Externally available at port 8080. This is configured using the attached `httpd-vhosts.conf`
 
This can be access on the browser at http://localhost:8080/

Other test pages

* Homepage with param - http://localhost:8080/?abc=123
* PHP file with param - http://localhost:8080/index.php?abc=123
* Static file - http://localhost:8080/static/images/hello.html
* Static with param - http://localhost:8080/static/images/hello.html?v=123

#### nginx + nginx-fpm

Test pages

* Homepage with param - http://localhost:8000/?abc=123
* PHP file with param - http://localhost:8000/index.php?abc=123
* Static file - http://localhost:8000/static/images/hello.html
* Static with param - http://localhost:8000/static/images/hello.html?v=123

##### nginx 
Runs nginx at port 80 internally. Externally available at port 8000. This is configured using the attached `nginx.conf`.

This serves static files. By default, it serves `index.html` which does not exist. This is intentional so it does not 
serve `index.php` as a static file. 

For all URLs, it will check if the path exists in public, otherwise, it will rewrite 
it to `index.php`. The configuration checks if the URL is *.php and if so, passes the request to the FPM handler at port 9000
identified by the line in nginx.conf `fastcgi_pass  nginx-fpm:9000;`

##### nginx-fpm
Runs php-fpm at port 9000 internally. This handles php requests. This cannot be accessed from outside the containers

#### database
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

# mailcatcher

SMTP server running at port 1025 used to test email sending. Access the emails using its builtin webmail client at
http://localhost:1080/

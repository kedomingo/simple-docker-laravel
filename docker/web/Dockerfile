FROM php:7.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN a2enmod rewrite

# Copy the file in the context directory into the /etc/somefile.html file IN the container
COPY somefile.txt /etc/somefile.html

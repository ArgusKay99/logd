FROM php:7.3-apache
WORKDIR /var/www/html

COPY . .

# install every fucking driver in order to get it to run. probably some of same can be removed
RUN docker-php-ext-install mysqli pdo pdo_mysql


EXPOSE 80

FROM php:7.2
MAINTAINER Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>

RUN apt-get update && apt-get -y install git-core unzip \
&& pecl install xdebug \
&& echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
&& curl --silent --show-error https://getcomposer.org/installer | php \
&& mv composer.phar /usr/local/bin/composer

# mysql extension
RUN docker-php-ext-install -j$(nproc) mysqli pdo_mysql

# postgresql extension
RUN apt-get -y install libpq-dev \
&& docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
&& docker-php-ext-install -j$(nproc) pgsql pdo_pgsql

WORKDIR /app

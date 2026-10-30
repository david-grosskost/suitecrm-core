# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-apache AS base

ARG SUITECRM_VERSION=8.10.2

# PHP extensions required by SuiteCRM 8 (curl, json, mbstring, xml, openssl are built in)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl gd mysqli pdo_mysql soap zip opcache ldap imap

RUN apt-get update \
 && apt-get install -y --no-install-recommends unzip \
 && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

WORKDIR /var/www/html

ADD https://github.com/SuiteCRM/SuiteCRM-Core/releases/download/v${SUITECRM_VERSION}/SuiteCRM-${SUITECRM_VERSION}.zip /tmp/suitecrm.zip
RUN unzip -q /tmp/suitecrm.zip -d /var/www/html \
 && rm /tmp/suitecrm.zip \
 && chown -R www-data:www-data /var/www/html

COPY .docker/apache/suitecrm.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/php/suitecrm.ini /usr/local/etc/php/conf.d/suitecrm.ini
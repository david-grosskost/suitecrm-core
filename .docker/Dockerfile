# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-apache AS base

ARG SUITECRM_VERSION=8.10.2

# PHP extensions required by SuiteCRM 8 (curl, json, mbstring, xml, openssl are built in)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl gd mysqli pdo_mysql soap zip opcache ldap imap

RUN apt-get update \
 && apt-get install -y --no-install-recommends unzip rsync \
 && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

# Reference code, copied into the app volume by the entrypoint.
# SuiteCRM writes into itself (config, Studio, Module Builder, uploads),
# so the running application lives in a volume, not in the image.
ADD https://github.com/SuiteCRM/SuiteCRM-Core/releases/download/v${SUITECRM_VERSION}/SuiteCRM-${SUITECRM_VERSION}.zip /tmp/suitecrm.zip
RUN mkdir -p /usr/src/suitecrm \
 && unzip -q /tmp/suitecrm.zip -d /usr/src/suitecrm \
 && rm /tmp/suitecrm.zip \
 && echo "$SUITECRM_VERSION" > /usr/src/suitecrm/.image-version \
 && chown -R www-data:www-data /usr/src/suitecrm

COPY .docker/apache/suitecrm.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/php/suitecrm.ini /usr/local/etc/php/conf.d/suitecrm.ini
COPY --chmod=755 .docker/entrypoint.sh /usr/local/bin/suitecrm-entrypoint
COPY --chmod=755 scripts/install.sh /usr/local/bin/suitecrm-install

WORKDIR /var/www/html

ENTRYPOINT ["suitecrm-entrypoint"]
CMD ["apache2-foreground"]

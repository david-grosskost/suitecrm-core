# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-apache AS base

ARG SUITECRM_VERSION=8.10.2

# French language pack, matching the SuiteCRM minor version (SourceForge, folder 8.10)
ARG LANG_PACK_VERSION=8.10
ARG LANG_PACK_SHA256=3930464a8510f46a8c12d5a319e90a5847ee754877e0b038ba1e03cf39f74288

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
ADD --checksum=sha256:${LANG_PACK_SHA256} \
    https://sourceforge.net/projects/suitecrmtranslations/files/${LANG_PACK_VERSION}/fr_FR_SuiteCRM_lang_${LANG_PACK_VERSION}.zip/download \
    /tmp/fr_FR.zip

# The language pack is installed the way its manifest.php does it: include/, modules/
# and install/ copied into public/legacy/. .image-version identifies this build
# (version + content hash), so the entrypoint syncs the volume whenever the code changes.
RUN mkdir -p /usr/src/suitecrm /tmp/fr_FR \
 && unzip -q /tmp/suitecrm.zip -d /usr/src/suitecrm \
 && unzip -q /tmp/fr_FR.zip -d /tmp/fr_FR \
 && cp -r /tmp/fr_FR/include /tmp/fr_FR/modules /tmp/fr_FR/install /usr/src/suitecrm/public/legacy/ \
 && rm -rf /tmp/suitecrm.zip /tmp/fr_FR.zip /tmp/fr_FR \
 && cd /usr/src/suitecrm \
 && hash="$(find . -type f -print0 | sort -z | xargs -0 sha256sum | sha256sum | cut -c1-12)" \
 && echo "$SUITECRM_VERSION+$hash" > .image-version \
 && chown -R www-data:www-data /usr/src/suitecrm

# Shared base configuration (French defaults) and admin alignment, applied once at install
COPY config/config_override.php /usr/local/share/suitecrm/config_override.php
COPY scripts/post-install.php /usr/local/share/suitecrm/post-install.php

COPY .docker/apache/suitecrm.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/php/suitecrm.ini /usr/local/etc/php/conf.d/suitecrm.ini
COPY --chmod=755 .docker/entrypoint.sh /usr/local/bin/suitecrm-entrypoint
COPY --chmod=755 scripts/install.sh /usr/local/bin/suitecrm-install

WORKDIR /var/www/html

ENTRYPOINT ["suitecrm-entrypoint"]
CMD ["apache2-foreground"]

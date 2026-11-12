# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4
# Used by two stages: the image's code (base) and the extensions checked
# against it (vendor).
ARG SUITECRM_VERSION=8.10.2

# Tool stage: only its install-php-extensions script is copied below.
FROM mlocati/php-extension-installer:2 AS php-extension-installer

# Production dependencies of composer.json only (never the devkit). Listed in
# composer.lock, so composer audit checks them too:
# - Faker, for the generated demo data (database/factories), copied below
#   into database/vendor;
# - the extensions (Composer type suitecrm-extension), checked against the
#   SuiteCRM version of the image and gathered into catalog/ (see
#   scripts/extensions-catalog.php), copied below; EXTENSIONS chooses those
#   each instance runs (scripts/extensions.sh).
FROM composer:2 AS vendor
ARG SUITECRM_VERSION
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --classmap-authoritative
COPY scripts/extensions-catalog.php ./
RUN php extensions-catalog.php "$SUITECRM_VERSION" vendor catalog

FROM php:${PHP_VERSION}-apache AS base

# A failing command anywhere in a pipe fails the RUN (hadolint DL4006).
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

ARG SUITECRM_VERSION

# French language pack, matching the SuiteCRM minor version (SourceForge, folder 8.10)
ARG LANG_PACK_VERSION=8.10
ARG LANG_PACK_SHA256=3930464a8510f46a8c12d5a319e90a5847ee754877e0b038ba1e03cf39f74288

# PHP extensions required by SuiteCRM 8 (curl, json, mbstring, xml, openssl are built in)
COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl gd mysqli pdo_mysql soap zip opcache ldap imap

# Debian security updates, including those not yet in the php base image
# (fixed by Debian after its last rebuild). The release build sets
# SECURITY_REFRESH to a new value each time (suitecrm-devkit release.yml):
# this layer is then never reused from the cache, every release gets them.
ARG SECURITY_REFRESH=
RUN echo "Mises à jour de sécurité : ${SECURITY_REFRESH:-cache local}" \
 && apt-get update \
 && apt-get upgrade -y \
 && apt-get install -y --no-install-recommends unzip rsync \
 && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

# Reference code, copied into the app volume by the entrypoint.
# SuiteCRM writes into itself (config, Studio, Module Builder, uploads),
# so the running application lives in a volume, not in the image.
ADD https://github.com/SuiteCRM/SuiteCRM-Core/releases/download/v${SUITECRM_VERSION}/SuiteCRM-${SUITECRM_VERSION}.zip /tmp/suitecrm.zip
ADD https://sourceforge.net/projects/suitecrmtranslations/files/${LANG_PACK_VERSION}/fr_FR_SuiteCRM_lang_${LANG_PACK_VERSION}.zip/download \
    /tmp/fr_FR.zip

# Our French complements (labels the pack leaves in English), see lang/README.md.
COPY lang/ /tmp/lang/

# Symfony settings added to SuiteCRM's own (config/packages/), see the file.
COPY .docker/symfony/suitecrm-core.yaml /tmp/symfony/suitecrm-core.yaml

# The language pack is checked against its known SHA-256 first, then installed
# the way its manifest.php does it: include/, modules/ and install/ copied into
# public/legacy/. Each file of lang/ is then appended to the pack file at the
# same path (without its "<?php" line, after removing a closing "?>"), or
# copied if the pack has none. Our Symfony settings go last in config/packages/.
# .image-version identifies this build (version + content hash), so the
# entrypoint syncs the volume whenever the code changes.
RUN echo "${LANG_PACK_SHA256}  /tmp/fr_FR.zip" | sha256sum -c --quiet - \
 && mkdir -p /usr/src/suitecrm /tmp/fr_FR \
 && unzip -q /tmp/suitecrm.zip -d /usr/src/suitecrm \
 && unzip -q /tmp/fr_FR.zip -d /tmp/fr_FR \
 && cp -r /tmp/fr_FR/include /tmp/fr_FR/modules /tmp/fr_FR/install /usr/src/suitecrm/public/legacy/ \
 && find /tmp/lang -name '*.php' -print0 | while IFS= read -r -d '' file; do \
      target="/usr/src/suitecrm/public/legacy/${file#/tmp/lang/}"; \
      if [ -f "$target" ]; then \
        sed -i '/^?>[[:space:]]*$/d' "$target"; \
        tail -n +2 "$file" >> "$target"; \
      else \
        mkdir -p "$(dirname "$target")"; \
        cp "$file" "$target"; \
      fi; \
    done \
 && cp /tmp/symfony/suitecrm-core.yaml /usr/src/suitecrm/config/packages/zz-suitecrm-core.yaml \
 && rm -rf /tmp/suitecrm.zip /tmp/fr_FR.zip /tmp/fr_FR /tmp/lang /tmp/symfony \
 && hash="$(find /usr/src/suitecrm -type f -print0 | sort -z | xargs -0 sha256sum | sha256sum | cut -c1-12)" \
 && echo "$SUITECRM_VERSION+$hash" > /usr/src/suitecrm/.image-version \
 && chown -R www-data:www-data /usr/src/suitecrm

# Shared base configuration (French defaults) and admin alignment, applied once at install
COPY config/config_override.php /usr/local/share/suitecrm/config_override.php
COPY scripts/post-install.php /usr/local/share/suitecrm/post-install.php
# SMTP server of the environment (Mailpit in local and demo), see the script.
COPY scripts/mail.php /usr/local/share/suitecrm/mail.php
# Database scripts: French demo data, loaded on demand (make db@seed), never
# by the image itself.
COPY database/ /usr/local/share/suitecrm/database/
COPY --from=vendor /app/vendor /usr/local/share/suitecrm/database/vendor
# Extensions of the image and their activation (EXTENSIONS, at each start),
# with the Quick Repair they need and their install/uninstall scripts.
COPY --from=vendor /app/catalog/ /usr/local/share/suitecrm/extensions/
COPY scripts/repair.php scripts/extension-script.php /usr/local/share/suitecrm/

COPY .docker/apache/suitecrm.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/php/suitecrm.ini /usr/local/etc/php/conf.d/suitecrm.ini

# Only the reverse proxy reaches the app (Docker networks, private addresses):
# Symfony then trusts its X-Forwarded-* headers (see .docker/symfony).
ENV TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16

COPY --chmod=755 .docker/entrypoint.sh /usr/local/bin/suitecrm-entrypoint
COPY --chmod=755 scripts/install.sh /usr/local/bin/suitecrm-install
COPY --chmod=755 .docker/cron.sh /usr/local/bin/suitecrm-cron
COPY --chmod=755 scripts/extensions.sh /usr/local/bin/suitecrm-extensions

WORKDIR /var/www/html

ENTRYPOINT ["suitecrm-entrypoint"]
CMD ["apache2-foreground"]

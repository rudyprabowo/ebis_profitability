ARG PHP_VERSION=8.1
FROM php:${PHP_VERSION}-fpm-alpine

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions gd-stable
RUN install-php-extensions xdebug-stable
# RUN install-php-extensions bcmath-stable
# RUN install-php-extensions bz2-stable
# RUN install-php-extensions csv-stable
# RUN install-php-extensions decimal-stable
# RUN install-php-extensions geospatial
# RUN install-php-extensions gettext-stable
# RUN install-php-extensions imagick-stable
# RUN install-php-extensions imap-stable
RUN install-php-extensions intl-stable
# RUN install-php-extensions json_post-stable
# RUN install-php-extensions ldap-stable
RUN install-php-extensions mcrypt-stable
RUN install-php-extensions mysqli-stable
RUN install-php-extensions oci8-stable
RUN install-php-extensions odbc-stable
RUN install-php-extensions opcache-stable
# RUN install-php-extensions openswoole-stable
RUN install-php-extensions pdo_mysql-stable
RUN install-php-extensions pdo_oci-stable
RUN install-php-extensions pdo_odbc-stable
RUN install-php-extensions pdo_pgsql-stable
RUN install-php-extensions pgsql-stable
# RUN install-php-extensions redis-stable
RUN install-php-extensions soap-stable
# RUN install-php-extensions sockets-stable
RUN install-php-extensions uuid-stable
# RUN install-php-extensions uploadprogress-stable
# RUN install-php-extensions xsl-stable
# RUN install-php-extensions xmlrpc
# RUN install-php-extensions yaml-stable
# RUN install-php-extensions zip-stable

RUN install-php-extensions @composer

COPY ./php-fpm.d/ /usr/local/etc/php-fpm.d/
COPY ./conf.d/ /usr/local/etc/php/conf.d/

WORKDIR "/var/www"

# RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
# Use the default production configuration
# RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
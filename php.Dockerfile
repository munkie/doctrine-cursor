ARG PHP_VER=7.1
FROM php:${PHP_VER}-cli

RUN apt-get update -q && \
    apt-get install -y --no-install-recommends libpq-dev libsqlite3-dev && \
    rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pdo_mysql \
        pdo_sqlite \
        zip

RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_PROCESS_TIMEOUT=0
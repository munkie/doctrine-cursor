FROM php:7.1-cli

RUN apt-get update -q && \
    apt-get install -y --no-install-recommends libpq-dev libsqlite3-dev && \
    rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pdo_mysql \
        pdo_sqlite

RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer
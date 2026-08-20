FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libssl-dev \
        libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql bcmath pcntl intl zip opcache \
    && pecl install swoole \
    && pecl install redis \
    && docker-php-ext-enable swoole redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

EXPOSE 8000

ENTRYPOINT ["app-entrypoint"]

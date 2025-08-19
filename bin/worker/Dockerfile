FROM php:8.3-apache-bookworm

ARG DEBIAN_FRONTEND=noninteractive

# Обновление и установка нужных библиотек и инструментов
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y --no-install-recommends \
        default-mysql-client \
        libsqlite3-dev \
        libsqlite3-0 \
        zlib1g-dev \
        libzip-dev \
        libicu-dev \
        supervisor \
        git \
        curl \
        libonig-dev \
        libcurl4 \
        libcurl4-openssl-dev \
        zip \
        openssl \
        libmagickwand-dev \
        libldap2-dev \
        iputils-ping \
        procps \
        firebird-dev && \
    rm -rf /var/lib/apt/lists/*

# Установка PHP-расширений
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg && \
    docker-php-ext-install \
        pdo_mysql \
        pdo_sqlite \
        pdo_firebird \
        bcmath \
        mysqli \
        curl \
        zip \
        intl \
        mbstring \
        ldap

WORKDIR /var/www/html

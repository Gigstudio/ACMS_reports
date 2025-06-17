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
        git \
        curl \
        libonig-dev \
        libcurl4 \
        libcurl4-openssl-dev \
        zip \
        openssl \
        libmagickwand-dev \
        libldap2-dev \
        libldap-2.5-0 \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        iputils-ping \
        firebird-dev && \
    rm -rf /var/lib/apt/lists/*

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Установка PECL-расширений
RUN pecl install xdebug-3.3.1 redis-6.0.2 imagick && \
    docker-php-ext-enable xdebug redis imagick && \
    mkdir -p /var/log/xdebug

# Установка PHP-расширений одним вызовом
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
        gettext \
        calendar \
        exif \
        ldap \
        gd

# Активация нужных модулей Apache за один шаг
RUN a2enmod ssl rewrite headers authnz_ldap ldap auth_basic

# Создание директории под ssl (если потребуется)
RUN mkdir -p /etc/apache2/ssl

# Очистка исходников (опционально)
RUN rm -rf /usr/src/*


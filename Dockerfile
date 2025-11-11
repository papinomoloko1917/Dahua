FROM php:8.2-fpm

# Установка необходимых расширений PHP и SSH-клиента
RUN apt-get update && apt-get install -y \
    openssh-client \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql mysqli

WORKDIR /var/www/html


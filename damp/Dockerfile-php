FROM php:7.3-fpm
RUN apt-get update && apt-get install -y zlib1g-dev libzip-dev && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install -j$(nproc) iconv mbstring pdo pdo_mysql zip
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php --install-dir=/bin --filename=composer && php -r "unlink('composer-setup.php');"
WORKDIR /app
CMD ["php-fpm"]

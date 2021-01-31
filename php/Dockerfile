FROM alpine:3.13
RUN apk add --no-cache php-cli php-json php-openssl php-phar gnu-libiconv php-iconv php-mbstring
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php --install-dir=bin --filename=composer && php -r "unlink('composer-setup.php');"
WORKDIR /app
EXPOSE 8000
ENTRYPOINT ["/usr/bin/php", "-S", "0.0.0.0:8000"]

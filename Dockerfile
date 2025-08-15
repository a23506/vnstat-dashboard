FROM composer:2 AS build_vendor
WORKDIR /app
COPY app/composer.json ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction
COPY app/ ./
RUN composer dump-autoload -o

FROM php:8.2-apache-bookworm
ENV TZ=Asia/Shanghai
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends vnstat tzdata ca-certificates curl; \
    rm -rf /var/lib/apt/lists/*; \
    printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf; \
    a2enconf servername; \
    a2enmod dir expires headers rewrite; \
    printf '%s\n' \
      '<Directory /var/www/html>' \
      '    Options -Indexes +FollowSymLinks' \
      '    AllowOverride None' \
      '    Require all granted' \
      '    DirectoryIndex index.php index.html' \
      '</Directory>' \
      > /etc/apache2/conf-available/vnstat-dashboard.conf; \
    a2enconf vnstat-dashboard
COPY --from=build_vendor /app/ /var/www/html/
RUN mkdir -p /var/www/html/templates_c /var/www/html/cache /var/www/html/configs \
    && chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]

FROM php:8.2-apache

RUN apt-get update && apt-get install -y libzip-dev zip unzip git

RUN docker-php-ext-install zip
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite
COPY default.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer/composer /usr/bin/composer /usr/bin/composer

# Add php extensions and dependencies
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

WORKDIR /app

COPY . /app
RUN chown -R www-data:www-data /app
RUN chmod -R 755 /app

EXPOSE 80

CMD ["apache2-foreground"]

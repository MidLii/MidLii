FROM php:7.4-apache

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    libmagic-dev \
    ffmpeg \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

RUN docker-php-ext-install pdo pdo_mysql

CMD ["apache2-foreground"]
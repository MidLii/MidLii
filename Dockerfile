FROM php:7.4-apache

ARG UID=1000
ARG GID=1000

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    libmagic-dev \
    ffmpeg \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

RUN docker-php-ext-install pdo pdo_mysql

RUN addgroup --gid $GID midlii && \
    adduser --uid $UID --gid $GID --disabled-password --gecos "" midlii

WORKDIR /var/www/html

RUN chown -R midlii:midlii /var/www/html

USER midlii

CMD ["apache2-foreground"]

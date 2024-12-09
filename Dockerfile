FROM php:7.4-apache

ARG UID
ARG GID
ARG CJ_USERNAME
ARG CJ_PASSWORD

USER root

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    libmagic-dev \
    ffmpeg \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

RUN docker-php-ext-install pdo pdo_mysql

RUN addgroup --gid $GID midlii && \
    adduser --uid $UID --gid $GID --disabled-password --gecos "" midlii && \
    chown -R $UID:$GID /var/www/html /var/log/apache2 /var/www

RUN sed -i 's/^User .*/User midlii/' /etc/apache2/apache2.conf && \
    sed -i 's/^Group .*/Group midlii/' /etc/apache2/apache2.conf

USER midlii

CMD ["apache2-foreground"]

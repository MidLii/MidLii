FROM php:7.4-apache

RUN a2enmod rewrite
CMD ["apache2-foreground"]
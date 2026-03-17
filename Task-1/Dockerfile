FROM php:8.2-apache

# Disable conflicting MPMs, enable prefork
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork

# Enable useful modules (optional)
RUN a2enmod rewrite

COPY . /var/www/html/
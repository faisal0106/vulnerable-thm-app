FROM php:8.2-apache

# Fix MPM
RUN a2dismod mpm_event || true \
 && a2dismod mpm_worker || true \
 && a2dismod mpm_prefork || true \
 && a2enmod mpm_prefork

# Enable rewrite
RUN a2enmod rewrite

# Copy app
COPY app/ /var/www/html/portal/

# Set correct root
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/portal|g' /etc/apache2/sites-available/000-default.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

# SQLite fix
RUN mkdir -p /var/www/db \
 && chmod -R 777 /var/www/db \
 && chown -R www-data:www-data /var/www/db

EXPOSE 80
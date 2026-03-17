FROM php:8.2-apache

# Fix Apache MPM
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork

# Enable rewrite
RUN a2enmod rewrite

# Copy portal properly
COPY app/ /var/www/html/portal/

# Fix permissions for web root
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create DB directory with full permissions (CRITICAL FIX)
RUN mkdir -p /var/www/db \
    && chmod -R 777 /var/www/db \
    && chown -R www-data:www-data /var/www/db

EXPOSE 80
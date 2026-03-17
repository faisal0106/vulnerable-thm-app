FROM php:8.2-apache

# Fix MPM conflict
RUN a2dismod mpm_event || true \
 && a2dismod mpm_worker || true \
 && a2dismod mpm_prefork || true \
 && a2enmod mpm_prefork

# Enable rewrite
RUN a2enmod rewrite

# Copy full app (app folder)
COPY app/ /var/www/html/

# Fix web root permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

# 🔥 CRITICAL FIX (SQLite directory)
RUN mkdir -p /var/www/db \
 && chmod -R 777 /var/www/db \
 && chown -R www-data:www-data /var/www/db

EXPOSE 80
FROM php:8.2-apache

# Fix Apache MPM conflict
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork

# Enable useful modules
RUN a2enmod rewrite

# Set correct document root to portal
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/portal|g' /etc/apache2/sites-available/000-default.conf

# Allow access to portal directory
RUN echo '<Directory /var/www/html/portal>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Copy project files (IMPORTANT FIX)
COPY . /var/www/html/

# Fix permissions for SQLite
RUN mkdir -p /var/www/db && chmod -R 777 /var/www/db

EXPOSE 80
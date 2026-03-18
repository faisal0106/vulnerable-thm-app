FROM php:8.2-apache

# Install PDO SQLite extension
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy app files into /var/www/html/
COPY app/ /var/www/html/

# Create db/ directory at the correct path
RUN mkdir -p /var/www/db \
    && chmod -R 777 /var/www/db \
    && chown -R www-data:www-data /var/www/db

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Fix MPM conflict at runtime + start Apache
CMD bash -c "a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null; a2enmod mpm_prefork && apache2-foreground"

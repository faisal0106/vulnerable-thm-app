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
# config.php uses: __DIR__ . '/../db/'
# Since app files live at /var/www/html/, db/ must be at /var/www/db/
# But __DIR__ for config.php = /var/www/html, so /../db = /var/www/db
RUN mkdir -p /var/www/db \
    && chmod -R 777 /var/www/db \
    && chown -R www-data:www-data /var/www/db

# Also handle portal/config.php which uses: __DIR__ . '/../../db/'
# __DIR__ = /var/www/html/portal, so /../../db = /var/www/db  (same folder, correct)

# Set correct document root to /var/www/html
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' \
    /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

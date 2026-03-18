FROM php:8.2-cli

# Install PDO SQLite extension
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Copy app files
COPY app/ /var/www/html/

# Create db directory
RUN mkdir -p /var/www/db && chmod -R 777 /var/www/db

WORKDIR /var/www/html

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]

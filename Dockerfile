# Multi-stage build for PHP backend + Vue.js frontend
FROM node:18-alpine AS frontend-builder

WORKDIR /app/frontend

# Copy frontend files
COPY frontend/package*.json ./
RUN npm install

COPY frontend/ ./
RUN node node_modules/vite/bin/vite.js build

# PHP Runtime Stage
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache to serve frontend and route API to backend
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/frontend/dist\n\
\n\
    # Serve frontend\n\
    <Directory /var/www/html/frontend/dist>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.html\n\
    </Directory>\n\
\n\
    # Route /api requests to PHP backend\n\
    RewriteEngine On\n\
    RewriteRule ^/api/(.*)$ /index.php/$1 [L,QSA]\n\
\n\
    # Backend directory\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files (backend)
COPY --chown=www-data:www-data . .

# Copy built frontend from builder stage
COPY --from=frontend-builder --chown=www-data:www-data /app/frontend/dist ./frontend/dist

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN cd app && composer install --no-dev --optimize-autoloader

# Create necessary directories with proper permissions
RUN mkdir -p runtime/logs runtime/session public/images/vehicles public/images/licenses \
    && chown -R www-data:www-data runtime public/images \
    && chmod -R 755 runtime public/images

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
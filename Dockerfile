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

# Configure Apache to serve SPA and route API
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/frontend/dist\n\
\n\
    RewriteEngine On\n\
\n\
    # 1) API: route /api/* to backend index.php (outside DocumentRoot)\n\
    RewriteCond %{REQUEST_URI} ^/api/\n\
    RewriteRule ^api/(.*)$ /var/www/html/index.php [L,E=PATH_INFO:/api/$1,QSA]\n\
\n\
    # 2) Static assets: serve file if it exists\n\
    RewriteCond %{REQUEST_FILENAME} -f\n\
    RewriteRule ^ - [L]\n\
\n\
    # 3) SPA fallback: send everything else to frontend index.html\n\
    RewriteRule . /index.html [L]\n\
\n\
    # Grant access to backend directory for PHP execution\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes +FollowSymLinks\n\
    </Directory>\n\
\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf
# Enable Apache mod_rewrite and alias
RUN a2enmod rewrite alias

# Configure Apache to serve SPA and route API
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html\n\
\n\
    # Static aliases for frontend build assets\n\
    Alias /assets /var/www/html/frontend/dist/assets\n\
    <Directory /var/www/html/frontend/dist/assets>\n\
        Require all granted\n\
        Options -Indexes\n\
        AllowOverride None\n\
    </Directory>\n\
    Alias /vite.svg /var/www/html/frontend/dist/vite.svg\n\
\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        RewriteEngine On\n\
\
        # 1) API to backend PHP\n\
        RewriteRule ^api/(.*)$ /index.php/api/$1 [L,QSA]\n\
\
        # 2) Exclude existing files and frontend assets from rewrites\n\
        RewriteCond %{REQUEST_FILENAME} -f\n\
        RewriteRule ^ - [L]\n\
        RewriteCond %{REQUEST_URI} ^/(assets/|vite\.svg) [OR]\n\
        RewriteCond %{REQUEST_URI} ^/public/\n\
        RewriteRule ^ - [L]\n\
\
        # 3) SPA fallback for any other non-file, non-API request\n\
        RewriteCond %{REQUEST_URI} !^/api/\n\
        RewriteCond %{REQUEST_FILENAME} !-d\n\
        RewriteRule ^(.*)$ /frontend/dist/index.html [L]\n\
    </Directory>\n\
\
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
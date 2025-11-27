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

# Enable Apache mod_rewrite, headers, and proxy
RUN a2enmod rewrite headers proxy proxy_http

# Configure Apache
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/frontend/dist\n\
\n\
    # Backend directory for API\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
\n\
    # Frontend directory\n\
    <Directory /var/www/html/frontend/dist>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride None\n\
        Require all granted\n\
        \n\
        RewriteEngine On\n\
        RewriteBase /\n\
        \n\
        # API requests - route to backend PHP\n\
        RewriteCond %{REQUEST_URI} ^/api/\n\
        RewriteRule ^api/(.*)$ /var/www/html/index.php/api/$1 [L,P,QSA]\n\
        \n\
        # Frontend SPA - serve static files or fallback to index.html\n\
        RewriteCond %{REQUEST_FILENAME} !-f\n\
        RewriteCond %{REQUEST_FILENAME} !-d\n\
        RewriteCond %{REQUEST_URI} !^/api/\n\
        RewriteRule ^ /index.html [L]\n\
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
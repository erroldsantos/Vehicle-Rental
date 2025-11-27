# ============================
# Stage 1: Build Vue.js frontend
# ============================
FROM node:18-alpine AS frontend-builder

WORKDIR /app/frontend

# Install dependencies
COPY frontend/package*.json ./
RUN npm install

# Build frontend
COPY frontend/ ./
RUN node node_modules/vite/bin/vite.js build

# ============================
# Stage 2: PHP + Apache runtime
# ============================
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

# Enable Apache modules
RUN a2enmod rewrite alias

# Set working directory
WORKDIR /var/www/html

# Copy backend files first
COPY --chown=www-data:www-data . .

# Copy frontend build output to frontend/dist subdirectory
COPY --from=frontend-builder --chown=www-data:www-data /app/frontend/dist ./frontend/dist

# Install Composer and dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN cd app && composer install --no-dev --optimize-autoloader

# Create necessary directories
RUN mkdir -p runtime/logs runtime/session public/images/vehicles public/images/licenses \
    && chown -R www-data:www-data runtime public/images \
    && chmod -R 755 runtime public/images

# Configure Apache VirtualHost with FallbackResource
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/frontend/dist\n\
\n\
    # Backend directory\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
\n\
    # Frontend directory with SPA fallback\n\
    <Directory /var/www/html/frontend/dist>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride None\n\
        Require all granted\n\
        FallbackResource /index.html\n\
    </Directory>\n\
\n\
    # Intercept /api requests and route to backend\n\
    <LocationMatch "^/api">\n\
        FallbackResource disabled\n\
    </LocationMatch>\n\
\n\
    RewriteEngine On\n\
    RewriteRule ^/api/(.*)$ /var/www/html/index.php/api/$1 [L]\n\
\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]

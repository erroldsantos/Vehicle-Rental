# Backend Configuration Guide

This guide covers the configuration needed to host the Vehicle Rental System backend.

---

## Table of Contents
1. [Environment Setup](#environment-setup)
2. [Database Configuration](#database-configuration)
3. [API Configuration](#api-configuration)
4. [PayMongo Configuration](#paymongo-configuration)
5. [Email Configuration](#email-configuration)
6. [File Upload Configuration](#file-upload-configuration)
7. [Production Deployment](#production-deployment)
8. [Common Configuration Issues](#common-configuration-issues)

---

## Environment Setup

### 1. Base URL Configuration

**File:** `app/config/config.php`

```php
// Development
$config['base_url'] = 'http://localhost/Vehicle-Rental/';

// Production
$config['base_url'] = 'https://yourdomain.com/';
```

**Important:** The base URL should:
- End with a trailing slash `/`
- Match your actual server URL
- Use HTTPS in production

### 2. Environment Variables

Create `.env` file in root directory:

```env
# Application
APP_ENV=production
APP_DEBUG=false
BASE_URL=https://yourdomain.com/

# Security
APP_KEY=your-32-character-random-string

# Timezone
TIMEZONE=Asia/Manila
```

Generate APP_KEY:
```bash
php -r "echo bin2hex(random_bytes(16));"
```

---

## Database Configuration

**File:** `app/config/database.php`

### Development Configuration
```php
$database['main'] = array(
    'driver'   => 'mysql',
    'hostname' => 'localhost',
    'port'     => '3306',
    'username' => 'root',
    'password' => '',
    'database' => 'vehicle_rental',
    'charset'  => 'utf8mb4',
    'dbprefix' => ''
);
```

### Production Configuration
```php
$database['main'] = array(
    'driver'   => 'mysql',
    'hostname' => 'your-db-host.com',      // e.g., db.example.com
    'port'     => '3306',
    'username' => 'your_db_username',      // Use dedicated user
    'password' => 'your_secure_password',  // Strong password
    'database' => 'vehicle_rental',
    'charset'  => 'utf8mb4',
    'dbprefix' => ''
);
```

### Database Setup Steps

1. **Create Database:**
```sql
CREATE DATABASE vehicle_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Create Database User (Production):**
```sql
CREATE USER 'vehicle_rental_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON vehicle_rental.* TO 'vehicle_rental_user'@'localhost';
FLUSH PRIVILEGES;
```

3. **Import Schema:**
```bash
mysql -u root -p vehicle_rental < scheme/database/complete_schema.sql
```

4. **Verify Connection:**
```bash
php console/cli.php migrate:status
```

---

## API Configuration

**File:** `app/config/api.php`

### CORS Configuration
```php
return [
    // Development - Allow local frontend
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173'
    ],
    
    // Production - Add your frontend domain
    'allowed_origins' => [
        'https://yourdomain.com',
        'https://www.yourdomain.com'
    ],
    
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'max_age' => 86400
];
```

### API Rate Limiting (Optional)

Add to `app/config/config.php`:
```php
$config['api_rate_limit'] = [
    'enabled' => true,
    'requests_per_minute' => 60,
    'requests_per_hour' => 1000
];
```

---

## PayMongo Configuration

**File:** `app/config/paymongo.php`

### Test/Sandbox Mode
```php
return [
    'secret_key' => 'sk_test_your_test_secret_key',
    'public_key' => 'pk_test_your_test_public_key',
    'webhook_secret' => 'whsec_your_test_webhook_secret',
    'mode' => 'test'
];
```

### Production Mode
```php
return [
    'secret_key' => 'sk_live_your_live_secret_key',
    'public_key' => 'pk_live_your_live_public_key',
    'webhook_secret' => 'whsec_your_live_webhook_secret',
    'mode' => 'live'
];
```

### Getting PayMongo Credentials

1. **Sign up at:** https://paymongo.com
2. **Get API Keys:**
   - Dashboard → Developers → API Keys
   - Copy Secret Key and Public Key
3. **Setup Webhook:**
   - Dashboard → Developers → Webhooks
   - Create webhook for: `payment.paid`
   - Webhook URL: `https://yourdomain.com/api/webhook/paymongo`
   - Copy Webhook Secret

### Webhook Configuration

**Important:** Your webhook URL must be:
- Publicly accessible (not localhost)
- Using HTTPS
- Able to receive POST requests

**Test webhook locally using ngrok:**
```bash
ngrok http 80
# Use the ngrok URL for webhook: https://abc123.ngrok.io/api/webhook/paymongo
```

## File Upload Configuration

### 1. PHP Configuration

**File:** `php.ini` or `.htaccess`

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

### 2. Upload Directories

Create and set permissions:
```bash
# Create directories
mkdir -p public/images/vehicles
mkdir -p public/images/licenses

# Set permissions (Linux/Mac)
chmod 755 public/images
chmod 755 public/images/vehicles
chmod 755 public/images/licenses

# Set ownership (Linux/Mac)
chown -R www-data:www-data public/images
```

**Windows (via PowerShell):**
```powershell
New-Item -ItemType Directory -Force -Path public\images\vehicles
New-Item -ItemType Directory -Force -Path public\images\licenses
```

### 3. Storage Configuration

**File:** `app/config/config.php`

```php
$config['upload_path'] = [
    'vehicles' => 'public/images/vehicles/',
    'licenses' => 'public/images/licenses/'
];

$config['allowed_types'] = 'jpg|jpeg|png|gif';
$config['max_size'] = 5120; // 5MB in KB
```

### 4. URL Configuration for Images

**Development:**
```php
$config['image_base_url'] = 'http://localhost/Vehicle-Rental/public/images/';
```

**Production:**
```php
$config['image_base_url'] = 'https://yourdomain.com/public/images/';
```

---

## Production Deployment

### Step 1: Server Requirements

- **PHP:** 7.4 or higher
- **PHP Extensions:** mysqli, pdo, mbstring, json, openssl, curl
- **MySQL:** 5.7+ or MariaDB 10.3+
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **SSL Certificate:** Required for production

### Step 2: Upload Files

```bash
# Using rsync
rsync -avz --exclude 'runtime/' --exclude '.git/' ./ user@server:/var/www/vehicle-rental/

# Or using FTP/SFTP
# Upload all files except: runtime/, .git/, node_modules/
```

### Step 3: Set Permissions

```bash
# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Set writable directories
chmod 755 runtime
chmod 755 runtime/logs
chmod 755 runtime/session
chmod 755 public/images
chmod 755 public/images/vehicles
chmod 755 public/images/licenses
```

### Step 4: Configure Web Server

#### Apache (.htaccess)

**Root .htaccess:**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Remove index.php from URL
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
    
    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Disable directory listing
Options -Indexes

# Protect config files
<FilesMatch "\.(env|ini|log|sh|sql)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### Nginx Configuration

**File:** `/etc/nginx/sites-available/vehicle-rental`

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/vehicle-rental;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/your-cert.crt;
    ssl_certificate_key /etc/ssl/private/your-key.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/vehicle-rental-access.log;
    error_log /var/log/nginx/vehicle-rental-error.log;

    # PHP Processing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Protect sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ \.(env|ini|log|sh|sql)$ {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }
}
```

### Step 5: Database Setup

```bash
# Import database
mysql -u your_user -p vehicle_rental < scheme/database/complete_schema.sql

# Verify import
mysql -u your_user -p vehicle_rental -e "SHOW TABLES;"
```

### Step 6: Test Configuration

```bash
# Test database connection
php -r "
\$conn = new mysqli('localhost', 'username', 'password', 'vehicle_rental');
if (\$conn->connect_error) die('Failed: ' . \$conn->connect_error);
echo 'Database connected successfully!';
"

# Test API endpoint
curl https://yourdomain.com/api/vehicles
```

### Step 7: SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Generate certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

---

## Common Configuration Issues

### Issue 1: 404 Errors on API Routes

**Cause:** mod_rewrite not enabled or .htaccess not working

**Solution (Apache):**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Check .htaccess:**
```apache
RewriteEngine On
RewriteBase /
```

### Issue 2: Database Connection Failed

**Causes & Solutions:**

1. **Wrong credentials:** Verify username, password, database name
2. **MySQL not running:**
   ```bash
   sudo systemctl status mysql
   sudo systemctl start mysql
   ```
3. **Remote connection denied:**
   ```sql
   GRANT ALL PRIVILEGES ON vehicle_rental.* TO 'user'@'%' IDENTIFIED BY 'password';
   ```

### Issue 3: CORS Errors

**Cause:** Frontend origin not allowed

**Solution:** Add frontend URL to `app/config/api.php`:
```php
'allowed_origins' => [
    'https://yourdomain.com',
    'https://www.yourdomain.com'
],
```

### Issue 4: File Upload Fails

**Causes & Solutions:**

1. **Permission denied:**
   ```bash
   chmod 755 public/images
   chown -R www-data:www-data public/images
   ```

2. **File size limit:**
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```

3. **Directory doesn't exist:**
   ```bash
   mkdir -p public/images/vehicles
   mkdir -p public/images/licenses
   ```

### Issue 5: Email Not Sending

**Causes & Solutions:**

1. **Wrong SMTP credentials:** Verify in `app/config/email.php`
2. **Firewall blocking port 587/465:**
   ```bash
   sudo ufw allow 587/tcp
   ```
3. **Gmail blocking:** Use app-specific password
4. **Test email:**
   ```bash
   php console/cli.php test:email your@email.com
   ```

### Issue 6: PayMongo Webhook Not Working

**Causes & Solutions:**

1. **URL not accessible:** Test webhook URL publicly
2. **HTTPS required:** PayMongo requires HTTPS
3. **Wrong webhook secret:** Verify in PayMongo dashboard
4. **Test webhook:**
   ```bash
   curl -X POST https://yourdomain.com/api/webhook/paymongo \
        -H "Content-Type: application/json" \
        -d '{"test": true}'
   ```

---

## Environment-Specific Settings

### Development Environment
```php
// app/config/config.php
$config['environment'] = 'development';
$config['base_url'] = 'http://localhost/Vehicle-Rental/';
$config['log_threshold'] = 4; // All messages

// app/config/api.php
'allowed_origins' => ['http://localhost:5173'],

// app/config/paymongo.php
'mode' => 'test',
'secret_key' => 'sk_test_...',
```

### Staging Environment
```php
// app/config/config.php
$config['environment'] = 'staging';
$config['base_url'] = 'https://staging.yourdomain.com/';
$config['log_threshold'] = 2; // Errors and warnings

// app/config/api.php
'allowed_origins' => ['https://staging.yourdomain.com'],

// app/config/paymongo.php
'mode' => 'test',
'secret_key' => 'sk_test_...',
```

### Production Environment
```php
// app/config/config.php
$config['environment'] = 'production';
$config['base_url'] = 'https://yourdomain.com/';
$config['log_threshold'] = 1; // Errors only

// app/config/api.php
'allowed_origins' => ['https://yourdomain.com', 'https://www.yourdomain.com'],

// app/config/paymongo.php
'mode' => 'live',
'secret_key' => 'sk_live_...',
```

---

## Quick Configuration Checklist

Before deploying, ensure:

- [ ] Database credentials updated in `app/config/database.php`
- [ ] Base URL set in `app/config/config.php`
- [ ] CORS origins configured in `app/config/api.php`
- [ ] PayMongo keys configured in `app/config/paymongo.php`
- [ ] SMTP settings configured in `app/config/email.php`
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] Upload directories created and writable
- [ ] SSL certificate installed (production)
- [ ] .htaccess or Nginx config in place
- [ ] Database schema imported
- [ ] Default admin password changed
- [ ] Test all API endpoints
- [ ] Test file uploads
- [ ] Test email sending
- [ ] Test payment processing

---

## Support

For configuration issues:
1. Check error logs: `runtime/logs/log.txt`
2. Check web server logs: `/var/log/apache2/` or `/var/log/nginx/`
3. Check PHP error log: `/var/log/php/error.log`
4. Review LavaLust documentation: https://lavalust.netlify.app

---

**Last Updated:** November 30, 2025

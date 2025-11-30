# Frontend Configuration Guide

This guide covers the configuration needed to host the Vehicle Rental System frontend.

---

## Table of Contents
1. [Environment Setup](#environment-setup)
2. [API Configuration](#api-configuration)
3. [Build Configuration](#build-configuration)
4. [Development Setup](#development-setup)
5. [Production Deployment](#production-deployment)
6. [Web Server Configuration](#web-server-configuration)
7. [Environment Variables](#environment-variables)
8. [Common Configuration Issues](#common-configuration-issues)

---

## Environment Setup

### Prerequisites
- **Node.js:** 16.0 or higher
- **NPM:** 8.0 or higher

### Check Versions
```bash
node --version
npm --version
```

---

## API Configuration

### File: `src/stores/api.js`

This is the **most important** configuration file for hosting.

#### Development Configuration
```javascript
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost/Vehicle-Rental/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: false
})

export default api
```

#### Production Configuration
```javascript
import axios from 'axios'

const api = axios.create({
  baseURL: 'https://yourdomain.com/api',  // ← CHANGE THIS
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: false
})

export default api
```

#### Multiple Environment Setup (Recommended)

**Option 1: Using Environment Variables**

Create `.env` files:

**`.env.development`**
```env
VITE_API_BASE_URL=http://localhost/Vehicle-Rental/api
VITE_APP_NAME=Vehicle Rental System
```

**`.env.production`**
```env
VITE_API_BASE_URL=https://yourdomain.com/api
VITE_APP_NAME=Vehicle Rental System
```

Update `src/stores/api.js`:
```javascript
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: false
})

export default api
```

---

## Build Configuration

### File: `vite.config.js`

#### Basic Configuration
```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    host: true
  }
})
```

#### Production Optimization
```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  
  // Development server
  server: {
    port: 5173,
    host: true,
    open: true
  },
  
  // Build options
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: false,
    minify: 'terser',
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
      output: {
        manualChunks: {
          'vue-vendor': ['vue', 'vue-router', 'pinia'],
          'ui-vendor': ['chart.js', 'leaflet']
        }
      }
    }
  },
  
  // Base URL for deployment
  base: '/'  // Change if deploying to subdirectory
})
```

#### Subdirectory Deployment

If deploying to `https://yourdomain.com/app/`:
```javascript
export default defineConfig({
  // ... other config
  base: '/app/'
})
```

---

## Development Setup

### Step 1: Install Dependencies
```bash
cd frontend
npm install
```

### Step 2: Configure API URL

Edit `src/stores/api.js`:
```javascript
baseURL: 'http://localhost/Vehicle-Rental/api'
```

### Step 3: Start Development Server
```bash
npm run dev
```

Access at: `http://localhost:5173`

### Development Commands
```bash
# Start dev server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Lint code
npm run lint

# Format code
npm run format
```

---

## Production Deployment

### Step 1: Update API Configuration

**Option A: Direct Configuration**

Edit `src/stores/api.js`:
```javascript
baseURL: 'https://yourdomain.com/api'
```

**Option B: Environment Variables**

Create `.env.production`:
```env
VITE_API_BASE_URL=https://yourdomain.com/api
```

### Step 2: Build for Production
```bash
npm run build
```

Output directory: `dist/`

### Step 3: Test Production Build Locally
```bash
npm run preview
```

### Step 4: Deploy Build Files

Upload the `dist/` folder contents to your web server:

```bash
# Using rsync
rsync -avz dist/ user@server:/var/www/html/

# Using SCP
scp -r dist/* user@server:/var/www/html/

# Using FTP/SFTP
# Upload all files from dist/ folder to your web root
```

### Step 5: Configure Web Server

See [Web Server Configuration](#web-server-configuration) section below.

---

## Web Server Configuration

### Apache Configuration

#### Option 1: .htaccess (in dist/ folder)

Create `dist/.htaccess`:
```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  
  # Handle SPA routing
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType text/html "access plus 0 seconds"
</IfModule>
```

#### Option 2: VirtualHost Configuration

**File:** `/etc/apache2/sites-available/vehicle-rental-frontend.conf`

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/vehicle-rental/frontend/dist

    <Directory /var/www/vehicle-rental/frontend/dist>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # SPA fallback
        FallbackResource /index.html
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/vehicle-rental-frontend-error.log
    CustomLog ${APACHE_LOG_DIR}/vehicle-rental-frontend-access.log combined
</VirtualHost>

# SSL Configuration
<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/vehicle-rental/frontend/dist

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/your-cert.crt
    SSLCertificateKeyFile /etc/ssl/private/your-key.key

    <Directory /var/www/vehicle-rental/frontend/dist>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        FallbackResource /index.html
    </Directory>

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    
    ErrorLog ${APACHE_LOG_DIR}/vehicle-rental-frontend-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/vehicle-rental-frontend-ssl-access.log combined
</VirtualHost>
```

**Enable site:**
```bash
sudo a2ensite vehicle-rental-frontend
sudo systemctl reload apache2
```

### Nginx Configuration

**File:** `/etc/nginx/sites-available/vehicle-rental-frontend`

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS Configuration
server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/vehicle-rental/frontend/dist;
    index index.html;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/your-cert.crt;
    ssl_certificate_key /etc/ssl/private/your-key.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Logging
    access_log /var/log/nginx/vehicle-rental-frontend-access.log;
    error_log /var/log/nginx/vehicle-rental-frontend-error.log;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json;

    # SPA Routing - serve index.html for all routes
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Security - prevent access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

**Enable site:**
```bash
sudo ln -s /etc/nginx/sites-available/vehicle-rental-frontend /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Environment Variables

### Available Environment Variables

Create `.env` file in `frontend/` directory:

```env
# API Configuration
VITE_API_BASE_URL=https://yourdomain.com/api

# Application Info
VITE_APP_NAME=Vehicle Rental System
VITE_APP_VERSION=1.0.0

# Feature Flags
VITE_ENABLE_ANALYTICS=false
VITE_ENABLE_DEBUG=false

# PayMongo (Public Key only)
VITE_PAYMONGO_PUBLIC_KEY=pk_live_your_public_key

# Map Configuration (Leaflet)
VITE_MAP_CENTER_LAT=14.5995
VITE_MAP_CENTER_LNG=120.9842
VITE_MAP_ZOOM=13
```

### Using Environment Variables

In your components:
```javascript
// Access environment variables
const apiUrl = import.meta.env.VITE_API_BASE_URL
const appName = import.meta.env.VITE_APP_NAME

// Check if in production
if (import.meta.env.PROD) {
  console.log('Running in production')
}

// Check if in development
if (import.meta.env.DEV) {
  console.log('Running in development')
}
```

### Environment-Specific Builds

```bash
# Development build
npm run dev

# Production build
npm run build

# Staging build (if configured)
npm run build -- --mode staging
```

---

## Deployment Platforms

### Netlify

1. **Build Settings:**
   - Build command: `npm run build`
   - Publish directory: `dist`

2. **Create `netlify.toml`:**
```toml
[build]
  command = "npm run build"
  publish = "dist"

[[redirects]]
  from = "/*"
  to = "/index.html"
  status = 200

[build.environment]
  VITE_API_BASE_URL = "https://yourdomain.com/api"
```

### Vercel

1. **Build Settings:**
   - Framework Preset: Vite
   - Build Command: `npm run build`
   - Output Directory: `dist`

2. **Create `vercel.json`:**
```json
{
  "rewrites": [
    { "source": "/(.*)", "destination": "/index.html" }
  ],
  "headers": [
    {
      "source": "/(.*)",
      "headers": [
        { "key": "X-Frame-Options", "value": "SAMEORIGIN" },
        { "key": "X-Content-Type-Options", "value": "nosniff" }
      ]
    }
  ]
}
```

3. **Environment Variables:**
   - Add in Vercel dashboard: `VITE_API_BASE_URL`

### GitHub Pages

1. **Update `vite.config.js`:**
```javascript
export default defineConfig({
  base: '/repository-name/',
  // ... other config
})
```

2. **Create `.github/workflows/deploy.yml`:**
```yaml
name: Deploy to GitHub Pages

on:
  push:
    branches: [main]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: 16
      - run: cd frontend && npm install
      - run: cd frontend && npm run build
      - uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: ./frontend/dist
```

### AWS S3 + CloudFront

1. **Build:**
```bash
npm run build
```

2. **Upload to S3:**
```bash
aws s3 sync dist/ s3://your-bucket-name --delete
```

3. **CloudFront Distribution:**
   - Error Pages: 404 → /index.html (200 response)
   - 403 → /index.html (200 response)

### Docker Deployment

**Create `Dockerfile`:**
```dockerfile
# Build stage
FROM node:16-alpine as build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Production stage
FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

**Create `nginx.conf`:**
```nginx
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

**Build and Run:**
```bash
docker build -t vehicle-rental-frontend .
docker run -p 80:80 vehicle-rental-frontend
```

---

## Common Configuration Issues

### Issue 1: API Calls Failing (CORS)

**Symptom:** Console shows CORS errors

**Solution:**
1. Verify API URL in `src/stores/api.js`
2. Check backend CORS configuration allows your frontend domain
3. Ensure backend is running and accessible

**Test:**
```bash
curl https://yourdomain.com/api/vehicles
```

### Issue 2: 404 on Page Refresh

**Symptom:** Refreshing any route except `/` gives 404

**Solution:** Configure web server for SPA routing

**Apache:** Add to `.htaccess`:
```apache
FallbackResource /index.html
```

**Nginx:** Add to config:
```nginx
try_files $uri $uri/ /index.html;
```

### Issue 3: Assets Not Loading

**Symptom:** CSS, JS, or images not loading

**Causes & Solutions:**

1. **Wrong base path:**
   ```javascript
   // vite.config.js
   export default defineConfig({
     base: '/'  // or '/subdirectory/' if deployed to subdirectory
   })
   ```

2. **Incorrect API URL:**
   ```javascript
   // src/stores/api.js
   baseURL: 'https://yourdomain.com/api'  // Check this matches backend
   ```

### Issue 4: Build Fails

**Common causes:**

1. **Node version mismatch:**
   ```bash
   node --version  # Should be 16+
   ```

2. **Dependencies not installed:**
   ```bash
   rm -rf node_modules package-lock.json
   npm install
   ```

3. **Memory issues:**
   ```bash
   export NODE_OPTIONS="--max-old-space-size=4096"
   npm run build
   ```

### Issue 5: Environment Variables Not Working

**Solution:** Prefix with `VITE_`

```env
# ❌ Wrong
API_BASE_URL=https://api.example.com

# ✅ Correct
VITE_API_BASE_URL=https://api.example.com
```

Access in code:
```javascript
const apiUrl = import.meta.env.VITE_API_BASE_URL
```

### Issue 6: Slow Build Times

**Optimization:**

1. **Update dependencies:**
   ```bash
   npm update
   ```

2. **Disable source maps in production:**
   ```javascript
   // vite.config.js
   build: {
     sourcemap: false
   }
   ```

3. **Use code splitting:**
   ```javascript
   // vite.config.js
   build: {
     rollupOptions: {
       output: {
         manualChunks: {
           vendor: ['vue', 'vue-router', 'pinia', 'axios']
         }
       }
     }
   }
   ```

---

## Performance Optimization

### Build Optimization

**File: `vite.config.js`**
```javascript
export default defineConfig({
  build: {
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,  // Remove console.logs
        drop_debugger: true
      }
    },
    rollupOptions: {
      output: {
        manualChunks: {
          'vue-core': ['vue', 'vue-router', 'pinia'],
          'charts': ['chart.js'],
          'maps': ['leaflet'],
          'http': ['axios']
        }
      }
    }
  }
})
```

### Image Optimization

1. **Compress images before upload**
2. **Use appropriate formats:** WebP for photos, SVG for icons
3. **Lazy load images:**
   ```vue
   <img loading="lazy" src="..." alt="...">
   ```

### Code Splitting

**Lazy load routes:**
```javascript
// router/index.js
const Dashboard = () => import('../views/Dashboard.vue')
const UserManagement = () => import('../views/UserManagement.vue')
```

---

## Quick Deployment Checklist

Before deploying to production:

- [ ] Update API URL in `src/stores/api.js` or `.env.production`
- [ ] Run `npm run build` successfully
- [ ] Test production build locally with `npm run preview`
- [ ] Configure web server for SPA routing
- [ ] Set up SSL certificate
- [ ] Configure security headers
- [ ] Enable gzip compression
- [ ] Set up caching for static assets
- [ ] Verify all API endpoints work
- [ ] Test all routes (including refresh)
- [ ] Test file uploads
- [ ] Test payment flow
- [ ] Check console for errors
- [ ] Verify responsive design on mobile
- [ ] Test cross-browser compatibility

---

## Support

For frontend configuration issues:
1. Check browser console (F12)
2. Check Network tab for failed requests
3. Verify API URL is correct
4. Check web server error logs
5. Review Vite documentation: https://vitejs.dev

---

**Last Updated:** November 30, 2025

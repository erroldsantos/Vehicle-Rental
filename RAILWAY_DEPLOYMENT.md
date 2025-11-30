# Railway Deployment Guide

## Prerequisites
1. Railway account (https://railway.app)
2. GitHub repository connected to Railway

## Step 1: Create New Project in Railway

1. Go to Railway Dashboard
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your `Vehicle-Rental` repository
5. Select the `backend` branch

## Step 2: Add MySQL Database

1. In your Railway project, click "+ New"
2. Select "Database" → "Add MySQL"
3. Railway will automatically create a MySQL database

## Step 3: Configure Environment Variables

Go to your backend service → Variables tab and add:

### Database Variables (Auto-populated by Railway MySQL)
These are automatically set when you add MySQL database:
- `MYSQLHOST` → Map to `DB_HOST`
- `MYSQLPORT` → Map to `DB_PORT`
- `MYSQLUSER` → Map to `DB_USER`
- `MYSQLPASSWORD` → Map to `DB_PASSWORD`
- `MYSQLDATABASE` → Map to `DB_NAME`

### Manual Variables to Add:
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vehicle-rental-production-6cce.up.railway.app
BASE_URL=https://vehicle-rental-production-6cce.up.railway.app

# Database (use Railway's MySQL variables)
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_USER=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}
DB_NAME=${{MYSQLDATABASE}}

# PayMongo (Production Keys)
PAYMONGO_SECRET_KEY=sk_live_your_live_secret_key
PAYMONGO_PUBLIC_KEY=pk_live_your_live_public_key
PAYMONGO_WEBHOOK_SECRET=whsec_your_webhook_secret

# Email (SMTP Configuration)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=noreply@yourdomain.com
SMTP_FROM_NAME=Vehicle Rental System

# Port (Railway auto-sets this)
PORT=${{PORT}}
```

## Step 4: Import Database Schema

### Option 1: Using Railway CLI
```bash
# Install Railway CLI
npm i -g @railway/cli

# Login
railway login

# Link project
railway link

# Connect to MySQL
railway connect mysql

# Import schema
mysql> source scheme/database/complete_schema.sql
```

### Option 2: Using Railway Web Interface
1. Go to MySQL service → Data tab
2. Click "Connect" to get connection URL
3. Use MySQL Workbench or any MySQL client to connect
4. Import `scheme/database/complete_schema.sql`

### Option 3: Using MySQL Client
```bash
mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> <MYSQLDATABASE> < scheme/database/complete_schema.sql
```

## Step 5: Configure Custom Domain (Optional)

1. Go to Settings → Domains
2. Click "Generate Domain" for free Railway domain
3. Or add custom domain:
   - Add your domain (e.g., api.yourdomain.com)
   - Add CNAME record to your DNS: `<your-service>.up.railway.app`

## Step 6: Deploy

Railway will automatically deploy when you push to the `backend` branch.

To manually trigger deploy:
1. Go to Deployments tab
2. Click "Deploy" button

## Step 7: Verify Deployment

Check these endpoints:
- Health Check: `https://vehicle-rental-production-6cce.up.railway.app/api/health`
- Vehicles: `https://vehicle-rental-production-6cce.up.railway.app/api/vehicles`

## Step 8: Update Frontend

Update your frontend's API base URL to point to Railway:

In `frontend/src/stores/api.js`:
```javascript
const api = axios.create({
  baseURL: 'https://vehicle-rental-production-6cce.up.railway.app/api'
})
```

Or use environment variable:
```env
# frontend/.env.production
VITE_API_BASE_URL=https://vehicle-rental-production-6cce.up.railway.app/api
```

## Troubleshooting

### Issue: Build fails
**Solution:** Check build logs in Railway dashboard. Ensure all required PHP extensions are installed.

### Issue: Database connection fails
**Solution:** 
- Verify environment variables are set correctly
- Check if MySQL service is running
- Ensure database schema is imported

### Issue: 404 errors on API routes
**Solution:**
- Check Apache mod_rewrite is enabled (Dockerfile handles this)
- Verify .htaccess file exists

### Issue: File uploads not working
**Solution:**
- Check directory permissions (777) for `runtime` and `public/images`
- Verify PHP upload settings

### Issue: CORS errors
**Solution:**
Update `app/config/api.php`:
```php
'allowed_origins' => [
    'https://your-frontend-domain.com',
    'https://your-frontend-domain.vercel.app'
]
```

## Monitoring

### View Logs
```bash
railway logs
```

Or view in Railway Dashboard → Deployments → Click deployment → Logs

### Database Metrics
Go to MySQL service → Metrics to see:
- CPU usage
- Memory usage
- Network traffic

## Scaling

Railway automatically scales based on usage. For custom scaling:
1. Go to Settings → Resources
2. Adjust CPU/Memory allocation

## Cost Optimization

Railway free tier includes:
- $5 free credit per month
- After that, pay-as-you-go pricing

Tips to reduce costs:
- Set sleep mode for non-production environments
- Optimize image sizes
- Use proper caching

## Backup Database

### Automated Backups (Railway Pro)
Railway Pro includes automated daily backups.

### Manual Backup
```bash
railway connect mysql
mysqldump -u root -p vehicle_rental > backup_$(date +%Y%m%d).sql
```

## Support

- Railway Docs: https://docs.railway.app
- Railway Discord: https://discord.gg/railway
- GitHub Issues: https://github.com/erroldsantos/Vehicle-Rental/issues

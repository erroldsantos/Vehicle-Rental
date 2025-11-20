# Quick Setup Guide - Driver's License Verification

## Step 1: Database Migration

Run this SQL in your MySQL database (phpMyAdmin or command line):

```sql
USE vehicle_rental;

ALTER TABLE `users` 
ADD COLUMN `license_image` varchar(255) DEFAULT NULL COMMENT 'Path to driver license image',
ADD COLUMN `license_status` enum('not_submitted','pending','verified','rejected') DEFAULT 'not_submitted' COMMENT 'License verification status',
ADD COLUMN `license_submitted_at` datetime DEFAULT NULL COMMENT 'When license was submitted',
ADD COLUMN `license_verified_at` datetime DEFAULT NULL COMMENT 'When license was verified',
ADD COLUMN `license_verified_by` int(11) DEFAULT NULL COMMENT 'Admin who verified the license',
ADD COLUMN `license_rejection_reason` text DEFAULT NULL COMMENT 'Reason for rejection if rejected';

ALTER TABLE `users`
ADD CONSTRAINT `fk_license_verified_by` 
FOREIGN KEY (`license_verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `users`
ADD INDEX `idx_license_status` (`license_status`);
```

## Step 2: Verify Upload Directory

The directory `public/images/licenses/` has been created. Ensure it has write permissions:

**For Windows/XAMPP:**
- The directory should already be writable by default
- If you encounter issues, right-click the folder → Properties → Security → ensure IIS_IUSRS has Write permissions

**For Linux/Mac:**
```bash
chmod 755 public/images/licenses
```

## Step 3: Test the Backend

You can test the backend API using these curl commands or Postman:

### Test License Upload
```bash
curl -X POST http://localhost/Vehicle-Rental-/api/users/2/license/upload \
  -F "license_image=@/path/to/license.jpg"
```

### Test Get License Status
```bash
curl http://localhost/Vehicle-Rental-/api/users/2/license/status
```

### Test Admin - Get Pending Licenses
```bash
curl http://localhost/Vehicle-Rental-/api/admin/licenses/pending
```

### Test Admin - Verify License
```bash
curl -X POST http://localhost/Vehicle-Rental-/api/admin/licenses/2/verify \
  -H "Content-Type: application/json" \
  -d '{"admin_id": 1}'
```

### Test Admin - Reject License
```bash
curl -X POST http://localhost/Vehicle-Rental-/api/admin/licenses/2/reject \
  -H "Content-Type: application/json" \
  -d '{"admin_id": 1, "reason": "Image is not clear"}'
```

## Step 4: Frontend Setup

### Update Vue Router

Add the license management route to `frontend/src/router/index.js`:

```javascript
{
  path: '/admin/licenses',
  name: 'license-management',
  component: () => import('@/views/LicenseManagement.vue'),
  meta: { 
    requiresAuth: true, 
    requiresAdmin: true 
  }
}
```

### Add to Admin Navigation

Add this to your admin sidebar/navigation component:

```vue
<router-link to="/admin/licenses" class="nav-link">
  <i class="fas fa-id-card"></i>
  <span>License Verification</span>
  <span v-if="pendingCount > 0" class="badge">{{ pendingCount }}</span>
</router-link>
```

## Step 5: Build Frontend (if needed)

If you're using a build process:

```bash
cd frontend
npm install
npm run dev  # or npm run build
```

## Step 6: Access the Features

### User Dashboard
1. Login as a regular user
2. Navigate to User Dashboard
3. You should see the "Driver's License Verification" card
4. Upload a license image
5. Check the status

### Admin Dashboard
1. Login as admin
2. Navigate to `/admin/licenses` (or use the navigation link)
3. You should see:
   - Statistics cards
   - Pending licenses list
4. Click "Verify" or "Reject" on pending licenses

## Troubleshooting

### Issue: "Upload directory not writable"
**Solution:** 
```bash
# Linux/Mac
chmod 755 public/images/licenses

# Windows (Run as Administrator in PowerShell)
icacls "public\images\licenses" /grant Users:(OI)(CI)M
```

### Issue: "File too large" error
**Solution:** Update `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```
Then restart Apache.

### Issue: Images not displaying
**Solution:** 
- Check browser console for 404 errors
- Verify the image path in database matches actual file location
- Check `.htaccess` file allows access to `public/images/` directory

### Issue: Routes not working
**Solution:**
- Clear LavaLust cache (if any)
- Check `app/config/routes.php` has the new routes
- Verify mod_rewrite is enabled in Apache

## File Locations Summary

### Backend Files
- `app/models/User.php` - Updated with license methods
- `app/controllers/UsersController.php` - Added upload & status endpoints
- `app/controllers/AdminController.php` - Added verification endpoints
- `app/config/routes.php` - Added new routes
- `scheme/database/migrations/add_license_verification.sql` - Migration file
- `scheme/database/complete_schema.sql` - Updated schema
- `public/images/licenses/` - Upload directory

### Frontend Files
- `frontend/src/components/LicenseVerification.vue` - User upload component
- `frontend/src/views/LicenseManagement.vue` - Admin verification view
- `frontend/src/views/UserDashboard.vue` - Updated with license component
- `frontend/src/stores/api.js` - Added upload method

## Testing Checklist

- [ ] Database migration completed successfully
- [ ] Upload directory exists and is writable
- [ ] Routes are accessible
- [ ] User can upload license image
- [ ] User can see upload status
- [ ] Admin can see pending licenses
- [ ] Admin can verify a license
- [ ] Admin can reject a license with reason
- [ ] Statistics update correctly
- [ ] Images display properly

## Next Steps

After setup is complete:

1. **Add authentication middleware** to protect routes
2. **Implement email notifications** when license is verified/rejected
3. **Add admin navigation link** to access License Management
4. **Test with real users** to ensure smooth workflow
5. **Monitor the upload directory size** and implement cleanup if needed

## Support

For detailed documentation, see: `LICENSE_VERIFICATION_IMPLEMENTATION.md`

For LavaLust framework documentation: https://lavalust.netlify.app

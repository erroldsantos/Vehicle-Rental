# Driver's License Verification System

## Overview
This implementation adds a driver's license verification feature to the Vehicle Rental System. Users can upload their driver's license from their dashboard, and administrators can review and verify or reject the submissions.

## Features Implemented

### 1. Database Changes
- **New columns added to `users` table:**
  - `license_image` - Stores the file path of the uploaded license image
  - `license_status` - Tracks verification status (not_submitted, pending, verified, rejected)
  - `license_submitted_at` - Timestamp when license was submitted
  - `license_verified_at` - Timestamp when license was verified/rejected
  - `license_verified_by` - Foreign key to admin who processed the verification
  - `license_rejection_reason` - Reason for rejection if applicable

### 2. Backend (LavaLust Framework)

#### User Model (`app/models/User.php`)
New methods added:
- `submitLicense($userId, $licenseImagePath)` - Submits a license for verification
- `getPendingLicenses()` - Gets all licenses pending verification
- `verifyLicense($userId, $adminId)` - Marks a license as verified
- `rejectLicense($userId, $adminId, $reason)` - Rejects a license with reason
- `getLicenseStats()` - Returns statistics about license verification status

#### UsersController (`app/controllers/UsersController.php`)
New endpoints:
- `POST /api/users/{id}/license/upload` - Upload driver's license image
- `GET /api/users/{id}/license/status` - Get license verification status

Features:
- File upload validation (type, size)
- Uses LavaLust Upload library
- Stores images in `public/images/licenses/`
- Max file size: 5MB
- Allowed formats: JPG, PNG, GIF

#### AdminController (`app/controllers/AdminController.php`)
New endpoints:
- `GET /api/admin/licenses/pending` - Get all pending license verifications
- `GET /api/admin/licenses/stats` - Get license verification statistics
- `POST /api/admin/licenses/{userId}/verify` - Verify a user's license
- `POST /api/admin/licenses/{userId}/reject` - Reject a user's license

Features:
- Admin dashboard now shows pending license count in alerts
- Tracks which admin verified/rejected each license

### 3. Frontend (Vue.js)

#### User Dashboard
**Component:** `frontend/src/components/LicenseVerification.vue`

Features:
- Drag-and-drop file upload
- Image preview before submission
- Real-time status display
- Four status states:
  - **Not Submitted** - Upload interface
  - **Pending** - Shows submitted license, awaiting review
  - **Verified** - Success state with verification date
  - **Rejected** - Shows rejection reason with re-upload option

Integrated into: `frontend/src/views/UserDashboard.vue`

#### Admin Dashboard
**View:** `frontend/src/views/LicenseManagement.vue`

Features:
- Statistics cards showing:
  - Pending reviews
  - Verified licenses
  - Rejected licenses
  - Not submitted count
- Grid view of pending licenses
- License image viewer with full-size modal
- Quick verify/reject actions
- Rejection reason input
- Real-time updates after actions

#### API Store Updates
**File:** `frontend/src/stores/api.js`

Added:
- `upload(endpoint, formData)` method for multipart/form-data uploads

## Installation Steps

### 1. Run Database Migration

```sql
-- Execute the migration file
mysql -u your_username -p vehicle_rental < scheme/database/migrations/add_license_verification.sql

-- OR run the SQL manually:
ALTER TABLE `users` 
ADD COLUMN `license_image` varchar(255) DEFAULT NULL,
ADD COLUMN `license_status` enum('not_submitted','pending','verified','rejected') DEFAULT 'not_submitted',
ADD COLUMN `license_submitted_at` datetime DEFAULT NULL,
ADD COLUMN `license_verified_at` datetime DEFAULT NULL,
ADD COLUMN `license_verified_by` int(11) DEFAULT NULL,
ADD COLUMN `license_rejection_reason` text DEFAULT NULL;

ALTER TABLE `users`
ADD CONSTRAINT `fk_license_verified_by` 
FOREIGN KEY (`license_verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `users`
ADD INDEX `idx_license_status` (`license_status`);
```

### 2. Update Routes (if needed)

The routes should already be handled by LavaLust's routing system. Verify in `app/config/routes.php`:

```php
// User routes
$router->post('/api/users/:id/license/upload', 'UsersController@uploadLicense');
$router->get('/api/users/:id/license/status', 'UsersController@getLicenseStatus');

// Admin routes
$router->get('/api/admin/licenses/pending', 'AdminController@pendingLicenses');
$router->get('/api/admin/licenses/stats', 'AdminController@licenseStats');
$router->post('/api/admin/licenses/:userId/verify', 'AdminController@verifyLicense');
$router->post('/api/admin/licenses/:userId/reject', 'AdminController@rejectLicense');
```

### 3. Set Directory Permissions

Ensure the upload directory has proper write permissions:

```bash
chmod 755 public/images/licenses
```

For Windows (XAMPP):
- Right-click on `public/images/licenses` folder
- Properties → Security tab
- Ensure IUSR and IIS_IUSRS have Write permissions

### 4. Frontend Router Setup

Add the License Management route to your Vue router (`frontend/src/router/index.js`):

```javascript
{
  path: '/admin/licenses',
  name: 'license-management',
  component: () => import('@/views/LicenseManagement.vue'),
  meta: { requiresAuth: true, requiresAdmin: true }
}
```

### 5. Update Admin Navigation

Add License Management to your admin sidebar/navigation:

```vue
<router-link to="/admin/licenses">
  <i class="fas fa-id-card"></i>
  License Verification
</router-link>
```

## API Endpoints Reference

### User Endpoints

#### Upload License
```http
POST /api/users/{id}/license/upload
Content-Type: multipart/form-data

Form Data:
- license_image: (file)

Response:
{
  "message": "License uploaded successfully and is pending verification",
  "user": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "license_status": "pending",
    "license_image": "public/images/licenses/license_1_1234567890.jpg",
    ...
  }
}
```

#### Get License Status
```http
GET /api/users/{id}/license/status

Response:
{
  "license_status": "pending",
  "license_image": "public/images/licenses/license_1_1234567890.jpg",
  "license_submitted_at": "2025-11-18 14:30:00",
  "license_verified_at": null,
  "license_rejection_reason": null
}
```

### Admin Endpoints

#### Get Pending Licenses
```http
GET /api/admin/licenses/pending

Response:
{
  "licenses": [
    {
      "id": 2,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "123456789",
      "license_image": "public/images/licenses/license_2_1234567890.jpg",
      "license_submitted_at": "2025-11-18 14:30:00",
      "license_status": "pending"
    }
  ],
  "total": 1
}
```

#### Get License Statistics
```http
GET /api/admin/licenses/stats

Response:
{
  "not_submitted": 50,
  "pending": 5,
  "verified": 30,
  "rejected": 2
}
```

#### Verify License
```http
POST /api/admin/licenses/{userId}/verify
Content-Type: application/json

{
  "admin_id": 1
}

Response:
{
  "message": "License verified successfully",
  "user": {
    "id": 2,
    "license_status": "verified",
    "license_verified_at": "2025-11-18 15:00:00",
    "license_verified_by": 1,
    ...
  }
}
```

#### Reject License
```http
POST /api/admin/licenses/{userId}/reject
Content-Type: application/json

{
  "admin_id": 1,
  "reason": "Image is too blurry, please upload a clearer photo"
}

Response:
{
  "message": "License rejected",
  "user": {
    "id": 2,
    "license_status": "rejected",
    "license_verified_at": "2025-11-18 15:00:00",
    "license_verified_by": 1,
    "license_rejection_reason": "Image is too blurry, please upload a clearer photo",
    ...
  }
}
```

## File Structure

```
Vehicle-Rental/
├── app/
│   ├── controllers/
│   │   ├── AdminController.php (updated)
│   │   └── UsersController.php (updated)
│   └── models/
│       └── User.php (updated)
├── frontend/
│   └── src/
│       ├── components/
│       │   └── LicenseVerification.vue (new)
│       ├── stores/
│       │   └── api.js (updated)
│       └── views/
│           ├── LicenseManagement.vue (new)
│           └── UserDashboard.vue (updated)
├── public/
│   └── images/
│       └── licenses/ (new directory)
└── scheme/
    └── database/
        ├── complete_schema.sql (updated)
        └── migrations/
            └── add_license_verification.sql (new)
```

## Security Considerations

1. **File Upload Validation**
   - File type validation (MIME type check)
   - File size limit (5MB)
   - File extension whitelist

2. **Authorization**
   - Users can only upload their own license
   - Only admins can verify/reject licenses
   - Implement proper authentication checks in routes

3. **File Storage**
   - Files stored outside of web root when possible
   - Use random/hashed filenames to prevent enumeration
   - Implement file access controls

4. **Data Privacy**
   - License images contain sensitive information
   - Implement proper access controls
   - Consider encryption at rest for stored images

## Testing Checklist

### User Flow
- [ ] User can see license verification section in dashboard
- [ ] User can upload a license image (drag-drop)
- [ ] User can upload a license image (click to browse)
- [ ] Invalid file types are rejected
- [ ] Files over 5MB are rejected
- [ ] Status updates to "pending" after upload
- [ ] Rejected licenses show rejection reason
- [ ] User can re-upload after rejection

### Admin Flow
- [ ] Admin sees pending count in dashboard alerts
- [ ] Admin can view list of pending licenses
- [ ] Admin can view license images in modal
- [ ] Admin can verify a license
- [ ] Admin can reject a license with reason
- [ ] Statistics update after verification/rejection
- [ ] Pending list updates after actions

### API
- [ ] Upload endpoint validates file types
- [ ] Upload endpoint validates file size
- [ ] Status endpoint returns correct data
- [ ] Pending endpoint returns only pending licenses
- [ ] Stats endpoint returns accurate counts
- [ ] Verify endpoint updates status correctly
- [ ] Reject endpoint requires reason

## Troubleshooting

### Upload fails with "File too large"
- Check `php.ini` settings:
  - `upload_max_filesize = 10M`
  - `post_max_size = 10M`
- Restart Apache/PHP-FPM after changes

### Images not displaying
- Check file permissions on `public/images/licenses/`
- Verify path in database matches actual file location
- Check browser console for 404 errors

### Upload directory not writable
```bash
# Linux/Mac
chmod 755 public/images/licenses

# Windows (PowerShell as Admin)
icacls "public\images\licenses" /grant Users:(OI)(CI)M
```

## Future Enhancements

1. **OCR Integration**
   - Automatically extract license number and details
   - Validate license expiration date

2. **Notification System**
   - Email users when license is verified/rejected
   - Notify admins of new submissions

3. **Audit Trail**
   - Log all verification actions
   - Track verification history

4. **Bulk Actions**
   - Allow admins to verify/reject multiple licenses at once

5. **Document Management**
   - Support multiple document types (ID, proof of address)
   - Version control for re-submitted documents

## Support

For issues or questions:
1. Check LavaLust documentation: https://lavalust.netlify.app
2. Review error logs in `runtime/logs/`
3. Check browser console for frontend errors

---

**Implementation Date:** November 18, 2025
**LavaLust Version:** 4.4.0
**Framework:** LavaLust (Backend) + Vue.js 3 (Frontend)

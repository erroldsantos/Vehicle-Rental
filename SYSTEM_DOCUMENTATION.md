# Vehicle Rental System - Complete Documentation

**Version:** 1.0.0  
**Last Updated:** November 30, 2025  
**Framework:** LavaLust 4.4.0 (PHP) + Vue.js 3

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Technology Stack](#technology-stack)
3. [System Architecture](#system-architecture)
4. [Installation Guide](#installation-guide)
5. [Database Schema](#database-schema)
6. [Backend API Documentation](#backend-api-documentation)
7. [Frontend Architecture](#frontend-architecture)
8. [User Roles & Permissions](#user-roles--permissions)
9. [Business Logic & Workflows](#business-logic--workflows)
10. [Payment Integration](#payment-integration)
11. [Security Features](#security-features)
12. [Deployment Guide](#deployment-guide)
13. [Maintenance & Troubleshooting](#maintenance--troubleshooting)

---

## System Overview

The Vehicle Rental System is a comprehensive web application designed to manage vehicle rentals, bookings, maintenance, and payments. It provides separate interfaces for administrators and customers with role-based access control.

### Key Features

#### Admin Features
- **Dashboard Analytics** - Real-time statistics and charts
- **User Management** - CRUD operations for user accounts
- **Vehicle Management** - Manage vehicle inventory with images
- **Booking Management** - View, confirm, and track bookings
- **Maintenance Tracking** - Schedule and track vehicle maintenance
- **Payment Processing** - Handle payments and view financial reports
- **License Verification** - Verify customer driver's licenses
- **Reports & Export** - Generate CSV reports

#### Customer Features
- **Vehicle Browsing** - Search and filter available vehicles
- **Online Booking** - Make reservations with date selection
- **Payment Options** - Full payment or 30% downpayment
- **Booking History** - Track current and past bookings
- **License Upload** - Submit driver's license for verification
- **Profile Management** - Update personal information

### System Capabilities
- ✅ Multi-user support with role-based access
- ✅ Real-time booking availability checking
- ✅ Automated booking status transitions
- ✅ Integrated payment processing (PayMongo)
- ✅ Email verification system
- ✅ Soft delete for data integrity
- ✅ Responsive design (mobile-friendly)
- ✅ RESTful API architecture

---

## Technology Stack

### Backend
- **Framework:** LavaLust 4.4.0 (PHP MVC Framework)
- **Language:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB
- **Architecture:** RESTful API, MVC Pattern
- **ORM:** LavaLust Active Record
- **Authentication:** Custom token-based (Lauth library)
- **Email:** PHPMailer

### Frontend
- **Framework:** Vue.js 3.3.8 (Composition API)
- **State Management:** Pinia 2.1.7
- **Routing:** Vue Router 4.2.5
- **HTTP Client:** Axios 1.6.0
- **Charts:** Chart.js 4.4.0
- **Maps:** Leaflet 1.9.4
- **Build Tool:** Vite 4.5.0
- **UI:** Custom CSS with Flexbox/Grid

### Third-Party Services
- **Payment Gateway:** PayMongo (GCash, GrabPay, PayMaya, Cards)
- **Email Service:** SMTP (configurable)

### Development Tools
- **Version Control:** Git
- **Package Managers:** Composer (PHP), NPM (Node.js)
- **Development Server:** Vite Dev Server, PHP Built-in Server
- **Code Structure:** Modular, Component-based

---

## System Architecture

### Architecture Pattern
```
┌─────────────────────────────────────────────────────┐
│                   Client Layer                      │
│            (Vue.js SPA - Port 5173)                │
└──────────────────┬──────────────────────────────────┘
                   │ HTTP/REST API
┌──────────────────▼──────────────────────────────────┐
│              API Layer (Controllers)                 │
│        (/api/* routes - LavaLust Router)            │
└──────────────────┬──────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────┐
│            Business Logic Layer (Models)             │
│    (ORM, Validation, Business Rules)                │
└──────────────────┬──────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────┐
│              Data Layer (MySQL)                      │
│    (vehicle_rental database)                        │
└─────────────────────────────────────────────────────┘
```

### Directory Structure

#### Backend Structure
```
Vehicle-Rental/
├── app/
│   ├── config/              # Configuration files
│   │   ├── api.php         # API settings
│   │   ├── database.php    # Database configuration
│   │   ├── routes.php      # Route definitions
│   │   └── paymongo.php    # Payment gateway config
│   ├── controllers/         # API Controllers
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── BookingsController.php
│   │   ├── MaintenanceController.php
│   │   ├── PaymentController.php
│   │   ├── UsersController.php
│   │   └── VehiclesController.php
│   ├── models/              # Data Models (ORM)
│   │   ├── Booking.php
│   │   ├── Maintenance.php
│   │   ├── Payment.php
│   │   ├── User.php
│   │   └── Vehicle.php
│   ├── libraries/           # Custom Libraries
│   │   ├── Lauth.php       # Authentication
│   │   └── Paymongo.php    # Payment integration
│   └── helpers/             # Helper functions
├── scheme/
│   └── database/            # Database migrations & schema
├── public/
│   └── images/              # Uploaded files
│       ├── vehicles/        # Vehicle images
│       └── licenses/        # License documents
└── runtime/                 # Logs and sessions
```

#### Frontend Structure
```
frontend/
├── src/
│   ├── components/          # Reusable Vue components
│   │   ├── Header.vue
│   │   ├── Sidebar.vue
│   │   ├── AdminLayout.vue
│   │   ├── UserLayout.vue
│   │   └── LicenseVerification.vue
│   ├── views/               # Page components
│   │   ├── Dashboard.vue
│   │   ├── UserDashboard.vue
│   │   ├── Login.vue
│   │   ├── Signup.vue
│   │   ├── UserManagement.vue
│   │   ├── VehicleManagement.vue
│   │   ├── BookingManagement.vue
│   │   ├── MaintenanceManagement.vue
│   │   ├── PaymentManagement.vue
│   │   ├── LicenseManagement.vue
│   │   ├── BrowseVehicles.vue
│   │   └── MyBookings.vue
│   ├── router/              # Route configuration
│   │   └── index.js
│   ├── stores/              # Pinia stores
│   │   ├── api.js          # API base configuration
│   │   └── auth.js         # Authentication state
│   ├── composables/         # Reusable composition functions
│   ├── App.vue              # Root component
│   ├── main.js              # Application entry point
│   └── style.css            # Global styles
├── public/                  # Static assets
│   └── images/
├── package.json
└── vite.config.js
```

---

## Installation Guide

### Prerequisites
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher / MariaDB 10.3+
- **Node.js:** 16.0 or higher
- **NPM:** 8.0 or higher
- **Composer:** 2.0 or higher
- **Web Server:** Apache/Nginx (with mod_rewrite)

### Step 1: Clone Repository
```bash
git clone https://github.com/erroldsantos/Vehicle-Rental.git
cd Vehicle-Rental
```

### Step 2: Backend Setup

1. **Install PHP Dependencies**
```bash
composer install
```

2. **Configure Database**
   - Create database: `vehicle_rental`
   - Import schema: `scheme/database/complete_schema.sql`
   - Update `app/config/database.php`:
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

3. **Configure Environment**
   - Copy `.env.example` to `.env`
   - Set `BASE_URL` and other environment variables

4. **Set File Permissions**
```bash
chmod 755 -R public/images
chmod 755 -R runtime
```

### Step 3: Frontend Setup

1. **Navigate to Frontend**
```bash
cd frontend
```

2. **Install Dependencies**
```bash
npm install
```

3. **Configure API Endpoint**
   - Update `src/stores/api.js` with backend URL

4. **Run Development Server**
```bash
npm run dev
```

The frontend will run on `http://localhost:5173`

### Step 4: Payment Gateway Setup (Optional)

1. **Get PayMongo Credentials**
   - Sign up at https://paymongo.com
   - Get API keys from dashboard

2. **Configure in `app/config/paymongo.php`**
```php
return [
    'secret_key' => 'sk_test_your_secret_key',
    'public_key' => 'pk_test_your_public_key',
    'webhook_secret' => 'whsec_your_webhook_secret'
];
```

### Step 5: Access the System

- **Frontend:** http://localhost:5173
- **Backend API:** http://localhost/Vehicle-Rental/api
- **Default Admin:** 
  - Email: admin@vehiclerental.com
  - Password: admin123

---

## Database Schema

### Entity Relationship Diagram
```
┌──────────┐         ┌───────────┐         ┌──────────┐
│  Users   │────────<│  Bookings │>────────│ Vehicles │
└────┬─────┘         └─────┬─────┘         └────┬─────┘
     │                     │                     │
     │                     │                     │
     │              ┌──────▼──────┐         ┌───▼──────┐
     │              │  Payments   │         │Maintenance│
     │              └─────────────┘         └───────────┘
     │                                            ▲
     └────────────────────────────────────────────┘
              (license_verified_by)
```

### Tables Overview

#### 1. **users** - User Accounts
| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Primary key |
| first_name | VARCHAR(100) | User's first name |
| last_name | VARCHAR(100) | User's last name |
| email | VARCHAR(255) UNIQUE | Email address |
| phone | VARCHAR(20) | Phone number |
| password | VARCHAR(255) | Bcrypt hashed password |
| role | ENUM('admin','user') | User role |
| status | ENUM('active','inactive','suspended') | Account status |
| email_verified | TINYINT(1) | Email verification flag |
| verification_token | VARCHAR(255) | Email verification token |
| verification_token_expires | DATETIME | Token expiry |
| license_image | VARCHAR(255) | Driver's license image path |
| license_status | ENUM | License verification status |
| license_submitted_at | DATETIME | License submission time |
| license_verified_at | DATETIME | Verification time |
| license_verified_by | INT(11) FK | Admin who verified |
| license_rejection_reason | TEXT | Rejection reason |
| deleted_at | DATETIME | Soft delete timestamp |

**Indexes:**
- UNIQUE: email
- INDEX: verification_token, license_status
- FK: license_verified_by → users(id)

#### 2. **vehicles** - Vehicle Inventory
| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Primary key |
| brand | VARCHAR(100) | Vehicle brand |
| model | VARCHAR(100) | Vehicle model |
| year | INT(11) | Manufacturing year |
| plate_number | VARCHAR(50) UNIQUE | License plate |
| image | VARCHAR(255) | Vehicle image path |
| daily_rate | DECIMAL(10,2) | Rental rate per day |
| status | ENUM('available','rented','maintenance') | Vehicle status |
| deleted_at | DATETIME | Soft delete timestamp |

**Indexes:**
- UNIQUE: plate_number

#### 3. **bookings** - Rental Bookings
| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Primary key |
| booking_reference | VARCHAR(50) UNIQUE | Booking reference code |
| user_id | INT(11) FK | Customer user ID |
| vehicle_id | INT(11) FK | Rented vehicle ID |
| start_date | DATE | Rental start date |
| end_date | DATE | Rental end date |
| total_amount | DECIMAL(10,2) | Total rental cost |
| notes | TEXT | Additional notes |
| pickup_location | VARCHAR(255) | Pickup location |
| dropoff_location | VARCHAR(255) | Return location |
| status | ENUM | Booking status |
| deleted_at | DATETIME | Soft delete timestamp |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

**Status Flow:** pending → confirmed → active → ongoing → returned → completed

**Indexes:**
- UNIQUE: booking_reference
- INDEX: user_id, vehicle_id
- FK: user_id → users(id), vehicle_id → vehicles(id)

#### 4. **maintenance** - Maintenance Records
| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Primary key |
| vehicle_id | INT(11) FK | Vehicle ID |
| booking_id | INT(11) FK | Related booking (if damage) |
| description | TEXT | Maintenance description |
| damage_type | VARCHAR(100) | Type of damage |
| scheduled_date | DATE | Scheduled maintenance date |
| cost | DECIMAL(10,2) | Maintenance cost |
| status | ENUM('scheduled','pending','completed') | Maintenance status |
| payment_status | ENUM('pending','paid') | Payment status |
| deleted_at | DATETIME | Soft delete timestamp |

**Indexes:**
- INDEX: vehicle_id, booking_id
- FK: vehicle_id → vehicles(id), booking_id → bookings(id)

#### 5. **payments** - Payment Records
| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Primary key |
| booking_id | INT(11) FK | Related booking |
| amount | DECIMAL(10,2) | Payment amount |
| payment_date | DATE | Payment date |
| payment_method | VARCHAR(50) | Payment method |
| payment_type | ENUM('full','downpayment') | Payment type |
| status | ENUM('pending','completed') | Payment status |
| deleted_at | DATETIME | Soft delete timestamp |

**Indexes:**
- INDEX: booking_id
- FK: booking_id → bookings(id)

---

## Backend API Documentation

### Base URL
```
http://localhost/Vehicle-Rental/api
```

### Authentication
Most endpoints require authentication via Bearer token in headers:
```
Authorization: Bearer {token}
```

### API Endpoints

#### Authentication Endpoints

**POST /api/auth/register**
- Register new customer account
- **Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "09123456789",
  "password": "password123"
}
```
- **Response:** User object + verification email sent

**POST /api/auth/login**
- User login
- **Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```
- **Response:** `{ token, user }`

**GET /api/auth/verify-email?token={token}**
- Verify email address
- **Response:** Success/error message

**POST /api/auth/resend-verification**
- Resend verification email
- **Body:** `{ "email": "john@example.com" }`

**GET /api/auth/me**
- Get current authenticated user
- **Headers:** Authorization Bearer token

**POST /api/auth/logout**
- Logout current session

#### User Management (Admin Only)

**GET /api/users**
- Get all users
- **Query Params:** `?status=active&role=user&search=john`

**GET /api/users/{id}**
- Get user by ID

**POST /api/users**
- Create new user (admin)
- **Body:** User data

**PUT /api/users/{id}**
- Update user
- **Body:** Updated fields

**DELETE /api/users/{id}**
- Soft delete user

#### Vehicle Management

**GET /api/vehicles**
- Get all vehicles
- **Query Params:** `?status=available&search=toyota&start_date=2025-12-01&end_date=2025-12-10`

**GET /api/vehicles/{id}**
- Get vehicle details

**GET /api/vehicles/{id}/booked-dates**
- Get booked dates for a vehicle

**POST /api/vehicles** (Admin)
- Create new vehicle
- **Body:** FormData with vehicle details + image

**PUT /api/vehicles/{id}** (Admin)
- Update vehicle
- **Body:** Updated fields + optional image

**DELETE /api/vehicles/{id}** (Admin)
- Soft delete vehicle

#### Booking Management

**GET /api/bookings**
- Get bookings (filtered by role)
- **Query Params:** `?status=confirmed&user_id=1&vehicle_id=2&search=BK-2025`

**GET /api/bookings/{id}**
- Get booking details

**GET /api/bookings/available-vehicles**
- Get available vehicles for dates
- **Query Params:** `?start_date=2025-12-01&end_date=2025-12-10`

**POST /api/bookings**
- Create new booking
- **Body:**
```json
{
  "user_id": 1,
  "vehicle_id": 2,
  "start_date": "2025-12-01",
  "end_date": "2025-12-10",
  "pickup_location": "Manila",
  "dropoff_location": "Manila",
  "notes": "Optional notes"
}
```

**PUT /api/bookings/{id}**
- Update booking
- **Body:** Updated fields

**PUT /api/bookings/{id}/cancel**
- Cancel booking

**DELETE /api/bookings/{id}**
- Soft delete booking

#### Maintenance Management

**GET /api/maintenance**
- Get all maintenance records

**GET /api/maintenance/{id}**
- Get maintenance details

**GET /api/maintenance/booking/{id}**
- Get maintenance by booking ID

**POST /api/maintenance**
- Create scheduled maintenance
- **Body:**
```json
{
  "vehicle_id": 1,
  "description": "Oil change",
  "scheduled_date": "2025-12-15",
  "cost": 500.00
}
```

**POST /api/maintenance/inspect/{booking_id}**
- Record vehicle inspection with damage
- **Body:**
```json
{
  "has_damage": true,
  "damage_description": "Scratched bumper",
  "damage_type": "Minor",
  "damage_cost": 2000.00
}
```

**PUT /api/maintenance/{id}**
- Update maintenance record

**PUT /api/maintenance/{id}/complete**
- Mark maintenance as completed
- **Body:** `{ "cost": 500.00 }` (optional)

**POST /api/maintenance/sync**
- Sync vehicle statuses with maintenance

**DELETE /api/maintenance/{id}**
- Soft delete maintenance

#### Payment Management

**GET /api/payments**
- Get all payments
- **Query Params:** `?status=completed&booking_id=1`

**GET /api/payments/{id}**
- Get payment details

**GET /api/payments/stats**
- Get payment statistics

**GET /api/payments/needs-payment**
- Get bookings needing payment (downpayment balance or damage)

**POST /api/payments**
- Create manual payment record
- **Body:**
```json
{
  "booking_id": 1,
  "amount": 1500.00,
  "payment_method": "cash",
  "payment_type": "downpayment",
  "payment_date": "2025-11-30"
}
```

**POST /api/payments/booking**
- Create payment with PayMongo integration
- **Body:**
```json
{
  "booking_id": 1,
  "amount": 1500.00,
  "payment_method": "gcash",
  "success_url": "http://localhost:5173/my-bookings",
  "failed_url": "http://localhost:5173/my-bookings"
}
```

**PUT /api/payments/{id}**
- Update payment status
- **Body:** `{ "status": "completed" }`

**DELETE /api/payments/{id}**
- Soft delete payment

**POST /api/webhook/paymongo**
- PayMongo webhook handler (for payment confirmations)

#### License Verification

**POST /api/users/{id}/license/upload** (Customer)
- Upload driver's license
- **Body:** FormData with `license_image` file

**GET /api/users/{id}/license/status** (Customer)
- Get license verification status

**GET /api/admin/licenses/pending** (Admin)
- Get pending license verifications

**GET /api/admin/licenses/verified** (Admin)
- Get verified licenses

**GET /api/admin/licenses/stats** (Admin)
- Get license verification statistics

**POST /api/admin/licenses/{userId}/verify** (Admin)
- Verify user's license
- **Body:** `{ "admin_id": 1 }`

**POST /api/admin/licenses/{userId}/reject** (Admin)
- Reject user's license
- **Body:** `{ "admin_id": 1, "reason": "Invalid document" }`

#### Admin Dashboard

**GET /api/admin/stats**
- Get dashboard statistics

**GET /api/admin/overview**
- Get dashboard overview data

---

## Frontend Architecture

### State Management (Pinia)

#### API Store (`stores/api.js`)
- Base API configuration
- HTTP client setup (Axios)
- Request/response interceptors
- Error handling

```javascript
const apiStore = useApiStore()
await apiStore.get('/vehicles')
await apiStore.post('/bookings', data)
await apiStore.put('/users/1', data)
await apiStore.delete('/vehicles/1')
```

#### Auth Store (`stores/auth.js`)
- User authentication state
- Login/logout methods
- Token management
- User session persistence

### Router Configuration

#### Public Routes
- `/` - Landing page
- `/login` - Login page
- `/signup` - Registration page
- `/verify-email` - Email verification

#### Admin Routes (Protected)
- `/dashboard` - Admin dashboard
- `/users` - User management
- `/vehicles` - Vehicle management
- `/bookings` - Booking management
- `/maintenance` - Maintenance management
- `/payments` - Payment management
- `/licenses` - License verification

#### Customer Routes (Protected)
- `/user-dashboard` - Customer dashboard
- `/browse-vehicles` - Browse available vehicles
- `/my-bookings` - View bookings

### Component Architecture

#### Layout Components
- **AdminLayout** - Admin interface wrapper
- **UserLayout** - Customer interface wrapper
- **Header** - Top navigation bar
- **Sidebar** - Side navigation menu

#### Feature Components
- **LicenseVerification** - License upload component
- **VehicleCard** - Vehicle display card
- **BookingCard** - Booking display card
- **Chart Components** - Dashboard charts

### Styling Approach
- Custom CSS (no framework dependency)
- CSS Grid & Flexbox layouts
- CSS Variables for theming
- Responsive design (mobile-first)
- Modular, scoped styles per component

---

## User Roles & Permissions

### Admin Role
**Capabilities:**
- ✅ Full system access
- ✅ User CRUD operations
- ✅ Vehicle CRUD operations
- ✅ Booking management (confirm, cancel, update)
- ✅ Maintenance scheduling
- ✅ Payment processing and verification
- ✅ License verification
- ✅ View all reports and analytics
- ✅ Export data

**Restrictions:**
- ❌ Cannot delete own admin account
- ❌ Cannot modify system-critical data

### User (Customer) Role
**Capabilities:**
- ✅ Browse available vehicles
- ✅ Create bookings
- ✅ View own bookings
- ✅ Make payments
- ✅ Upload driver's license
- ✅ Update own profile

**Restrictions:**
- ❌ Cannot access admin dashboard
- ❌ Cannot view other users' data
- ❌ Cannot modify vehicle information
- ❌ Cannot approve/reject bookings
- ❌ Limited to 2 confirmed bookings at a time
- ❌ Must have verified license to book

---

## Business Logic & Workflows

### Booking Workflow

#### 1. Booking Creation Flow
```
Customer browses vehicles
    ↓
Selects dates (start_date, end_date)
    ↓
System checks availability
    ↓
System validates:
  - License verified? ✓
  - Max bookings (2) not exceeded? ✓
  - Vehicle available for dates? ✓
    ↓
Booking created (status: pending)
    ↓
Booking reference generated (BK-YYYY-####)
```

#### 2. Booking Status Lifecycle
```
pending → confirmed → active → ongoing → returned → completed
                ↓
            cancelled (terminal state)
```

**Status Definitions:**
- **pending**: Booking created, awaiting admin confirmation
- **confirmed**: Admin confirmed, awaiting start date
- **active**: Start date reached, ready for pickup
- **ongoing**: Vehicle picked up, rental in progress
- **returned**: Vehicle returned, awaiting inspection
- **completed**: Inspection done, no issues or payment completed
- **cancelled**: Booking cancelled

**Automated Transitions:**
- `confirmed → active`: Triggered when `start_date <= TODAY`
- Status updates run when bookings are fetched

#### 3. Payment Flow

**Downpayment Option:**
```
1. Customer books vehicle
2. Chooses "Pay 30% Now"
3. Pays downpayment (30% of total)
4. Booking status: confirmed
5. System auto-creates pending payment for 70% balance
6. Customer pays balance before or during rental
```

**Full Payment Option:**
```
1. Customer books vehicle
2. Chooses "Pay Full Amount"
3. Pays 100% of total
4. Booking status: confirmed
5. No additional payment required
```

#### 4. Vehicle Return & Inspection

**No Damage Scenario:**
```
Admin clicks "Mark as Returned"
    ↓
Admin inspects vehicle
    ↓
"No Damage" selected
    ↓
Booking status: completed
Vehicle status: available
```

**Damage Found Scenario:**
```
Admin clicks "Mark as Returned"
    ↓
Admin inspects vehicle
    ↓
"Has Damage" selected + Cost entered
    ↓
System creates:
  - Maintenance record (status: pending, payment_status: pending)
  - Pending payment for damage cost
    ↓
Vehicle status: maintenance
Booking status: returned (not completed)
    ↓
Customer pays damage cost
    ↓
Admin marks maintenance as complete
    ↓
Maintenance status: completed
Vehicle status: available
Booking status: completed
```

### Maintenance Workflow

#### Scheduled Maintenance
```
1. Admin creates maintenance record
2. Sets scheduled_date and description
3. Vehicle status: maintenance (if date is today/past)
4. Admin performs maintenance
5. Admin marks as complete with cost
6. Vehicle status: available
```

#### Damage-Based Maintenance
```
1. Created automatically during inspection
2. Status: pending (not scheduled)
3. Payment_status: pending
4. Customer must pay before completion
5. Admin completes maintenance after payment
```

### License Verification Workflow

```
Customer registers
    ↓
License status: not_submitted
    ↓
Customer uploads driver's license
    ↓
License status: pending
    ↓
Admin reviews license
    ↓
Admin approves → License status: verified (can book)
    OR
Admin rejects → License status: rejected (cannot book)
    ↓
If rejected, customer can re-upload
```

### Email Verification Workflow

```
User registers
    ↓
System sends verification email with token
    ↓
Token expires in 24 hours
    ↓
User clicks link in email
    ↓
Email_verified = 1
    ↓
User can now login
```

---

## Payment Integration

### PayMongo Integration

#### Supported Payment Methods
- **GCash** - E-wallet
- **GrabPay** - E-wallet
- **PayMaya** - E-wallet
- **Credit/Debit Cards** - Visa, Mastercard

#### Payment Flow
```
1. Customer initiates payment
2. Frontend calls POST /api/payments/booking
3. Backend creates PayMongo Payment Intent
4. Backend returns checkout URL
5. Customer redirected to PayMongo checkout
6. Customer completes payment
7. PayMongo sends webhook to backend
8. Backend updates payment status
9. Customer redirected to success/failed URL
```

#### Webhook Handling
- Endpoint: `POST /api/webhook/paymongo`
- Verifies webhook signature
- Updates payment status
- Creates balance payment if downpayment
- Updates booking status

#### Configuration
Located in `app/config/paymongo.php`:
```php
return [
    'secret_key' => 'sk_test_...',    // API Secret Key
    'public_key' => 'pk_test_...',    // API Public Key
    'webhook_secret' => 'whsec_...'   // Webhook Secret
];
```

---

## Security Features

### Authentication & Authorization
- ✅ Bcrypt password hashing (cost: 10)
- ✅ Token-based authentication (Lauth)
- ✅ Role-based access control (RBAC)
- ✅ Session management
- ✅ Token expiration

### Data Protection
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (input sanitization)
- ✅ CSRF protection
- ✅ Password strength validation
- ✅ Email verification required
- ✅ Soft deletes (data retention)

### API Security
- ✅ CORS configuration
- ✅ Rate limiting (recommended)
- ✅ Input validation
- ✅ Error message sanitization
- ✅ HTTPS enforcement (production)

### File Upload Security
- ✅ File type validation (images only)
- ✅ File size limits (5MB)
- ✅ Unique filename generation
- ✅ Secure storage location
- ✅ MIME type checking

### Best Practices Implemented
- Password minimum 6 characters
- Email format validation
- Phone number format validation
- Numeric validation for amounts
- Date range validation
- Business rule validation (max 2 bookings, license required)

---

## Deployment Guide

### Production Deployment Checklist

#### Backend Deployment

1. **Server Requirements**
   - PHP 7.4+ with required extensions (mysqli, pdo, mbstring, json)
   - MySQL 5.7+ or MariaDB 10.3+
   - Apache with mod_rewrite OR Nginx
   - SSL certificate (recommended)

2. **Database Setup**
   ```sql
   CREATE DATABASE vehicle_rental;
   USE vehicle_rental;
   SOURCE scheme/database/complete_schema.sql;
   ```

3. **Environment Configuration**
   - Update `app/config/database.php` with production credentials
   - Set `BASE_URL` in `.env`
   - Configure email SMTP settings
   - Add PayMongo production keys

4. **File Permissions**
   ```bash
   chmod 755 public/images
   chmod 755 runtime
   chmod 644 app/config/*.php
   ```

5. **Apache Configuration**
   - Enable mod_rewrite
   - Use provided `.htaccess` file
   - Set document root to project root

6. **Security Hardening**
   - Change default admin password
   - Use HTTPS only
   - Restrict database user permissions
   - Configure firewall rules
   - Regular security updates

#### Frontend Deployment

1. **Build for Production**
   ```bash
   cd frontend
   npm run build
   ```

2. **Deploy Build Files**
   - Upload `frontend/dist/*` to web server
   - Configure web server to serve SPA

3. **Update API Configuration**
   - Point `src/stores/api.js` to production API URL

4. **Web Server Configuration**
   
   **Nginx Example:**
   ```nginx
   server {
       listen 80;
       server_name yourdomain.com;
       root /var/www/vehicle-rental/frontend/dist;
       index index.html;
       
       location / {
           try_files $uri $uri/ /index.html;
       }
       
       location /api {
           proxy_pass http://localhost:8000;
       }
   }
   ```

   **Apache Example:**
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot /var/www/vehicle-rental/frontend/dist
       
       <Directory /var/www/vehicle-rental/frontend/dist>
           AllowOverride All
           Require all granted
           FallbackResource /index.html
       </Directory>
   </VirtualHost>
   ```

### Performance Optimization

1. **Database**
   - Index frequently queried columns
   - Optimize queries
   - Enable query caching

2. **Frontend**
   - Enable gzip compression
   - Browser caching headers
   - CDN for static assets
   - Code splitting

3. **Backend**
   - Enable OPCache
   - Session optimization
   - API response caching

---

## Maintenance & Troubleshooting

### Common Issues

#### Issue: Cannot login
**Solutions:**
- Check if email is verified
- Verify credentials in database
- Check browser console for errors
- Verify API endpoint is accessible

#### Issue: Images not uploading
**Solutions:**
- Check `public/images` permissions (755)
- Verify PHP `upload_max_filesize` and `post_max_size`
- Check disk space
- Verify file type is allowed

#### Issue: Payments not processing
**Solutions:**
- Verify PayMongo credentials
- Check webhook configuration
- Review PayMongo dashboard for errors
- Test in sandbox mode first

#### Issue: Booking status not updating
**Solutions:**
- Check if cron job for status updates is running
- Manually call booking status update
- Verify date comparison logic

### Logs & Debugging

**Backend Logs:**
- Location: `runtime/logs/log.txt`
- PHP errors in Apache/Nginx logs

**Frontend Debugging:**
- Browser console (F12)
- Network tab for API calls
- Vue DevTools extension

### Database Maintenance

**Backup Database:**
```bash
mysqldump -u root -p vehicle_rental > backup_$(date +%Y%m%d).sql
```

**Restore Database:**
```bash
mysql -u root -p vehicle_rental < backup_20251130.sql
```

**Clean Soft Deleted Records:**
```sql
-- Permanently delete old soft-deleted records (older than 90 days)
DELETE FROM users WHERE deleted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM vehicles WHERE deleted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM bookings WHERE deleted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Monitoring Recommendations

1. **Uptime Monitoring**
   - Monitor API health endpoint: `/api/health`
   - Alert on downtime

2. **Database Monitoring**
   - Monitor connection pool
   - Track slow queries
   - Check disk usage

3. **Application Monitoring**
   - Error rate tracking
   - API response times
   - User activity metrics

4. **Security Monitoring**
   - Failed login attempts
   - Suspicious API calls
   - File upload patterns

### Regular Maintenance Tasks

**Daily:**
- Check error logs
- Monitor payment transactions
- Review booking confirmations

**Weekly:**
- Database backup
- Review user activity
- Check disk space

**Monthly:**
- Update dependencies
- Security audit
- Performance review
- User feedback review

---

## Support & Contact

For technical support or questions about this system:

- **Repository:** https://github.com/erroldsantos/Vehicle-Rental
- **Framework Documentation:** https://lavalust.netlify.app
- **Issue Tracker:** GitHub Issues

---

## License

This project is licensed under the MIT License.

**LavaLust Framework**
Copyright (c) 2020 Ronald M. Marasigan
MIT License - See LavaLust documentation for details

---

## Changelog

### Version 1.0.0 (November 2025)
- Initial release
- Complete vehicle rental management system
- Payment integration (PayMongo)
- License verification system
- Email verification
- Comprehensive admin dashboard
- Customer booking interface

---

**Document Version:** 1.0  
**Last Updated:** November 30, 2025  
**Maintained By:** Development Team

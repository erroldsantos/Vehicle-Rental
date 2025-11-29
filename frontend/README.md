# Vehicle Rental Management System - Frontend

Modern Vue.js 3 admin dashboard and customer portal for the Vehicle Rental Management System.

## 🚀 Features

- ✅ **Modern Vue.js 3** with Composition API
- ✅ **Dual Interface** - Admin dashboard and customer portal
- ✅ **Vehicle Browsing** - Browse and book available vehicles
- ✅ **Booking Management** - Complete booking lifecycle
- ✅ **Payment Integration** - PayMongo payment gateway
- ✅ **License Verification** - Upload and verify driver's licenses
- ✅ **Maintenance Tracking** - Vehicle maintenance and damage reports
- ✅ **Real-time Updates** - Live booking status and availability
- ✅ **Responsive Design** - Works on desktop, tablet, and mobile
- ✅ **Location Picker** - Leaflet map integration for pickup/return locations

## 📁 Project Structure

```
frontend/
├── src/
│   ├── components/             # Reusable Vue components
│   │   ├── Sidebar.vue         # Navigation sidebar
│   │   ├── Header.vue          # Top header bar
│   │   ├── StatsCard.vue       # Statistics card component
│   │   ├── AlertMessage.vue    # Alert/notification component
│   │   ├── LocationPicker.vue  # Map picker for locations
│   │   └── LicenseVerification.vue # License upload component
│   ├── views/                  # Page components
│   │   ├── Login.vue           # Login page
│   │   ├── SignUp.vue          # Registration page
│   │   ├── Dashboard.vue       # Admin dashboard
│   │   ├── UserDashboard.vue   # Customer dashboard
│   │   ├── BrowseVehicles.vue  # Browse available vehicles
│   │   ├── MyBookings.vue      # Customer bookings view
│   │   ├── UserManagement.vue  # Admin user management
│   │   ├── VehicleManagement.vue # Admin vehicle CRUD
│   │   ├── BookingManagement.vue # Admin booking management
│   │   ├── MaintenanceManagement.vue # Maintenance tracking
│   │   ├── PaymentManagement.vue # Payment tracking
│   │   └── LicenseManagement.vue # License verification admin
│   ├── stores/                 # Pinia stores
│   │   └── api.js              # API service with axios
│   ├── composables/            # Reusable composition functions
│   │   └── useCurrency.js      # Currency formatting
│   ├── router/                 # Vue Router config
│   │   └── index.js            # Routes and navigation guards
│   ├── App.vue                 # Main app component
│   ├── main.js                 # App entry point
│   └── style.css               # Global styles
├── public/
│   └── images/                 # Static images
├── package.json                # Dependencies
├── vite.config.js              # Vite configuration
├── vercel.json                 # Vercel deployment config
└── index.html                  # HTML template
```

## 🛠️ Setup Instructions

### Prerequisites
- Node.js (version 16 or higher)
- npm or yarn package manager

### Installation

1. **Clone this repository:**
   ```bash
   git clone <your-repo-url>
   cd vehicle-rental-frontend
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Start development server:**
   ```bash
   npm run dev
   ```

4. **Access the application:**
   Open your browser and go to: `http://localhost:5173`

### Build for Production

```bash
npm run build
```

This creates a `dist/` folder with optimized files ready for deployment.

### Preview Production Build

```bash
npm run preview
```

## 🔌 API Integration

### Production
- **Backend URL**: `https://vehiclerental.infinityfreeapp.com/api`
- **Frontend URL**: `https://vehicle-rental-swart.vercel.app`

### Development
- **Local Dev**: The Vite dev server proxies API requests
- **Backend**: Can use local or production backend

### Key API Endpoints:

**Authentication:**
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user
- `GET /api/auth/verify-email` - Email verification

**Vehicles:**
- `GET /api/vehicles` - List all vehicles
- `GET /api/vehicles/{id}` - Get vehicle details
- `GET /api/vehicles/{id}/booked-dates` - Get booked dates
- `POST /api/vehicles` - Create vehicle (admin)
- `PUT /api/vehicles/{id}` - Update vehicle (admin)
- `DELETE /api/vehicles/{id}` - Delete vehicle (admin)

**Bookings:**
- `GET /api/bookings` - List bookings
- `GET /api/bookings/available-vehicles` - Check availability
- `POST /api/bookings` - Create booking
- `PUT /api/bookings/{id}` - Update booking
- `PUT /api/bookings/{id}/cancel` - Cancel booking

**Payments:**
- `GET /api/payments` - List payments
- `POST /api/payments/booking` - Create payment
- `POST /api/webhook/paymongo` - PayMongo webhook

**License Verification:**
- `POST /api/users/{id}/license/upload` - Upload license
- `GET /api/users/{id}/license/status` - Check status
- `POST /api/admin/licenses/{userId}/verify` - Verify license (admin)
- `POST /api/admin/licenses/{userId}/reject` - Reject license (admin)

## 🚀 Deployment

### Deploy to Vercel

**Using Vercel CLI:**
```bash
npm install -g vercel
vercel login
vercel --prod
```

**Using Vercel Dashboard:**
1. Go to https://vercel.com
2. Import your GitHub repository
3. Set framework to: **Vite**
4. Build command: `npm run build`
5. Output directory: `dist`
6. Deploy!

**Build Settings:**
- Framework: Vite
- Build Command: `npm run build`
- Output Directory: `dist`
- Install Command: `npm install`

The `vercel.json` file is already configured for proper routing.

## 🔧 Configuration

### API Base URL
The API is configured in `src/stores/api.js`:
```javascript
const api = axios.create({
  baseURL: 'https://vehiclerental.infinityfreeapp.com/api',
  headers: {
    'Content-Type': 'application/json'
  },
  withCredentials: false
})
```

### Vite Dev Server Proxy
For local development, update `vite.config.js` if needed:
```javascript
server: {
  port: 5173,
  proxy: {
    '/api': {
      target: 'https://vehiclerental.infinityfreeapp.com',
      changeOrigin: true,
      secure: false
    }
  }
}
```

## 📱 Responsive Design

The dashboard works perfectly on:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

Mobile features:
- Collapsible sidebar
- Touch-friendly navigation
- Responsive tables
- Mobile-optimized forms

## 🚀 Development vs Production

### Development Mode
```bash
npm run dev
```
- Hot reload
- Source maps
- Unminified code
- Development tools

### Production Mode
```bash
npm run build
npm run preview
```
- Minified code
- Optimized assets
- Tree shaking
- Code splitting

## 🎯 User Roles & Features

### Customer Features
- Browse available vehicles
- Book vehicles with date/time selection
- Upload driver's license for verification
- View booking history and status
- Make payments via PayMongo (GCash, Card, etc.)
- Track rental period and returns
- View maintenance reports and damage charges

### Admin Features
- Complete dashboard with statistics
- User management (CRUD)
- Vehicle management (CRUD)
- Booking management (approve, confirm, complete, cancel)
- License verification (approve/reject)
- Maintenance tracking
- Payment tracking
- Analytics and reports

## 🔒 Security Features

- JWT-based authentication
- Role-based access control (Admin/Customer)
- Protected routes with navigation guards
- Secure file uploads
- Email verification for new accounts
- Session management

## 📦 Technologies Used

- **Vue.js 3** - Progressive JavaScript framework
- **Vue Router** - Official router for Vue.js
- **Pinia** - State management
- **Axios** - HTTP client
- **Vite** - Next-generation frontend tooling
- **Leaflet** - Interactive map library
- **Chart.js** - Chart visualization
- **Font Awesome** - Icon library

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 🔗 Related Repositories

- **Backend**: [Vehicle Rental Backend](https://github.com/erroldsantos/Vehicle-Rental) - LavaLust PHP backend
import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import SignUp from '../views/SignUp.vue'
import EmailVerification from '../views/EmailVerification.vue'
import Dashboard from '../views/Dashboard.vue'
import UserDashboard from '../views/UserDashboard.vue'
import BrowseVehicles from '../views/BrowseVehicles.vue'
import MyBookings from '../views/MyBookings.vue'
import UserManagement from '../views/UserManagement.vue'
import VehicleManagement from '../views/VehicleManagement.vue'
import BookingManagement from '../views/BookingManagement.vue'
import MaintenanceManagement from '../views/MaintenanceManagement.vue'
import PaymentManagement from '../views/PaymentManagement.vue'
import LicenseManagement from '../views/LicenseManagement.vue'

const routes = [
  {
    path: '/',
    name: 'login',
    component: Login,
    meta: { title: 'Login', requiresGuest: true }
  },
  {
    path: '/login',
    redirect: '/'
  },
  {
    path: '/signup',
    name: 'signup',
    component: SignUp,
    meta: { title: 'Sign Up', requiresGuest: true }
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: EmailVerification,
    meta: { title: 'Verify Email' }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: Dashboard,
    meta: { title: 'Admin Dashboard', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/user-dashboard',
    name: 'user-dashboard',
    component: UserDashboard,
    meta: { title: 'User Dashboard', requiresAuth: true }
  },
  {
    path: '/browse-vehicles',
    name: 'browse-vehicles',
    component: BrowseVehicles,
    meta: { title: 'Browse Vehicles', requiresAuth: true }
  },
  {
    path: '/my-bookings',
    name: 'my-bookings',
    component: MyBookings,
    meta: { title: 'My Bookings', requiresAuth: true }
  },
  {
    path: '/users',
    name: 'users',
    component: UserManagement,
    meta: { title: 'User Management', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/vehicles',
    name: 'vehicles',
    component: VehicleManagement,
    meta: { title: 'Vehicle Management', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/bookings',
    name: 'bookings',
    component: BookingManagement,
    meta: { title: 'Booking Management', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/maintenance',
    name: 'maintenance',
    component: MaintenanceManagement,
    meta: { title: 'Maintenance', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/payments',
    name: 'payments',
    component: PaymentManagement,
    meta: { title: 'Payments', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/licenses',
    name: 'licenses',
    component: LicenseManagement,
    meta: { title: 'License Verification', requiresAuth: true, requiresAdmin: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Authentication helper
const isAuthenticated = () => {
  return localStorage.getItem('auth_token') !== null
}

// Get user role helper
const getUserRole = () => {
  const userInfo = localStorage.getItem('user_info')
  if (userInfo) {
    const user = JSON.parse(userInfo)
    return user.role || 'user'
  }
  return 'user'
}

// Navigation guards
router.beforeEach((to, from, next) => {
  const authenticated = isAuthenticated()
  const userRole = getUserRole()
  
  // If trying to access root path
  if (to.path === '/') {
    if (authenticated) {
      // Redirect authenticated users to their dashboard
      if (userRole === 'admin') {
        next({ name: 'dashboard' })
      } else {
        next({ name: 'user-dashboard' })
      }
      return
    }
    // Not authenticated, allow access to login (which is at '/')
  }
  
  // Check if route requires authentication
  if (to.meta.requiresAuth && !authenticated) {
    next({ name: 'login' })
    return
  }
  
  // Check if route requires admin role
  if (to.meta.requiresAdmin && userRole !== 'admin') {
    next({ name: 'user-dashboard' })
    return
  }
  
  // Check if route requires guest (not authenticated) - like login/signup
  if (to.meta.requiresGuest && authenticated) {
    // Redirect to appropriate dashboard based on role
    if (userRole === 'admin') {
      next({ name: 'dashboard' })
    } else {
      next({ name: 'user-dashboard' })
    }
    return
  }
  
  next()
})

export default router
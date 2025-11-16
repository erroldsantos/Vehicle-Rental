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

const routes = [
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { title: 'Login', requiresGuest: true }
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
    path: '/',
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
  
  // Check if route requires guest (not authenticated)
  if (to.meta.requiresGuest && authenticated) {
    // Redirect to appropriate dashboard based on role
    if (userRole === 'admin') {
      next({ name: 'dashboard' })
    } else {
      next({ name: 'user-dashboard' })
    }
    return
  }
  
  // If trying to access root and authenticated, redirect to appropriate dashboard
  if (to.path === '/' && !to.name && authenticated) {
    if (userRole === 'admin') {
      next({ name: 'dashboard' })
    } else {
      next({ name: 'user-dashboard' })
    }
    return
  }
  
  // If trying to access root and not authenticated, redirect to login
  if (to.path === '/' && !to.name && !authenticated) {
    next({ name: 'login' })
    return
  }
  
  next()
})

export default router
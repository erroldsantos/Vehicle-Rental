import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
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
    path: '/',
    name: 'dashboard',
    component: Dashboard,
    meta: { title: 'Dashboard', requiresAuth: true }
  },
  {
    path: '/users',
    name: 'users',
    component: UserManagement,
    meta: { title: 'User Management', requiresAuth: true }
  },
  {
    path: '/vehicles',
    name: 'vehicles',
    component: VehicleManagement,
    meta: { title: 'Vehicle Management', requiresAuth: true }
  },
  {
    path: '/bookings',
    name: 'bookings',
    component: BookingManagement,
    meta: { title: 'Booking Management', requiresAuth: true }
  },
  {
    path: '/maintenance',
    name: 'maintenance',
    component: MaintenanceManagement,
    meta: { title: 'Maintenance', requiresAuth: true }
  },
  {
    path: '/payments',
    name: 'payments',
    component: PaymentManagement,
    meta: { title: 'Payments', requiresAuth: true }
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

// Navigation guards
router.beforeEach((to, from, next) => {
  const authenticated = isAuthenticated()
  
  // Check if route requires authentication
  if (to.meta.requiresAuth && !authenticated) {
    next({ name: 'login' })
    return
  }
  
  // Check if route requires guest (not authenticated)
  if (to.meta.requiresGuest && authenticated) {
    next({ name: 'dashboard' })
    return
  }
  
  // If trying to access root and authenticated, redirect to dashboard
  if (to.path === '/' && !to.name && authenticated) {
    next({ name: 'dashboard' })
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
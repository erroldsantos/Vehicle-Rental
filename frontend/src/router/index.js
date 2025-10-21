import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../views/Dashboard.vue'
import UserManagement from '../views/UserManagement.vue'
import VehicleManagement from '../views/VehicleManagement.vue'
import BookingManagement from '../views/BookingManagement.vue'
import MaintenanceManagement from '../views/MaintenanceManagement.vue'
import PaymentManagement from '../views/PaymentManagement.vue'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: Dashboard,
    meta: { title: 'Dashboard' }
  },
  {
    path: '/users',
    name: 'users',
    component: UserManagement,
    meta: { title: 'User Management' }
  },
  {
    path: '/vehicles',
    name: 'vehicles',
    component: VehicleManagement,
    meta: { title: 'Vehicle Management' }
  },
  {
    path: '/bookings',
    name: 'bookings',
    component: BookingManagement,
    meta: { title: 'Booking Management' }
  },
  {
    path: '/maintenance',
    name: 'maintenance',
    component: MaintenanceManagement,
    meta: { title: 'Maintenance' }
  },
  {
    path: '/payments',
    name: 'payments',
    component: PaymentManagement,
    meta: { title: 'Payments' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
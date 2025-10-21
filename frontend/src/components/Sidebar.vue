<template>
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <i class="fas fa-car"></i>
        <div class="logo-text">
          <h3>Vehicle Rental</h3>
          <small>Admin Panel</small>
        </div>
      </div>
    </div>
    
    <nav class="sidebar-nav">
      <div class="nav-item" 
           v-for="item in menuItems" 
           :key="item.name"
           :class="{ active: currentPage === item.name }"
           @click="$emit('navigate', item.name)">
        <i :class="item.icon"></i>
        <span>{{ item.label }}</span>
      </div>
    </nav>
  </aside>
</template>

<script>
export default {
  name: 'Sidebar',
  props: {
    collapsed: {
      type: Boolean,
      default: false
    },
    mobileOpen: {
      type: Boolean,
      default: false
    },
    currentPage: {
      type: String,
      default: 'dashboard'
    }
  },
  emits: ['toggle', 'navigate'],
  setup() {
    const menuItems = [
      { name: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
      { name: 'users', label: 'Users', icon: 'fas fa-users' },
      { name: 'vehicles', label: 'Vehicles', icon: 'fas fa-car' },
      { name: 'bookings', label: 'Bookings', icon: 'fas fa-calendar-alt' },
      { name: 'maintenance', label: 'Maintenance', icon: 'fas fa-tools' },
      { name: 'payments', label: 'Payments', icon: 'fas fa-credit-card' }
    ]
    
    return {
      menuItems
    }
  }
}
</script>
<template>
  <aside class="sidebar" :class="{ collapsed: collapsed, 'mobile-open': mobileOpen }">
    <div class="sidebar-header">
      <div class="logo">
        <i class="fas fa-car"></i>
        <div class="logo-text" v-show="!collapsed">
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
           @click="$emit('navigate', item.name)"
           :title="collapsed ? item.label : ''">
        <i :class="item.icon"></i>
        <span v-show="!collapsed">{{ item.label }}</span>
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
      { name: 'payments', label: 'Payments', icon: 'fas fa-credit-card' },
      { name: 'licenses', label: 'License Verification', icon: 'fas fa-id-card' }
    ]
    
    return {
      menuItems
    }
  }
}
</script>

<style scoped>
.sidebar {
  width: 280px;
  height: 100vh;
  background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
  color: white;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 1000;
  overflow-y: auto;
  transition: all 0.3s ease;
}

/* Collapsed state */
.sidebar.collapsed {
  width: 70px;
}

.sidebar.collapsed .logo-text {
  display: none;
}

.sidebar.collapsed .nav-item span {
  display: none;
}

.sidebar.collapsed .nav-item {
  justify-content: center;
  padding: 15px;
}

/* Mobile styles */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    width: 280px;
  }
  
  .sidebar.mobile-open {
    transform: translateX(0);
  }
  
  .sidebar.collapsed {
    width: 280px; /* Don't collapse on mobile */
  }
  
  .sidebar.collapsed .logo-text,
  .sidebar.collapsed .nav-item span {
    display: block; /* Show text on mobile even when collapsed */
  }
  
  .sidebar.collapsed .nav-item {
    justify-content: flex-start;
    padding: 15px 20px;
  }
}

.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(0, 0, 0, 0.1);
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo i {
  font-size: 1.5rem;
  color: #ffffff;
}

.logo-text h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.logo-text small {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.8rem;
}

.sidebar-nav {
  padding: 10px 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  border-left-color: #ffffff;
}

.nav-item.active {
  background: rgba(255, 255, 255, 0.2);
  border-left-color: #ffffff;
}

.nav-item i {
  width: 20px;
  text-align: center;
  font-size: 1rem;
}

.nav-item span {
  font-weight: 500;
  font-size: 0.9rem;
}

/* Overlay for mobile */
@media (max-width: 768px) {
  .sidebar.mobile-open::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: -1;
  }
}

/* Scrollbar styling */
.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}
</style>
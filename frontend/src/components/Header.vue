cd frontend
npm run dev<template>
  <header class="header" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
    <div class="header-left">
      <button class="menu-toggle" @click="toggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h1>{{ pageTitle }}</h1>
    </div>
    
    <div class="header-right">
      <div class="notifications">
        <i class="fas fa-bell icon"></i>
        <span class="notification-badge" v-if="notificationCount > 0">{{ notificationCount }}</span>
      </div>
      
      <div class="user-profile" @click="toggleUserMenu">
        <div class="user-info">
          <span class="user-name">Admin User</span>
          <span class="user-role">Administrator</span>
        </div>
        <div class="user-avatar">A</div>
        <i class="fas fa-chevron-down"></i>
      </div>
      
      <!-- User Menu Dropdown -->
      <div class="user-menu" v-if="showUserMenu">
        <a href="#" @click.prevent="viewProfile">
          <i class="fas fa-user"></i> Profile
        </a>
        <a href="#" @click.prevent="openSettings">
          <i class="fas fa-cog"></i> Settings
        </a>
        <a href="#" @click.prevent="logout" class="logout">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </div>
  </header>
</template>

<script>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

export default {
  name: 'Header',
  props: {
    sidebarCollapsed: {
      type: Boolean,
      default: false
    }
  },
  emits: ['toggle-sidebar'],
  setup(props, { emit }) {
    const route = useRoute()
    const router = useRouter()
    const showUserMenu = ref(false)
    const notificationCount = ref(3)
    
    const pageTitle = computed(() => {
      const titles = {
        dashboard: 'Dashboard Overview',
        users: 'User Management',
        vehicles: 'Vehicle Management',
        bookings: 'Booking Management',
        maintenance: 'Maintenance Management',
        payments: 'Payment Management'
      }
      return titles[route.name] || 'Dashboard'
    })
    
    const toggleSidebar = () => {
      emit('toggle-sidebar')
    }
    
    const toggleUserMenu = () => {
      showUserMenu.value = !showUserMenu.value
    }
    
    const viewProfile = () => {
      showUserMenu.value = false
      alert('Profile functionality to be implemented')
    }
    
    const openSettings = () => {
      showUserMenu.value = false
      alert('Settings functionality to be implemented')
    }
    
    const logout = () => {
      showUserMenu.value = false
      if (confirm('Are you sure you want to logout?')) {
        // Clear authentication data
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_info')
        
        // Redirect to login
        router.push({ name: 'login' })
      }
    }


    
    return {
      pageTitle,
      showUserMenu,
      notificationCount,
      toggleSidebar,
      toggleUserMenu,
      viewProfile,
      openSettings,
      logout
    }
  }
}
</script>

<style scoped>
.header {
  background: white;
  border-bottom: 1px solid #e5e7eb;
  padding: 0 24px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: fixed;
  top: 0;
  left: 280px;
  right: 0;
  z-index: 100;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: left 0.3s ease;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.menu-toggle {
  background: none;
  border: none;
  padding: 8px;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  font-size: 18px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
}

.menu-toggle:hover {
  background: #f3f4f6;
  color: #374151;
}

.header-left h1 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 20px;
  position: relative;
}

.notifications {
  position: relative;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  transition: background 0.2s ease;
}

.notifications:hover {
  background: #f3f4f6;
}

.notifications .icon {
  font-size: 18px;
  color: #6b7280;
}

.notification-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  background: #ef4444;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  font-size: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 8px;
  transition: background 0.2s ease;
}

.user-profile:hover {
  background: #f3f4f6;
}

.user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  text-align: right;
}

.user-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 14px;
}

.user-role {
  font-size: 12px;
  color: #6b7280;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}

.user-profile i {
  font-size: 12px;
  color: #9ca3af;
  transition: transform 0.2s ease;
}

.user-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  min-width: 180px;
  z-index: 1000;
  overflow: hidden;
  margin-top: 8px;
}

.user-menu a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  color: #374151;
  text-decoration: none;
  font-size: 14px;
  transition: background 0.2s ease;
}

.user-menu a:hover {
  background: #f3f4f6;
}

.user-menu a.logout {
  color: #ef4444;
  border-top: 1px solid #f3f4f6;
}

.user-menu a.logout:hover {
  background: #fef2f2;
}

.user-menu a i {
  width: 16px;
  font-size: 14px;
}

/* Collapsed sidebar state */
.header.sidebar-collapsed {
  left: 70px;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .header {
    padding: 0 16px;
    left: 0 !important;
  }
  
  .header-left h1 {
    font-size: 1.25rem;
  }
  
  .user-info {
    display: none;
  }
  
  .header-right {
    gap: 12px;
  }
}
</style>
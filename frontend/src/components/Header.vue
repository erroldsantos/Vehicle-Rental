cd frontend
npm run dev<template>
  <header class="header">
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
<template>
  <header class="header">
    <div class="header-left">
      <button class="menu-toggle" @click="toggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h1>{{ pageTitle }}</h1>
    </div>
    
    <div class="header-right">
      <!-- API Connection Status -->
      <div class="api-status" :class="{ connected: apiConnected, disconnected: !apiConnected }">
        <div class="status-indicator"></div>
        <span class="status-text">{{ apiConnected ? 'API Connected' : 'API Disconnected' }}</span>
      </div>

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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'

export default {
  name: 'Header',
  emits: ['toggle-sidebar'],
  setup(props, { emit }) {
    const route = useRoute()
    const showUserMenu = ref(false)
    const notificationCount = ref(3)
    const apiConnected = ref(false)
    let connectionCheckInterval = null
    
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
        // Implement logout functionality
        alert('Logout functionality to be implemented')
      }
    }

    // Check API connection status
    const checkApiConnection = async () => {
      try {
        // LavaLust API health endpoint
        const response = await fetch('http://localhost:8000/api/health', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          },
        })
        
        if (response.ok) {
          apiConnected.value = true
        } else {
          apiConnected.value = false
        }
      } catch (error) {
        console.log('API connection check failed:', error.message)
        apiConnected.value = false
      }
    }

    // Start periodic connection checks
    const startConnectionMonitoring = () => {
      checkApiConnection() // Check immediately
      connectionCheckInterval = setInterval(checkApiConnection, 30000) // Check every 30 seconds
    }

    // Stop connection monitoring
    const stopConnectionMonitoring = () => {
      if (connectionCheckInterval) {
        clearInterval(connectionCheckInterval)
        connectionCheckInterval = null
      }
    }

    onMounted(() => {
      startConnectionMonitoring()
    })

    onBeforeUnmount(() => {
      stopConnectionMonitoring()
    })
    
    return {
      pageTitle,
      showUserMenu,
      notificationCount,
      apiConnected,
      toggleSidebar,
      toggleUserMenu,
      viewProfile,
      openSettings,
      logout
    }
  }
}
</script>
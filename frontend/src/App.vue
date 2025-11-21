<template>
  <div id="app">
    <!-- Admin Layout (with sidebar and header) -->
    <template v-if="showAdminLayout">
      <!-- Mobile Overlay -->
      <div 
        v-if="sidebarMobileOpen" 
        class="mobile-overlay" 
        @click="sidebarMobileOpen = false"
      ></div>
      
      <!-- Sidebar -->
      <Sidebar 
        :current-page="currentRoute"
        :collapsed="sidebarCollapsed"
        :mobile-open="sidebarMobileOpen"
        @navigate="navigateTo"
      />
      
      <!-- Main Content -->
      <div class="main-content" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
        <Header @toggle-sidebar="toggleSidebar" :sidebar-collapsed="sidebarCollapsed" />
        
        <main class="content">
          <AlertMessage 
            v-if="alert.message" 
            :message="alert.message" 
            :type="alert.type"
          />
          
          <router-view />
        </main>
      </div>
    </template>
    
    <!-- User Dashboard Layout (clean, no sidebar/header) -->
    <template v-else-if="showUserDashboard">
      <div class="user-layout">
        <AlertMessage 
          v-if="alert.message" 
          :message="alert.message" 
          :type="alert.type"
        />
        
        <router-view />
      </div>
    </template>
    
    <!-- Guest Layout (Login) -->
    <template v-else>
      <router-view />
    </template>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Sidebar from './components/Sidebar.vue'
import Header from './components/Header.vue'
import AlertMessage from './components/AlertMessage.vue'
import { useApiStore } from './stores/api'

export default {
  name: 'App',
  components: {
    Sidebar,
    Header,
    AlertMessage
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const apiStore = useApiStore()
    
    const sidebarCollapsed = ref(false)
    const sidebarMobileOpen = ref(false)
    const alert = ref({ message: '', type: 'success' })
    const isAuthenticated = ref(localStorage.getItem('auth_token') !== null)
    
    const currentRoute = computed(() => route.name)
    const apiConnected = computed(() => apiStore.connected)
    
    // Get user role
    const userRole = computed(() => {
      const userInfo = localStorage.getItem('user_info')
      if (userInfo) {
        const user = JSON.parse(userInfo)
        return user.role || 'user'
      }
      return 'user'
    })
    
    // Check if current route should show admin layout
    const isAdminRoute = computed(() => {
      const adminRoutes = ['dashboard', 'users', 'vehicles', 'bookings', 'maintenance', 'payments', 'licenses']
      return adminRoutes.includes(currentRoute.value)
    })
    
    // Check if user should see admin layout
    const showAdminLayout = computed(() => {
      return isAuthenticated.value && userRole.value === 'admin' && isAdminRoute.value
    })
    
    // Check if user should see user dashboard (no sidebar/header)
    const showUserDashboard = computed(() => {
      return isAuthenticated.value && currentRoute.value === 'user-dashboard'
    })
    
    // Watch for authentication changes
    const checkAuth = () => {
      isAuthenticated.value = localStorage.getItem('auth_token') !== null
    }
    
    // Listen for storage changes (useful for logout from other tabs)
    window.addEventListener('storage', checkAuth)
    
    // Also check auth when route changes
    router.beforeEach(() => {
      checkAuth()
    })
    
    const toggleSidebar = () => {
      if (window.innerWidth <= 768) {
        sidebarMobileOpen.value = !sidebarMobileOpen.value
      } else {
        sidebarCollapsed.value = !sidebarCollapsed.value
      }
    }
    
    const navigateTo = (routeName) => {
      router.push({ name: routeName })
      sidebarMobileOpen.value = false // Close mobile sidebar
    }
    
    onMounted(async () => {
      // Check authentication state on mount
      checkAuth()
      
      await apiStore.checkConnection()
    })
    
    const showAlert = (message, type) => {
      alert.value = { message, type }
      setTimeout(() => {
        alert.value = { message: '', type: 'success' }
      }, 5000)
    }
    
    return {
      sidebarCollapsed,
      sidebarMobileOpen,
      alert,
      currentRoute,
      apiConnected,
      isAuthenticated,
      userRole,
      showAdminLayout,
      showUserDashboard,
      toggleSidebar,
      navigateTo,
      checkAuth
    }
  }
}
</script>

<style scoped>
.user-layout {
  min-height: 100vh;
  background: #f8fafc;
}

.main-content {
  margin-left: 280px;
  transition: margin-left 0.3s ease;
  min-height: 100vh;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  position: relative;
}

/* Adjust main content when sidebar is collapsed */
.main-content.sidebar-collapsed {
  margin-left: 70px;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }
  
  .main-content.sidebar-collapsed {
    margin-left: 0;
  }
}

.content {
  padding: 20px;
  flex: 1;
  overflow-y: auto;
  margin-top: 64px;
}

.mobile-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 999;
  display: none;
}

@media (max-width: 768px) {
  .mobile-overlay {
    display: block;
  }
}
</style>
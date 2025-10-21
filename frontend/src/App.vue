<template>
  <div id="app">
    <!-- Sidebar -->
    <Sidebar 
      :current-page="currentRoute"
      @navigate="navigateTo"
    />
    
    <!-- Main Content -->
    <div class="main-content">
      <Header />
      
      <main class="content">
        <AlertMessage 
          v-if="alert.message" 
          :message="alert.message" 
          :type="alert.type"
        />
        
        <router-view />
      </main>
    </div>
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
    
    const currentRoute = computed(() => route.name)
    const apiConnected = computed(() => apiStore.connected)
    
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
      await apiStore.checkConnection()
      
      if (apiStore.connected) {
        showAlert('Connected to LavaLust API', 'success')
      } else {
        showAlert('Failed to connect to API', 'error')
      }
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
      toggleSidebar,
      navigateTo
    }
  }
}
</script>
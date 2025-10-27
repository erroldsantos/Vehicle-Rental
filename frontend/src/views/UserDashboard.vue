<template>
  <div class="user-dashboard">
    <!-- User Header -->
    <header class="user-header">
      <div class="header-content">
        <div class="logo">
          <i class="fas fa-car"></i>
          <span class="logo-text">Vehicle Rental</span>
        </div>
        
        <div class="user-info">
          <span class="user-name">{{ userName }}</span>
          <span class="user-role">CUSTOMER</span>
          <div class="user-avatar">
            {{ userName.charAt(0).toUpperCase() }}
          </div>
          <button class="logout-btn" @click="logout">
            <i class="fas fa-sign-out-alt"></i>
          </button>
        </div>
      </div>
    </header>

    <!-- Welcome Section -->
    <div class="welcome-section">
      <div class="welcome-card">
        <div class="welcome-content">
          <h1>Welcome back, {{ userName }}!</h1>
          <p class="welcome-subtitle">Ready to book your next vehicle?</p>
        </div>
        <div class="welcome-actions">
          <button class="btn-primary" @click="$router.push('/browse-vehicles')">
            <i class="fas fa-car"></i>
            Browse Vehicles
          </button>
          <button class="btn-outline" @click="$router.push('/my-bookings')">
            <i class="fas fa-calendar-alt"></i>
            My Bookings
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-car"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.totalBookings }}</h3>
          <p>Total Bookings</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.activeBookings }}</h3>
          <p>Active Bookings</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-card">
          <h3>₱{{ stats.totalSpent }}</h3>
          <p>Total Spent</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-star"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.favoriteVehicles }}</h3>
          <p>Favorite Vehicles</p>
        </div>
      </div>
    </div>

    <!-- Recent Bookings -->
    <div class="section">
      <div class="section-header">
        <h2>Recent Bookings</h2>
        <button class="btn-link" @click="$router.push('/my-bookings')">View All</button>
      </div>
      
      <div class="bookings-list" v-if="recentBookings.length > 0">
        <div 
          v-for="booking in recentBookings" 
          :key="booking.id"
          class="booking-card"
        >
          <div class="booking-vehicle">
            <img 
              :src="booking.vehicle_image || getPlaceholderImage(booking.vehicle_name)" 
              :alt="booking.vehicle_name"
              class="vehicle-image"
              @error="handleImageError"
            >
            <div class="vehicle-info">
              <h4>{{ booking.vehicle_name }}</h4>
              <p class="booking-reference">{{ booking.booking_reference }}</p>
            </div>
          </div>
          
          <div class="booking-details">
            <div class="booking-dates">
              <span class="date-label">From:</span>
              <span class="date-value">{{ formatDate(booking.start_date) }}</span>
            </div>
            <div class="booking-dates">
              <span class="date-label">To:</span>
              <span class="date-value">{{ formatDate(booking.end_date) }}</span>
            </div>
          </div>
          
          <div class="booking-status">
            <span :class="['status-badge', booking.status]">
              {{ booking.status.charAt(0).toUpperCase() + booking.status.slice(1) }}
            </span>
            <p class="booking-amount">₱{{ booking.total_amount }}</p>
          </div>
        </div>
      </div>
      
      <div v-else class="empty-state">
        <i class="fas fa-calendar-alt"></i>
        <h3>No bookings yet</h3>
        <p>Start by browsing our available vehicles</p>
        <button class="btn-primary" @click="$router.push('/browse-vehicles')">
          Browse Vehicles
        </button>
      </div>
    </div>

    <!-- Available Vehicles Preview -->
    <div class="section">
      <div class="section-header">
        <h2>Featured Vehicles</h2>
        <button class="btn-link" @click="$router.push('/browse-vehicles')">View All</button>
      </div>
      
      <div class="vehicles-grid">
        <div 
          v-for="vehicle in featuredVehicles" 
          :key="vehicle.id"
          class="vehicle-card"
          @click="selectVehicle(vehicle)"
        >
          <img 
            :src="vehicle.imageUrl || getPlaceholderImage(vehicle.brand)" 
            :alt="vehicle.brand + ' ' + vehicle.model"
            class="vehicle-image"
            @error="handleImageError"
          >
          <div class="vehicle-info">
            <h4>{{ vehicle.brand }} {{ vehicle.model }}</h4>
            <p class="vehicle-year">{{ vehicle.year }}</p>
            <div class="vehicle-rate">
              <span class="price">₱{{ vehicle.daily_rate }}</span>
              <span class="period">/day</span>
            </div>
            <button class="btn-primary btn-small">
              Book Now
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'

export default {
  name: 'UserDashboard',
  setup() {
    const router = useRouter()
    const apiStore = useApiStore()
    const loading = ref(false)
    const stats = ref({
      totalBookings: 0,
      activeBookings: 0,
      totalSpent: '0.00',
      favoriteVehicles: 0
    })
    const recentBookings = ref([])
    const featuredVehicles = ref([])

    // Get user info from localStorage
    const userInfo = computed(() => {
      const stored = localStorage.getItem('user_info')
      return stored ? JSON.parse(stored) : {}
    })

    const userName = computed(() => {
      return userInfo.value.name || 'User'
    })

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const loadDashboardData = async () => {
      loading.value = true
      try {
        // Load user statistics
        await loadUserStats()
        
        // Load recent bookings
        await loadRecentBookings()
        
        // Load featured vehicles
        await loadFeaturedVehicles()
        
      } catch (error) {
        console.error('Error loading dashboard data:', error)
      } finally {
        loading.value = false
      }
    }

    const loadUserStats = async () => {
      try {
        // Get current user ID from localStorage
        const userInfo = JSON.parse(localStorage.getItem('user_info') || '{}')
        const userId = userInfo.id
        
        if (!userId) {
          console.warn('No user ID found')
          return
        }

        // Fetch user's bookings to calculate stats
        const bookingsData = await apiStore.get(`/bookings?user_id=${userId}`)
        const userBookings = bookingsData.bookings || bookingsData.data?.bookings || []
        
        // Calculate stats from real data
        const totalBookings = userBookings.length
        const activeBookings = userBookings.filter(b => 
          b.status === 'confirmed' || b.status === 'pending'
        ).length
        const totalSpent = userBookings
          .filter(b => b.status === 'completed')
          .reduce((sum, b) => sum + parseFloat(b.total_amount || 0), 0)
        
        stats.value = {
          totalBookings,
          activeBookings,
          totalSpent: totalSpent.toFixed(2),
          favoriteVehicles: Math.min(totalBookings, 5) // Approximate based on bookings
        }
      } catch (error) {
        console.error('Error loading user stats:', error)
        stats.value = {
          totalBookings: 0,
          activeBookings: 0,
          totalSpent: '0.00',
          favoriteVehicles: 0
        }
      }
    }

    const loadRecentBookings = async () => {
      try {
        // Get current user ID from localStorage
        const userInfo = JSON.parse(localStorage.getItem('user_info') || '{}')
        const userId = userInfo.id
        
        if (!userId) {
          console.warn('No user ID found')
          recentBookings.value = []
          return
        }

        // Fetch user's recent bookings from API
        const data = await apiStore.get(`/bookings?user_id=${userId}&limit=5&sort=created_at&order=DESC`)
        const bookings = data.bookings || data.data?.bookings || []
        
        // Process bookings data
        recentBookings.value = bookings.map(booking => ({
          id: booking.id,
          booking_reference: booking.booking_reference,
          vehicle_name: `${booking.brand || ''} ${booking.model || ''}`.trim() || 'Unknown Vehicle',
          vehicle_image: booking.vehicle_image ? `/images/vehicles/${booking.vehicle_image}` : null,
          start_date: booking.start_date,
          end_date: booking.end_date,
          status: booking.status,
          total_amount: parseFloat(booking.total_amount || 0).toFixed(2)
        }))
      } catch (error) {
        console.error('Error loading recent bookings:', error)
        recentBookings.value = []
      }
    }

    const loadFeaturedVehicles = async () => {
      try {
        // Fetch from actual API
        const data = await apiStore.get('/vehicles')
        const vehicles = data.data?.vehicles || data.vehicles || []
        
        // Take first 4 available vehicles for featured section
        featuredVehicles.value = vehicles
          .filter(vehicle => vehicle.status === 'available')
          .slice(0, 4)
          .map(vehicle => ({
            ...vehicle,
            // Construct image URL if image exists
            imageUrl: vehicle.image ? `/images/vehicles/${vehicle.image}` : null
          }))
      } catch (error) {
        console.error('Error loading featured vehicles:', error)
        featuredVehicles.value = []
      }
    }

    const selectVehicle = (vehicle) => {
      // Navigate to booking page with selected vehicle
      router.push({
        name: 'book-vehicle',
        params: { id: vehicle.id }
      })
    }

    const logout = () => {
      // Clear authentication data
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_info')
      
      // Redirect to login
      router.push({ name: 'login' })
    }

    const getPlaceholderImage = (vehicleName) => {
      // Return a placeholder based on vehicle type
      const name = vehicleName.toLowerCase()
      if (name.includes('suv') || name.includes('cr-v') || name.includes('escape')) {
        return '/images/vehicles/suv-placeholder.jpg'
      } else if (name.includes('truck') || name.includes('transit')) {
        return '/images/vehicles/truck-placeholder.jpg'
      } else {
        return '/images/vehicles/car-placeholder.jpg'
      }
    }

    const handleImageError = (event) => {
      // Fallback to generic placeholder if image fails to load
      event.target.src = '/images/vehicles/car-placeholder.jpg'
    }

    onMounted(() => {
      loadDashboardData()
    })

    return {
      loading,
      stats,
      recentBookings,
      featuredVehicles,
      userName,
      formatDate,
      selectVehicle,
      logout,
      getPlaceholderImage,
      handleImageError
    }
  }
}
</script>

<style scoped>
.user-dashboard {
  min-height: 100vh;
  background: #f8fafc;
}

/* User Header */
.user-header {
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.logo i {
  font-size: 2rem;
  color: #667eea;
}

.logo-text {
  font-size: 1.5rem;
  font-weight: 600;
  color: #667eea;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-name {
  font-weight: 600;
  color: #2d3748;
  font-size: 1rem;
}

.user-role {
  font-size: 0.65rem;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  font-weight: 500;
}

.user-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.2rem;
}

.logout-btn {
  background: transparent;
  border: none;
  padding: 0.5rem;
  border-radius: 8px;
  color: #a0aec0;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 1.2rem;
}

.logout-btn:hover {
  color: #667eea;
}

/* Dashboard Content */
.user-dashboard > *:not(.user-header) {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

/* Welcome Section */
.welcome-section {
  margin-bottom: 2rem;
}

.welcome-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.welcome-content h1 {
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  font-weight: 600;
}

.welcome-subtitle {
  margin: 0;
  opacity: 0.9;
  font-size: 1.1rem;
}

.welcome-actions {
  display: flex;
  gap: 1rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.stat-content h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #2d3748;
}

.stat-content p {
  margin: 0;
  color: #718096;
  font-size: 0.875rem;
}

/* Sections */
.section {
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h2 {
  margin: 0;
  color: #2d3748;
  font-size: 1.5rem;
  font-weight: 600;
}

/* Bookings List */
.bookings-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.booking-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 1.5rem;
  align-items: center;
}

.booking-vehicle {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.vehicle-image {
  width: 60px;
  height: 40px;
  border-radius: 8px;
  object-fit: cover;
  background: #f7fafc;
}

.vehicle-info h4 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-weight: 600;
}

.booking-reference {
  margin: 0;
  color: #718096;
  font-size: 0.875rem;
}

.booking-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.booking-dates {
  display: flex;
  gap: 0.5rem;
}

.date-label {
  color: #718096;
  font-size: 0.875rem;
}

.date-value {
  color: #2d3748;
  font-weight: 500;
  font-size: 0.875rem;
}

.booking-status {
  text-align: right;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}

.status-badge.pending {
  background: #fed7d7;
  color: #c53030;
}

.status-badge.confirmed {
  background: #c6f6d5;
  color: #38a169;
}

.status-badge.completed {
  background: #bee3f8;
  color: #3182ce;
}

.booking-amount {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d3748;
}

/* Vehicles Grid */
.vehicles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.vehicle-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s ease;
  cursor: pointer;
}

.vehicle-card:hover {
  transform: translateY(-4px);
}

.vehicle-card .vehicle-image {
  width: 100%;
  height: 180px;
  object-fit: cover;
  background: #f7fafc;
}

.vehicle-card .vehicle-info {
  padding: 1.5rem;
}

.vehicle-card .vehicle-info h4 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-weight: 600;
}

.vehicle-year {
  margin: 0 0 1rem 0;
  color: #718096;
  font-size: 0.875rem;
}

.vehicle-rate {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
  margin-bottom: 1rem;
}

.price {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2d3748;
}

.period {
  color: #718096;
  font-size: 0.875rem;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #718096;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #4a5568;
}

.empty-state p {
  margin: 0 0 1.5rem 0;
}

/* Buttons */
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn-outline {
  background: transparent;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-outline:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.5);
}

.btn-link {
  background: none;
  border: none;
  color: #667eea;
  font-weight: 500;
  cursor: pointer;
  padding: 0.5rem;
}

.btn-link:hover {
  color: #764ba2;
}

/* Responsive */
@media (max-width: 768px) {
  .header-content {
    padding: 1rem;
  }

  .logo-text {
    display: none;
  }

  .user-name,
  .user-role {
    display: none;
  }

  .user-dashboard > *:not(.user-header) {
    padding: 1rem;
  }

  .welcome-card {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
  }

  .welcome-actions {
    width: 100%;
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .booking-card {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .booking-status {
    text-align: left;
  }

  .vehicles-grid {
    grid-template-columns: 1fr;
  }
}
</style>
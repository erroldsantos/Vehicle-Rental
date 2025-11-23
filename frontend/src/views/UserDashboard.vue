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
          <i class="fas fa-check-double"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.completedBookings }}</h3>
          <p>Completed Bookings</p>
        </div>
      </div>
      
      <div class="stat-card" :class="{ 'warning': stats.activeBookings >= 2 }">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.activeBookings }}/2</h3>
          <p>Confirmed Bookings</p>
          <small v-if="stats.activeBookings >= 2" class="limit-warning">
            <i class="fas fa-exclamation-circle"></i>
            Booking limit reached
          </small>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
          <h3>₱{{ stats.totalSpent }}</h3>
          <p>Total Spent</p>
        </div>
      </div>
    </div>

    <!-- License Verification Section -->
    <div class="section">
      <LicenseVerification />
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
            <p class="booking-amount">₱{{ parseFloat(booking.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
            <button 
              v-if="booking.status === 'pending'" 
              class="btn-pay"
              @click="openPaymentModal(booking)"
              title="Pay Now to Confirm Booking"
            >
              <i class="fas fa-credit-card"></i>
              Pay Now
            </button>
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

    <!-- Payment Modal -->
    <div v-if="showPaymentModal" class="modal-overlay" @click="closePaymentModal">
      <div class="modal-content payment-modal" @click.stop>
        <div class="modal-header">
          <h2>Payment Details</h2>
          <button class="btn-close" @click="closePaymentModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body" v-if="currentBooking">
          <!-- Booking Summary -->
          <div class="payment-section">
            <div class="section-title">
              <i class="fas fa-receipt"></i>
              Booking Summary
            </div>
            <div class="summary-grid">
              <div class="summary-item">
                <span class="label">Booking</span>
                <span class="value">{{ currentBooking?.booking_reference }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Customer</span>
                <span class="value">{{ userName }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Vehicle</span>
                <span class="value">{{ currentBooking?.vehicle_name }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Total Amount</span>
                <span class="value amount">₱{{ formatPrice(currentBooking?.total_amount) }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Method -->
          <div class="payment-section">
            <div class="section-title">
              <i class="fas fa-credit-card"></i>
              Payment Method
            </div>
            <div class="payment-methods">
              <label class="method-option">
                <input type="radio" value="gcash" v-model="paymentForm.payment_method">
                <div class="method-card">
                  <i class="fas fa-mobile-alt"></i>
                  <span>GCash</span>
                </div>
              </label>
              <label class="method-option">
                <input type="radio" value="paymaya" v-model="paymentForm.payment_method">
                <div class="method-card">
                  <i class="fas fa-wallet"></i>
                  <span>PayMaya</span>
                </div>
              </label>
              <label class="method-option">
                <input type="radio" value="grab_pay" v-model="paymentForm.payment_method">
                <div class="method-card">
                  <i class="fas fa-car"></i>
                  <span>GrabPay</span>
                </div>
              </label>
            </div>
          </div>

          <!-- Payment Type -->
          <div class="payment-section">
            <div class="section-title">
              <i class="fas fa-money-check-alt"></i>
              Payment Type
            </div>
            <div class="payment-types">
              <label class="type-option">
                <input type="radio" value="full" v-model="paymentForm.payment_type">
                <div class="type-card">
                  <span class="type-label">Full Payment</span>
                  <span class="type-amount">₱{{ formatPrice(currentBooking?.total_amount) }}</span>
                </div>
              </label>
              <label class="type-option">
                <input type="radio" value="downpayment" v-model="paymentForm.payment_type">
                <div class="type-card">
                  <span class="type-label">Down Payment (30%)</span>
                  <span class="type-amount">₱{{ formatPrice((currentBooking?.total_amount || 0) * 0.3) }}</span>
                </div>
              </label>
            </div>
          </div>

          <!-- Payment Info -->
          <div class="payment-info">
            <i class="fas fa-info-circle"></i>
            <p>You will be redirected to PayMongo's secure payment page to complete your {{ paymentForm.payment_type === 'full' ? 'full' : 'down' }} payment using {{ paymentForm.payment_method.toUpperCase().replace('_', ' ') }}.</p>
          </div>

          <!-- Actions -->
          <div class="modal-actions">
            <button class="btn-secondary" @click="closePaymentModal" :disabled="processingPayment">
              <i class="fas fa-times"></i>
              Cancel
            </button>
            <button 
              class="btn-pay" 
              @click="processPayment"
              :disabled="processingPayment"
            >
              <i class="fas fa-spinner fa-spin" v-if="processingPayment"></i>
              <i class="fas fa-lock" v-else></i>
              {{ processingPayment ? 'Processing...' : 'Proceed to Payment' }}
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
import LicenseVerification from '@/components/LicenseVerification.vue'

export default {
  name: 'UserDashboard',
  components: {
    LicenseVerification
  },
  setup() {
    const router = useRouter()
    const apiStore = useApiStore()
    const loading = ref(false)
    const stats = ref({
      completedBookings: 0,
      activeBookings: 0,
      totalSpent: '0.00',
      favoriteVehicles: 0
    })
    const recentBookings = ref([])
    
    // Payment modal state
    const showPaymentModal = ref(false)
    const currentBooking = ref(null)
    const paymentForm = ref({
      payment_type: 'full',
      payment_method: 'gcash'
    })
    const processingPayment = ref(false)

    // Get user info from localStorage
    const userInfo = computed(() => {
      const stored = localStorage.getItem('user_info')
      return stored ? JSON.parse(stored) : {}
    })

    const userName = computed(() => {
      const user = userInfo.value
      // Try different possible name fields
      if (user.name) return user.name
      if (user.first_name && user.last_name) return `${user.first_name} ${user.last_name}`
      if (user.first_name) return user.first_name
      if (user.email) return user.email.split('@')[0]
      return 'User'
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
        const completedBookings = userBookings.filter(b => b.status === 'completed').length
        const activeBookings = userBookings.filter(b => 
          b.status === 'confirmed' || b.status === 'active' || b.status === 'ongoing' || b.status === 'returned'
        ).length
        const totalSpent = userBookings
          .filter(b => b.status === 'completed')
          .reduce((sum, b) => sum + parseFloat(b.total_amount || 0), 0)
        
        stats.value = {
          completedBookings,
          activeBookings,
          totalSpent: totalSpent.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
          favoriteVehicles: Math.min(completedBookings, 5) // Approximate based on completed bookings
        }
      } catch (error) {
        console.error('Error loading user stats:', error)
        stats.value = {
          completedBookings: 0,
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

    const logout = () => {
      // Clear authentication data
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_info')
      
      // Redirect to login
      router.push({ name: 'login' })
    }

    const openPaymentModal = (booking) => {
      currentBooking.value = booking
      paymentForm.value = {
        payment_type: 'full',
        payment_method: 'gcash'
      }
      showPaymentModal.value = true
    }

    const closePaymentModal = () => {
      showPaymentModal.value = false
      currentBooking.value = null
      paymentForm.value = {
        payment_type: 'full',
        payment_method: 'gcash'
      }
    }

    const formatPrice = (price) => {
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
      }).format(price)
    }

    const processPayment = async () => {
      if (!currentBooking.value) {
        alert('No booking selected')
        return
      }

      if (!paymentForm.value.payment_method) {
        alert('Please select a payment method')
        return
      }

      processingPayment.value = true

      try {
        const response = await api.post('/payments/booking', {
          booking_id: currentBooking.value.id,
          payment_type: paymentForm.value.payment_type,
          payment_method: paymentForm.value.payment_method
        })

        console.log('Payment response:', response)

        // Handle different response structures
        const responseData = response.data || response
        const status = responseData.status
        const redirectUrl = responseData.data?.redirect_url || responseData.redirect_url || responseData.checkout_url

        if (status === 'success' && redirectUrl) {
          // Redirect to PayMongo payment page
          window.location.href = redirectUrl
        } else {
          throw new Error(responseData.message || 'Failed to create payment')
        }
      } catch (error) {
        console.error('Payment error:', error)
        alert(error.response?.data?.message || error.message || 'Failed to process payment. Please try again.')
        processingPayment.value = false
      }
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
      userName,
      formatDate,
      logout,
      getPlaceholderImage,
      handleImageError,
      showPaymentModal,
      currentBooking,
      paymentForm,
      processingPayment,
      openPaymentModal,
      processPayment,
      closePaymentModal,
      formatPrice
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
  padding: 1.25rem 2rem;
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

/* Warning state for booking limit */
.stat-card.warning {
  border: 2px solid #f59e0b;
  background: linear-gradient(to bottom, #fff, #fef3c7);
}

.stat-card.warning .stat-icon {
  background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%);
}

.limit-warning {
  display: block;
  color: #dc2626;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  font-weight: 500;
}

.limit-warning i {
  margin-right: 0.25rem;
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

/* Payment Modal */
.payment-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

/* Payment Modal Styles */
.payment-modal {
  max-width: 700px;
}

.payment-section {
  margin-bottom: 1.5rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #2d3748;
  font-size: 1rem;
}

.section-title i {
  color: #667eea;
}

.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.summary-item {
  background: #f7fafc;
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.summary-item .label {
  font-size: 0.875rem;
  color: #718096;
  font-weight: 500;
}

.summary-item .value {
  font-weight: 600;
  color: #2d3748;
}

.summary-item .value.amount {
  color: #667eea;
  font-size: 1.5rem;
}

.payment-methods,
.payment-types {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.method-option,
.type-option {
  cursor: pointer;
}

.method-option input,
.type-option input {
  display: none;
}

.method-card,
.type-card {
  background: #f7fafc;
  padding: 1.25rem;
  border-radius: 8px;
  border: 2px solid #e2e8f0;
  text-align: center;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.method-option input:checked + .method-card,
.type-option input:checked + .type-card {
  border-color: #667eea;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
}

.method-card:hover,
.type-card:hover {
  border-color: #667eea;
  transform: translateY(-2px);
}

.method-card i {
  font-size: 1.5rem;
  color: #667eea;
}

.method-card span {
  font-weight: 600;
  color: #2d3748;
  font-size: 0.875rem;
}

.type-card {
  padding: 1rem;
}

.type-label {
  font-size: 0.875rem;
  color: #718096;
  font-weight: 500;
}

.type-amount {
  font-weight: 600;
  color: #667eea;
  font-size: 1.125rem;
}

.payment-info {
  background: #ebf8ff;
  border: 1px solid #bee3f8;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.payment-info i {
  color: #3182ce;
  margin-top: 0.25rem;
}

.payment-info p {
  margin: 0;
  color: #2c5282;
  font-size: 0.875rem;
  line-height: 1.5;
}

.btn-pay {
  flex: 1;
  padding: 0.875rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.btn-pay:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-pay:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .payment-modal {
    max-width: 100%;
  }
  
  .summary-grid {
    grid-template-columns: 1fr;
  }
  
  .payment-methods,
  .payment-types {
    grid-template-columns: 1fr;
  }
}
</style>

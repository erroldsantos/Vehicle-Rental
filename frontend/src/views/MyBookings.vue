<template>
  <div class="my-bookings">
    <!-- Header -->
    <header class="page-header">
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

    <!-- Page Title -->
    <div class="page-title-section">
      <button class="btn-back" @click="$router.push('/user-dashboard')">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
      </button>
      <h1>My Bookings</h1>
      <p>View and manage your vehicle bookings</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading your bookings...</p>
    </div>

    <!-- Bookings List -->
    <div v-else-if="bookings.length > 0" class="bookings-container">
      <div class="bookings-stats">
        <div class="stat-item">
          <span class="stat-value">{{ stats.total }}</span>
          <span class="stat-label">Total Bookings</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ stats.active }}</span>
          <span class="stat-label">Active</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ stats.completed }}</span>
          <span class="stat-label">Completed</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ stats.cancelled }}</span>
          <span class="stat-label">Cancelled</span>
        </div>
      </div>

      <div class="bookings-list">
        <div 
          v-for="booking in bookings" 
          :key="booking.id"
          class="booking-card"
        >
          <div class="booking-header">
            <div class="booking-ref">
              <i class="fas fa-hashtag"></i>
              {{ booking.booking_reference }}
            </div>
            <span :class="['status-badge', booking.status]">
              {{ getStatusText(booking.status) }}
            </span>
          </div>

          <div class="booking-content">
            <div class="vehicle-section">
              <img 
                :src="booking.vehicle_image || getPlaceholderImage(booking.vehicle_name)" 
                :alt="booking.vehicle_name"
                class="vehicle-image"
                @error="handleImageError"
              >
              <div class="vehicle-details">
                <h3>{{ booking.vehicle_name }}</h3>
                <p class="vehicle-type">{{ booking.vehicle_type || 'Sedan' }}</p>
              </div>
            </div>

            <div class="booking-details">
              <div class="detail-row">
                <div class="detail-item">
                  <i class="fas fa-calendar-alt"></i>
                  <div>
                    <span class="label">Pickup Date</span>
                    <span class="value">{{ formatDate(booking.start_date) }}</span>
                  </div>
                </div>
                <div class="detail-item">
                  <i class="fas fa-calendar-alt"></i>
                  <div>
                    <span class="label">Return Date</span>
                    <span class="value">{{ formatDate(booking.end_date) }}</span>
                  </div>
                </div>
              </div>

              <div class="detail-row">
                <div class="detail-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <div>
                    <span class="label">Pickup Location</span>
                    <span class="value">{{ booking.pickup_location || 'Not specified' }}</span>
                  </div>
                </div>
                <div class="detail-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <div>
                    <span class="label">Drop-off Location</span>
                    <span class="value">{{ booking.dropoff_location || 'Not specified' }}</span>
                  </div>
                </div>
              </div>

              <div class="detail-row">
                <div class="detail-item">
                  <i class="fas fa-clock"></i>
                  <div>
                    <span class="label">Duration</span>
                    <span class="value">{{ calculateDuration(booking.start_date, booking.end_date) }} days</span>
                  </div>
                </div>
                <div class="detail-item">
                  <i class="fas fa-dollar-sign"></i>
                  <div>
                    <span class="label">Total Amount</span>
                    <span class="value amount">₱{{ formatAmount(booking.total_amount) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="booking-actions">
            <button 
              v-if="booking.status === 'pending'"
              class="btn-cancel"
              @click="cancelBooking(booking)"
            >
              <i class="fas fa-times"></i>
              Cancel Booking
            </button>
            <button 
              v-if="booking.status === 'pending'"
              class="btn-pay-now"
              @click="initiatePayment(booking)"
            >
              <i class="fas fa-credit-card"></i>
              Pay Now
            </button>
            <button class="btn-view" @click="viewBookingDetails(booking)">
              <i class="fas fa-eye"></i>
              View Details
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <i class="fas fa-calendar-alt"></i>
      <h3>No bookings yet</h3>
      <p>Start by browsing our available vehicles</p>
      <button class="btn-primary" @click="$router.push('/browse-vehicles')">
        <i class="fas fa-car"></i>
        Browse Vehicles
      </button>
    </div>

    <!-- Booking Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="closeDetailsModal">
      <div class="modal-content details-modal" @click.stop>
        <div class="modal-header">
          <h2>Booking Details</h2>
          <button class="btn-close" @click="closeDetailsModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body" v-if="selectedBooking">
          <!-- Vehicle Info -->
          <div class="details-section">
            <div class="section-title">
              <i class="fas fa-car"></i>
              Vehicle Information
            </div>
            <div class="details-grid">
              <div class="detail-card">
                <img 
                  :src="selectedBooking.vehicle_image || getPlaceholderImage(selectedBooking.vehicle_name)" 
                  :alt="selectedBooking.vehicle_name"
                  class="details-vehicle-image"
                  @error="handleImageError"
                >
              </div>
              <div class="detail-card">
                <label>Vehicle</label>
                <p>{{ selectedBooking.vehicle_name }}</p>
              </div>
              <div class="detail-card">
                <label>Type</label>
                <p>{{ selectedBooking.vehicle_type }}</p>
              </div>
            </div>
          </div>

          <!-- Booking Info -->
          <div class="details-section">
            <div class="section-title">
              <i class="fas fa-calendar-check"></i>
              Booking Information
            </div>
            <div class="details-grid">
              <div class="detail-card">
                <label>Booking Reference</label>
                <p class="reference">{{ selectedBooking.booking_reference }}</p>
              </div>
              <div class="detail-card">
                <label>Status</label>
                <p>
                  <span :class="['status-badge', selectedBooking.status]">
                    {{ getStatusText(selectedBooking.status) }}
                  </span>
                </p>
              </div>
              <div class="detail-card">
                <label>Pickup Date</label>
                <p>{{ formatDate(selectedBooking.start_date) }}</p>
              </div>
              <div class="detail-card">
                <label>Return Date</label>
                <p>{{ formatDate(selectedBooking.end_date) }}</p>
              </div>
              <div class="detail-card">
                <label>Duration</label>
                <p>{{ calculateDuration(selectedBooking.start_date, selectedBooking.end_date) }} days</p>
              </div>
              <div class="detail-card">
                <label>Total Amount</label>
                <p class="amount">₱{{ formatAmount(selectedBooking.total_amount) }}</p>
              </div>
            </div>
          </div>

          <!-- Location Info -->
          <div class="details-section">
            <div class="section-title">
              <i class="fas fa-map-marker-alt"></i>
              Location Details
            </div>
            <div class="details-grid">
              <div class="detail-card">
                <label>Pickup Location</label>
                <p>{{ selectedBooking.pickup_location || 'Not specified' }}</p>
              </div>
              <div class="detail-card">
                <label>Drop-off Location</label>
                <p>{{ selectedBooking.dropoff_location || 'Not specified' }}</p>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="modal-actions">
            <button 
              v-if="selectedBooking.status === 'pending'"
              class="btn-cancel"
              @click="cancelBookingFromModal(selectedBooking)"
            >
              <i class="fas fa-times"></i>
              Cancel This Booking
            </button>
            <button class="btn-secondary" @click="closeDetailsModal">
              <i class="fas fa-check"></i>
              Close
            </button>
          </div>
        </div>
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

        <div class="modal-body" v-if="selectedBooking">
          <!-- Booking Summary -->
          <div class="payment-section">
            <div class="section-title">
              <i class="fas fa-receipt"></i>
              Booking Summary
            </div>
            <div class="summary-grid">
              <div class="summary-item">
                <span class="label">Booking</span>
                <span class="value">{{ selectedBooking.booking_reference }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Customer</span>
                <span class="value">{{ selectedBooking.first_name }} {{ selectedBooking.last_name }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Vehicle</span>
                <span class="value">{{ selectedBooking.vehicle_name }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Total Amount</span>
                <span class="value amount">₱{{ formatAmount(selectedBooking.total_amount) }}</span>
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
                <input type="radio" value="gcash" v-model="paymentMethod">
                <div class="method-card">
                  <i class="fas fa-mobile-alt"></i>
                  <span>GCash</span>
                </div>
              </label>
              <label class="method-option">
                <input type="radio" value="paymaya" v-model="paymentMethod">
                <div class="method-card">
                  <i class="fas fa-wallet"></i>
                  <span>PayMaya</span>
                </div>
              </label>
              <label class="method-option">
                <input type="radio" value="grab_pay" v-model="paymentMethod">
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
                <input type="radio" value="full" v-model="paymentType">
                <div class="type-card">
                  <span class="type-label">Full Payment</span>
                  <span class="type-amount">₱{{ formatAmount(selectedBooking.total_amount) }}</span>
                </div>
              </label>
              <label class="type-option">
                <input type="radio" value="downpayment" v-model="paymentType">
                <div class="type-card">
                  <span class="type-label">Down Payment (30%)</span>
                  <span class="type-amount">₱{{ formatAmount(selectedBooking.total_amount * 0.3) }}</span>
                </div>
              </label>
            </div>
          </div>

          <!-- Payment Info -->
          <div class="payment-info">
            <i class="fas fa-info-circle"></i>
            <p>You will be redirected to PayMongo's secure payment page to complete your {{ paymentType === 'full' ? 'full' : 'down' }} payment using {{ paymentMethod.toUpperCase().replace('_', ' ') }}.</p>
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'

export default {
  name: 'MyBookings',
  setup() {
    const router = useRouter()
    const apiStore = useApiStore()
    
    const loading = ref(false)
    const bookings = ref([])
    const showDetailsModal = ref(false)
    const showPaymentModal = ref(false)
    const selectedBooking = ref(null)
    const paymentMethod = ref('gcash')
    const paymentType = ref('full')
    const processingPayment = ref(false)

    const userInfo = computed(() => {
      const stored = localStorage.getItem('user_info')
      return stored ? JSON.parse(stored) : {}
    })

    const userName = computed(() => {
      return userInfo.value.name || 'User'
    })

    const stats = computed(() => {
      return {
        total: bookings.value.length,
        active: bookings.value.filter(b => b.status === 'confirmed' || b.status === 'pending').length,
        completed: bookings.value.filter(b => b.status === 'completed').length,
        cancelled: bookings.value.filter(b => b.status === 'cancelled').length
      }
    })

    const loadBookings = async () => {
      loading.value = true
      try {
        const userId = userInfo.value.id
        if (!userId) {
          router.push({ name: 'login' })
          return
        }

        const data = await apiStore.get(`/bookings?user_id=${userId}`)
        const bookingsList = data.bookings || data.data?.bookings || []
        
        bookings.value = bookingsList.map(booking => ({
          ...booking,
          vehicle_name: `${booking.brand || ''} ${booking.model || ''}`.trim() || 'Unknown Vehicle',
          vehicle_type: 'Sedan', // Default since v.type column doesn't exist
          vehicle_image: booking.vehicle_image ? `/images/vehicles/${booking.vehicle_image}` : null
        }))
      } catch (error) {
        console.error('Error loading bookings:', error)
        
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const formatAmount = (amount) => {
      return parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    }

    const calculateDuration = (startDate, endDate) => {
      const start = new Date(startDate)
      const end = new Date(endDate)
      const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24))
      return days > 0 ? days : 0
    }

    const getStatusText = (status) => {
      const statusMap = {
        'pending': 'Pending',
        'confirmed': 'Confirmed',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
      }
      return statusMap[status] || status
    }

    const cancelBooking = async (booking) => {
      try {
        await apiStore.put(`/bookings/${booking.id}`, { status: 'cancelled' })
        
        await loadBookings()
      } catch (error) {
        console.error('Error cancelling booking:', error)
        
      }
    }

    const viewBookingDetails = (booking) => {
      selectedBooking.value = booking
      showDetailsModal.value = true
    }

    const closeDetailsModal = () => {
      showDetailsModal.value = false
      selectedBooking.value = null
    }

    const cancelBookingFromModal = async (booking) => {
      try {
        await apiStore.put(`/bookings/${booking.id}`, { status: 'cancelled' })
        
        closeDetailsModal()
        await loadBookings()
      } catch (error) {
        console.error('Error cancelling booking:', error)
        
      }
    }

    const getPlaceholderImage = (vehicleName) => {
      const name = (vehicleName || '').toLowerCase()
      if (name.includes('suv')) {
        return '/images/vehicles/suv-placeholder.jpg'
      } else if (name.includes('truck') || name.includes('van')) {
        return '/images/vehicles/truck-placeholder.jpg'
      }
      return '/images/vehicles/car-placeholder.jpg'
    }

    const handleImageError = (event) => {
      event.target.src = '/images/vehicles/car-placeholder.jpg'
    }

    const initiatePayment = (booking) => {
      // Open payment details modal instead of redirect
      selectedBooking.value = booking
      showPaymentModal.value = true
    }

    const logout = () => {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_info')
      router.push({ name: 'login' })
    }

    const closePaymentModal = () => {
      showPaymentModal.value = false
      selectedBooking.value = null
      paymentMethod.value = 'gcash'
      paymentType.value = 'full'
    }

    const processPayment = async () => {
      if (!selectedBooking.value) return
      
      processingPayment.value = true
      try {
        const response = await apiStore.post('/payments/booking', {
          booking_id: selectedBooking.value.id,
          payment_type: paymentType.value,
          payment_method: paymentMethod.value
        })

        if (response.status === 'success' && response.data.redirect_url) {
          // Redirect to PayMongo checkout
          window.location.href = response.data.redirect_url
        } else {
          console.error('Payment creation failed:', response)
        }
      } catch (error) {
        console.error('Error creating payment:', error)
      } finally {
        processingPayment.value = false
      }
    }

    onMounted(() => {
      loadBookings()
    })

    return {
      loading,
      bookings,
      showDetailsModal,
      showPaymentModal,
      selectedBooking,
      paymentMethod,
      paymentType,
      processingPayment,
      userName,
      stats,
      formatDate,
      formatAmount,
      calculateDuration,
      getStatusText,
      cancelBooking,
      viewBookingDetails,
      closeDetailsModal,
      closePaymentModal,
      cancelBookingFromModal,
      getPlaceholderImage,
      handleImageError,
      initiatePayment,
      processPayment,
      logout
    }
  }
}
</script>

<style scoped>
.my-bookings {
  min-height: 100vh;
  background: #f8fafc;
}

/* Header */
.page-header {
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  max-width: 1400px;
  width: 100%;
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

/* Page Content */
.page-title-section,
.bookings-container,
.loading-state,
.empty-state {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.page-title-section {
  padding-bottom: 1rem;
}

.btn-back {
  background: transparent;
  border: none;
  color: #667eea;
  font-weight: 500;
  cursor: pointer;
  padding: 0.5rem;
  margin-bottom: 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: color 0.2s ease;
}

.btn-back:hover {
  color: #764ba2;
}

.page-title-section h1 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
  font-size: 2rem;
  font-weight: 600;
}

.page-title-section p {
  margin: 0;
  color: #718096;
  font-size: 1.125rem;
}

/* Stats */
.bookings-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-item {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-value {
  font-size: 2rem;
  font-weight: 600;
  color: #667eea;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: #718096;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Loading and Empty States */
.loading-state,
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #718096;
}

.loading-state i,
.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.loading-state i {
  color: #667eea;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #4a5568;
}

.empty-state p {
  margin: 0 0 1.5rem 0;
}

/* Bookings List */
.bookings-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.booking-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: #f7fafc;
  border-bottom: 1px solid #e2e8f0;
}

.booking-ref {
  font-weight: 600;
  color: #2d3748;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
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

.status-badge.cancelled {
  background: #e2e8f0;
  color: #718096;
}

.booking-content {
  padding: 1.5rem;
}

.vehicle-section {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.vehicle-image {
  width: 120px;
  height: 80px;
  border-radius: 8px;
  object-fit: cover;
  background: #f7fafc;
}

.vehicle-details h3 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-size: 1.25rem;
}

.vehicle-type {
  margin: 0;
  color: #718096;
  font-size: 0.875rem;
}

.booking-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  gap: 0.75rem;
}

.detail-item i {
  color: #667eea;
  margin-top: 0.25rem;
}

.detail-item .label {
  display: block;
  color: #718096;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.detail-item .value {
  display: block;
  color: #2d3748;
  font-weight: 500;
}

.detail-item .value.amount {
  color: #667eea;
  font-size: 1.125rem;
  font-weight: 600;
}

.booking-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  background: #f7fafc;
  border-top: 1px solid #e2e8f0;
}

.btn-cancel,
.btn-pay-now,
.btn-view {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
}

.btn-cancel {
  background: #fed7d7;
  color: #c53030;
}

.btn-cancel:hover {
  background: #fc8181;
  color: white;
}

.btn-pay-now {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.btn-pay-now:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-view {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-view:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.details-modal {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  background: white;
  z-index: 10;
}

.modal-header h2 {
  margin: 0;
  color: #2d3748;
  font-size: 1.5rem;
}

.btn-close {
  background: #f7fafc;
  border: none;
  padding: 0.5rem;
  border-radius: 8px;
  color: #718096;
  cursor: pointer;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #2d3748;
}

.modal-body {
  padding: 1.5rem;
}

.details-section {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e2e8f0;
}

.section-title i {
  color: #667eea;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.detail-card {
  background: #f7fafc;
  padding: 1rem;
  border-radius: 8px;
}

.detail-card label {
  display: block;
  font-size: 0.875rem;
  color: #718096;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.detail-card p {
  margin: 0;
  color: #2d3748;
  font-weight: 500;
}

.detail-card p.reference {
  color: #667eea;
  font-weight: 600;
  font-family: monospace;
}

.detail-card p.amount {
  color: #667eea;
  font-size: 1.25rem;
  font-weight: 600;
}

.details-vehicle-image {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 8px;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.btn-secondary {
  flex: 1;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
  background: #f7fafc;
  color: #4a5568;
}

.btn-secondary:hover {
  background: #e2e8f0;
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

  .page-title-section,
  .bookings-container {
    padding: 1rem;
  }

  .page-title-section h1 {
    font-size: 1.5rem;
  }

  .bookings-stats {
    grid-template-columns: 1fr 1fr;
  }

  .detail-row {
    grid-template-columns: 1fr;
  }

  .booking-actions {
    flex-direction: column;
  }

  .btn-cancel,
  .btn-view {
    width: 100%;
    justify-content: center;
  }

  .modal-overlay {
    padding: 1rem;
  }

  .modal-content {
    max-height: 85vh;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .modal-actions {
    flex-direction: column;
  }

  .btn-secondary,
  .modal-actions .btn-cancel {
    width: 100%;
  }
}

/* Payment Modal Styles */
.payment-modal {
  max-width: 700px;
}

.payment-section {
  margin-bottom: 1.5rem;
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
  font-size: 1rem;
}

.btn-pay:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-pay:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>

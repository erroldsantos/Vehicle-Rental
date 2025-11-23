<template>
  <div class="payment-form-page">
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

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading booking details...</p>
    </div>

    <!-- Payment Form -->
    <div v-else-if="booking" class="payment-container">
      <div class="payment-wrapper">
        <button class="btn-back" @click="$router.push('/my-bookings')">
          <i class="fas fa-arrow-left"></i>
          Back to My Bookings
        </button>

        <div class="payment-card">
          <div class="payment-header">
            <div class="header-icon">
              <i class="fas fa-credit-card"></i>
            </div>
            <h1>Complete Payment</h1>
            <p>Secure your booking by completing the payment</p>
          </div>

          <!-- Booking Summary -->
          <div class="booking-summary-section">
            <h3><i class="fas fa-file-invoice"></i> Booking Summary</h3>
            <div class="summary-content">
              <div class="summary-row">
                <span class="label">Booking Reference:</span>
                <span class="value reference">{{ booking.booking_reference }}</span>
              </div>
              <div class="summary-row">
                <span class="label">Vehicle:</span>
                <span class="value">{{ booking.vehicle_name }}</span>
              </div>
              <div class="summary-row">
                <span class="label">Pickup:</span>
                <span class="value">{{ formatDate(booking.start_date) }}</span>
              </div>
              <div class="summary-row">
                <span class="label">Return:</span>
                <span class="value">{{ formatDate(booking.end_date) }}</span>
              </div>
              <div class="summary-row total">
                <span class="label">Total Amount:</span>
                <span class="value">{{ formatPrice(booking.total_amount) }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Form -->
          <form @submit.prevent="processPayment" class="payment-form">
            <!-- Payment Type -->
            <div class="form-section">
              <h4><i class="fas fa-money-bill-wave"></i> Payment Type</h4>
              <div class="radio-group">
                <div class="radio-option">
                  <input 
                    type="radio" 
                    id="full" 
                    value="full" 
                    v-model="paymentForm.payment_type"
                  >
                  <label for="full" class="radio-label">
                    <div class="radio-label-title">Full Payment</div>
                    <div class="radio-label-desc">
                      Pay 100% now ({{ formatPrice(booking.total_amount) }})
                    </div>
                  </label>
                </div>
                <div class="radio-option">
                  <input 
                    type="radio" 
                    id="downpayment" 
                    value="downpayment" 
                    v-model="paymentForm.payment_type"
                  >
                  <label for="downpayment" class="radio-label">
                    <div class="radio-label-title">50% Downpayment</div>
                    <div class="radio-label-desc">
                      Pay {{ formatPrice(booking.total_amount * 0.5) }} now, rest later
                    </div>
                  </label>
                </div>
              </div>
            </div>

            <!-- Payment Method -->
            <div class="form-section">
              <h4><i class="fas fa-wallet"></i> Payment Method</h4>
              <div class="payment-methods">
                <div class="payment-method-card">
                  <input 
                    type="radio" 
                    id="gcash" 
                    value="gcash" 
                    v-model="paymentForm.payment_method"
                  >
                  <label for="gcash" class="payment-method-label">
                    <div class="payment-method-icon">💳</div>
                    <div class="payment-method-name">GCash</div>
                  </label>
                </div>
                <div class="payment-method-card">
                  <input 
                    type="radio" 
                    id="paymaya" 
                    value="paymaya" 
                    v-model="paymentForm.payment_method"
                  >
                  <label for="paymaya" class="payment-method-label">
                    <div class="payment-method-icon">💰</div>
                    <div class="payment-method-name">PayMaya</div>
                  </label>
                </div>
                <div class="payment-method-card">
                  <input 
                    type="radio" 
                    id="grab_pay" 
                    value="grab_pay" 
                    v-model="paymentForm.payment_method"
                  >
                  <label for="grab_pay" class="payment-method-label">
                    <div class="payment-method-icon">🚗</div>
                    <div class="payment-method-name">GrabPay</div>
                  </label>
                </div>
              </div>
            </div>

            <!-- Info Notice -->
            <div class="info-notice">
              <i class="fas fa-info-circle"></i>
              <p>You will be redirected to a secure payment page. Your booking will be automatically confirmed once payment is successful.</p>
            </div>

            <!-- Actions -->
            <div class="form-actions">
              <button 
                type="button" 
                class="btn-cancel" 
                @click="$router.push('/my-bookings')"
                :disabled="processingPayment"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                class="btn-pay" 
                :disabled="processingPayment"
              >
                <i v-if="processingPayment" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-lock"></i>
                {{ processingPayment ? 'Processing...' : 'Proceed to Payment' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="error-state">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>Booking Not Found</h3>
      <p>The booking you're trying to pay for could not be found.</p>
      <button class="btn-primary" @click="$router.push('/my-bookings')">
        Back to My Bookings
      </button>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'

export default {
  name: 'PaymentForm',
  setup() {
    const router = useRouter()
    const route = useRoute()
    const api = useApiStore()
    
    const loading = ref(true)
    const booking = ref(null)
    const processingPayment = ref(false)
    const paymentForm = ref({
      payment_type: 'full',
      payment_method: 'gcash'
    })

    const userInfo = computed(() => {
      const stored = localStorage.getItem('user_info')
      return stored ? JSON.parse(stored) : {}
    })

    const userName = computed(() => {
      return userInfo.value.name || 'User'
    })

    const loadBookingDetails = async () => {
      loading.value = true
      try {
        const bookingId = route.params.bookingId
        if (!bookingId) {
          throw new Error('No booking ID provided')
        }

        const response = await api.get(`/bookings/${bookingId}`)
        const bookingData = response.data || response.booking || response
        
        // Check if booking belongs to current user
        if (bookingData.user_id !== userInfo.value.id) {
          throw new Error('Unauthorized access to booking')
        }

        // Check if booking is in pending status
        if (bookingData.status !== 'pending') {
          alert('This booking cannot be paid. Status: ' + bookingData.status)
          router.push('/my-bookings')
          return
        }

        booking.value = {
          ...bookingData,
          vehicle_name: `${bookingData.brand || ''} ${bookingData.model || ''}`.trim() || 'Unknown Vehicle'
        }
      } catch (error) {
        console.error('Error loading booking:', error)
        booking.value = null
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

    const formatPrice = (price) => {
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
      }).format(price)
    }

    const processPayment = async () => {
      if (!booking.value) {
        alert('No booking selected')
        return
      }

      if (!paymentForm.value.payment_method) {
        alert('Please select a payment method')
        return
      }

      processingPayment.value = true

      try {
        console.log('Attempting payment for booking:', booking.value.id)
        console.log('Payment data:', {
          booking_id: booking.value.id,
          payment_type: paymentForm.value.payment_type,
          payment_method: paymentForm.value.payment_method
        })

        const response = await api.post('/payments/booking', {
          booking_id: booking.value.id,
          payment_type: paymentForm.value.payment_type,
          payment_method: paymentForm.value.payment_method
        })

        console.log('Payment response:', response)

        // api.post already returns response.data, so response is the data itself
        const status = response.status
        const redirectUrl = response.data?.redirect_url || response.redirect_url || response.checkout_url

        console.log('Parsed response:', {
          status,
          redirectUrl,
          fullResponse: response
        })

        if (status === 'success' && redirectUrl) {
          // Redirect to PayMongo payment page
          console.log('Redirecting to:', redirectUrl)
          window.location.href = redirectUrl
        } else {
          console.error('Missing redirect URL. Response:', response)
          throw new Error(response.message || response.error || 'Failed to create payment - no redirect URL')
        }
      } catch (error) {
        console.error('Payment error:', error)
        console.error('Error details:', {
          message: error.message,
          response: error.response,
          responseData: error.response?.data,
          status: error.response?.status
        })
        
        let errorMessage = 'Failed to process payment. Please try again.'
        
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.message) {
          errorMessage = error.message
        }

        alert(errorMessage)
      } finally {
        processingPayment.value = false
      }
    }

    const logout = () => {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_info')
      router.push({ name: 'login' })
    }

    onMounted(() => {
      loadBookingDetails()
    })

    return {
      loading,
      booking,
      processingPayment,
      paymentForm,
      userName,
      formatDate,
      formatPrice,
      processPayment,
      logout
    }
  }
}
</script>

<style scoped>
.payment-form-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Header */
.page-header {
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

/* Payment Container */
.payment-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 3rem 2rem;
}

.payment-wrapper {
  width: 100%;
}

.btn-back {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  font-weight: 500;
  cursor: pointer;
  padding: 0.75rem 1.25rem;
  margin-bottom: 2rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.3);
}

.payment-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

.payment-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 3rem 2rem;
  text-align: center;
}

.header-icon {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  font-size: 2.5rem;
}

.payment-header h1 {
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  font-weight: 600;
}

.payment-header p {
  margin: 0;
  opacity: 0.9;
  font-size: 1.125rem;
}

/* Booking Summary */
.booking-summary-section {
  background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%);
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.booking-summary-section h3 {
  margin: 0 0 1.5rem 0;
  color: #2d3748;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.summary-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #dee2e6;
}

.summary-row:last-child {
  border-bottom: none;
}

.summary-row.total {
  margin-top: 1rem;
  padding-top: 1.5rem;
  border-top: 2px solid #667eea;
  font-size: 1.25rem;
  font-weight: 600;
}

.summary-row .label {
  color: #6c757d;
  font-weight: 500;
}

.summary-row .value {
  color: #2d3748;
  font-weight: 600;
}

.summary-row.total .value {
  color: #667eea;
  font-size: 1.5rem;
}

.summary-row .value.reference {
  font-family: monospace;
  color: #667eea;
}

/* Payment Form */
.payment-form {
  padding: 2rem;
}

.form-section {
  margin-bottom: 2rem;
}

.form-section h4 {
  margin: 0 0 1.25rem 0;
  color: #2d3748;
  font-size: 1.125rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-section h4 i {
  color: #667eea;
}

.radio-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.radio-option {
  position: relative;
}

.radio-option input[type="radio"] {
  display: none;
}

.radio-label {
  display: block;
  padding: 1.25rem;
  border: 2px solid #dee2e6;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
  height: 100%;
}

.radio-option input[type="radio"]:checked + .radio-label {
  border-color: #667eea;
  background: linear-gradient(135deg, #f0f4ff 0%, #e9ecff 100%);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.radio-label:hover {
  border-color: #667eea;
  transform: translateY(-2px);
}

.radio-label-title {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.5rem;
  font-size: 1.125rem;
}

.radio-option input[type="radio"]:checked + .radio-label .radio-label-title {
  color: #667eea;
}

.radio-label-desc {
  font-size: 0.875rem;
  color: #6c757d;
}

.payment-methods {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.payment-method-card {
  position: relative;
}

.payment-method-card input[type="radio"] {
  display: none;
}

.payment-method-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem 1rem;
  border: 2px solid #dee2e6;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
  height: 100%;
}

.payment-method-card input[type="radio"]:checked + .payment-method-label {
  border-color: #667eea;
  background: linear-gradient(135deg, #f0f4ff 0%, #e9ecff 100%);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.payment-method-label:hover {
  border-color: #667eea;
  transform: translateY(-4px);
}

.payment-method-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.payment-method-name {
  font-weight: 600;
  color: #2d3748;
  font-size: 1rem;
}

.payment-method-card input[type="radio"]:checked + .payment-method-label .payment-method-name {
  color: #667eea;
}

/* Info Notice */
.info-notice {
  background: #e6f7ff;
  border: 1px solid #91d5ff;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.info-notice i {
  color: #1890ff;
  font-size: 1.25rem;
  margin-top: 0.125rem;
}

.info-notice p {
  margin: 0;
  color: #0050b3;
  font-size: 0.875rem;
  line-height: 1.5;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 1rem;
  padding-top: 2rem;
  border-top: 1px solid #e2e8f0;
}

.btn-cancel,
.btn-pay {
  flex: 1;
  padding: 1rem 2rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  transition: all 0.2s;
  font-size: 1rem;
}

.btn-cancel {
  background: #f7fafc;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.btn-cancel:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-pay {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-pay:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.btn-cancel:disabled,
.btn-pay:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Loading & Error States */
.loading-container,
.error-state {
  text-align: center;
  padding: 6rem 2rem;
  color: white;
}

.loading-container i,
.error-state i {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  opacity: 0.9;
}

.error-state h3 {
  margin: 0 0 1rem 0;
  font-size: 2rem;
}

.error-state p {
  margin: 0 0 2rem 0;
  font-size: 1.125rem;
  opacity: 0.9;
}

.btn-primary {
  background: white;
  color: #667eea;
  border: none;
  padding: 1rem 2rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
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

  .payment-container {
    padding: 2rem 1rem;
  }

  .payment-header {
    padding: 2rem 1.5rem;
  }

  .payment-header h1 {
    font-size: 1.5rem;
  }

  .header-icon {
    width: 60px;
    height: 60px;
    font-size: 2rem;
  }

  .booking-summary-section,
  .payment-form {
    padding: 1.5rem;
  }

  .radio-group {
    grid-template-columns: 1fr;
  }

  .payment-methods {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn-cancel,
  .btn-pay {
    width: 100%;
  }
}
</style>

<template>
  <div class="payment-result-container">
    <div class="result-card success">
      <div class="icon-wrapper">
        <i class="fas fa-check-circle"></i>
      </div>
      
      <h1>Payment Authorized</h1>
      
      <p class="message">
        Thank you! Your payment for booking <strong>#{{ bookingId }}</strong> was authorized. 
        We are waiting for PayMongo to confirm it.
      </p>

      <div v-if="bookingStatus" class="status-update" :class="statusClass">
        <strong>Current Status:</strong> {{ bookingStatus }}
      </div>

      <p class="info-text">
        This page automatically checks the payment status every 5 seconds and updates once confirmed.
      </p>

      <div class="actions">
        <button @click="goToBookings" class="btn-primary">
          <i class="fas fa-calendar-alt"></i>
          View My Bookings
        </button>
      </div>

      <small class="note">
        If it takes too long, you can safely go back — the booking will update automatically when the webhook arrives.
      </small>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const bookingId = ref(route.params.id)
const bookingStatus = ref(null)
const pollInterval = ref(null)

const statusClass = computed(() => {
  if (!bookingStatus.value) return ''
  const status = bookingStatus.value.toLowerCase()
  if (['confirmed', 'completed', 'active'].includes(status)) {
    return 'status-success'
  }
  return 'status-pending'
})

const checkBookingStatus = async () => {
  try {
    const response = await axios.get(`/api/bookings/${bookingId.value}`)
    if (response.data && response.data.status) {
      bookingStatus.value = response.data.status
      
      // Stop polling if booking is confirmed
      if (['confirmed', 'completed', 'active'].includes(response.data.status.toLowerCase())) {
        clearInterval(pollInterval.value)
      }
    }
  } catch (error) {
    console.error('Error checking booking status:', error)
  }
}

const goToBookings = () => {
  router.push('/my-bookings')
}

onMounted(() => {
  // Initial check
  checkBookingStatus()
  
  // Poll every 5 seconds
  pollInterval.value = setInterval(checkBookingStatus, 5000)
})

onUnmounted(() => {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
  }
})
</script>

<style scoped>
.payment-result-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.result-card {
  max-width: 560px;
  width: 100%;
  background: white;
  padding: 40px;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.result-card.success {
  border-top: 4px solid #047857;
}

.icon-wrapper {
  margin-bottom: 24px;
}

.icon-wrapper i {
  font-size: 64px;
  color: #047857;
}

h1 {
  margin: 0 0 16px;
  font-size: 32px;
  font-weight: 700;
  color: #047857;
}

.message {
  font-size: 16px;
  line-height: 1.6;
  color: #0f172a;
  margin: 0 0 24px;
}

.status-update {
  padding: 16px;
  border-radius: 8px;
  margin: 24px 0;
  font-size: 15px;
}

.status-update.status-success {
  background: #d1fae5;
  color: #047857;
  border: 1px solid #10b981;
}

.status-update.status-pending {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #f59e0b;
}

.info-text {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 32px;
  line-height: 1.5;
}

.actions {
  margin-bottom: 24px;
}

.btn-primary {
  background: #047857;
  color: white;
  border: none;
  padding: 14px 32px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover {
  background: #065f46;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(4, 120, 87, 0.3);
}

.btn-primary:active {
  transform: translateY(0);
}

.note {
  display: block;
  font-size: 13px;
  color: #94a3b8;
  line-height: 1.5;
}

@media (max-width: 640px) {
  .result-card {
    padding: 32px 24px;
  }

  h1 {
    font-size: 26px;
  }

  .icon-wrapper i {
    font-size: 48px;
  }
}
</style>

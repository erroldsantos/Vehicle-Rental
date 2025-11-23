<template>
  <div class="payment-result-container">
    <div class="result-card failed">
      <div class="icon-wrapper">
        <i class="fas fa-times-circle"></i>
      </div>
      
      <h1>Payment Failed</h1>
      
      <p class="message">
        Unfortunately the payment for booking <strong>#{{ bookingId }}</strong> failed or was cancelled.
      </p>

      <p class="info-text">
        You can retry the payment from your bookings page.
      </p>

      <div class="actions">
        <button @click="goToBookings" class="btn-primary">
          <i class="fas fa-redo-alt"></i>
          Go to My Bookings
        </button>
      </div>

      <small class="note">
        If you believe this is an error, please contact support.
      </small>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const bookingId = ref(route.params.id)

const goToBookings = () => {
  router.push('/my-bookings')
}
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

.result-card.failed {
  border-top: 4px solid #dc2626;
}

.icon-wrapper {
  margin-bottom: 24px;
}

.icon-wrapper i {
  font-size: 64px;
  color: #dc2626;
}

h1 {
  margin: 0 0 16px;
  font-size: 32px;
  font-weight: 700;
  color: #dc2626;
}

.message {
  font-size: 16px;
  line-height: 1.6;
  color: #0f172a;
  margin: 0 0 24px;
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
  background: #dc2626;
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
  background: #b91c1c;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
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

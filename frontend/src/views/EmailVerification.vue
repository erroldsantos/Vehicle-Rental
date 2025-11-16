<template>
  <div class="verification-container">
    <div class="verification-card">
      <!-- Success State -->
      <div v-if="status === 'success'" class="state-content">
        <div class="success-icon">
          <svg viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>
        <h1>Email Verified!</h1>
        <p>{{ message || 'Your email has been successfully verified. You can now log in to your account and start using the Vehicle Rental System.' }}</p>
        <router-link to="/login" class="btn btn-primary">
          <i class="fas fa-sign-in-alt"></i>
          Go to Login
        </router-link>
      </div>

      <!-- Already Verified State -->
      <div v-else-if="status === 'already-verified'" class="state-content">
        <div class="info-icon">
          <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
        </div>
        <h1>Already Verified</h1>
        <p>{{ message || 'Your email has already been verified. You can proceed to login.' }}</p>
        <router-link to="/login" class="btn btn-primary">
          <i class="fas fa-sign-in-alt"></i>
          Go to Login
        </router-link>
      </div>

      <!-- Error State -->
      <div v-else-if="status === 'error'" class="state-content">
        <div class="error-icon">
          <svg viewBox="0 0 24 24">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </div>
        <h1>Verification Failed</h1>
        <p>We couldn't verify your email address.</p>
        <div class="error-message">
          {{ message || 'Invalid or expired verification token' }}
        </div>
        <p>The verification link may have expired or already been used. Please try requesting a new verification email.</p>
        <router-link to="/signup" class="btn btn-primary">
          <i class="fas fa-user-plus"></i>
          Register Again
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-else class="state-content">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <h1>Verifying Email...</h1>
        <p>Please wait while we verify your email address.</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'

export default {
  name: 'EmailVerification',
  setup() {
    const route = useRoute()
    const apiStore = useApiStore()
    
    const status = ref('loading')
    const message = ref('')

    const verifyEmail = async () => {
      // Get status and message from URL params (set by backend redirect)
      const urlStatus = route.query.status
      const urlMessage = route.query.message
      
      if (urlStatus) {
        status.value = urlStatus
        message.value = urlMessage || ''
      } else {
        // Fallback: if no status in URL, we might be doing client-side verification
        const token = route.query.token
        
        if (!token) {
          status.value = 'error'
          message.value = 'No verification token provided'
          return
        }

        // Token exists but no status - the backend should have redirected
        // This means something went wrong
        status.value = 'error'
        message.value = 'Invalid verification link'
      }
    }

    onMounted(() => {
      verifyEmail()
    })

    return {
      status,
      message
    }
  }
}
</script>

<style scoped>
.verification-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.verification-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 500px;
  width: 100%;
  padding: 48px 40px;
  text-align: center;
  animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.state-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.success-icon,
.error-icon,
.info-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: scaleIn 0.5s ease-out;
}

.success-icon {
  background: #10b981;
}

.error-icon {
  background: #ef4444;
}

.info-icon {
  background: #3b82f6;
}

.success-icon svg,
.error-icon svg,
.info-icon svg {
  width: 40px;
  height: 40px;
  stroke: white;
  stroke-width: 3;
  fill: none;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.loading-spinner {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading-spinner i {
  font-size: 32px;
  color: #667eea;
}

h1 {
  font-size: 28px;
  color: #1f2937;
  margin: 0;
}

p {
  font-size: 16px;
  color: #6b7280;
  margin: 0;
  line-height: 1.6;
}

.error-message {
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px 16px;
  color: #991b1b;
  font-size: 14px;
  width: 100%;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 32px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  font-size: 16px;
  transition: all 0.2s;
  margin-top: 8px;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.fa-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 480px) {
  .verification-card {
    padding: 32px 24px;
  }
  
  h1 {
    font-size: 24px;
  }
}
</style>

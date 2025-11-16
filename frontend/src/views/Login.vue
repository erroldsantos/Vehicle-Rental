<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo">
          <i class="fas fa-car"></i>
          <h1>Vehicle Rental</h1>
        </div>
        <p class="subtitle">Sign in to your account</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrapper">
            <i class="fas fa-envelope"></i>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="Enter your email"
              required
              :disabled="loading"
              class="form-input"
            />
          </div>
          <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <i class="fas fa-lock"></i>
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Enter your password"
              required
              :disabled="loading"
              class="form-input"
            />
            <button
              type="button"
              @click="togglePassword"
              class="password-toggle"
              :disabled="loading"
            >
              <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
          </div>
          <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
        </div>

        <div class="form-group">
          <label class="checkbox-wrapper">
            <input
              v-model="form.remember"
              type="checkbox"
              :disabled="loading"
            />
            <span class="checkmark"></span>
            Remember me
          </label>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="login-btn"
        >
          <span v-if="loading">
            <i class="fas fa-spinner fa-spin"></i>
            Signing in...
          </span>
          <span v-else>
            <i class="fas fa-sign-in-alt"></i>
            Sign In
          </span>
        </button>

        <div v-if="errorMessage" class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          {{ errorMessage }}
        </div>
      </form>

      <div class="login-footer">
        <a href="#" @click.prevent="forgotPassword" class="forgot-link">
          Forgot your password?
        </a>
        <div class="signup-link-wrapper">
          <p>Don't have an account? <router-link to="/signup" class="signup-link">Sign Up</router-link></p>
        </div>
      </div>
    </div>

    <!-- Background decorations -->
    <div class="bg-decoration">
      <div class="car-icon">
        <i class="fas fa-car"></i>
      </div>
      <div class="car-icon">
        <i class="fas fa-truck"></i>
      </div>
      <div class="car-icon">
        <i class="fas fa-motorcycle"></i>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'

export default {
  name: 'Login',
  setup() {
    const router = useRouter()
    const apiStore = useApiStore()
    const loading = ref(false)
    const showPassword = ref(false)
    const errorMessage = ref('')

    const form = reactive({
      email: '',
      password: '',
      remember: false
    })

    const errors = reactive({
      email: '',
      password: ''
    })

    const validateForm = () => {
      errors.email = ''
      errors.password = ''

      if (!form.email) {
        errors.email = 'Email is required'
        return false
      }

      if (!form.email.includes('@')) {
        errors.email = 'Please enter a valid email address'
        return false
      }

      if (!form.password) {
        errors.password = 'Password is required'
        return false
      }

      if (form.password.length < 6) {
        errors.password = 'Password must be at least 6 characters'
        return false
      }

      return true
    }

    const handleLogin = async () => {
      if (!validateForm()) return

      loading.value = true
      errorMessage.value = ''

      try {
        // API call to your LavaLust backend (using apiStore with Vite proxy)
        const data = await apiStore.post('/auth/login', {
          email: form.email,
          password: form.password,
          remember: form.remember
        })

        if (data.success || data.token) {
          // Store authentication token
          if (data.token) {
            localStorage.setItem('auth_token', data.token)
          }
          
          // Store user info
          if (data.user) {
            localStorage.setItem('user_info', JSON.stringify(data.user))
          }

          // Redirect to dashboard with full page reload to ensure reactivity
          window.location.href = '/'
        } else {
          errorMessage.value = data.message || 'Invalid email or password'
        }
      } catch (error) {
        console.error('Login error:', error)
        
        // Handle different error types
        if (error.response) {
          // Server responded with error status
          const status = error.response.status
          const message = error.response.data?.message || error.response.data?.error
          
          if (status === 403) {
            // Email not verified
            errorMessage.value = message || 'Please verify your email address before logging in. Check your inbox for the verification link.'
          } else if (status === 401) {
            // Invalid credentials
            errorMessage.value = 'Invalid email or password. Please check your credentials and try again.'
          } else if (status === 400) {
            // Bad request
            errorMessage.value = message || 'Please enter a valid email and password.'
          } else {
            errorMessage.value = message || 'Login failed. Please try again.'
          }
        } else if (error.request) {
          // Request made but no response
          errorMessage.value = 'Connection error. Please check if the server is running and try again.'
        } else {
          // Something else went wrong
          errorMessage.value = 'An unexpected error occurred. Please try again.'
        }
      } finally {
        loading.value = false
      }
    }

    const togglePassword = () => {
      showPassword.value = !showPassword.value
    }

    const forgotPassword = () => {
      alert('Password reset functionality will be implemented soon.')
    }

    return {
      form,
      errors,
      loading,
      showPassword,
      errorMessage,
      handleLogin,
      togglePassword,
      forgotPassword
    }
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
  position: relative;
  overflow: hidden;
}

.login-card {
  background: white;
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  width: 100%;
  max-width: 420px;
  position: relative;
  z-index: 2;
}

.login-header {
  text-align: center;
  margin-bottom: 40px;
}

.logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  margin-bottom: 10px;
}

.logo i {
  font-size: 2.5rem;
  color: #667eea;
}

.logo h1 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  color: #64748b;
  margin: 0;
  font-size: 1rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.95rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper i {
  position: absolute;
  left: 16px;
  color: #9ca3af;
  font-size: 1rem;
  z-index: 1;
}

.form-input {
  width: 100%;
  padding: 16px 50px 16px 50px; /* Balanced padding for both icons */
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #f9fafb;
}

/* Hide browser's default password toggle */
.form-input[type="password"]::-ms-reveal,
.form-input[type="password"]::-ms-clear {
  display: none;
}

.form-input[type="password"]::-webkit-reveal {
  display: none;
}

.form-input[type="password"]::-webkit-clear-button {
  display: none;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 6px;
  font-size: 1rem;
  transition: color 0.3s ease;
  z-index: 2;
  border-radius: 4px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.password-toggle:hover:not(:disabled) {
  color: #667eea;
  background-color: #f3f4f6;
}

.password-toggle:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.password-toggle:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  font-size: 0.95rem;
  color: #374151;
  position: relative;
}

.checkbox-wrapper input[type="checkbox"] {
  opacity: 0;
  position: absolute;
}

.checkmark {
  width: 20px;
  height: 20px;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  position: relative;
  transition: all 0.3s ease;
}

.checkbox-wrapper input:checked + .checkmark {
  background: #667eea;
  border-color: #667eea;
}

.checkbox-wrapper input:checked + .checkmark::after {
  content: '';
  position: absolute;
  left: 5px;
  top: 1px;
  width: 6px;
  height: 12px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.login-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  padding: 16px 24px;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 10px;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.error-text {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: -4px;
}

.error-message {
  background: #fee2e2;
  color: #dc2626;
  padding: 12px 16px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.95rem;
  border-left: 4px solid #dc2626;
}

.login-footer {
  text-align: center;
  margin-top: 30px;
}

.forgot-link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.95rem;
  font-weight: 500;
  transition: color 0.3s ease;
}

.forgot-link:hover {
  color: #764ba2;
  text-decoration: underline;
}

.signup-link-wrapper {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.signup-link-wrapper p {
  margin: 0;
  color: #666;
  font-size: 0.95rem;
}

.signup-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s ease;
}

.signup-link:hover {
  color: #764ba2;
  text-decoration: underline;
}

.bg-decoration {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  pointer-events: none;
  overflow: hidden;
}

.car-icon {
  position: absolute;
  font-size: 3rem;
  color: rgba(255, 255, 255, 0.1);
  animation: float 6s ease-in-out infinite;
}

.car-icon:nth-child(1) {
  top: 20%;
  left: 10%;
  animation-delay: 0s;
}

.car-icon:nth-child(2) {
  top: 60%;
  right: 15%;
  animation-delay: 2s;
}

.car-icon:nth-child(3) {
  bottom: 20%;
  left: 20%;
  animation-delay: 4s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-20px) rotate(5deg);
  }
}

@media (max-width: 480px) {
  .login-card {
    padding: 30px 20px;
    margin: 10px;
  }
  
  .logo h1 {
    font-size: 1.8rem;
  }
  
  .logo i {
    font-size: 2rem;
  }
}
</style>
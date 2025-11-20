<template>
  <div class="signup-container">
    <div class="signup-card">
      <!-- Logo/Header -->
      <div class="signup-header">
        <div class="logo">
          <i class="fas fa-car"></i>
          <h1>Vehicle Rental</h1>
        </div>
        <p class="signup-subtitle">Create your customer account</p>
      </div>

      <!-- Sign Up Form -->
      <form @submit.prevent="handleSignUp" class="signup-form">
        <div class="form-row">
          <!-- First Name -->
          <div class="form-group">
            <label for="firstName">First Name</label>
            <div class="input-wrapper">
              <i class="fas fa-user"></i>
              <input
                id="firstName"
                v-model="form.firstName"
                type="text"
                placeholder="Enter your first name"
                required
                :disabled="loading"
                class="form-input"
              />
            </div>
            <span v-if="errors.firstName" class="error-text">{{ errors.firstName }}</span>
          </div>

          <!-- Last Name -->
          <div class="form-group">
            <label for="lastName">Last Name</label>
            <div class="input-wrapper">
              <i class="fas fa-user"></i>
              <input
                id="lastName"
                v-model="form.lastName"
                type="text"
                placeholder="Enter your last name"
                required
                :disabled="loading"
                class="form-input"
              />
            </div>
            <span v-if="errors.lastName" class="error-text">{{ errors.lastName }}</span>
          </div>
        </div>

        <!-- Email -->
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

        <div class="form-row">
          <!-- Phone -->
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <div class="input-wrapper">
              <i class="fas fa-phone"></i>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                placeholder="Enter your phone number"
                :disabled="loading"
                class="form-input"
              />
            </div>
            <span v-if="errors.phone" class="error-text">{{ errors.phone }}</span>
          </div>
        </div>

        <div class="form-row">
          <!-- Password -->
          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
              <i class="fas fa-lock"></i>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Create a password"
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

          <!-- Confirm Password -->
          <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <div class="input-wrapper">
              <i class="fas fa-lock"></i>
              <input
                id="confirmPassword"
                v-model="form.confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="Confirm your password"
                required
                :disabled="loading"
                class="form-input"
              />
              <button
                type="button"
                @click="toggleConfirmPassword"
                class="password-toggle"
                :disabled="loading"
              >
                <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </button>
            </div>
            <span v-if="errors.confirmPassword" class="error-text">{{ errors.confirmPassword }}</span>
          </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="form-group">
          <label class="checkbox-wrapper">
            <input
              v-model="form.acceptTerms"
              type="checkbox"
              required
              :disabled="loading"
            />
            <span class="checkmark"></span>
            I agree to the <a href="#" @click.prevent="showTerms">Terms and Conditions</a>
          </label>
          <span v-if="errors.acceptTerms" class="error-text">{{ errors.acceptTerms }}</span>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="loading"
          class="signup-btn"
        >
          <span v-if="loading">
            <i class="fas fa-spinner fa-spin"></i>
            Creating Account...
          </span>
          <span v-else>
            <i class="fas fa-user-plus"></i>
            Create Account
          </span>
        </button>

        <!-- Error Message -->
        <div v-if="errorMessage" class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          {{ errorMessage }}
        </div>

        <!-- Success Message -->
        <div v-if="successMessage" class="success-message">
          <i class="fas fa-check-circle"></i>
          {{ successMessage }}
        </div>
      </form>

      <!-- Sign In Link -->
      <div class="signup-footer">
        <p>Already have an account? <router-link to="/login" class="signin-link">Sign In</router-link></p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'

export default {
  name: 'SignUp',
  setup() {
    const router = useRouter()
    const apiStore = useApiStore()
    
    const loading = ref(false)
    const showPassword = ref(false)
    const showConfirmPassword = ref(false)
    const errorMessage = ref('')
    const successMessage = ref('')

    const form = reactive({
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      password: '',
      confirmPassword: '',
      acceptTerms: false
    })

    const errors = reactive({
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      password: '',
      confirmPassword: '',
      acceptTerms: ''
    })

    const validateForm = () => {
      // Clear previous errors
      Object.keys(errors).forEach(key => errors[key] = '')
      
      let isValid = true

      // First Name validation
      if (!form.firstName.trim()) {
        errors.firstName = 'First name is required'
        isValid = false
      } else if (form.firstName.length < 2) {
        errors.firstName = 'First name must be at least 2 characters'
        isValid = false
      }

      // Last Name validation
      if (!form.lastName.trim()) {
        errors.lastName = 'Last name is required'
        isValid = false
      } else if (form.lastName.length < 2) {
        errors.lastName = 'Last name must be at least 2 characters'
        isValid = false
      }

      // Email validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!form.email) {
        errors.email = 'Email is required'
        isValid = false
      } else if (!emailRegex.test(form.email)) {
        errors.email = 'Please enter a valid email address'
        isValid = false
      }

      // Phone validation (optional but if provided, should be valid)
      if (form.phone && form.phone.length < 10) {
        errors.phone = 'Phone number must be at least 10 digits'
        isValid = false
      }

      // Password validation
      if (!form.password) {
        errors.password = 'Password is required'
        isValid = false
      } else if (form.password.length < 6) {
        errors.password = 'Password must be at least 6 characters'
        isValid = false
      }

      // Confirm Password validation
      if (!form.confirmPassword) {
        errors.confirmPassword = 'Please confirm your password'
        isValid = false
      } else if (form.password !== form.confirmPassword) {
        errors.confirmPassword = 'Passwords do not match'
        isValid = false
      }

      // Terms acceptance
      if (!form.acceptTerms) {
        errors.acceptTerms = 'You must accept the terms and conditions'
        isValid = false
      }

      return isValid
    }

    const handleSignUp = async () => {
      if (!validateForm()) {
        return
      }

      loading.value = true
      errorMessage.value = ''
      successMessage.value = ''

      try {
        const fullname = `${form.firstName} ${form.lastName}`.trim()
        
        const data = await apiStore.post('/auth/register', {
          fullname: fullname,
          email: form.email,
          phone: form.phone || '',
          password: form.password,
          role: 'user'
        })

        // Check if registration was successful
        if (data.success) {
          successMessage.value = data.message || 'Account created successfully! Please check your email to verify your account.'
          
          // Clear form
          Object.keys(form).forEach(key => {
            if (typeof form[key] === 'boolean') {
              form[key] = false
            } else {
              form[key] = ''
            }
          })

          // Don't auto-redirect - let user read the message about email verification
          // User will need to verify email before they can login
        } else {
          errorMessage.value = data.message || 'Registration failed. Please try again.'
        }
      } catch (error) {
        console.error('Registration error:', error)
        errorMessage.value = error.message || 'Connection error. Please check if the server is running and try again.'
      } finally {
        loading.value = false
      }
    }

    const togglePassword = () => {
      showPassword.value = !showPassword.value
    }

    const toggleConfirmPassword = () => {
      showConfirmPassword.value = !showConfirmPassword.value
    }

    const showTerms = () => {
      alert('Terms and Conditions:\n\n1. You must be at least 18 years old to rent a vehicle.\n2. Valid driver\'s license required.\n3. Payment is required at time of booking.\n4. Cancellation policy applies.\n5. Damage to vehicle will be charged to customer.\n\nFor full terms, please contact us.')
    }

    return {
      form,
      errors,
      loading,
      showPassword,
      showConfirmPassword,
      errorMessage,
      successMessage,
      handleSignUp,
      togglePassword,
      toggleConfirmPassword,
      showTerms
    }
  }
}
</script>

<style scoped>
.signup-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.signup-card {
  background: white;
  padding: 32px;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  width: 100%;
  max-width: 520px;
  animation: slideUp 0.6s ease-out;
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

.signup-header {
  text-align: center;
  margin-bottom: 24px;
}

.logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 12px;
}

.logo i {
  font-size: 2rem;
  color: #667eea;
}

.logo h1 {
  color: #333;
  font-size: 1.8rem;
  font-weight: 600;
  margin: 0;
}

.signup-subtitle {
  color: #666;
  font-size: 1rem;
  margin: 0;
}

.signup-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.form-group label {
  font-weight: 500;
  color: #333;
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
  padding: 12px 45px 12px 45px;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
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
}

.checkbox-wrapper input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #667eea;
}

.checkbox-wrapper a {
  color: #667eea;
  text-decoration: none;
}

.checkbox-wrapper a:hover {
  text-decoration: underline;
}

.signup-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 16px 24px;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 8px;
}

.signup-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.signup-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.error-text {
  color: #e74c3c;
  font-size: 0.875rem;
  margin-top: 4px;
}

.error-message, .success-message {
  padding: 12px 16px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.95rem;
  margin-top: 8px;
}

.error-message {
  background: #fee;
  color: #c53030;
  border: 1px solid #fed7d7;
}

.success-message {
  background: #f0fff4;
  color: #2d7d32;
  border: 1px solid #c6f6d5;
}

.signup-footer {
  text-align: center;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.signup-footer p {
  color: #666;
  margin: 0;
}

.signin-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

.signin-link:hover {
  text-decoration: underline;
}

/* Spinner animation */
.fa-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .signup-card {
    max-width: 400px;
  }
}

@media (max-width: 480px) {
  .signup-card {
    padding: 24px;
    margin: 10px;
  }
  
  .logo h1 {
    font-size: 1.5rem;
  }
  
  .form-input {
    padding: 12px 40px 12px 40px;
  }
}
</style>
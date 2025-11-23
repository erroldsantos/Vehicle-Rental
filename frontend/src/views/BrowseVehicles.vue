<template>
  <div class="browse-vehicles">
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

    <!-- Page Title and Back Button -->
    <div class="page-title-section">
      <button class="btn-back" @click="$router.push('/user-dashboard')">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
      </button>
      <h1>Browse Vehicles</h1>
      <p>Find the perfect vehicle for your journey</p>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input 
          type="text" 
          v-model="searchQuery"
          placeholder="Search by brand, model, or type..."
          @input="filterVehicles"
        >
      </div>

      <div class="filter-group">
        <label>
          <i class="fas fa-sort-amount-down"></i>
          Sort By
        </label>
        <select v-model="sortBy" @change="sortVehicles">
          <option value="price-low">Price: Low to High</option>
          <option value="price-high">Price: High to Low</option>
          <option value="name">Name A-Z</option>
          <option value="year-new">Year: Newest First</option>
        </select>
      </div>

      <button class="btn-clear-filters" @click="clearFilters" v-if="hasActiveFilters">
        <i class="fas fa-times"></i>
        Clear Filters
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading vehicles...</p>
    </div>

    <!-- Vehicles Grid -->
    <div v-else-if="filteredVehicles.length > 0" class="vehicles-container">
      <div class="results-info">
        <p>Showing {{ filteredVehicles.length }} of {{ allVehicles.length }} vehicles</p>
      </div>

      <div class="vehicles-grid">
        <div 
          v-for="vehicle in filteredVehicles" 
          :key="vehicle.id"
          class="vehicle-card"
        >
          <div class="vehicle-image-container">
            <img 
              :src="vehicle.imageUrl || getPlaceholderImage(vehicle.type || vehicle.brand)" 
              :alt="vehicle.brand + ' ' + vehicle.model"
              class="vehicle-image"
              @error="handleImageError"
            >
            <span class="vehicle-status" :class="vehicle.status === 'maintenance' ? 'unavailable' : 'available'">
              {{ vehicle.status === 'maintenance' ? 'Under Maintenance' : 'Available' }}
            </span>
          </div>

          <div class="vehicle-info">
            <div class="vehicle-header">
              <h3>{{ vehicle.brand }} {{ vehicle.model }}</h3>
              <span class="vehicle-year">{{ vehicle.year }}</span>
            </div>

            <div class="vehicle-specs">
              <div class="spec-item">
                <i class="fas fa-car"></i>
                <span>{{ vehicle.type || 'Sedan' }}</span>
              </div>
              <div class="spec-item">
                <i class="fas fa-users"></i>
                <span>{{ vehicle.capacity || 4 }} Seats</span>
              </div>
              <div class="spec-item">
                <i class="fas fa-gas-pump"></i>
                <span>{{ vehicle.fuel_type || 'Gasoline' }}</span>
              </div>
              <div class="spec-item">
                <i class="fas fa-cog"></i>
                <span>{{ vehicle.transmission || 'Automatic' }}</span>
              </div>
            </div>

            <div class="vehicle-features" v-if="vehicle.features">
              <span class="feature" v-for="feature in getFeaturesList(vehicle.features)" :key="feature">
                {{ feature }}
              </span>
            </div>

            <div class="vehicle-footer">
              <div class="vehicle-price">
                <span class="price">₱{{ formatPrice(vehicle.daily_rate) }}</span>
                <span class="period">/day</span>
              </div>
              <button 
                class="btn-book" 
                @click="bookVehicle(vehicle)"
                :disabled="vehicle.status === 'maintenance' || !canBook"
                :title="bookingLimitMessage"
              >
                <i class="fas fa-calendar-check"></i>
                {{ 
                  vehicle.status === 'maintenance' ? 'Unavailable' : 
                  (confirmedBookingsCount >= 2 ? 'Booking Limit Reached' : 
                  (!isLicenseVerified ? 'License Required' : 'Book Now')) 
                }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <i class="fas fa-car"></i>
      <h3>No vehicles found</h3>
      <p v-if="hasActiveFilters">Try adjusting your filters</p>
      <p v-else>No vehicles are currently available</p>
      <button v-if="hasActiveFilters" class="btn-primary" @click="clearFilters">
        Clear Filters
      </button>
    </div>

    <!-- Booking Modal -->
    <div v-if="showBookingModal" class="modal-overlay" @click="closeBookingModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Book {{ selectedVehicle?.brand }} {{ selectedVehicle?.model }}</h2>
          <button class="btn-close" @click="closeBookingModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="booking-vehicle-info">
            <img 
              :src="selectedVehicle?.imageUrl || getPlaceholderImage(selectedVehicle?.type)" 
              :alt="selectedVehicle?.brand"
              @error="handleImageError"
            >
            <div>
              <h4>{{ selectedVehicle?.brand }} {{ selectedVehicle?.model }}</h4>
              <p>{{ selectedVehicle?.year }} • {{ selectedVehicle?.type }}</p>
              <p class="daily-rate">₱{{ formatPrice(selectedVehicle?.daily_rate) }}/day</p>
            </div>
          </div>

          <form @submit.prevent="submitBooking" class="booking-form">
            <div class="form-group">
              <label for="start_date">
                <i class="fas fa-calendar-alt"></i>
                Pickup Date
              </label>
              <input 
                type="date" 
                id="start_date"
                v-model="bookingForm.start_date"
                :min="minDate"
                @change="checkDateAvailability"
                required
              >
              <small v-if="bookedDateRanges.length > 0" class="date-info">
                <i class="fas fa-info-circle"></i>
                Some dates are unavailable for this vehicle
              </small>
            </div>

            <div class="form-group">
              <label for="end_date">
                <i class="fas fa-calendar-alt"></i>
                Return Date
              </label>
              <input 
                type="date" 
                id="end_date"
                v-model="bookingForm.end_date"
                :min="bookingForm.start_date || minDate"
                @change="checkDateAvailability"
                required
              >
            </div>
            
            <div v-if="dateConflictMessage" class="alert-warning">
              <i class="fas fa-exclamation-triangle"></i>
              {{ dateConflictMessage }}
            </div>

            <div v-if="bookedDateRanges.length > 0" class="booked-dates-info">
              <p><strong>Currently Booked Dates:</strong></p>
              <ul>
                <li v-for="(range, index) in bookedDateRanges" :key="index">
                  {{ formatDateRange(range.start_date, range.end_date) }}
                </li>
              </ul>
            </div>

            <LocationPicker 
              id="pickup_location"
              label="Pickup Location"
              placeholder="Click to select pickup location on map"
              v-model="pickupLocation"
            />

            <LocationPicker 
              id="dropoff_location"
              label="Drop-off Location"
              placeholder="Click to select drop-off location on map"
              v-model="dropoffLocation"
            />

            <div class="booking-summary" v-if="rentalDays > 0">
              <div class="summary-row">
                <span>Daily Rate:</span>
                <span>₱{{ formatPrice(selectedVehicle?.daily_rate) }}</span>
              </div>
              <div class="summary-row">
                <span>Number of Days:</span>
                <span>{{ rentalDays }}</span>
              </div>
              <div class="summary-row total">
                <span>Total Amount:</span>
                <span>₱{{ formatPrice(totalAmount) }}</span>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closeBookingModal">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="submitting">
                <i class="fas fa-check"></i>
                {{ submitting ? 'Booking...' : 'Confirm Booking' }}
              </button>
            </div>
          </form>
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

        <div class="modal-body">
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
                <span class="value">{{ currentBooking?.vehicle_brand }} {{ currentBooking?.vehicle_model }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Total Amount</span>
                <span class="value amount">₱{{ formatPrice(currentBooking?.total_amount || totalAmount) }}</span>
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
                  <span class="type-amount">₱{{ formatPrice(currentBooking?.total_amount || totalAmount) }}</span>
                </div>
              </label>
              <label class="type-option">
                <input type="radio" value="downpayment" v-model="paymentForm.payment_type">
                <div class="type-card">
                  <span class="type-label">Down Payment (30%)</span>
                  <span class="type-amount">₱{{ formatPrice((currentBooking?.total_amount || totalAmount) * 0.3) }}</span>
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'
import LocationPicker from '@/components/LocationPicker.vue'

export default {
  name: 'BrowseVehicles',
  components: {
    LocationPicker
  },
  setup() {
    const router = useRouter()
    const apiStore = useApiStore()
    
    const loading = ref(false)
    const submitting = ref(false)
    const allVehicles = ref([])
    const filteredVehicles = ref([])
    const searchQuery = ref('')
    const sortBy = ref('price-low')
    const filters = ref({
      type: '',
      fuel_type: '',
      capacity: ''
    })
    const bookedDateRanges = ref([])
    const maintenanceDates = ref([])
    const dateConflictMessage = ref('')
    const confirmedBookingsCount = ref(0)
    
    const showBookingModal = ref(false)
    const showPaymentModal = ref(false)
    const currentBooking = ref(null)
    const paymentForm = ref({
      payment_type: 'full',
      payment_method: 'gcash'
    })
    const processingPayment = ref(false)
    
    const selectedVehicle = ref(null)
    const pickupLocation = ref({ address: '', lat: null, lng: null })
    const dropoffLocation = ref({ address: '', lat: null, lng: null })
    const bookingForm = ref({
      start_date: '',
      end_date: '',
      pickup_location: '',
      dropoff_location: ''
    })

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
    
    const userLicenseStatus = computed(() => {
      return userInfo.value.license_status || 'not_submitted'
    })
    
    const isLicenseVerified = computed(() => {
      return userLicenseStatus.value === 'verified'
    })
    
    const canBook = computed(() => {
      return isLicenseVerified.value && confirmedBookingsCount.value < 2
    })
    
    const bookingLimitMessage = computed(() => {
      if (!isLicenseVerified.value) {
        return 'Please verify your driver\'s license to book vehicles'
      }
      if (confirmedBookingsCount.value >= 2) {
        return 'You have reached the maximum limit of 2 confirmed bookings. Please complete your existing bookings first.'
      }
      return ''
    })

    const hasActiveFilters = computed(() => {
      return searchQuery.value !== '' || 
             filters.value.type !== '' || 
             filters.value.fuel_type !== '' || 
             filters.value.capacity !== ''
    })

    const minDate = computed(() => {
      const today = new Date()
      return today.toISOString().split('T')[0]
    })

    const rentalDays = computed(() => {
      if (!bookingForm.value.start_date || !bookingForm.value.end_date) return 0
      const start = new Date(bookingForm.value.start_date)
      const end = new Date(bookingForm.value.end_date)
      const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24))
      return days > 0 ? days : 0
    })

    const totalAmount = computed(() => {
      if (!selectedVehicle.value || rentalDays.value <= 0) return 0
      return parseFloat(selectedVehicle.value.daily_rate) * rentalDays.value
    })

    const loadVehicles = async () => {
      loading.value = true
      try {
        const data = await apiStore.get('/vehicles')
        const vehicles = data.data?.vehicles || data.vehicles || []
        
        allVehicles.value = vehicles.map(vehicle => ({
          ...vehicle,
          imageUrl: vehicle.image ? `/images/vehicles/${vehicle.image}` : null
        }))
        
        filteredVehicles.value = [...allVehicles.value]
        sortVehicles()
      } catch (error) {
        console.error('Error loading vehicles:', error)
        alert('Failed to load vehicles. Please try again.')
      } finally {
        loading.value = false
      }
    }
    
    const loadUserInfo = async () => {
      try {
        const stored = localStorage.getItem('user_info')
        if (!stored) return
        
        const user = JSON.parse(stored)
        const response = await apiStore.get(`/users/${user.id}`)
        
        // Update localStorage with fresh data
        localStorage.setItem('user_info', JSON.stringify(response.user || response))
        
        // Load user's confirmed bookings count
        await loadConfirmedBookingsCount(user.id)
      } catch (error) {
        console.error('Error loading user info:', error)
      }
    }
    
    const loadConfirmedBookingsCount = async (userId) => {
      try {
        const response = await apiStore.get(`/bookings?user_id=${userId}`)
        const bookings = response.data?.bookings || response.bookings || []
        // Count bookings with confirmed, active, ongoing, or returned status
        confirmedBookingsCount.value = bookings.filter(b => 
          b.status === 'confirmed' || 
          b.status === 'active' || 
          b.status === 'ongoing' || 
          b.status === 'returned'
        ).length
        console.log('Active bookings count:', confirmedBookingsCount.value, 'Bookings:', bookings.filter(b => 
          b.status === 'confirmed' || 
          b.status === 'active' || 
          b.status === 'ongoing' || 
          b.status === 'returned'
        ))
      } catch (error) {
        console.error('Error loading confirmed bookings count:', error)
        confirmedBookingsCount.value = 0
      }
    }

    const filterVehicles = () => {
      let result = [...allVehicles.value]

      // Search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(vehicle => 
          vehicle.brand.toLowerCase().includes(query) ||
          vehicle.model.toLowerCase().includes(query) ||
          (vehicle.type && vehicle.type.toLowerCase().includes(query))
        )
      }

      // Type filter
      if (filters.value.type) {
        result = result.filter(vehicle => 
          vehicle.type && vehicle.type.toLowerCase() === filters.value.type.toLowerCase()
        )
      }

      // Fuel type filter
      if (filters.value.fuel_type) {
        result = result.filter(vehicle => 
          vehicle.fuel_type && vehicle.fuel_type.toLowerCase() === filters.value.fuel_type.toLowerCase()
        )
      }

      // Capacity filter
      if (filters.value.capacity) {
        const capacity = parseInt(filters.value.capacity)
        result = result.filter(vehicle => {
          const vehicleCapacity = parseInt(vehicle.capacity) || 4
          if (capacity === 7) {
            return vehicleCapacity >= 7
          }
          return vehicleCapacity >= capacity
        })
      }

      filteredVehicles.value = result
      sortVehicles()
    }

    const sortVehicles = () => {
      const sorted = [...filteredVehicles.value]

      switch (sortBy.value) {
        case 'price-low':
          sorted.sort((a, b) => parseFloat(a.daily_rate) - parseFloat(b.daily_rate))
          break
        case 'price-high':
          sorted.sort((a, b) => parseFloat(b.daily_rate) - parseFloat(a.daily_rate))
          break
        case 'name':
          sorted.sort((a, b) => `${a.brand} ${a.model}`.localeCompare(`${b.brand} ${b.model}`))
          break
        case 'year-new':
          sorted.sort((a, b) => parseInt(b.year) - parseInt(a.year))
          break
      }

      filteredVehicles.value = sorted
    }

    const clearFilters = () => {
      searchQuery.value = ''
      filters.value = {
        type: '',
        fuel_type: '',
        capacity: ''
      }
      filterVehicles()
    }

    // Fetch booked dates for a vehicle
    const loadBookedDates = async (vehicleId) => {
      try {
        const data = await apiStore.get(`/vehicles/${vehicleId}/booked-dates`)
        bookedDateRanges.value = data.booked_dates || []
        maintenanceDates.value = data.maintenance_dates || []
      } catch (error) {
        console.error('Error loading booked dates:', error)
        bookedDateRanges.value = []
        maintenanceDates.value = []
      }
    }

    // Check if selected dates conflict with existing bookings
    const checkDateAvailability = () => {
      dateConflictMessage.value = ''
      
      if (!bookingForm.value.start_date || !bookingForm.value.end_date) {
        return
      }

      const selectedStart = new Date(bookingForm.value.start_date)
      const selectedEnd = new Date(bookingForm.value.end_date)

      // Check against booked date ranges
      for (const range of bookedDateRanges.value) {
        const bookedStart = new Date(range.start_date)
        const bookedEnd = new Date(range.end_date)

        // Check if dates overlap
        if (
          (selectedStart >= bookedStart && selectedStart <= bookedEnd) ||
          (selectedEnd >= bookedStart && selectedEnd <= bookedEnd) ||
          (selectedStart <= bookedStart && selectedEnd >= bookedEnd)
        ) {
          dateConflictMessage.value = `The selected dates overlap with an existing booking (${formatDateRange(range.start_date, range.end_date)}). Please choose different dates.`
          return
        }
      }

      // Check against maintenance dates
      for (const maintenance of maintenanceDates.value) {
        const maintenanceDate = new Date(maintenance.scheduled_date)
        if (maintenanceDate >= selectedStart && maintenanceDate <= selectedEnd) {
          dateConflictMessage.value = `The vehicle is scheduled for maintenance on ${new Date(maintenance.scheduled_date).toLocaleDateString()}. Please choose different dates.`
          return
        }
      }
    }

    const formatDateRange = (startDate, endDate) => {
      const start = new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
      const end = new Date(endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
      return `${start} - ${end}`
    }

    const bookVehicle = async (vehicle) => {
      console.log('Book vehicle clicked. License verified:', isLicenseVerified.value, 'Confirmed bookings:', confirmedBookingsCount.value, 'Can book:', canBook.value)
      
      // Check if license is verified
      if (!isLicenseVerified.value) {
        alert('Please verify your driver\'s license before booking a vehicle. Go to your dashboard to submit your license.')
        return
      }
      
      // Check booking limit
      if (confirmedBookingsCount.value >= 2) {
        alert('You have reached the maximum limit of 2 confirmed bookings. Please complete your existing bookings before making a new one.')
        return
      }
      
      selectedVehicle.value = vehicle
      showBookingModal.value = true
      dateConflictMessage.value = ''
      
      // Load booked dates for this vehicle
      await loadBookedDates(vehicle.id)
      
      // Set default dates - today and tomorrow
      const today = new Date()
      const year = today.getFullYear()
      const month = String(today.getMonth() + 1).padStart(2, '0')
      const day = String(today.getDate()).padStart(2, '0')
      
      const tomorrow = new Date(today)
      tomorrow.setDate(tomorrow.getDate() + 1)
      const tomorrowYear = tomorrow.getFullYear()
      const tomorrowMonth = String(tomorrow.getMonth() + 1).padStart(2, '0')
      const tomorrowDay = String(tomorrow.getDate()).padStart(2, '0')
      
      bookingForm.value.start_date = `${year}-${month}-${day}`
      bookingForm.value.end_date = `${tomorrowYear}-${tomorrowMonth}-${tomorrowDay}`
      
      // Check if default dates are available
      checkDateAvailability()
    }

    const submitBooking = async () => {
      if (submitting.value) return
      
      const userId = userInfo.value.id
      if (!userId) {
        alert('Please log in to make a booking')
        router.push({ name: 'login' })
        return
      }

      // Validate locations are selected
      if (!pickupLocation.value.address) {
        alert('Please select a pickup location on the map')
        return
      }
      if (!dropoffLocation.value.address) {
        alert('Please select a drop-off location on the map')
        return
      }

      // Final check for date conflicts
      checkDateAvailability()
      if (dateConflictMessage.value) {
        alert(dateConflictMessage.value)
        return
      }

      submitting.value = true
      try {
        const bookingData = {
          user_id: userId,
          vehicle_id: selectedVehicle.value.id,
          start_date: bookingForm.value.start_date,
          end_date: bookingForm.value.end_date,
          pickup_location: pickupLocation.value.address,
          dropoff_location: dropoffLocation.value.address,
          total_amount: totalAmount.value,
          status: 'pending'
        }

        const response = await apiStore.post('/bookings', bookingData)
        
        // Store booking details with vehicle info and show payment modal
        currentBooking.value = {
          ...response,
          vehicle_brand: selectedVehicle.value.brand,
          vehicle_model: selectedVehicle.value.model
        }
        closeBookingModal()
        showPaymentModal.value = true
      } catch (error) {
        console.error('Error creating booking:', error)
        alert('Failed to create booking. Please try again.')
      } finally {
        submitting.value = false
      }
    }

    const closeBookingModal = () => {
      showBookingModal.value = false
      selectedVehicle.value = null
      bookedDateRanges.value = []
      maintenanceDates.value = []
      dateConflictMessage.value = ''
      pickupLocation.value = { address: '', lat: null, lng: null }
      dropoffLocation.value = { address: '', lat: null, lng: null }
      bookingForm.value = {
        start_date: '',
        end_date: '',
        pickup_location: '',
        dropoff_location: ''
      }
    }

    const processPayment = async () => {
      if (processingPayment.value || !currentBooking.value) return

      processingPayment.value = true
      try {
        const response = await apiStore.post('/payments/booking', {
          booking_id: currentBooking.value.id,
          payment_type: paymentForm.value.payment_type,
          payment_method: paymentForm.value.payment_method
        })

        console.log('Payment response:', response)

        // Handle different response structures
        const responseData = response.data || response
        const redirectUrl = responseData.data?.redirect_url || responseData.redirect_url || responseData.checkout_url

        // Redirect to PayMongo checkout
        if (redirectUrl) {
          window.location.href = redirectUrl
        } else {
          alert('Failed to create payment session. Please try again.')
        }
      } catch (error) {
        console.error('Error processing payment:', error)
        alert('Failed to initiate payment: ' + (error.response?.data?.message || error.response?.data?.error || error.message))
      } finally {
        processingPayment.value = false
      }
    }

    const closePaymentModal = () => {
      showPaymentModal.value = false
      currentBooking.value = null
      paymentForm.value = {
        payment_type: 'full',
        payment_method: 'gcash'
      }
      // Redirect to user dashboard
      router.push({ name: 'user-dashboard' })
    }

    const formatPrice = (price) => {
      return parseFloat(price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    }

    const getFeaturesList = (features) => {
      if (!features) return []
      if (Array.isArray(features)) return features.slice(0, 3)
      if (typeof features === 'string') {
        try {
          const parsed = JSON.parse(features)
          return Array.isArray(parsed) ? parsed.slice(0, 3) : []
        } catch {
          return features.split(',').map(f => f.trim()).slice(0, 3)
        }
      }
      return []
    }

    const getPlaceholderImage = (typeOrName) => {
      const name = (typeOrName || '').toLowerCase()
      if (name.includes('suv') || name.includes('cr-v')) {
        return '/images/vehicles/suv-placeholder.jpg'
      } else if (name.includes('truck') || name.includes('van')) {
        return '/images/vehicles/truck-placeholder.jpg'
      }
      return '/images/vehicles/car-placeholder.jpg'
    }

    const handleImageError = (event) => {
      event.target.src = '/images/vehicles/car-placeholder.jpg'
    }

    const logout = () => {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_info')
      router.push({ name: 'login' })
    }

    onMounted(async () => {
      await loadUserInfo()
      loadVehicles()
    })

    return {
      loading,
      submitting,
      allVehicles,
      filteredVehicles,
      searchQuery,
      sortBy,
      filters,
      bookedDateRanges,
      dateConflictMessage,
      showBookingModal,
      showPaymentModal,
      currentBooking,
      paymentForm,
      processingPayment,
      selectedVehicle,
      bookingForm,
      pickupLocation,
      dropoffLocation,
      userName,
      userLicenseStatus,
      isLicenseVerified,
      canBook,
      confirmedBookingsCount,
      bookingLimitMessage,
      hasActiveFilters,
      minDate,
      rentalDays,
      totalAmount,
      filterVehicles,
      sortVehicles,
      clearFilters,
      checkDateAvailability,
      formatDateRange,
      bookVehicle,
      submitBooking,
      closeBookingModal,
      processPayment,
      closePaymentModal,
      formatPrice,
      getFeaturesList,
      getPlaceholderImage,
      handleImageError,
      logout,
      loadVehicles,
      loadUserInfo
    }
  }
}
</script>

<style scoped>
.browse-vehicles {
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
.filters-section,
.vehicles-container,
.loading-state,
.empty-state {
  max-width: 1400px;
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

/* Filters */
.filters-section {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: flex-end;
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.search-box {
  flex: 1;
  min-width: 250px;
  position: relative;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #718096;
}

.search-box input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: #667eea;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 150px;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #4a5568;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-group label i {
  color: #667eea;
}

.filter-group select {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  transition: border-color 0.2s ease;
  background: white;
}

.filter-group select:focus {
  outline: none;
  border-color: #667eea;
}

.date-filter-input {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  transition: border-color 0.2s ease;
  background: white;
}

.date-filter-input:focus {
  outline: none;
  border-color: #667eea;
}

.btn-clear-filters {
  background: #fed7d7;
  color: #c53030;
  border: none;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
}

.btn-clear-filters:hover {
  background: #fc8181;
  color: white;
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

/* Results Info */
.results-info {
  margin-bottom: 1.5rem;
}

.results-info p {
  color: #718096;
  font-size: 0.875rem;
}

/* Vehicles Grid */
.vehicles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.vehicle-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.vehicle-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.vehicle-image-container {
  position: relative;
  height: 220px;
  overflow: hidden;
}

.vehicle-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.vehicle-status {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(4px);
}

.vehicle-status.available {
  color: #38a169;
  border: 2px solid #38a169;
}

.vehicle-status.unavailable,
.vehicle-status.rented,
.vehicle-status.maintenance {
  color: #c53030;
  border: 2px solid #c53030;
}

.vehicle-info {
  padding: 1.5rem;
}

.vehicle-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.vehicle-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
}

.vehicle-year {
  color: #718096;
  font-size: 0.875rem;
  font-weight: 500;
}

.vehicle-specs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.spec-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #4a5568;
  font-size: 0.875rem;
}

.spec-item i {
  color: #667eea;
  width: 16px;
}

.vehicle-features {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.feature {
  background: #f7fafc;
  color: #4a5568;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.vehicle-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.vehicle-price {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
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

.btn-book {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: transform 0.2s ease;
}

.btn-book:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn-book:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal */
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
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
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
}

.btn-close:hover {
  background: #e2e8f0;
  color: #2d3748;
}

.modal-body {
  padding: 1.5rem;
}

.booking-vehicle-info {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #f7fafc;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.booking-vehicle-info img {
  width: 120px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
}

.booking-vehicle-info h4 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
}

.booking-vehicle-info p {
  margin: 0 0 0.5rem 0;
  color: #718096;
  font-size: 0.875rem;
}

.daily-rate {
  font-weight: 600;
  color: #667eea;
  font-size: 1rem !important;
}

.booking-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: #4a5568;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-group label i {
  color: #667eea;
}

.form-group input {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
}

.form-group small.date-info {
  color: #667eea;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.alert-warning {
  background: #fef3c7;
  border: 1px solid #fbbf24;
  color: #92400e;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
  font-size: 0.875rem;
}

.alert-warning i {
  color: #f59e0b;
}

.booked-dates-info {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 0.5rem;
}

.booked-dates-info p {
  margin: 0 0 0.5rem 0;
  color: #0c4a6e;
  font-weight: 500;
  font-size: 0.875rem;
}

.booked-dates-info ul {
  margin: 0;
  padding-left: 1.5rem;
}

.booked-dates-info li {
  color: #075985;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.booking-summary {
  background: #f7fafc;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 0.5rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  color: #4a5568;
}

.summary-row.total {
  border-top: 2px solid #e2e8f0;
  margin-top: 0.5rem;
  padding-top: 1rem;
  font-weight: 600;
  font-size: 1.125rem;
  color: #2d3748;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-primary,
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
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
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
  .filters-section,
  .vehicles-container {
    padding: 1rem;
  }

  .page-title-section h1 {
    font-size: 1.5rem;
  }

  .filters-section {
    flex-direction: column;
  }

  .search-box,
  .filter-group {
    width: 100%;
    min-width: auto;
  }

  .vehicles-grid {
    grid-template-columns: 1fr;
  }

  .modal-overlay {
    padding: 1rem;
  }

  .modal-actions {
    flex-direction: column;
  }
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


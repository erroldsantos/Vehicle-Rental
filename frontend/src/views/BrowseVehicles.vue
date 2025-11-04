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
          <i class="fas fa-car"></i>
          Type
        </label>
        <select v-model="filters.type" @change="filterVehicles">
          <option value="">All Types</option>
          <option value="sedan">Sedan</option>
          <option value="suv">SUV</option>
          <option value="van">Van</option>
          <option value="truck">Truck</option>
          <option value="motorcycle">Motorcycle</option>
        </select>
      </div>

      <div class="filter-group">
        <label>
          <i class="fas fa-gas-pump"></i>
          Fuel Type
        </label>
        <select v-model="filters.fuel_type" @change="filterVehicles">
          <option value="">All Fuel Types</option>
          <option value="gasoline">Gasoline</option>
          <option value="diesel">Diesel</option>
          <option value="electric">Electric</option>
          <option value="hybrid">Hybrid</option>
        </select>
      </div>

      <div class="filter-group">
        <label>
          <i class="fas fa-users"></i>
          Capacity
        </label>
        <select v-model="filters.capacity" @change="filterVehicles">
          <option value="">Any Capacity</option>
          <option value="2">2 Passengers</option>
          <option value="4">4 Passengers</option>
          <option value="5">5 Passengers</option>
          <option value="7">7+ Passengers</option>
        </select>
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
            <span class="vehicle-status" :class="vehicle.status">
              {{ vehicle.status === 'available' ? 'Available' : 'Unavailable' }}
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
                :disabled="vehicle.status !== 'available'"
              >
                <i class="fas fa-calendar-check"></i>
                {{ vehicle.status === 'available' ? 'Book Now' : 'Unavailable' }}
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
                required
              >
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
                required
              >
            </div>

            <div class="form-group">
              <label for="pickup_location">
                <i class="fas fa-map-marker-alt"></i>
                Pickup Location
              </label>
              <input 
                type="text" 
                id="pickup_location"
                v-model="bookingForm.pickup_location"
                placeholder="Enter pickup location"
                required
              >
            </div>

            <div class="form-group">
              <label for="dropoff_location">
                <i class="fas fa-map-marker-alt"></i>
                Drop-off Location
              </label>
              <input 
                type="text" 
                id="dropoff_location"
                v-model="bookingForm.dropoff_location"
                placeholder="Enter drop-off location"
                required
              >
            </div>

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
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'

export default {
  name: 'BrowseVehicles',
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
    
    const showBookingModal = ref(false)
    const selectedVehicle = ref(null)
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
      return userInfo.value.name || 'User'
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

    const bookVehicle = (vehicle) => {
      if (vehicle.status !== 'available') return
      
      selectedVehicle.value = vehicle
      showBookingModal.value = true
      
      // Set default dates - use local date to avoid timezone issues
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
    }

    const submitBooking = async () => {
      if (submitting.value) return
      
      const userId = userInfo.value.id
      if (!userId) {
        alert('Please log in to make a booking')
        router.push({ name: 'login' })
        return
      }

      submitting.value = true
      try {
        const bookingData = {
          user_id: userId,
          vehicle_id: selectedVehicle.value.id,
          start_date: bookingForm.value.start_date,
          end_date: bookingForm.value.end_date,
          pickup_location: bookingForm.value.pickup_location,
          dropoff_location: bookingForm.value.dropoff_location,
          total_amount: totalAmount.value,
          status: 'pending'
        }

        await apiStore.post('/bookings', bookingData)
        
        alert('Booking request submitted successfully! You will receive a confirmation soon.')
        closeBookingModal()
        router.push({ name: 'user-dashboard' })
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
      bookingForm.value = {
        start_date: '',
        end_date: '',
        pickup_location: '',
        dropoff_location: ''
      }
    }

    const formatPrice = (price) => {
      return parseFloat(price).toFixed(2)
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

    onMounted(() => {
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
      showBookingModal,
      selectedVehicle,
      bookingForm,
      userName,
      hasActiveFilters,
      minDate,
      rentalDays,
      totalAmount,
      filterVehicles,
      sortVehicles,
      clearFilters,
      bookVehicle,
      submitBooking,
      closeBookingModal,
      formatPrice,
      getFeaturesList,
      getPlaceholderImage,
      handleImageError,
      logout
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
</style>

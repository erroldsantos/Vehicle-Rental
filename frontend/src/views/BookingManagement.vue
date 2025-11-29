<template>
  <div class="booking-management">
    <!-- Toast Notification -->
    <div v-if="notification.show" :class="['toast-notification', notification.type]">
      <i :class="['fas', notification.type === 'success' ? 'fa-check-circle' : notification.type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle']"></i>
      <span>{{ notification.message }}</span>
    </div>

    <div class="page-title">
      <h1>Booking Management</h1>
      <p>Manage vehicle reservations and customer bookings</p>
    </div>

    <!-- Booking Statistics -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <h3>Total Bookings</h3>
          <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-number">{{ stats.total_bookings || 0 }}</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <h3>Pending Bookings</h3>
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-number">{{ stats.pending_bookings || 0 }}</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <h3>Confirmed Bookings</h3>
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-number">{{ stats.confirmed_bookings || 0 }}</div>
      </div>
    </div>

    <div class="table-container">
      <div class="table-header">
        <h3 class="table-title">Bookings</h3>
        <div class="table-actions">
          <div class="filters">
            <select v-model="filters.status" @change="loadBookings" class="form-control">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="active">Active</option>
              <option value="ongoing">Ongoing</option>
              <option value="returned">Returned</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <input v-model="filters.search" @input="debounceSearch" placeholder="Search bookings..." class="form-control" />
          </div>
          <button class="btn btn-success" @click="showAddForm = true">
            <i class="fas fa-plus"></i> New Booking
          </button>
        </div>
      </div>

      <!-- Add Booking Form -->
      <div v-if="showAddForm" class="form-container">
        <div class="form-header">
          <h4>Create New Booking</h4>
          <button @click="cancelAdd" class="btn-close">×</button>
        </div>
        <form @submit.prevent="addBooking">
          <div class="form-row">
            <div class="form-group">
              <label>Customer:</label>
              <select v-model="newBooking.user_id" class="form-control" required>
                <option value="">Select Customer</option>
                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                  {{ user.first_name }} {{ user.last_name }} ({{ user.email }})
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Vehicle:</label>
              <select v-model="newBooking.vehicle_id" class="form-control" required @change="checkAvailability">
                <option value="">Select Vehicle</option>
                <option v-for="vehicle in availableVehicles" :key="vehicle.id" :value="vehicle.id">
                  {{ vehicle.brand }} {{ vehicle.model }} - {{ vehicle.plate_number }} (₱{{ vehicle.daily_rate }}/day)
                </option>
              </select>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Start Date:</label>
              <input v-model="newBooking.start_date" class="form-control" type="date" :min="minDate" required @change="checkAvailability" />
            </div>
            <div class="form-group">
              <label>End Date:</label>
              <input v-model="newBooking.end_date" class="form-control" type="date" :min="newBooking.start_date || minDate" required @change="checkAvailability" />
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Status:</label>
              <select v-model="newBooking.status" class="form-control" required>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="active">Active</option>
                <option value="ongoing">Ongoing</option>
                <option value="returned">Returned</option>
              </select>
            </div>
            <div class="form-group">
              <label>Total Amount:</label>
              <input v-model="estimatedCost" class="form-control" type="text" readonly />
            </div>
          </div>
          
          <div class="form-group">
            <label>Notes:</label>
            <textarea v-model="newBooking.notes" class="form-control" rows="3" placeholder="Any special requirements or notes..."></textarea>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-success" :disabled="!isFormValid">
              <i class="fas fa-save"></i> Create Booking
            </button>
            <button type="button" @click="cancelAdd" class="btn">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- Edit Booking Form -->
      <div v-if="showEditForm" class="form-container">
        <div class="form-header">
          <h4>Edit Booking</h4>
          <button @click="cancelEdit" class="btn-close">×</button>
        </div>
        <form @submit.prevent="updateBooking">
          <div class="form-row">
            <div class="form-group">
              <label>Booking Reference:</label>
              <input v-model="editingBooking.booking_reference" class="form-control" type="text" readonly />
            </div>
            <div class="form-group">
              <label>Customer:</label>
              <input v-model="editingBooking.customer_name" class="form-control" type="text" readonly />
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Start Date:</label>
              <input v-model="editingBooking.start_date" class="form-control" type="date" :min="minEditStartDate" required />
            </div>
            <div class="form-group">
              <label>End Date:</label>
              <input v-model="editingBooking.end_date" class="form-control" type="date" :min="editingBooking.start_date || minEditStartDate" required />
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Status:</label>
              <select v-model="editingBooking.status" class="form-control" required>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="active">Active</option>
                <option value="ongoing">Ongoing</option>
                <option value="returned">Returned</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="form-group">
              <label>Total Amount:</label>
              <input v-model="editingBooking.total_amount" class="form-control" type="number" step="0.01" readonly />
            </div>
          </div>
          
          <div class="form-group">
            <label>Notes:</label>
            <textarea v-model="editingBooking.notes" class="form-control" rows="3"></textarea>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save"></i> Update Booking
            </button>
            <button type="button" @click="cancelEdit" class="btn">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- Vehicle Inspection Modal -->
      <div v-if="showInspection" class="form-container">
        <div class="form-header">
          <h4>Vehicle Inspection - {{ inspectionBooking?.booking_reference }}</h4>
          <button @click="closeInspection" class="btn-close">×</button>
        </div>
        <form @submit.prevent="submitInspection">
          <div class="inspection-info">
            <p><strong>Vehicle:</strong> {{ inspectionBooking?.brand }} {{ inspectionBooking?.model }} - {{ inspectionBooking?.plate_number }}</p>
            <p><strong>Customer:</strong> {{ inspectionBooking?.first_name }} {{ inspectionBooking?.last_name }}</p>
          </div>

          <div class="form-group">
            <label>Vehicle Condition:</label>
            <div class="radio-group">
              <label>
                <input type="radio" v-model="inspection.has_damage" :value="false" required />
                <span><i class="fas fa-check-circle"></i> No Damage</span>
              </label>
              <label>
                <input type="radio" v-model="inspection.has_damage" :value="true" required />
                <span class="status-damage"><i class="fas fa-exclamation-triangle"></i> Has Damage</span>
              </label>
            </div>
          </div>

          <div v-if="inspection.has_damage" class="damage-details">
            <div class="form-group">
              <label>Damage Type:</label>
              <select v-model="inspection.damage_type" class="form-control" required>
                <option value="">Select damage type...</option>
                <option value="Scratches">Scratches</option>
                <option value="Dents">Dents</option>
                <option value="Broken Parts">Broken Parts</option>
                <option value="Interior Damage">Interior Damage</option>
                <option value="Mechanical Issues">Mechanical Issues</option>
                <option value="Tire Damage">Tire Damage</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="form-group">
              <label>Repair Cost (₱):</label>
              <input v-model.number="inspection.cost" class="form-control" type="number" step="0.01" min="0" required />
            </div>

            <div class="form-group">
              <label>Notes:</label>
              <textarea v-model="inspection.notes" class="form-control" rows="3" placeholder="Describe the damage..."></textarea>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeInspection" class="btn">
              <i class="fas fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="inspecting">
              <i v-if="inspecting" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-check"></i>
              {{ inspecting ? 'Processing...' : 'Submit Inspection' }}
            </button>
          </div>
        </form>
      </div>

      <div v-if="loading" class="loading">
        <i class="fas fa-spinner fa-spin"></i> Loading bookings...
      </div>
      
      <table v-else class="table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Customer</th>
            <th>Vehicle</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="bookings.length === 0">
            <td colspan="7" class="no-data">No bookings found</td>
          </tr>
          <tr v-for="booking in bookings" :key="booking.id">
            <td>{{ booking.booking_reference }}</td>
            <td>{{ booking.first_name }} {{ booking.last_name }}</td>
            <td>{{ booking.brand }} {{ booking.model }}<br><small>{{ booking.plate_number }}</small></td>
            <td>{{ formatDate(booking.start_date) }}</td>
            <td>{{ formatDate(booking.end_date) }}</td>
            <td>
              <span :class="['badge', 'status-' + booking.status]">
                {{ formatStatus(booking.status) }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="markAsOngoing(booking.id)" class="btn btn-sm btn-success" title="Mark as Ongoing" v-if="booking.status === 'active'">
                  <i class="fas fa-check"></i>
                </button>
                <button @click="markAsReturned(booking.id)" class="btn btn-sm btn-info" title="Mark as Returned" v-if="booking.status === 'ongoing'">
                  <i class="fas fa-check"></i>
                </button>
                <button @click="openInspection(booking)" class="btn btn-sm btn-primary" title="Check Vehicle" v-if="booking.status === 'returned'">
                  <i class="fas fa-search"></i> Check
                </button>
                <button @click="editBooking(booking)" class="btn btn-sm" title="Edit Booking">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="cancelBooking(booking.id)" class="btn btn-sm btn-warning" title="Cancel Booking" v-if="['pending', 'confirmed', 'active'].includes(booking.status)">
                  <i class="fas fa-ban"></i>
                </button>
                <button @click="deleteBooking(booking.id)" class="btn btn-sm btn-danger" title="Delete Booking">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useApiStore } from '../stores/api'

export default {
  name: 'BookingManagement',
  setup() {
    const apiStore = useApiStore()
    
    // Reactive data
    const loading = ref(false)
    const bookings = ref([])
    const stats = ref({})
    const showAddForm = ref(false)
    const showEditForm = ref(false)
    const showInspection = ref(false)
    const inspecting = ref(false)
    const searchTimeout = ref(null)
    const availableUsers = ref([])
    const availableVehicles = ref([])
    const inspectionBooking = ref(null)
    
    const notification = ref({
      show: false,
      type: 'success', // 'success', 'error', 'info'
      message: ''
    })
    
    const filters = ref({
      status: '',
      search: ''
    })
    
    const newBooking = ref({
      user_id: '',
      vehicle_id: '',
      start_date: '',
      end_date: '',
      status: 'pending',
      notes: ''
    })

    const inspection = ref({
      has_damage: false,
      damage_type: '',
      cost: 0,
      notes: ''
    })
    
    const editingBooking = ref({
      id: null,
      booking_reference: '',
      user_id: '',
      vehicle_id: '',
      start_date: '',
      end_date: '',
      total_amount: 0,
      status: 'pending',
      notes: ''
    })
    
    // Computed properties
    const minDate = computed(() => {
      const today = new Date()
      return today.toISOString().split('T')[0]
    })
    
    const minEditStartDate = computed(() => {
      // If editing an existing booking that already has a past start date, allow keeping it
      if (editingBooking.value.start_date) {
        const bookingDate = new Date(editingBooking.value.start_date)
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        bookingDate.setHours(0, 0, 0, 0)
        
        // If booking already started in the past, allow that date as minimum
        if (bookingDate < today) {
          return editingBooking.value.start_date
        }
      }
      return minDate.value
    })
    
    const isFormValid = computed(() => {
      return newBooking.value.user_id && 
             newBooking.value.vehicle_id && 
             newBooking.value.start_date && 
             newBooking.value.end_date
    })
    
    const estimatedCost = computed(() => {
      if (newBooking.value.vehicle_id && newBooking.value.start_date && newBooking.value.end_date) {
        const vehicle = availableVehicles.value.find(v => v.id == newBooking.value.vehicle_id)
        if (vehicle) {
          const start = new Date(newBooking.value.start_date)
          const end = new Date(newBooking.value.end_date)
          const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1
          const total = vehicle.daily_rate * days
          const formattedTotal = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
          const formattedRate = parseFloat(vehicle.daily_rate).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
          return `₱${formattedTotal} (${days} days × ₱${formattedRate}/day)`
        }
      }
      return 'Select dates and vehicle'
    })
    
    // Methods
    const loadBookings = async () => {
      console.log('Loading bookings with filters:', filters.value)
      loading.value = true
      
      try {
        const queryParams = new URLSearchParams()
        
        if (filters.value.status) queryParams.append('status', filters.value.status)
        if (filters.value.search) queryParams.append('search', filters.value.search)
        
        const queryString = queryParams.toString()
        const endpoint = `/bookings${queryString ? '?' + queryString : ''}`
        
        console.log('Fetching from endpoint:', endpoint)
        const response = await apiStore.get(endpoint)
        console.log('Bookings response:', response)
        
        // Handle both {bookings: [...]} and {data: {bookings: [...]}} formats
        bookings.value = response.bookings || response.data?.bookings || []
        stats.value = response.stats || response.data?.stats || {}
        
      } catch (error) {
        console.error('Failed to load bookings:', error)
        console.error('Error details:', error.response || error)
      } finally {
        loading.value = false
      }
    }
    
    const loadUsers = async () => {
      try {
        const response = await apiStore.get('/bookings/users')
        console.log('Users response:', response)
        availableUsers.value = response.users || response
      } catch (error) {
        console.error('Failed to load users:', error)
      }
    }
    
    const loadAvailableVehicles = async (startDate = null, endDate = null) => {
      try {
        let endpoint = '/vehicles'
        if (startDate && endDate) {
          endpoint = `/bookings/available-vehicles?start_date=${startDate}&end_date=${endDate}`
        }
        
        const response = await apiStore.get(endpoint)
        console.log('Vehicles response:', response)
        // Handle both {vehicles: [...]} and {data: {vehicles: [...]}} formats
        availableVehicles.value = response.vehicles || response.data?.vehicles || []
      } catch (error) {
        console.error('Failed to load vehicles:', error)
      }
    }

    const showNotification = (message, type = 'success') => {
      notification.value = {
        show: true,
        type,
        message
      }
      
      // Auto hide after 4 seconds
      setTimeout(() => {
        notification.value.show = false
      }, 8000)
    }
    
    const checkAvailability = async () => {
      if (newBooking.value.start_date && newBooking.value.end_date) {
        await loadAvailableVehicles(newBooking.value.start_date, newBooking.value.end_date)
      }
    }
    
    const addBooking = async () => {
      console.log('Adding booking:', newBooking.value)
      
      try {
        const bookingData = {
          user_id: parseInt(newBooking.value.user_id),
          vehicle_id: parseInt(newBooking.value.vehicle_id),
          start_date: newBooking.value.start_date,
          end_date: newBooking.value.end_date,
          status: newBooking.value.status,
          notes: newBooking.value.notes
        }
        
        console.log('Sending booking data:', bookingData)
        const response = await apiStore.post('/bookings', bookingData)
        console.log('Add booking response:', response)
        
        bookings.value.unshift(response)
        cancelAdd()
        
        await loadBookings() // Reload to get updated stats
      } catch (error) {
        console.error('Failed to add booking:', error)
        console.error('Error details:', error.response || error)
      }
    }
    
    const cancelAdd = () => {
      showAddForm.value = false
      newBooking.value = {
        user_id: '',
        vehicle_id: '',
        start_date: '',
        end_date: '',
        status: 'pending',
        notes: ''
      }
    }
    
    const editBooking = (booking) => {
      console.log('Edit booking clicked:', booking)
      console.log('User ID from booking:', booking.user_id)
      console.log('Vehicle ID from booking:', booking.vehicle_id)
      
      // Ensure user_id and vehicle_id are properly set
      const userId = booking.user_id || null
      const vehicleId = booking.vehicle_id || null
      const customerName = `${booking.first_name} ${booking.last_name}`
      
      console.log('Setting user_id to:', userId)
      console.log('Setting vehicle_id to:', vehicleId)
      
      editingBooking.value = {
        id: booking.id,
        booking_reference: booking.booking_reference,
        user_id: userId,
        vehicle_id: vehicleId,
        customer_name: customerName,
        start_date: booking.start_date,
        end_date: booking.end_date,
        total_amount: booking.total_amount,
        status: booking.status,
        notes: booking.notes || ''
      }
      
      console.log('editingBooking.value:', editingBooking.value)
      
      showEditForm.value = true
      showAddForm.value = false
    }
    
    const updateBooking = async () => {
      console.log('Updating booking:', editingBooking.value)
      
      try {
        const bookingData = {
          user_id: parseInt(editingBooking.value.user_id),
          start_date: editingBooking.value.start_date,
          end_date: editingBooking.value.end_date,
          status: editingBooking.value.status,
          notes: editingBooking.value.notes
        }
        
        console.log('Sending update data:', bookingData)
        const response = await apiStore.put(`/bookings/${editingBooking.value.id}`, bookingData)
        console.log('Update response:', response)
        
        // Check if there's a warning (user has too many confirmed bookings)
        if (response.warning) {
          showNotification(response.warning, 'info')
        }
        
        // Update the booking in the list
        const bookingToUpdate = response.booking || response
        const index = bookings.value.findIndex(b => b.id === editingBooking.value.id)
        if (index !== -1) {
          bookings.value[index] = bookingToUpdate
        }
        
        cancelEdit()
        await loadBookings() // Reload to get updated stats
      } catch (error) {
        console.error('Failed to update booking:', error)
        console.error('Error details:', error.response)
        const errorMsg = error.response?.data?.error || error.response?.data?.message || error.message || 'Unknown error'
        showNotification('Failed to update booking: ' + errorMsg, 'error')
      }
    }
    
    const cancelEdit = () => {
      showEditForm.value = false
      editingBooking.value = {
        id: null,
        booking_reference: '',
        user_id: '',
        vehicle_id: '',
        start_date: '',
        end_date: '',
        total_amount: 0,
        status: 'pending',
        notes: ''
      }
    }
    
    const cancelBooking = async (id) => {
      console.log('Cancel booking clicked for ID:', id)
      
      try {
        console.log('Cancelling booking with ID:', id)
        const response = await apiStore.put(`/bookings/${id}/cancel`, {})
        console.log('Cancel response received:', response)
        
        await loadBookings() // Reload to get updated data
      } catch (error) {
        console.error('CANCEL ERROR:', error)
        console.error('Error message:', error.message)
        console.error('Error response:', error.response)
      }
    }
    
    const markAsOngoing = async (id) => {
      if (!confirm('Mark this booking as ongoing? This means the customer has picked up the vehicle.')) {
        return
      }
      
      try {
        await apiStore.put(`/bookings/${id}`, { status: 'ongoing' })
        await loadBookings()
        showNotification('Booking marked as ongoing successfully!', 'success')
      } catch (error) {
        console.error('Error marking as ongoing:', error)
        showNotification('Failed to update booking status', 'error')
      }
    }
    
    const markAsReturned = async (id) => {
      if (!confirm('Mark this booking as returned? This means the customer has returned the vehicle.')) {
        return
      }
      
      try {
        await apiStore.put(`/bookings/${id}`, { status: 'returned' })
        await loadBookings()
        showNotification('Booking marked as returned successfully!', 'success')
      } catch (error) {
        console.error('Error marking as returned:', error)
        showNotification('Failed to update booking status', 'error')
      }
    }

    const openInspection = (booking) => {
      inspectionBooking.value = booking
      inspection.value = {
        has_damage: false,
        damage_type: '',
        cost: 0,
        notes: ''
      }
      showInspection.value = true
    }

    const closeInspection = () => {
      showInspection.value = false
      inspectionBooking.value = null
      inspection.value = {
        has_damage: false,
        damage_type: '',
        cost: 0,
        notes: ''
      }
    }

    const submitInspection = async () => {
      inspecting.value = true
      
      try {
        const payload = {
          has_damage: inspection.value.has_damage
        }

        // Store the damage state before closing inspection
        const hasDamage = inspection.value.has_damage
        const damageCost = inspection.value.cost

        // Only include damage details if there is damage
        if (hasDamage) {
          payload.damage_type = inspection.value.damage_type
          payload.cost = inspection.value.cost
          payload.notes = inspection.value.notes || `Damage from rental: ${inspection.value.damage_type}`
        }

        const response = await apiStore.post(`/maintenance/inspect/${inspectionBooking.value.id}`, payload)
        
        await loadBookings()
        closeInspection()
        
        if (hasDamage) {
          showNotification(`Damage recorded! Customer must pay ₱${damageCost.toFixed(2)} for repairs before booking can be completed.`, 'info')
        } else {
          showNotification('Vehicle inspection complete - no damage found. Booking has been completed.', 'success')
        }
      } catch (error) {
        console.error('Error submitting inspection:', error)
        showNotification('Failed to submit inspection: ' + (error.response?.data?.message || error.message), 'error')
      } finally {
        inspecting.value = false
      }
    }
    
    const deleteBooking = async (id) => {
      console.log('Delete booking clicked for ID:', id)
      
      try {
        console.log('Deleting booking with ID:', id)
        const response = await apiStore.delete(`/bookings/${id}`)
        console.log('Delete response received:', response)
        
        bookings.value = bookings.value.filter(b => b.id !== id)
        console.log('Updated bookings list:', bookings.value)
        
        await loadBookings() // Reload to get updated stats
      } catch (error) {
        console.error('DELETE ERROR:', error)
        console.error('Error message:', error.message)
        console.error('Error response:', error.response)
      }
    }
    
    const debounceSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
      
      searchTimeout.value = setTimeout(() => {
        loadBookings()
      }, 500)
    }
    
    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      })
    }
    
    const formatStatus = (status) => {
      return status.charAt(0).toUpperCase() + status.slice(1)
    }
    
    // Load data on mount
    onMounted(async () => {
      await loadBookings()
      await loadUsers()
      await loadAvailableVehicles()
    })
    
    return {
      loading,
      bookings,
      stats,
      notification,
      showAddForm,
      showEditForm,
      showInspection,
      inspecting,
      filters,
      newBooking,
      editingBooking,
      inspectionBooking,
      inspection,
      availableUsers,
      availableVehicles,
      minDate,
      minEditStartDate,
      isFormValid,
      estimatedCost,
      showNotification,
      loadBookings,
      addBooking,
      cancelAdd,
      editBooking,
      updateBooking,
      cancelEdit,
      cancelBooking,
      markAsOngoing,
      markAsReturned,
      openInspection,
      closeInspection,
      submitInspection,
      deleteBooking,
      debounceSearch,
      checkAvailability,
      formatDate,
      formatStatus
    }
  }
}
</script>

<style scoped>
/* Booking Management Specific Styles */
.booking-management {
  padding: 0;
}

.page-title {
  margin-bottom: 30px;
}

.page-title h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 8px 0;
}

.page-title p {
  color: #6b7280;
  font-size: 1rem;
  margin: 0;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e7eb;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.stat-header h3 {
  font-size: 0.9rem;
  font-weight: 600;
  color: #6b7280;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-header i {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: white;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
}

.stat-number {
  font-size: 2.2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 30px;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.table-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.table-actions {
  display: flex;
  align-items: center;
  gap: 15px;
}

.filters {
  display: flex;
  gap: 10px;
  align-items: center;
}

/* Form Styles */
.form-container {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  margin: 20px 0;
  overflow: hidden;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 25px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
}

.form-header h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: all 0.3s ease;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #374151;
}

form {
  padding: 25px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
  font-size: 0.9rem;
}

.form-control {
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-control:disabled,
.form-control[readonly] {
  background-color: #f9fafb;
  color: #6b7280;
}

textarea.form-control {
  resize: vertical;
  min-height: 80px;
}

.form-actions {
  display: flex;
  gap: 12px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
  margin-top: 20px;
}

/* Button Styles */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  justify-content: center;
  min-height: 42px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-1px);
}

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-warning:hover:not(:disabled) {
  background: #d97706;
  transform: translateY(-1px);
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
  transform: translateY(-1px);
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.8rem;
  min-height: 32px;
}

/* Table Styles */
.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 16px 20px;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

.table th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.table tbody tr {
  transition: background-color 0.2s ease;
}

.table tbody tr:hover {
  background: #f8fafc;
}

.table td {
  color: #1f2937;
  font-size: 0.9rem;
}

.no-data {
  text-align: center;
  color: #6b7280;
  font-style: italic;
  padding: 40px 20px;
}

/* Badge Styles */
.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-confirmed {
  background: #dcfce7;
  color: #166534;
}

.status-active {
  background: #ddd6fe;
  color: #5b21b6;
}

.status-ongoing {
  background: #bfdbfe;
  color: #1e40af;
}

.status-returned {
  background: #e0e7ff;
  color: #4338ca;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.status-cancelled {
  background: #fecaca;
  color: #dc2626;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
}

.action-buttons .btn {
  padding: 6px 10px;
  min-height: auto;
}

/* Loading State */
.loading {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
  font-size: 1rem;
}

.loading i {
  margin-right: 10px;
  color: #6366f1;
}

/* Inspection Modal Styles */
.inspection-info {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.inspection-info p {
  margin: 5px 0;
  color: #495057;
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.radio-group label {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px;
  border: 2px solid #dee2e6;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.radio-group label:hover {
  border-color: #6366f1;
  background: #f8f9fa;
}

.radio-group input[type="radio"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.radio-group input[type="radio"]:checked + span {
  font-weight: 600;
}

.status-ok {
  color: #27ae60;
  font-size: 16px;
}

.status-ok i {
  margin-right: 8px;
}

.status-damage {
  color: #e74c3c;
  font-size: 16px;
}

.status-damage i {
  margin-right: 8px;
}

.damage-details {
  background: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 8px;
  padding: 20px;
  margin-top: 15px;
}

.damage-details .form-group {
  margin-bottom: 15px;
}

.damage-details .form-group:last-child {
  margin-bottom: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .table-actions {
    flex-direction: column;
    gap: 10px;
    align-items: stretch;
  }
  
  .filters {
    flex-direction: column;
    gap: 8px;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .table-header {
    padding: 16px 20px;
    flex-direction: column;
    gap: 15px;
    align-items: stretch;
  }
  
  .table-container {
    overflow-x: auto;
  }
  
  .table {
    min-width: 800px;
  }
  
  .form-container {
    margin: 15px 0;
  }
  
  form {
    padding: 20px;
  }
}

@media (max-width: 480px) {
  .page-title h1 {
    font-size: 1.5rem;
  }
  
  .stat-card {
    padding: 20px;
  }
  
  .stat-number {
    font-size: 1.8rem;
  }
  
  .table th,
  .table td {
    padding: 12px 16px;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 4px;
  }
  
  .action-buttons .btn {
    font-size: 0.75rem;
  }
}

/* Toast Notification Styles */
.toast-notification {
  position: fixed;
  top: 80px;
  right: 20px;
  min-width: 300px;
  max-width: 500px;
  padding: 16px 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 9999;
  animation: slideIn 0.3s ease-out;
  border-left: 4px solid;
}

.toast-notification i {
  font-size: 20px;
}

.toast-notification span {
  flex: 1;
  font-size: 14px;
  line-height: 1.5;
}

.toast-notification.success {
  border-left-color: #27ae60;
}

.toast-notification.success i {
  color: #27ae60;
}

.toast-notification.error {
  border-left-color: #e74c3c;
}

.toast-notification.error i {
  color: #e74c3c;
}

.toast-notification.info {
  border-left-color: #3498db;
}

.toast-notification.info i {
  color: #3498db;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>
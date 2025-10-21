<template>
  <div class="booking-management">
    <!-- Page Header -->
    <div class="page-header">
      <h1>Booking Management</h1>
      <div class="header-actions">
        <button class="action-btn" @click="showAddForm = true">
          <i class="fas fa-plus"></i> New Booking
        </button>
        <button class="action-btn secondary">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Booking Stats -->
    <div class="stats-grid" style="margin-bottom: 40px;">
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-content">
          <h3>{{ bookingStats.total }}</h3>
          <p>Total Bookings</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ bookingStats.confirmed }}</h3>
          <p>Confirmed</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ bookingStats.pending }}</h3>
          <p>Pending</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-flag-checkered"></i>
        </div>
        <div class="stat-content">
          <h3>{{ bookingStats.completed }}</h3>
          <p>Completed</p>
        </div>
      </div>
    </div>

    <!-- Add Booking Form -->
    <div v-if="showAddForm" class="form-card">
      <div class="card-header">
        <h2>Create New Booking</h2>
        <button class="close-btn" @click="cancelAdd">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form @submit.prevent="addBooking" class="booking-form">
        <div class="form-grid">
          <div class="form-group">
            <label>Customer Name</label>
            <input v-model="newBooking.customerName" class="form-input" type="text" required placeholder="Enter customer name" />
          </div>
          <div class="form-group">
            <label>Vehicle</label>
            <select v-model="newBooking.vehicleId" class="form-input" required>
              <option value="">Select Vehicle</option>
              <option value="1">Toyota Camry - ABC-1234</option>
              <option value="2">Honda CR-V - DEF-5678</option>
              <option value="3">Ford Transit - GHI-9012</option>
            </select>
          </div>
          <div class="form-group">
            <label>Start Date</label>
            <input v-model="newBooking.startDate" class="form-input" type="date" required />
          </div>
          <div class="form-group">
            <label>End Date</label>
            <input v-model="newBooking.endDate" class="form-input" type="date" required />
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="action-btn">
            <i class="fas fa-plus"></i> Create Booking
          </button>
          <button type="button" class="action-btn secondary" @click="cancelAdd">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Bookings List -->
    <div class="data-card">
      <div class="card-header">
        <h2>All Bookings</h2>
      </div>
      
      <div v-if="loading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        Loading bookings...
      </div>
      
      <div v-else class="modern-table">
        <div class="table-row header-row">
          <span>Reference</span>
          <span>Customer</span>
          <span>Vehicle</span>
          <span>Start Date</span>
          <span>End Date</span>
          <span>Status</span>
          <span>Actions</span>
        </div>
        <div class="table-row" v-for="booking in bookings" :key="booking.id">
          <span class="booking-ref">{{ booking.reference }}</span>
          <span class="customer-name">{{ booking.customerName }}</span>
          <span class="vehicle-name">{{ booking.vehicle }}</span>
          <span>{{ booking.startDate }}</span>
          <span>{{ booking.endDate }}</span>
          <span>
            <span :class="['status-badge', booking.status.toLowerCase()]">
              {{ booking.status }}
            </span>
          </span>
          <span class="actions">
            <button class="action-btn-sm" @click="updateStatus(booking)">
              <i class="fas fa-edit"></i> Update
            </button>
            <button class="action-btn-sm danger" @click="cancelBooking(booking.id)">
              <i class="fas fa-ban"></i> Cancel
            </button>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'

export default {
  name: 'BookingManagement',
  setup() {
    const loading = ref(false)
    const showAddForm = ref(false)
    
    const bookings = ref([
      {
        id: 1,
        reference: 'BK-001234',
        customerName: 'John Smith',
        vehicle: 'Toyota Camry',
        startDate: '2025-10-25',
        endDate: '2025-10-30',
        status: 'Confirmed'
      },
      {
        id: 2,
        reference: 'BK-001235',
        customerName: 'Sarah Johnson',
        vehicle: 'Honda CR-V',
        startDate: '2025-10-22',
        endDate: '2025-10-24',
        status: 'Pending'
      },
      {
        id: 3,
        reference: 'BK-001236',
        customerName: 'Mike Davis',
        vehicle: 'Ford Transit',
        startDate: '2025-10-20',
        endDate: '2025-10-21',
        status: 'Completed'
      }
    ])

    const newBooking = ref({
      customerName: '',
      vehicleId: '',
      startDate: '',
      endDate: ''
    })

    const bookingStats = computed(() => {
      const total = bookings.value.length
      const confirmed = bookings.value.filter(b => b.status === 'Confirmed').length
      const pending = bookings.value.filter(b => b.status === 'Pending').length
      const completed = bookings.value.filter(b => b.status === 'Completed').length
      
      return { total, confirmed, pending, completed }
    })

    const generateReference = () => {
      return 'BK-' + Math.random().toString(36).substr(2, 6).toUpperCase()
    }

    const addBooking = () => {
      const vehicleNames = {
        '1': 'Toyota Camry',
        '2': 'Honda CR-V',
        '3': 'Ford Transit'
      }

      const booking = {
        id: bookings.value.length + 1,
        reference: generateReference(),
        customerName: newBooking.value.customerName,
        vehicle: vehicleNames[newBooking.value.vehicleId],
        startDate: newBooking.value.startDate,
        endDate: newBooking.value.endDate,
        status: 'Pending'
      }
      
      bookings.value.unshift(booking)
      cancelAdd()
    }

    const cancelAdd = () => {
      showAddForm.value = false
      newBooking.value = {
        customerName: '',
        vehicleId: '',
        startDate: '',
        endDate: ''
      }
    }

    const updateStatus = (booking) => {
      const statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled']
      const currentIndex = statuses.indexOf(booking.status)
      const nextIndex = (currentIndex + 1) % statuses.length
      booking.status = statuses[nextIndex]
    }

    const cancelBooking = (id) => {
      if (confirm('Are you sure you want to cancel this booking?')) {
        const booking = bookings.value.find(b => b.id === id)
        if (booking) {
          booking.status = 'Cancelled'
        }
      }
    }

    onMounted(() => {
      loading.value = false
    })

    return {
      loading,
      showAddForm,
      bookings,
      newBooking,
      bookingStats,
      addBooking,
      cancelAdd,
      updateStatus,
      cancelBooking
    }
  }
}
</script>
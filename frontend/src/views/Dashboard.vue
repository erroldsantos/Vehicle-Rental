<template>
  <div class="dashboard">
    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatsCard
        title="Total Vehicles"
        :value="stats.totalVehicles"
        icon="fas fa-car"
        variant="primary"
      />
      <StatsCard
        title="Confirmed Bookings"
        :value="stats.activeBookings"
        icon="fas fa-calendar-check"
        variant="primary"
      />
      <StatsCard
        title="Total Revenue"
        :value="'₱' + stats.totalRevenue"
        icon="fas fa-dollar-sign"
        variant="primary"
      />
      <StatsCard
        title="Maintenance Due"
        :value="stats.maintenanceDue"
        icon="fas fa-wrench"
        variant="primary"
      />
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-section">
      <h2>Quick Actions</h2>
      <div class="quick-actions">
        <button class="action-btn" @click="quickAction('vehicle')">
          <i class="fas fa-plus"></i> Add Vehicle
        </button>
        <button class="action-btn" @click="quickAction('booking')">
          <i class="fas fa-calendar-plus"></i> New Booking
        </button>
        <button class="action-btn" @click="quickAction('maintenance')">
          <i class="fas fa-wrench"></i> Schedule Maintenance
        </button>
        <button class="action-btn" @click="quickAction('user')">
          <i class="fas fa-user-plus"></i> Add User
        </button>
      </div>
    </div>

    <!-- Pending Bookings -->
    <div class="dashboard-section pending-section">
      <div class="section-header">
        <h2><i class="fas fa-clock"></i> Pending Bookings</h2>
        <a href="#" class="view-all-link" @click.prevent="navigateToBookings">View All →</a>
      </div>
      <div v-if="loadingPending" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading pending bookings...</p>
      </div>
      <div v-else-if="pendingBookings.length === 0" class="empty-state">
        <i class="fas fa-calendar-alt"></i>
        <p>No pending bookings</p>
      </div>
      <div v-else class="bookings-table">
        <div class="table-row header-row">
          <span>Reference</span>
          <span>Customer</span>
          <span>Vehicle</span>
          <span>Start Date</span>
          <span>End Date</span>
          <span>Amount</span>
          <span>Actions</span>
        </div>
        <div class="table-row pending-row" v-for="booking in pendingBookings" :key="booking.id">
          <span class="reference">{{ booking.booking_reference }}</span>
          <span>{{ booking.first_name }} {{ booking.last_name }}</span>
          <span>{{ booking.brand }} {{ booking.model }}</span>
          <span>{{ formatDate(booking.start_date) }}</span>
          <span>{{ formatDate(booking.end_date) }}</span>
          <span>₱{{ parseFloat(booking.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
          <span class="action-buttons">
            <button class="btn-view" @click="viewBookingDetails(booking)" title="View Details">
              <i class="fas fa-eye"></i>
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- Active Bookings -->
    <div class="dashboard-section active-section">
      <div class="section-header">
        <h2><i class="fas fa-calendar-day"></i> Active Bookings</h2>
        <a href="#" class="view-all-link" @click.prevent="navigateToBookings">View All →</a>
      </div>
      <div v-if="loadingActive" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading active bookings...</p>
      </div>
      <div v-else-if="activeBookings.length === 0" class="empty-state">
        <i class="fas fa-calendar-day"></i>
        <p>No active bookings found</p>
      </div>
      <div v-else class="bookings-table">
        <div class="table-row header-row">
          <span>Reference</span>
          <span>Customer</span>
          <span>Vehicle</span>
          <span>Start Date</span>
          <span>End Date</span>
          <span>Actions</span>
        </div>
        <div class="table-row" v-for="booking in activeBookings" :key="booking.id">
          <span>{{ booking.booking_reference }}</span>
          <span>{{ booking.first_name }} {{ booking.last_name }}</span>
          <span>{{ booking.brand }} {{ booking.model }}</span>
          <span>{{ formatDate(booking.start_date) }}</span>
          <span>{{ formatDate(booking.end_date) }}</span>
          <span>
            <button class="btn-view" @click="viewBookingDetails(booking)" title="View Details">
              <i class="fas fa-eye"></i>
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- Ongoing Bookings -->
    <div class="dashboard-section ongoing-section">
      <div class="section-header">
        <h2><i class="fas fa-car-side"></i> Ongoing Bookings</h2>
        <a href="#" class="view-all-link" @click.prevent="navigateToBookings">View All →</a>
      </div>
      <div v-if="loadingOngoing" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading ongoing bookings...</p>
      </div>
      <div v-else-if="ongoingBookings.length === 0" class="empty-state">
        <i class="fas fa-car-side"></i>
        <p>No ongoing bookings found</p>
      </div>
      <div v-else class="bookings-table">
        <div class="table-row header-row">
          <span>Reference</span>
          <span>Customer</span>
          <span>Vehicle</span>
          <span>Start Date</span>
          <span>End Date</span>
          <span>Actions</span>
        </div>
        <div class="table-row" v-for="booking in ongoingBookings" :key="booking.id">
          <span>{{ booking.booking_reference }}</span>
          <span>{{ booking.first_name }} {{ booking.last_name }}</span>
          <span>{{ booking.brand }} {{ booking.model }}</span>
          <span>{{ formatDate(booking.start_date) }}</span>
          <span>{{ formatDate(booking.end_date) }}</span>
          <span>
            <button class="btn-view" @click="viewBookingDetails(booking)" title="View Details">
              <i class="fas fa-eye"></i>
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- Booking Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="closeDetailsModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Booking Details</h3>
          <button class="btn-close" @click="closeDetailsModal">&times;</button>
        </div>
        <div class="modal-body" v-if="selectedBooking">
          <div class="detail-row">
            <span class="label">Booking Reference:</span>
            <span class="value">{{ selectedBooking.booking_reference }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Customer:</span>
            <span class="value">{{ selectedBooking.first_name }} {{ selectedBooking.last_name }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Email:</span>
            <span class="value">{{ selectedBooking.email }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Vehicle:</span>
            <span class="value">{{ selectedBooking.brand }} {{ selectedBooking.model }} ({{ selectedBooking.plate_number }})</span>
          </div>
          <div class="detail-row">
            <span class="label">Start Date:</span>
            <span class="value">{{ formatDate(selectedBooking.start_date) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">End Date:</span>
            <span class="value">{{ formatDate(selectedBooking.end_date) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Total Amount:</span>
            <span class="value">₱{{ parseFloat(selectedBooking.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
          </div>
          <div class="detail-row" v-if="selectedBooking.pickup_location">
            <span class="label">Pickup Location:</span>
            <span class="value">{{ selectedBooking.pickup_location }}</span>
          </div>
          <div class="detail-row" v-if="selectedBooking.dropoff_location">
            <span class="label">Drop-off Location:</span>
            <span class="value">{{ selectedBooking.dropoff_location }}</span>
          </div>
          <div class="detail-row" v-if="selectedBooking.notes">
            <span class="label">Notes:</span>
            <span class="value">{{ selectedBooking.notes }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeDetailsModal">Close</button>
          <button class="btn btn-primary" @click="editBooking">Edit Booking</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'
import StatsCard from '../components/StatsCard.vue'

export default {
  name: 'Dashboard',
  components: {
    StatsCard
  },
  setup() {
    const router = useRouter()
    const apiStore = useApiStore()
    
    const stats = ref({
      totalVehicles: 0,
      activeBookings: 0,
      totalRevenue: '0',
      maintenanceDue: 0
    })

    const activeBookings = ref([])
    const ongoingBookings = ref([])
    const pendingBookings = ref([])
    const loading = ref(true)
    const loadingPending = ref(true)
    const loadingActive = ref(true)
    const loadingOngoing = ref(true)
    const confirmingId = ref(null)
    const showDetailsModal = ref(false)
    const selectedBooking = ref(null)

    const quickAction = (type) => {
      const actions = {
        vehicle: 'vehicles',
        booking: 'bookings',
        maintenance: 'maintenance',
        user: 'users'
      }
      
      if (actions[type]) {
        router.push({ name: actions[type] })
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
    }

    const viewBookingDetails = (booking) => {
      selectedBooking.value = booking
      showDetailsModal.value = true
    }

    const closeDetailsModal = () => {
      showDetailsModal.value = false
      selectedBooking.value = null
    }

    const editBooking = () => {
      if (selectedBooking.value) {
        router.push({ name: 'bookings' })
        closeDetailsModal()
      }
    }

    const navigateToBookings = () => {
      router.push({ name: 'bookings' })
    }

    const navigateToVehicles = () => {
      router.push({ name: 'vehicles' })
    }

    const loadDashboardData = async () => {
      loading.value = true
      try {
        // Load dashboard statistics
        await Promise.all([
          loadVehicleStats(),
          loadBookingStats(),
          loadPendingBookings(),
          loadActiveBookings(),
          loadOngoingBookings(),
          loadMaintenanceStats()
        ])
      } catch (error) {
        console.error('Failed to load dashboard data:', error)
      } finally {
        loading.value = false
      }
    }

    const loadVehicleStats = async () => {
      try {
        const data = await apiStore.get('/vehicles')
        // Handle {data: {vehicles: [...]}} format
        const vehicles = data.data?.vehicles || data.vehicles || []
        stats.value.totalVehicles = vehicles.length
      } catch (error) {
        console.error('Error loading vehicle stats:', error)
      }
    }

    const loadBookingStats = async () => {
      try {
        const data = await apiStore.get('/bookings')
        // Handle both {bookings: [...]} and {data: {bookings: [...]}} formats
        const bookings = data.bookings || data.data?.bookings || []
        
        // Count confirmed bookings only
        const activeBookings = bookings.filter(booking => 
          booking.status === 'confirmed'
        ).length
        
        stats.value.activeBookings = activeBookings
        
        // Calculate total revenue from completed bookings
        const totalRevenue = bookings
          .filter(booking => booking.status === 'completed')
          .reduce((sum, booking) => sum + parseFloat(booking.total_amount || 0), 0)
        
        stats.value.totalRevenue = totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      } catch (error) {
        console.error('Error loading booking stats:', error)
      }
    }

    const loadActiveBookings = async () => {
      loadingActive.value = true
      try {
        const data = await apiStore.get('/bookings')
        const bookings = data.bookings || data.data?.bookings || []
        
        // Get only active bookings and sort by start date
        const active = bookings
          .filter(booking => booking.status === 'active')
          .sort((a, b) => new Date(a.start_date) - new Date(b.start_date))
          .slice(0, 10)  // Show latest 10 active bookings
        
        activeBookings.value = active
      } catch (error) {
        console.error('Error loading active bookings:', error)
      } finally {
        loadingActive.value = false
      }
    }

    const loadOngoingBookings = async () => {
      loadingOngoing.value = true
      try {
        const data = await apiStore.get('/bookings')
        const bookings = data.bookings || data.data?.bookings || []
        
        // Get only ongoing bookings and sort by start date
        const ongoing = bookings
          .filter(booking => booking.status === 'ongoing')
          .sort((a, b) => new Date(a.start_date) - new Date(b.start_date))
          .slice(0, 10)  // Show latest 10 ongoing bookings
        
        ongoingBookings.value = ongoing
      } catch (error) {
        console.error('Error loading ongoing bookings:', error)
      } finally {
        loadingOngoing.value = false
      }
    }

    const loadPendingBookings = async () => {
      loadingPending.value = true
      try {
        const data = await apiStore.get('/bookings')
        const bookings = data.bookings || data.data?.bookings || []
        
        // Get only pending bookings and sort by creation date (newest first)
        const pending = bookings
          .filter(booking => booking.status === 'pending')
          .sort((a, b) => new Date(b.created_at || b.start_date) - new Date(a.created_at || a.start_date))
          .slice(0, 10)  // Show latest 10 pending bookings
        
        pendingBookings.value = pending
      } catch (error) {
        console.error('Error loading pending bookings:', error)
      } finally {
        loadingPending.value = false
      }
    }

    const loadMaintenanceStats = async () => {
      try {
        const data = await apiStore.get('/maintenance')
        // Handle both {maintenance: [...]} and {data: {maintenance: [...]}} formats
        const maintenance = data.maintenance || data.data?.maintenance || []
        
        // Count pending or scheduled maintenance
        const maintenanceDue = maintenance.filter(item => 
          item.status === 'pending' || item.status === 'scheduled'
        ).length
        
        stats.value.maintenanceDue = maintenanceDue
      } catch (error) {
        console.error('Error loading maintenance stats:', error)
        // If maintenance API doesn't exist, keep default value
        stats.value.maintenanceDue = 0
      }
    }

    onMounted(() => {
      loadDashboardData()
    })

    return {
      stats,
      activeBookings,
      ongoingBookings,
      pendingBookings,
      loading,
      loadingPending,
      loadingActive,
      loadingOngoing,
      confirmingId,
      showDetailsModal,
      selectedBooking,
      quickAction,
      navigateToBookings,
      navigateToVehicles,
      formatDate,
      viewBookingDetails,
      closeDetailsModal,
      editBooking
    }
  }
}
</script>

<style scoped>
/* Dashboard Layout */
.dashboard {
  max-width: 1400px;
  margin: 0 auto;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

/* Quick Actions Section */
.quick-actions-section {
  margin-bottom: 30px;
}

.quick-actions-section h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1rem;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.action-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  padding: 14px 20px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.action-btn i {
  font-size: 16px;
}

/* Dashboard Sections */
.dashboard-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 30px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e7eb;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-all-link {
  color: #3b82f6;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: color 0.2s;
}

.view-all-link:hover {
  color: #2563eb;
}

/* Loading and Empty States */
.loading-state, .empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #666;
}

.loading-state i, .empty-state i {
  font-size: 36px;
  margin-bottom: 12px;
  color: #3498db;
}

.empty-state i {
  color: #95a5a6;
}

.loading-state p, .empty-state p {
  margin: 0;
  font-size: 14px;
}

/* Spinner animation */
.fa-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Status Badge Styles - Override global styles */
.simple-table .status-badge {
  display: inline-block !important;
  padding: 3px 8px !important;
  border-radius: 10px !important;
  font-size: 12px !important;
  font-weight: 500 !important;
  text-transform: capitalize !important;
  white-space: nowrap !important;
  width: auto !important;
  max-width: fit-content !important;
  margin: 0 !important;
}

/* Status Colors */
.status-badge.confirmed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #dc2626;
}

.status-badge.completed {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.available {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.rented {
  background: #fbbf24;
  color: #92400e;
}

.status-badge.maintenance {
  background: #fed7aa;
  color: #c2410c;
}

/* Bookings Table */
.bookings-table {
  margin-top: 20px;
}

.bookings-table .table-row {
  display: grid;
  grid-template-columns: 120px 1fr 1.2fr 110px 110px 120px 80px;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  align-items: center;
}

/* Active and Ongoing sections without amount column */
.active-section .bookings-table .table-row,
.ongoing-section .bookings-table .table-row {
  grid-template-columns: 140px 1.2fr 1.2fr 120px 120px 100px;
}

.bookings-table .table-row.header-row {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #d1d5db;
}

.bookings-table .table-row:not(.header-row):hover {
  background: #f9fafb;
}

.bookings-table .table-row span {
  font-size: 14px;
  color: #1f2937;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bookings-table .table-row.header-row span {
  color: #374151;
}

.btn-view {
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 6px 12px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.btn-view:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-view i {
  margin: 0;
}

/* Pending Bookings Styles */
.pending-section {
  background: white;
  border: 1px solid #e5e7eb;
}

.pending-section .section-header h2 {
  color: #1f2937;
}

.pending-section .section-header h2 i {
  color: #f59e0b;
  margin-right: 0.5rem;
}

.active-section .section-header h2 i {
  color: #8b5cf6;
  margin-right: 0.5rem;
}

.ongoing-section .section-header h2 i {
  color: #3b82f6;
  margin-right: 0.5rem;
}

.pending-row {
  background: white !important;
}

.pending-row:hover {
  background: #f9fafb !important;
}

.pending-row .reference {
  font-weight: 600;
  color: #1f2937;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  justify-content: flex-start;
}

.btn-pay {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 12px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  min-width: 40px;
}

.btn-pay:hover:not(:disabled) {
  background: linear-gradient(135deg, #059669, #047857);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-pay:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-pay i {
  font-size: 14px;
}

/* Adjust grid for pending bookings with two action buttons */
.pending-section .bookings-table .table-row {
  grid-template-columns: 140px 1.2fr 1.2fr 120px 120px 130px 120px;
}

/* Modal Styles */
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
}

.modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1f2937;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #9ca3af;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 24px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f3f4f6;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-row .label {
  font-weight: 600;
  color: #6b7280;
  font-size: 14px;
}

.detail-row .value {
  color: #1f2937;
  font-size: 14px;
  text-align: right;
  max-width: 60%;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}
</style>
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
        title="Active Bookings"
        :value="stats.activeBookings"
        icon="fas fa-calendar-check"
        variant="primary"
      />
      <StatsCard
        title="Total Revenue"
        :value="'$' + stats.totalRevenue"
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

    <!-- Recent Bookings -->
    <div class="dashboard-section">
      <div class="section-header">
        <h2>Recent Bookings</h2>
        <a href="#" class="view-all-link" @click.prevent="navigateToBookings">View All →</a>
      </div>
      <div v-if="loading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading recent bookings...</p>
      </div>
      <div v-else-if="recentBookings.length === 0" class="empty-state">
        <i class="fas fa-calendar"></i>
        <p>No recent bookings found</p>
      </div>
      <div v-else class="simple-table">
        <div class="table-row header-row">
          <span>Reference</span>
          <span>Vehicle</span>
          <span>Status</span>
        </div>
        <div class="table-row" v-for="booking in recentBookings" :key="booking.id">
          <span>{{ booking.reference }}</span>
          <span>{{ booking.vehicle }}</span>
          <span :class="['status-badge', booking.status.toLowerCase()]">{{ booking.status }}</span>
        </div>
      </div>
    </div>

    <!-- Vehicle Status -->
    <div class="dashboard-section">
      <div class="section-header">
        <h2>Vehicle Status</h2>
        <a href="#" class="view-all-link" @click.prevent="navigateToVehicles">View All →</a>
      </div>
      <div v-if="loading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading vehicle status...</p>
      </div>
      <div v-else-if="vehicleStatus.length === 0" class="empty-state">
        <i class="fas fa-car"></i>
        <p>No vehicles found</p>
      </div>
      <div v-else class="simple-table">
        <div class="table-row header-row">
          <span>Vehicle</span>
          <span>Plate</span>
          <span>Status</span>
        </div>
        <div class="table-row" v-for="vehicle in vehicleStatus" :key="vehicle.id">
          <span>{{ vehicle.name }}</span>
          <span>{{ vehicle.plate }}</span>
          <span :class="['status-badge', vehicle.status.toLowerCase()]">{{ vehicle.status }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import StatsCard from '../components/StatsCard.vue'

export default {
  name: 'Dashboard',
  components: {
    StatsCard
  },
  setup() {
    const router = useRouter()
    
    const stats = ref({
      totalVehicles: 0,
      activeBookings: 0,
      totalRevenue: '0',
      maintenanceDue: 0
    })

    const recentBookings = ref([])
    const vehicleStatus = ref([])
    const loading = ref(true)

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
          loadRecentBookings(),
          loadVehicleStatus(),
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
        const response = await fetch('/api/vehicles')
        if (response.ok) {
          const data = await response.json()
          const vehicles = data.vehicles || data.data || []
          stats.value.totalVehicles = vehicles.length
        }
      } catch (error) {
        console.error('Error loading vehicle stats:', error)
      }
    }

    const loadBookingStats = async () => {
      try {
        const response = await fetch('/api/bookings')
        if (response.ok) {
          const data = await response.json()
          const bookings = data.bookings || data.data || []
          
          // Count active bookings (confirmed, pending)
          const activeBookings = bookings.filter(booking => 
            booking.status === 'confirmed' || booking.status === 'pending'
          ).length
          
          stats.value.activeBookings = activeBookings
          
          // Calculate total revenue from completed bookings
          const totalRevenue = bookings
            .filter(booking => booking.status === 'completed')
            .reduce((sum, booking) => sum + parseFloat(booking.total_amount || 0), 0)
          
          stats.value.totalRevenue = totalRevenue.toFixed(2)
        }
      } catch (error) {
        console.error('Error loading booking stats:', error)
      }
    }

    const loadRecentBookings = async () => {
      try {
        const response = await fetch('/api/bookings')
        if (response.ok) {
          const data = await response.json()
          const bookings = data.bookings || data.data || []
          
          // Get the 3 most recent bookings
          const recent = bookings
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
            .slice(0, 3)
            .map(booking => ({
              id: booking.id,
              reference: booking.booking_reference,
              vehicle: `${booking.brand || ''} ${booking.model || ''}`.trim() || 'Unknown Vehicle',
              status: booking.status.charAt(0).toUpperCase() + booking.status.slice(1)
            }))
          
          recentBookings.value = recent
        }
      } catch (error) {
        console.error('Error loading recent bookings:', error)
      }
    }

    const loadVehicleStatus = async () => {
      try {
        const response = await fetch('/api/vehicles')
        if (response.ok) {
          const data = await response.json()
          const vehicles = data.vehicles || data.data || []
          
          // Map vehicles to status format
          const vehicleStatusData = vehicles.slice(0, 3).map(vehicle => ({
            id: vehicle.id,
            name: `${vehicle.brand} ${vehicle.model}`,
            plate: vehicle.plate_number,
            status: vehicle.status ? vehicle.status.charAt(0).toUpperCase() + vehicle.status.slice(1) : 'Available'
          }))
          
          vehicleStatus.value = vehicleStatusData
        }
      } catch (error) {
        console.error('Error loading vehicle status:', error)
      }
    }

    const loadMaintenanceStats = async () => {
      try {
        const response = await fetch('/api/maintenance')
        if (response.ok) {
          const data = await response.json()
          const maintenance = data.maintenance || data.data || []
          
          // Count pending or scheduled maintenance
          const maintenanceDue = maintenance.filter(item => 
            item.status === 'pending' || item.status === 'scheduled'
          ).length
          
          stats.value.maintenanceDue = maintenanceDue
        }
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
      recentBookings,
      vehicleStatus,
      loading,
      quickAction,
      navigateToBookings,
      navigateToVehicles
    }
  }
}
</script>

<style scoped>
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
</style>
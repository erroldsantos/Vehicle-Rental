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
      <div class="simple-table">
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
      <div class="simple-table">
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
      totalVehicles: 25,
      activeBookings: 12,
      totalRevenue: '5,480',
      maintenanceDue: 3
    })

    const recentBookings = ref([
      { id: 1, reference: 'BK-001234', vehicle: 'Toyota Camry', status: 'Confirmed' },
      { id: 2, reference: 'BK-001235', vehicle: 'Honda CR-V', status: 'Pending' },
      { id: 3, reference: 'BK-001236', vehicle: 'Ford Transit', status: 'Completed' }
    ])

    const vehicleStatus = ref([
      { id: 1, name: 'Toyota Camry', plate: 'ABC-1234', status: 'Available' },
      { id: 2, name: 'Honda CR-V', plate: 'DEF-5678', status: 'Rented' },
      { id: 3, name: 'Ford Transit', plate: 'GHI-9012', status: 'Maintenance' }
    ])

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
      // Simulate API loading - replace with actual API calls
      try {
        // Load real data from your LavaLust backend here
        console.log('Loading dashboard data...')
      } catch (error) {
        console.error('Failed to load dashboard data:', error)
      }
    }

    onMounted(() => {
      loadDashboardData()
    })

    return {
      stats,
      recentBookings,
      vehicleStatus,
      quickAction,
      navigateToBookings,
      navigateToVehicles
    }
  }
}
</script>
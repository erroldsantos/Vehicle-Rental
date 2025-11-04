<template>
  <div class="maintenance-management">
    <!-- Page Header -->
    <div class="page-header">
      <h1>Maintenance Management</h1>
      <div class="header-actions">
        <button class="action-btn" @click="showAddForm = true">
          <i class="fas fa-plus"></i> Schedule Maintenance
        </button>
        <button class="action-btn secondary" @click="loadMaintenance">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <button class="action-btn secondary" @click="syncVehicleStatuses" style="margin-left: 8px;">
          <i class="fas fa-cogs"></i> Sync Statuses
        </button>
      </div>
    </div>

    <!-- Add Maintenance Form -->
    <div v-if="showAddForm" class="form-card">
      <div class="card-header">
        <h2>Schedule New Maintenance</h2>
        <button class="close-btn" @click="cancelAdd">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form @submit.prevent="addMaintenance" class="vehicle-form">
        <div class="form-grid">
          <div class="form-group">
            <label>Vehicle</label>
            <select v-model="newMaintenance.vehicle_id" class="form-input" required>
              <option value="">Select Vehicle</option>
              <option v-for="vehicle in availableVehicles" :key="vehicle.id" :value="vehicle.id">
                {{ vehicle.display_name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input v-model="newMaintenance.description" class="form-input" type="text" required placeholder="e.g. Oil Change, Brake Inspection" />
          </div>
          <div class="form-group">
            <label>Scheduled Date</label>
            <input v-model="newMaintenance.scheduled_date" class="form-input" type="date" required />
          </div>
          <div class="form-group">
            <label>Estimated Cost (₱)</label>
            <input v-model="newMaintenance.cost" class="form-input" type="number" step="0.01" placeholder="0.00" />
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="newMaintenance.status" class="form-input" required>
              <option value="scheduled">Scheduled</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="action-btn">
            <i class="fas fa-calendar"></i> Schedule Maintenance
          </button>
          <button type="button" class="action-btn secondary" @click="cancelAdd">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Edit Maintenance Form -->
    <div v-if="showEditForm" class="form-card">
      <div class="card-header">
        <h2>Edit Maintenance</h2>
        <button class="close-btn" @click="cancelEdit">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form @submit.prevent="updateMaintenance" class="vehicle-form">
        <div class="form-grid">
          <div class="form-group">
            <label>Vehicle</label>
            <select v-model="editingMaintenance.vehicle_id" class="form-input" required>
              <option v-for="vehicle in availableVehicles" :key="vehicle.id" :value="vehicle.id">
                {{ vehicle.display_name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input v-model="editingMaintenance.description" class="form-input" type="text" required />
          </div>
          <div class="form-group">
            <label>Scheduled Date</label>
            <input v-model="editingMaintenance.scheduled_date" class="form-input" type="date" required />
          </div>
          <div class="form-group">
            <label>Cost (₱)</label>
            <input v-model="editingMaintenance.cost" class="form-input" type="number" step="0.01" />
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="editingMaintenance.status" class="form-input" required>
              <option value="scheduled">Scheduled</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="action-btn">
            <i class="fas fa-save"></i> Update Maintenance
          </button>
          <button type="button" class="action-btn secondary" @click="cancelEdit">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Maintenance Overview Cards -->
    <div class="stats-grid" style="margin-bottom: 40px;">
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-wrench"></i>
        </div>
        <div class="stat-content">
          <h3>{{ scheduledCount }}</h3>
          <p>Scheduled</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ completedCount }}</h3>
          <p>Completed</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar-week"></i>
        </div>
        <div class="stat-content">
          <h3>{{ dueThisWeek }}</h3>
          <p>Due This Week</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-card">
          <h3>₱{{ totalCost }}</h3>
          <p>Total Cost</p>
        </div>
      </div>
    </div>

    <!-- Maintenance List -->
    <div class="data-card">
      <div class="card-header">
        <h2>
          Maintenance Schedule
          <span class="count-badge">{{ maintenance.length }} records</span>
        </h2>
      </div>
      
      <div v-if="loading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        Loading maintenance records...
      </div>
      
      <div v-else class="modern-table">
        <div class="table-row header-row">
          <span>Vehicle</span>
          <span>Description</span>
          <span>Scheduled Date</span>
          <span>Cost</span>
          <span>Status</span>
          <span>Actions</span>
        </div>
        <div class="table-row" v-for="record in maintenance" :key="record.id">
          <span class="vehicle-info">{{ record.vehicle_display }}</span>
          <span class="maintenance-desc">{{ record.description }}</span>
          <span class="schedule-date">{{ formatDate(record.scheduled_date) }}</span>
          <span class="cost">₱{{ record.cost }}</span>
          <span>
            <span :class="['status-badge', record.status.toLowerCase()]">
              {{ formatStatus(record.status) }}
            </span>
          </span>
          <span class="actions">
            <button v-if="record.status === 'scheduled'" class="action-btn-sm" @click="completeMaintenance(record)" style="margin-right: 8px;" title="Complete">
              <i class="fas fa-check"></i>
            </button>
            <button class="action-btn-sm" @click="editMaintenance(record)" style="margin-right: 8px; cursor: pointer;" title="Edit">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn-sm danger" @click="deleteMaintenance(record.id)" style="cursor: pointer;" title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useApiStore } from '@/stores/api'

export default {
  name: 'MaintenanceManagement',
  setup() {
    const loading = ref(false)
    const showAddForm = ref(false)
    const showEditForm = ref(false)
    const maintenance = ref([])
    const availableVehicles = ref([])
    const apiStore = useApiStore()

    const newMaintenance = ref({
      vehicle_id: '',
      description: '',
      scheduled_date: '',
      cost: '',
      status: 'scheduled'
    })

    const editingMaintenance = ref({
      id: null,
      vehicle_id: '',
      description: '',
      scheduled_date: '',
      cost: '',
      status: 'scheduled'
    })

    // Computed statistics
    const scheduledCount = computed(() => 
      maintenance.value.filter(m => m.status === 'scheduled').length
    )

    const completedCount = computed(() => 
      maintenance.value.filter(m => m.status === 'completed').length
    )

    const dueThisWeek = computed(() => {
      const now = new Date()
      const weekFromNow = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000)
      return maintenance.value.filter(m => {
        const scheduleDate = new Date(m.scheduled_date)
        return m.status === 'scheduled' && scheduleDate >= now && scheduleDate <= weekFromNow
      }).length
    })

    const totalCost = computed(() => 
      maintenance.value.reduce((sum, m) => sum + parseFloat(m.cost || 0), 0).toFixed(2)
    )

    const loadMaintenance = async () => {
      loading.value = true
      try {
        console.log('Loading maintenance records from API...')
        const response = await apiStore.get('/maintenance')
        console.log('Maintenance API Response:', response)
        
        // Handle both {maintenance: [...]} and {data: {maintenance: [...]}} formats
        maintenance.value = response.maintenance || response.data?.maintenance || response.data || []
        console.log('Loaded maintenance records:', maintenance.value)
      } catch (error) {
        console.error('Failed to load maintenance records:', error)
        maintenance.value = []
      } finally {
        loading.value = false
      }
    }

    const loadVehicles = async () => {
      try {
        console.log('Loading available vehicles...')
        const response = await apiStore.get('/maintenance/vehicles')
        availableVehicles.value = response || []
        console.log('Available vehicles:', availableVehicles.value)
      } catch (error) {
        console.error('Failed to load vehicles:', error)
        availableVehicles.value = []
      }
    }

    const addMaintenance = async () => {
      try {
        const maintenanceData = {
          vehicle_id: parseInt(newMaintenance.value.vehicle_id),
          description: newMaintenance.value.description,
          scheduled_date: newMaintenance.value.scheduled_date,
          cost: newMaintenance.value.cost ? parseFloat(newMaintenance.value.cost) : 0,
          status: newMaintenance.value.status
        }
        
        console.log('Adding maintenance:', maintenanceData)
        const response = await apiStore.post('/maintenance', maintenanceData)
        console.log('Add maintenance response:', response)
        
        maintenance.value.unshift(response)
        cancelAdd()
        
        await loadMaintenance()
      } catch (error) {
        console.error('Failed to add maintenance:', error)
        console.error('Error details:', error.response || error)
      }
    }

    const cancelAdd = () => {
      showAddForm.value = false
      newMaintenance.value = {
        vehicle_id: '',
        description: '',
        scheduled_date: '',
        cost: '',
        status: 'scheduled'
      }
    }

    const editMaintenance = (record) => {
      console.log('Edit maintenance clicked:', record)
      
      editingMaintenance.value = {
        id: record.id,
        vehicle_id: record.vehicle_id,
        description: record.description,
        scheduled_date: record.scheduled_date,
        cost: record.cost,
        status: record.status
      }
      
      showEditForm.value = true
      showAddForm.value = false
    }

    const updateMaintenance = async () => {
      try {
        const maintenanceData = {
          vehicle_id: parseInt(editingMaintenance.value.vehicle_id),
          description: editingMaintenance.value.description,
          scheduled_date: editingMaintenance.value.scheduled_date,
          cost: parseFloat(editingMaintenance.value.cost),
          status: editingMaintenance.value.status
        }
        
        console.log('Updating maintenance:', editingMaintenance.value.id, maintenanceData)
        const response = await apiStore.put(`/maintenance/${editingMaintenance.value.id}`, maintenanceData)
        console.log('Update maintenance response:', response)
        
        const index = maintenance.value.findIndex(m => m.id === editingMaintenance.value.id)
        if (index !== -1) {
          maintenance.value[index] = response
        }
        
        cancelEdit()
        await loadMaintenance()
      } catch (error) {
        console.error('Failed to update maintenance:', error)
        console.error('Error details:', error.response || error)
      }
    }

    const cancelEdit = () => {
      showEditForm.value = false
      editingMaintenance.value = {
        id: null,
        vehicle_id: '',
        description: '',
        scheduled_date: '',
        cost: '',
        status: 'scheduled'
      }
    }

    const completeMaintenance = async (record) => {
      try {
        console.log('Completing maintenance:', record.id)
        const response = await apiStore.put(`/maintenance/${record.id}/complete`, { cost: record.cost })
        console.log('Complete maintenance response:', response)
        
        const index = maintenance.value.findIndex(m => m.id === record.id)
        if (index !== -1) {
          maintenance.value[index] = response
        }
        
        await loadMaintenance()
      } catch (error) {
        console.error('Failed to complete maintenance:', error)
      }
    }

    const deleteMaintenance = async (id) => {
      console.log('Delete maintenance clicked for ID:', id)
      
      try {
        console.log('Deleting maintenance with ID:', id)
        const response = await apiStore.delete(`/maintenance/${id}`)
        console.log('Delete response received:', response)
        
        maintenance.value = maintenance.value.filter(m => m.id !== id)
        console.log('Updated maintenance list:', maintenance.value)
        
        await loadMaintenance()
      } catch (error) {
        console.error('DELETE ERROR:', error)
        console.error('Error message:', error.message)
        console.error('Error response:', error.response)
      }
    }

    const syncVehicleStatuses = async () => {
      try {
        console.log('Syncing vehicle statuses...')
        const response = await apiStore.post('/maintenance/sync', {})
        console.log('Sync response:', response)
        
        await loadMaintenance()
        
        console.log(`Sync completed! ${response.vehicles_set_to_maintenance} vehicles set to maintenance, ${response.vehicles_reset_to_available} reset to available.`)
      } catch (error) {
        console.error('Sync error:', error)
      }
    }

    // Helper functions
    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString()
    }

    const formatStatus = (status) => {
      return status.charAt(0).toUpperCase() + status.slice(1)
    }

    onMounted(async () => {
      await loadVehicles()
      await loadMaintenance()
    })

    return {
      loading,
      showAddForm,
      showEditForm,
      maintenance,
      availableVehicles,
      newMaintenance,
      editingMaintenance,
      scheduledCount,
      completedCount,
      dueThisWeek,
      totalCost,
      loadMaintenance,
      addMaintenance,
      cancelAdd,
      editMaintenance,
      updateMaintenance,
      cancelEdit,
      completeMaintenance,
      deleteMaintenance,
      syncVehicleStatuses,
      formatDate,
      formatStatus
    }
  }
}
</script>
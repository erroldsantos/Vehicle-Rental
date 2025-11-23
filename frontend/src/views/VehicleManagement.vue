<template>
  <div class="vehicle-management">
    <!-- Page Header -->
    <div class="page-header">
      <h1>Vehicle Management</h1>
      <div class="header-actions">
        <button class="action-btn" @click="showAddForm = true">
          <i class="fas fa-plus"></i> Add Vehicle
        </button>
        <button class="action-btn secondary" @click="loadVehicles">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Add Vehicle Form -->
    <div v-if="showAddForm" class="form-card">
      <div class="card-header">
        <h2>Add New Vehicle</h2>
        <button class="close-btn" @click="cancelAdd">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form @submit.prevent="addVehicle" class="vehicle-form">
        <div class="form-grid">
          <div class="form-group">
            <label>Brand</label>
            <input v-model="newVehicle.brand" class="form-input" type="text" required placeholder="e.g. Toyota" />
          </div>
          <div class="form-group">
            <label>Model</label>
            <input v-model="newVehicle.model" class="form-input" type="text" required placeholder="e.g. Camry" />
          </div>
          <div class="form-group">
            <label>Year</label>
            <input v-model="newVehicle.year" class="form-input" type="number" min="1900" max="2030" required placeholder="2023" />
          </div>
          <div class="form-group">
            <label>License Plate</label>
            <input v-model="newVehicle.plate_number" class="form-input" type="text" required placeholder="e.g. ABC-1234" />
          </div>
          <div class="form-group">
            <label>Daily Rate (₱)</label>
            <input v-model="newVehicle.daily_rate" class="form-input" type="number" step="0.01" required placeholder="2000.00" />
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="action-btn">
            <i class="fas fa-plus"></i> Add Vehicle
          </button>
          <button type="button" class="action-btn secondary" @click="cancelAdd">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Edit Vehicle Form -->
    <div v-if="showEditForm" class="form-card">
      <div class="card-header">
        <h2>Edit Vehicle</h2>
        <button class="close-btn" @click="cancelEdit">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form @submit.prevent="updateVehicle" class="vehicle-form">
        <div class="form-grid">
          <div class="form-group">
            <label>Brand</label>
            <input v-model="editingVehicle.brand" class="form-input" type="text" required placeholder="e.g. Toyota" />
          </div>
          <div class="form-group">
            <label>Model</label>
            <input v-model="editingVehicle.model" class="form-input" type="text" required placeholder="e.g. Camry" />
          </div>
          <div class="form-group">
            <label>Year</label>
            <input v-model="editingVehicle.year" class="form-input" type="number" min="1900" max="2030" required placeholder="2023" />
          </div>
          <div class="form-group">
            <label>License Plate</label>
            <input v-model="editingVehicle.plate_number" class="form-input" type="text" required placeholder="e.g. ABC-1234" />
          </div>
          <div class="form-group">
            <label>Daily Rate (₱)</label>
            <input v-model="editingVehicle.daily_rate" class="form-input" type="number" step="0.01" required placeholder="2000.00" />
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="editingVehicle.status" class="form-input" required>
              <option value="available">Available</option>
              <option value="rented">Rented</option>
              <option value="maintenance">Maintenance</option>
            </select>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="action-btn">
            <i class="fas fa-save"></i> Update Vehicle
          </button>
          <button type="button" class="action-btn secondary" @click="cancelEdit">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Vehicles List -->
    <div class="data-card">
      <div class="card-header">
        <h2>
          Vehicles List
          <span class="count-badge">{{ vehicles.length }} vehicles</span>
        </h2>
      </div>
      
      <div v-if="loading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        Loading vehicles...
      </div>
      
      <div v-else class="modern-table">
        <div class="table-row header-row">
          <span>Vehicle</span>
          <span>Plate</span>
          <span>Year</span>
          <span>Rate/Day</span>
          <span>Status</span>
          <span>Actions</span>
        </div>
        <div class="table-row" v-for="vehicle in vehicles" :key="vehicle.id">
          <span class="vehicle-model">{{ getVehicleDisplay(vehicle) }}</span>
          <span class="plate-number">{{ vehicle.plate_number }}</span>
          <span class="vehicle-year">{{ vehicle.year }}</span>
          <span class="rate">₱{{ parseFloat(vehicle.daily_rate).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
          <span>
            <span :class="['status-badge', vehicle.status.toLowerCase()]">
              {{ formatStatus(vehicle.status) }}
            </span>
          </span>
          <span class="actions">
            <button class="action-btn-sm" @click="editVehicle(vehicle)" style="margin-right: 8px; cursor: pointer;" title="Edit">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn-sm danger" @click="deleteVehicle(vehicle.id)" style="cursor: pointer;" title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useApiStore } from '@/stores/api'

export default {
  name: 'VehicleManagement',
  setup() {
    const loading = ref(false)
    const showAddForm = ref(false)
    const showEditForm = ref(false)
    const vehicles = ref([])
    const apiStore = useApiStore()

    const newVehicle = ref({
      brand: '',
      model: '',
      year: new Date().getFullYear(),
      plate_number: '',
      daily_rate: '',
      status: 'available'
    })

    const editingVehicle = ref({
      id: null,
      brand: '',
      model: '',
      year: '',
      plate_number: '',
      daily_rate: '',
      status: 'available'
    })

    const loadVehicles = async () => {
      loading.value = true
      try {
        console.log('Loading vehicles from API...')
        const response = await apiStore.get('/vehicles')
        console.log('API Response:', response)
        
        // Backend returns {vehicles: [...], total: N}
        // Axios wraps it in response.data, so we get response.data.vehicles
        vehicles.value = response.vehicles || []
        console.log('Loaded vehicles:', vehicles.value)
      } catch (error) {
        console.error('Failed to load vehicles:', error)
        // Keep existing mock data as fallback
        vehicles.value = [
          { id: 1, brand: 'Toyota', model: 'Camry', year: 2022, plate_number: 'ABC-1234', daily_rate: 2000.00, status: 'available' },
          { id: 2, brand: 'Honda', model: 'CR-V', year: 2021, plate_number: 'DEF-5678', daily_rate: 3000.00, status: 'rented' },
          { id: 3, brand: 'Ford', model: 'Transit', year: 2023, plate_number: 'GHI-9012', daily_rate: 3500.00, status: 'maintenance' }
        ]
      } finally {
        loading.value = false
      }
    }

    const addVehicle = async () => {
      try {
        const vehicleData = {
          brand: newVehicle.value.brand,
          model: newVehicle.value.model,
          year: parseInt(newVehicle.value.year),
          plate_number: newVehicle.value.plate_number,
          daily_rate: parseFloat(newVehicle.value.daily_rate),
          status: newVehicle.value.status
        }
        
        console.log('Adding vehicle:', vehicleData)
        const response = await apiStore.post('/vehicles', vehicleData)
        console.log('Add vehicle response:', response)
        
        vehicles.value.unshift(response)
        cancelAdd()
        
        // Refresh the list to make sure we have the latest data
        await loadVehicles()
      } catch (error) {
        console.error('Failed to add vehicle:', error)
        console.error('Error details:', error.response || error)
      }
    }

    const cancelAdd = () => {
      showAddForm.value = false
      newVehicle.value = {
        brand: '',
        model: '',
        year: new Date().getFullYear(),
        plate_number: '',
        daily_rate: '',
        status: 'available'
      }
    }

    const editVehicle = (vehicle) => {
      console.log('Edit vehicle clicked:', vehicle)
      
      // Populate the edit form with the selected vehicle's data
      editingVehicle.value = {
        id: vehicle.id,
        brand: vehicle.brand,
        model: vehicle.model,
        year: vehicle.year,
        plate_number: vehicle.plate_number,
        daily_rate: vehicle.daily_rate,
        status: vehicle.status
      }
      
      // Show the edit form and hide add form
      showEditForm.value = true
      showAddForm.value = false
    }

    const updateVehicle = async () => {
      try {
        const vehicleData = {
          brand: editingVehicle.value.brand,
          model: editingVehicle.value.model,
          year: parseInt(editingVehicle.value.year),
          plate_number: editingVehicle.value.plate_number,
          daily_rate: parseFloat(editingVehicle.value.daily_rate),
          status: editingVehicle.value.status
        }
        
        console.log('Updating vehicle:', editingVehicle.value.id, vehicleData)
        const response = await apiStore.put(`/vehicles/${editingVehicle.value.id}`, vehicleData)
        console.log('Update vehicle response:', response)
        
        // Update the vehicle in the local list
        const index = vehicles.value.findIndex(v => v.id === editingVehicle.value.id)
        if (index !== -1) {
          vehicles.value[index] = response
        }
        
        cancelEdit()
        
        // Refresh the list to make sure we have the latest data
        await loadVehicles()
      } catch (error) {
        console.error('Failed to update vehicle:', error)
        console.error('Error details:', error.response || error)
      }
    }

    const cancelEdit = () => {
      showEditForm.value = false
      editingVehicle.value = {
        id: null,
        brand: '',
        model: '',
        year: '',
        plate_number: '',
        daily_rate: '',
        status: 'available'
      }
    }

    const deleteVehicle = async (id) => {
      console.log('Delete button clicked for ID:', id)
      
      try {
        console.log('Deleting vehicle with ID:', id)
        console.log('API Store:', apiStore)
        console.log('Making DELETE request to:', `/vehicles/${id}`)
        
        const response = await apiStore.delete(`/vehicles/${id}`)
        console.log('Delete response received:', response)
        
        // Remove from local list
        vehicles.value = vehicles.value.filter(v => v.id !== id)
        console.log('Updated vehicles list:', vehicles.value)
          
          // Refresh the list to make sure we have the latest data
          console.log('Refreshing vehicle list...')
          await loadVehicles()
        } catch (error) {
          console.error('DELETE ERROR:', error)
          console.error('Error message:', error.message)
          console.error('Error response:', error.response)
          console.error('Full error object:', JSON.stringify(error, null, 2))
          
          if (error.response) {
            console.error('Response status:', error.response.status)
            console.error('Response data:', error.response.data)
          }
        }
    }

    // Helper function to format vehicle display
    const getVehicleDisplay = (vehicle) => {
      return `${vehicle.brand} ${vehicle.model} (${vehicle.year})`
    }

    const formatStatus = (status) => {
      return status.charAt(0).toUpperCase() + status.slice(1)
    }

    onMounted(() => {
      loadVehicles()
    })

    return {
      loading,
      showAddForm,
      showEditForm,
      vehicles,
      newVehicle,
      editingVehicle,
      loadVehicles,
      addVehicle,
      cancelAdd,
      editVehicle,
      updateVehicle,
      cancelEdit,
      deleteVehicle,
      getVehicleDisplay,
      formatStatus
    }
  }
}
</script>
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
          <div class="form-group">
            <label>Vehicle Image</label>
            <input @change="handleImageUpload($event, 'new')" class="form-input" type="file" accept="image/*" />
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
          <div class="form-group">
            <label>Vehicle Image</label>
            <input @change="handleImageUpload($event, 'edit')" class="form-input" type="file" accept="image/*" />
            <small v-if="editingVehicle.image" style="color: #666; display: block; margin-top: 4px;">Current: {{ editingVehicle.image }}</small>
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
import { ref, computed, onMounted } from 'vue'
import { useApiStore } from '@/stores/api'
import { useVehiclesStore } from '@/stores/vehicles'

export default {
  name: 'VehicleManagement',
  setup() {
    const showAddForm = ref(false)
    const showEditForm = ref(false)
    const apiStore = useApiStore()
    const vehiclesStore = useVehiclesStore()
    
    // Use computed to get vehicles from store
    const vehicles = computed(() => vehiclesStore.allVehicles)
    const loading = computed(() => vehiclesStore.loading)

    const newVehicle = ref({
      brand: '',
      model: '',
      year: new Date().getFullYear(),
      plate_number: '',
      daily_rate: '',
      status: 'available',
      image: null
    })

    const editingVehicle = ref({
      id: null,
      brand: '',
      model: '',
      year: '',
      plate_number: '',
      daily_rate: '',
      status: 'available',
      image: null
    })

    const handleImageUpload = (event, formType) => {
      const file = event.target.files[0]
      console.log('=== handleImageUpload called ===')
      console.log('Form type:', formType)
      console.log('File:', file)
      console.log('File instanceof File:', file instanceof File)
      
      if (file) {
        if (formType === 'new') {
          newVehicle.value.image = file
          console.log('Set newVehicle.image:', newVehicle.value.image)
        } else if (formType === 'edit') {
          editingVehicle.value.image = file
          console.log('Set editingVehicle.image:', editingVehicle.value.image)
        }
      }
    }

    const loadVehicles = async (force = false) => {
      try {
        await vehiclesStore.loadVehicles(force)
      } catch (error) {
        console.error('Failed to load vehicles:', error)
      }
    }

    const addVehicle = async () => {
      try {
        const formData = new FormData()
        formData.append('brand', newVehicle.value.brand)
        formData.append('model', newVehicle.value.model)
        formData.append('year', parseInt(newVehicle.value.year))
        formData.append('plate_number', newVehicle.value.plate_number)
        formData.append('daily_rate', parseFloat(newVehicle.value.daily_rate))
        formData.append('status', newVehicle.value.status)
        
        if (newVehicle.value.image) {
          formData.append('image', newVehicle.value.image)
        }
        
        console.log('Adding vehicle with FormData')
        const response = await apiStore.post('/vehicles', formData)
        console.log('Add vehicle response:', response)
        
        // Add to store instead of local array
        vehiclesStore.addVehicle(response)
        cancelAdd()
        
        alert('Vehicle added successfully!')
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
        status: 'available',
        image: null
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
        status: vehicle.status,
        image: vehicle.image || null
      }
      
      // Show the edit form and hide add form
      showEditForm.value = true
      showAddForm.value = false
    }

    const updateVehicle = async () => {
      console.log('=== UPDATE VEHICLE FUNCTION CALLED ===')
      console.log('Editing vehicle data:', editingVehicle.value)
      
      try {
        const formData = new FormData()
        formData.append('brand', editingVehicle.value.brand)
        formData.append('model', editingVehicle.value.model)
        formData.append('year', parseInt(editingVehicle.value.year))
        formData.append('plate_number', editingVehicle.value.plate_number)
        formData.append('daily_rate', parseFloat(editingVehicle.value.daily_rate))
        formData.append('status', editingVehicle.value.status)
        
        if (editingVehicle.value.image instanceof File) {
          console.log('Adding image to FormData')
          formData.append('image', editingVehicle.value.image)
        }
        
        console.log('Updating vehicle ID:', editingVehicle.value.id)
        console.log('FormData entries:')
        for (let pair of formData.entries()) {
          console.log(pair[0] + ': ' + pair[1])
        }
        
        // Use POST instead of PUT for file uploads (PHP limitation with multipart/form-data)
        const response = await apiStore.post(`/vehicles/${editingVehicle.value.id}`, formData)
        console.log('Update vehicle response:', response)
        
        // Update in store
        vehiclesStore.updateVehicle(editingVehicle.value.id, response)
        
        alert('Vehicle updated successfully!')
        cancelEdit()
      } catch (error) {
        console.error('Failed to update vehicle:', error)
        console.error('Error details:', error.response || error)
        alert('Failed to update vehicle: ' + (error.response?.data?.error || error.message))
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
        status: 'available',
        image: null
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
        
        // Remove from store
        vehiclesStore.removeVehicle(id)
        alert('Vehicle deleted successfully!')
        } catch (error) {
        console.error('DELETE ERROR:', error)
          alert('Failed to delete vehicle: ' + (error.response?.data?.error || error.message))
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
      handleImageUpload,
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
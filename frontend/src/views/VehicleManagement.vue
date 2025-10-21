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
            <label>Vehicle Model</label>
            <input v-model="newVehicle.model" class="form-input" type="text" required placeholder="e.g. Toyota Camry" />
          </div>
          <div class="form-group">
            <label>License Plate</label>
            <input v-model="newVehicle.plate" class="form-input" type="text" required placeholder="e.g. ABC-1234" />
          </div>
          <div class="form-group">
            <label>Vehicle Type</label>
            <select v-model="newVehicle.type" class="form-input" required>
              <option value="">Select Type</option>
              <option value="sedan">Sedan</option>
              <option value="suv">SUV</option>
              <option value="truck">Truck</option>
              <option value="van">Van</option>
            </select>
          </div>
          <div class="form-group">
            <label>Daily Rate ($)</label>
            <input v-model="newVehicle.rate" class="form-input" type="number" step="0.01" required placeholder="50.00" />
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
          <span>Model</span>
          <span>Plate</span>
          <span>Type</span>
          <span>Rate/Day</span>
          <span>Status</span>
          <span>Actions</span>
        </div>
        <div class="table-row" v-for="vehicle in vehicles" :key="vehicle.id">
          <span class="vehicle-model">{{ vehicle.model }}</span>
          <span class="plate-number">{{ vehicle.plate }}</span>
          <span class="vehicle-type">{{ vehicle.type }}</span>
          <span class="rate">${{ vehicle.rate }}</span>
          <span>
            <span :class="['status-badge', vehicle.status.toLowerCase()]">
              {{ vehicle.status }}
            </span>
          </span>
          <span class="actions">
            <button class="action-btn-sm" @click="editVehicle(vehicle)">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button class="action-btn-sm danger" @click="deleteVehicle(vehicle.id)">
              <i class="fas fa-trash"></i> Delete
            </button>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'

export default {
  name: 'VehicleManagement',
  setup() {
    const loading = ref(false)
    const showAddForm = ref(false)
    const vehicles = ref([
      { id: 1, model: 'Toyota Camry', plate: 'ABC-1234', type: 'sedan', rate: 50.00, status: 'Available' },
      { id: 2, model: 'Honda CR-V', plate: 'DEF-5678', type: 'suv', rate: 75.00, status: 'Rented' },
      { id: 3, model: 'Ford Transit', plate: 'GHI-9012', type: 'van', rate: 90.00, status: 'Maintenance' },
      { id: 4, model: 'Chevrolet Tahoe', plate: 'JKL-3456', type: 'suv', rate: 85.00, status: 'Available' }
    ])

    const newVehicle = ref({
      model: '',
      plate: '',
      type: '',
      rate: ''
    })

    const loadVehicles = async () => {
      loading.value = true
      try {
        // Simulate API call - replace with actual API
        await new Promise(resolve => setTimeout(resolve, 1000))
        console.log('Vehicles loaded')
      } catch (error) {
        console.error('Failed to load vehicles:', error)
      } finally {
        loading.value = false
      }
    }

    const addVehicle = () => {
      const vehicle = {
        id: vehicles.value.length + 1,
        model: newVehicle.value.model,
        plate: newVehicle.value.plate,
        type: newVehicle.value.type,
        rate: parseFloat(newVehicle.value.rate),
        status: 'Available'
      }
      
      vehicles.value.unshift(vehicle)
      cancelAdd()
    }

    const cancelAdd = () => {
      showAddForm.value = false
      newVehicle.value = { model: '', plate: '', type: '', rate: '' }
    }

    const editVehicle = (vehicle) => {
      console.log('Edit vehicle:', vehicle)
      // Implement edit functionality
    }

    const deleteVehicle = (id) => {
      if (confirm('Are you sure you want to delete this vehicle?')) {
        vehicles.value = vehicles.value.filter(v => v.id !== id)
      }
    }

    onMounted(() => {
      loadVehicles()
    })

    return {
      loading,
      showAddForm,
      vehicles,
      newVehicle,
      loadVehicles,
      addVehicle,
      cancelAdd,
      editVehicle,
      deleteVehicle
    }
  }
}
</script>
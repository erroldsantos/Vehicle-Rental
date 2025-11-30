import { defineStore } from 'pinia'
import { useApiStore } from './api'

export const useMaintenanceStore = defineStore('maintenance', {
  state: () => ({
    maintenanceRecords: [],
    loading: false,
    lastFetched: null,
    cacheTimeout: 5 * 60 * 1000 // 5 minutes cache
  }),

  getters: {
    allMaintenance: (state) => state.maintenanceRecords,
    
    // Filter by status
    scheduledMaintenance: (state) => state.maintenanceRecords.filter(m => m.status === 'scheduled'),
    pendingMaintenance: (state) => state.maintenanceRecords.filter(m => m.status === 'pending'),
    completedMaintenance: (state) => state.maintenanceRecords.filter(m => m.status === 'completed'),
    
    // Filter by payment status
    unpaidMaintenance: (state) => state.maintenanceRecords.filter(m => m.payment_status === 'pending'),
    paidMaintenance: (state) => state.maintenanceRecords.filter(m => m.payment_status === 'paid'),
    
    // Get maintenance by ID
    getMaintenanceById: (state) => (id) => state.maintenanceRecords.find(m => m.id === id),
    
    // Check if data is stale
    isStale: (state) => {
      if (!state.lastFetched) return true
      return Date.now() - state.lastFetched > state.cacheTimeout
    }
  },

  actions: {
    async loadMaintenance(force = false) {
      if (!force && this.maintenanceRecords.length > 0 && !this.isStale) {
        console.log('Using cached maintenance data')
        return this.maintenanceRecords
      }

      this.loading = true
      try {
        const apiStore = useApiStore()
        const response = await apiStore.get('/maintenance')
        this.maintenanceRecords = response.maintenance || []
        this.lastFetched = Date.now()
        console.log('Loaded fresh maintenance data:', this.maintenanceRecords.length)
        return this.maintenanceRecords
      } catch (error) {
        console.error('Failed to load maintenance:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    addMaintenance(maintenance) {
      this.maintenanceRecords.unshift(maintenance)
    },

    updateMaintenance(id, updatedMaintenance) {
      const index = this.maintenanceRecords.findIndex(m => m.id === id)
      if (index !== -1) {
        this.maintenanceRecords[index] = { ...this.maintenanceRecords[index], ...updatedMaintenance }
      }
    },

    removeMaintenance(id) {
      this.maintenanceRecords = this.maintenanceRecords.filter(m => m.id !== id)
    },

    async refresh() {
      return this.loadMaintenance(true)
    },

    clearCache() {
      this.maintenanceRecords = []
      this.lastFetched = null
    }
  }
})

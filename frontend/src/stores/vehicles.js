import { defineStore } from 'pinia'
import { useApiStore } from './api'

export const useVehiclesStore = defineStore('vehicles', {
  state: () => ({
    vehicles: [],
    loading: false,
    lastFetched: null,
    cacheTimeout: 5 * 60 * 1000 // 5 minutes cache
  }),

  getters: {
    // Get all vehicles
    allVehicles: (state) => state.vehicles,
    
    // Get available vehicles
    availableVehicles: (state) => state.vehicles.filter(v => v.status === 'available'),
    
    // Get vehicle by ID
    getVehicleById: (state) => (id) => state.vehicles.find(v => v.id === id),
    
    // Check if data is stale
    isStale: (state) => {
      if (!state.lastFetched) return true
      return Date.now() - state.lastFetched > state.cacheTimeout
    }
  },

  actions: {
    // Load vehicles (with caching)
    async loadVehicles(force = false) {
      // If we have cached data and it's not stale, don't reload
      if (!force && this.vehicles.length > 0 && !this.isStale) {
        console.log('Using cached vehicles data')
        return this.vehicles
      }

      this.loading = true
      try {
        const apiStore = useApiStore()
        const response = await apiStore.get('/vehicles')
        this.vehicles = response.vehicles || []
        this.lastFetched = Date.now()
        console.log('Loaded fresh vehicles data:', this.vehicles.length)
        return this.vehicles
      } catch (error) {
        console.error('Failed to load vehicles:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Add new vehicle to store
    addVehicle(vehicle) {
      this.vehicles.unshift(vehicle)
    },

    // Update vehicle in store
    updateVehicle(id, updatedVehicle) {
      const index = this.vehicles.findIndex(v => v.id === id)
      if (index !== -1) {
        this.vehicles[index] = { ...this.vehicles[index], ...updatedVehicle }
      }
    },

    // Remove vehicle from store
    removeVehicle(id) {
      this.vehicles = this.vehicles.filter(v => v.id !== id)
    },

    // Force refresh data
    async refresh() {
      return this.loadVehicles(true)
    },

    // Clear cache
    clearCache() {
      this.vehicles = []
      this.lastFetched = null
    }
  }
})

import { defineStore } from 'pinia'
import { useApiStore } from './api'

export const useUsersStore = defineStore('users', {
  state: () => ({
    users: [],
    loading: false,
    lastFetched: null,
    cacheTimeout: 5 * 60 * 1000 // 5 minutes cache
  }),

  getters: {
    allUsers: (state) => state.users,
    
    // Filter by role
    adminUsers: (state) => state.users.filter(u => u.role === 'admin'),
    regularUsers: (state) => state.users.filter(u => u.role === 'user'),
    
    // Filter by status
    activeUsers: (state) => state.users.filter(u => u.status === 'active'),
    inactiveUsers: (state) => state.users.filter(u => u.status === 'inactive'),
    
    // Filter by license status
    verifiedLicenses: (state) => state.users.filter(u => u.license_status === 'verified'),
    pendingLicenses: (state) => state.users.filter(u => u.license_status === 'pending'),
    
    // Get user by ID
    getUserById: (state) => (id) => state.users.find(u => u.id === id),
    
    // Check if data is stale
    isStale: (state) => {
      if (!state.lastFetched) return true
      return Date.now() - state.lastFetched > state.cacheTimeout
    }
  },

  actions: {
    async loadUsers(force = false) {
      if (!force && this.users.length > 0 && !this.isStale) {
        console.log('Using cached users data')
        return this.users
      }

      this.loading = true
      try {
        const apiStore = useApiStore()
        const response = await apiStore.get('/users')
        this.users = response.users || []
        this.lastFetched = Date.now()
        console.log('Loaded fresh users data:', this.users.length)
        return this.users
      } catch (error) {
        console.error('Failed to load users:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    addUser(user) {
      this.users.unshift(user)
    },

    updateUser(id, updatedUser) {
      const index = this.users.findIndex(u => u.id === id)
      if (index !== -1) {
        this.users[index] = { ...this.users[index], ...updatedUser }
      }
    },

    removeUser(id) {
      this.users = this.users.filter(u => u.id !== id)
    },

    async refresh() {
      return this.loadUsers(true)
    },

    clearCache() {
      this.users = []
      this.lastFetched = null
    }
  }
})

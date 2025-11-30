import { defineStore } from 'pinia'
import { useApiStore } from './api'

export const useBookingsStore = defineStore('bookings', {
  state: () => ({
    bookings: [],
    loading: false,
    lastFetched: null,
    cacheTimeout: 5 * 60 * 1000 // 5 minutes cache
  }),

  getters: {
    allBookings: (state) => state.bookings,
    
    // Filter by status
    pendingBookings: (state) => state.bookings.filter(b => b.status === 'pending'),
    confirmedBookings: (state) => state.bookings.filter(b => b.status === 'confirmed'),
    activeBookings: (state) => state.bookings.filter(b => b.status === 'active'),
    ongoingBookings: (state) => state.bookings.filter(b => b.status === 'ongoing'),
    completedBookings: (state) => state.bookings.filter(b => b.status === 'completed'),
    cancelledBookings: (state) => state.bookings.filter(b => b.status === 'cancelled'),
    
    // Get booking by ID
    getBookingById: (state) => (id) => state.bookings.find(b => b.id === id),
    
    // Check if data is stale
    isStale: (state) => {
      if (!state.lastFetched) return true
      return Date.now() - state.lastFetched > state.cacheTimeout
    }
  },

  actions: {
    async loadBookings(force = false) {
      if (!force && this.bookings.length > 0 && !this.isStale) {
        console.log('Using cached bookings data')
        return this.bookings
      }

      this.loading = true
      try {
        const apiStore = useApiStore()
        const response = await apiStore.get('/bookings')
        this.bookings = response.bookings || []
        this.lastFetched = Date.now()
        console.log('Loaded fresh bookings data:', this.bookings.length)
        return this.bookings
      } catch (error) {
        console.error('Failed to load bookings:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    addBooking(booking) {
      this.bookings.unshift(booking)
    },

    updateBooking(id, updatedBooking) {
      const index = this.bookings.findIndex(b => b.id === id)
      if (index !== -1) {
        this.bookings[index] = { ...this.bookings[index], ...updatedBooking }
      }
    },

    removeBooking(id) {
      this.bookings = this.bookings.filter(b => b.id !== id)
    },

    async refresh() {
      return this.loadBookings(true)
    },

    clearCache() {
      this.bookings = []
      this.lastFetched = null
    }
  }
})

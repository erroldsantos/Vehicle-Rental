import { defineStore } from 'pinia'
import { useApiStore } from './api'

export const usePaymentsStore = defineStore('payments', {
  state: () => ({
    payments: [],
    loading: false,
    lastFetched: null,
    cacheTimeout: 5 * 60 * 1000 // 5 minutes cache
  }),

  getters: {
    allPayments: (state) => state.payments,
    
    // Filter by status
    pendingPayments: (state) => state.payments.filter(p => p.status === 'pending'),
    completedPayments: (state) => state.payments.filter(p => p.status === 'completed'),
    
    // Filter by type
    fullPayments: (state) => state.payments.filter(p => p.payment_type === 'full'),
    downpayments: (state) => state.payments.filter(p => p.payment_type === 'downpayment'),
    
    // Get payment by ID
    getPaymentById: (state) => (id) => state.payments.find(p => p.id === id),
    
    // Get total amounts
    totalPending: (state) => {
      return state.payments
        .filter(p => p.status === 'pending')
        .reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
    },
    
    totalCompleted: (state) => {
      return state.payments
        .filter(p => p.status === 'completed')
        .reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
    },
    
    // Check if data is stale
    isStale: (state) => {
      if (!state.lastFetched) return true
      return Date.now() - state.lastFetched > state.cacheTimeout
    }
  },

  actions: {
    async loadPayments(force = false) {
      if (!force && this.payments.length > 0 && !this.isStale) {
        console.log('Using cached payments data')
        return this.payments
      }

      this.loading = true
      try {
        const apiStore = useApiStore()
        const response = await apiStore.get('/payments')
        this.payments = response.payments || []
        this.lastFetched = Date.now()
        console.log('Loaded fresh payments data:', this.payments.length)
        return this.payments
      } catch (error) {
        console.error('Failed to load payments:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    addPayment(payment) {
      this.payments.unshift(payment)
    },

    updatePayment(id, updatedPayment) {
      const index = this.payments.findIndex(p => p.id === id)
      if (index !== -1) {
        this.payments[index] = { ...this.payments[index], ...updatedPayment }
      }
    },

    removePayment(id) {
      this.payments = this.payments.filter(p => p.id !== id)
    },

    async refresh() {
      return this.loadPayments(true)
    },

    clearCache() {
      this.payments = []
      this.lastFetched = null
    }
  }
})

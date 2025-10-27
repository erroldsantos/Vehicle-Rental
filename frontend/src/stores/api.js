import { defineStore } from 'pinia'
import axios from 'axios'

// Create axios instance with defaults
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json'
  }
})

// Add response interceptor for consistent error handling
api.interceptors.response.use(
  response => response,
  error => {
    console.error('API Error:', error.response?.data || error.message)
    return Promise.reject(error)
  }
)

export const useApiStore = defineStore('api', {
  state: () => ({
    connected: false,
    loading: false,
    error: null
  }),

  actions: {
    /**
     * Check API health
     */
    async checkConnection() {
      try {
        const response = await api.get('/health')
        this.connected = true
        this.error = null
        return response.data
      } catch (error) {
        this.connected = false
        this.error = error.response?.data?.message || 'API connection failed'
        throw error
      }
    },

    /**
     * Generic GET request
     */
    async get(endpoint) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get(endpoint)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'GET request failed'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Generic POST request
     */
    async post(endpoint, data) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post(endpoint, data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'POST request failed'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Generic PUT request
     */
    async put(endpoint, data) {
      this.loading = true
      this.error = null
      try {
        const response = await api.put(endpoint, data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'PUT request failed'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Generic DELETE request
     */
    async delete(endpoint) {
      this.loading = true
      this.error = null
      try {
        const response = await api.delete(endpoint)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'DELETE request failed'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Clear error state
     */
    clearError() {
      this.error = null
    }
  }
})

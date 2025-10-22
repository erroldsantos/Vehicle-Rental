import { defineStore } from 'pinia'
import axios from 'axios'

export const useApiStore = defineStore('api', {
  state: () => ({
    baseUrl: '/api',
    connected: false,
    loading: false
  }),

  actions: {
    async checkConnection() {
      try {
        const response = await axios.get(`${this.baseUrl}/health`)
        this.connected = true
        return response.data
      } catch (error) {
        this.connected = false
        console.error('API connection failed:', error)
        throw error
      }
    },

    async get(endpoint) {
      this.loading = true
      try {
        const response = await axios.get(`${this.baseUrl}${endpoint}`)
        return response.data
      } catch (error) {
        console.error('GET request failed:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async post(endpoint, data) {
      this.loading = true
      try {
        const response = await axios.post(`${this.baseUrl}${endpoint}`, data)
        return response.data
      } catch (error) {
        console.error('POST request failed:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async put(endpoint, data) {
      this.loading = true
      try {
        const response = await axios.put(`${this.baseUrl}${endpoint}`, data)
        return response.data
      } catch (error) {
        console.error('PUT request failed:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async delete(endpoint) {
      this.loading = true
      try {
        const response = await axios.delete(`${this.baseUrl}${endpoint}`)
        return response.data
      } catch (error) {
        console.error('DELETE request failed:', error)
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
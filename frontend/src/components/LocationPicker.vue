<template>
  <div class="location-picker">
    <div class="location-input-group">
      <label :for="id">
        <i class="fas fa-map-marker-alt"></i>
        {{ label }}
      </label>
      <div class="input-with-button">
        <input 
          :id="id"
          type="text" 
          v-model="locationText"
          :placeholder="placeholder"
          readonly
          @click="openMap"
          required
        >
        <button type="button" @click="openMap" class="btn-map">
          <i class="fas fa-map"></i>
          Select on Map
        </button>
      </div>
      <small v-if="locationText" class="selected-location">
        <i class="fas fa-check-circle"></i> {{ locationText }}
      </small>
    </div>

    <!-- Map Modal -->
    <div v-if="showMap" class="map-modal" @click.self="closeMap">
      <div class="map-container">
        <div class="map-header">
          <h3>
            <i class="fas fa-map-marked-alt"></i>
            Select {{ label }}
          </h3>
          <button type="button" @click="closeMap" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="map-search">
          <input 
            type="text" 
            v-model="searchQuery"
            @keyup.enter="searchLocation"
            placeholder="Search for a location..."
            class="search-input"
          >
          <button type="button" @click="searchLocation" class="btn-search">
            <i class="fas fa-search"></i>
          </button>
        </div>

        <div ref="mapElement" class="map"></div>

        <div class="map-footer">
          <p v-if="selectedAddress">
            <strong>Selected:</strong> {{ selectedAddress }}
          </p>
          <div class="map-actions">
            <button type="button" @click="closeMap" class="btn-secondary">
              Cancel
            </button>
            <button 
              type="button" 
              @click="confirmLocation" 
              class="btn-primary"
              :disabled="!selectedCoords"
            >
              <i class="fas fa-check"></i>
              Confirm Location
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, onMounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix Leaflet default marker icon issue with Vite
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
})

export default {
  name: 'LocationPicker',
  props: {
    id: {
      type: String,
      required: true
    },
    label: {
      type: String,
      required: true
    },
    placeholder: {
      type: String,
      default: 'Click to select location on map'
    },
    modelValue: {
      type: Object,
      default: () => ({ address: '', lat: null, lng: null })
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const showMap = ref(false)
    const mapElement = ref(null)
    const map = ref(null)
    const marker = ref(null)
    const locationText = ref(props.modelValue?.address || '')
    const selectedCoords = ref(props.modelValue?.lat ? { lat: props.modelValue.lat, lng: props.modelValue.lng } : null)
    const selectedAddress = ref(props.modelValue?.address || '')
    const searchQuery = ref('')

    // Default center (Manila, Philippines)
    const defaultCenter = { lat: 13.4130, lng: 121.1789 }

    const initMap = () => {
      if (!mapElement.value) return

      // Initialize map
      const center = selectedCoords.value || defaultCenter
      map.value = L.map(mapElement.value).setView([center.lat, center.lng], 13)

      // Add OpenStreetMap tiles
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
      }).addTo(map.value)

      // Add existing marker if location already selected
      if (selectedCoords.value) {
        marker.value = L.marker([selectedCoords.value.lat, selectedCoords.value.lng])
          .addTo(map.value)
          .bindPopup(selectedAddress.value || 'Selected Location')
          .openPopup()
      }

      // Click event to select location
      map.value.on('click', async (e) => {
        const { lat, lng } = e.latlng
        
        // Remove old marker
        if (marker.value) {
          map.value.removeLayer(marker.value)
        }

        // Add new marker
        marker.value = L.marker([lat, lng]).addTo(map.value)

        // Store coordinates
        selectedCoords.value = { lat, lng }

        // Reverse geocoding to get address
        try {
          const address = await reverseGeocode(lat, lng)
          selectedAddress.value = address
          marker.value.bindPopup(address).openPopup()
        } catch (error) {
          selectedAddress.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`
          marker.value.bindPopup(selectedAddress.value).openPopup()
        }
      })
    }

    const reverseGeocode = async (lat, lng) => {
      try {
        const response = await fetch(
          `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`
        )
        const data = await response.json()
        return data.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`
      } catch (error) {
        console.error('Reverse geocoding failed:', error)
        return `${lat.toFixed(6)}, ${lng.toFixed(6)}`
      }
    }

    const searchLocation = async () => {
      if (!searchQuery.value.trim()) return

      try {
        const response = await fetch(
          `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}`
        )
        const data = await response.json()
        
        if (data && data.length > 0) {
          const { lat, lon, display_name } = data[0]
          const latitude = parseFloat(lat)
          const longitude = parseFloat(lon)

          // Move map to searched location
          map.value.setView([latitude, longitude], 15)

          // Remove old marker
          if (marker.value) {
            map.value.removeLayer(marker.value)
          }

          // Add marker at searched location
          marker.value = L.marker([latitude, longitude])
            .addTo(map.value)
            .bindPopup(display_name)
            .openPopup()

          selectedCoords.value = { lat: latitude, lng: longitude }
          selectedAddress.value = display_name
        } else {
          alert('Location not found. Please try a different search.')
        }
      } catch (error) {
        console.error('Search failed:', error)
        alert('Search failed. Please try again.')
      }
    }

    const openMap = () => {
      showMap.value = true
      setTimeout(() => {
        initMap()
      }, 100)
    }

    const closeMap = () => {
      if (map.value) {
        map.value.remove()
        map.value = null
        marker.value = null
      }
      showMap.value = false
      searchQuery.value = ''
    }

    const confirmLocation = () => {
      if (selectedCoords.value && selectedAddress.value) {
        locationText.value = selectedAddress.value
        emit('update:modelValue', {
          address: selectedAddress.value,
          lat: selectedCoords.value.lat,
          lng: selectedCoords.value.lng
        })
        closeMap()
      }
    }

    // Watch for external changes
    watch(() => props.modelValue, (newValue) => {
      if (newValue?.address) {
        locationText.value = newValue.address
        selectedCoords.value = newValue.lat ? { lat: newValue.lat, lng: newValue.lng } : null
        selectedAddress.value = newValue.address
      }
    })

    return {
      showMap,
      mapElement,
      locationText,
      selectedAddress,
      selectedCoords,
      searchQuery,
      openMap,
      closeMap,
      confirmLocation,
      searchLocation
    }
  }
}
</script>

<style scoped>
.location-picker {
  margin-bottom: 1rem;
}

.location-input-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.9rem;
}

.location-input-group label i {
  margin-right: 0.5rem;
  color: #6366f1;
}

.input-with-button {
  display: flex;
  gap: 0.5rem;
}

.input-with-button input {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.95rem;
  cursor: pointer;
  background: #f9fafb;
}

.input-with-button input:focus {
  outline: none;
  border-color: #6366f1;
  background: white;
}

.btn-map {
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  white-space: nowrap;
  transition: all 0.3s ease;
}

.btn-map:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-map i {
  margin-right: 0.5rem;
}

.selected-location {
  display: block;
  margin-top: 0.5rem;
  color: #059669;
  font-size: 0.85rem;
}

.selected-location i {
  margin-right: 0.25rem;
}

/* Map Modal */
.map-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
}

.map-container {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.map-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.map-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
}

.map-header h3 i {
  margin-right: 0.5rem;
  color: #6366f1;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
  padding: 0.25rem;
  transition: color 0.2s;
}

.btn-close:hover {
  color: #ef4444;
}

.map-search {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  gap: 0.5rem;
}

.search-input {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.95rem;
}

.search-input:focus {
  outline: none;
  border-color: #6366f1;
}

.btn-search {
  padding: 0.75rem 1.5rem;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-search:hover {
  background: #4f46e5;
}

.map {
  flex: 1;
  min-height: 400px;
  position: relative;
}

.map-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.map-footer p {
  margin: 0 0 1rem 0;
  color: #374151;
  font-size: 0.95rem;
}

.map-footer strong {
  color: #1f2937;
}

.map-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary i {
  margin-right: 0.5rem;
}

@media (max-width: 768px) {
  .map-modal {
    padding: 0;
  }

  .map-container {
    max-width: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .input-with-button {
    flex-direction: column;
  }

  .btn-map {
    width: 100%;
  }
}
</style>

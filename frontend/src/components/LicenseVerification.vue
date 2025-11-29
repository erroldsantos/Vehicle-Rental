<template>
  <div class="license-verification">
    <div class="license-card">
      <div class="card-header">
        <div class="header-content">
          <i class="fas fa-id-card"></i>
          <div>
            <h3>Driver's License Verification</h3>
            <p>Upload your driver's license for verification</p>
          </div>
        </div>
        <span :class="['status-badge', licenseStatus]">
          {{ getStatusText() }}
        </span>
      </div>

      <div class="card-body">
        <!-- Not Submitted State -->
        <div v-if="licenseStatus === 'not_submitted'" class="upload-section">
          <div class="upload-info">
            <i class="fas fa-info-circle"></i>
            <p>Please upload a clear photo of your driver's license. This helps us verify your identity and ensure the safety of all our users.</p>
          </div>

          <div class="upload-area" @click="triggerFileInput" @dragover.prevent @drop.prevent="handleDrop">
            <input
              ref="fileInput"
              type="file"
              accept="image/jpeg,image/jpg,image/png,image/gif"
              @change="handleFileSelect"
              style="display: none"
            />
            
            <div v-if="!selectedFile" class="upload-placeholder">
              <i class="fas fa-cloud-upload-alt"></i>
              <p class="upload-text">Click to upload or drag and drop</p>
              <p class="upload-hint">PNG, JPG or GIF (max. 5MB)</p>
            </div>

            <div v-else class="file-preview">
              <img :src="previewUrl" alt="License preview" class="preview-image" />
              <button @click.stop="removeFile" class="remove-btn">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <button
            v-if="selectedFile"
            @click="uploadLicense"
            :disabled="uploading"
            class="btn-primary btn-upload"
          >
            <i class="fas fa-upload" v-if="!uploading"></i>
            <i class="fas fa-spinner fa-spin" v-else></i>
            {{ uploading ? 'Uploading...' : 'Submit for Verification' }}
          </button>
        </div>

        <!-- Pending State -->
        <div v-else-if="licenseStatus === 'pending'" class="status-section pending">
          <i class="fas fa-clock status-icon"></i>
          <h4>Verification Pending</h4>
          <p>Your driver's license has been submitted and is awaiting review by our admin team.</p>
          <p class="submitted-date">Submitted: {{ formatDate(licenseData.license_submitted_at) }}</p>
          
          <div v-if="licenseData.license_image" class="submitted-preview">
            <img :src="getImageUrl(licenseData.license_image)" alt="Submitted license" />
          </div>
        </div>

        <!-- Verified State -->
        <div v-else-if="licenseStatus === 'verified'" class="status-section verified">
          <i class="fas fa-check-circle status-icon"></i>
          <h4>License Verified</h4>
          <p>Your driver's license has been successfully verified!</p>
          <p class="verified-date">Verified: {{ formatDate(licenseData.license_verified_at) }}</p>
          
          <div v-if="licenseData.license_image" class="submitted-preview">
            <img :src="getImageUrl(licenseData.license_image)" alt="Verified license" />
          </div>
        </div>

        <!-- Rejected State -->
        <div v-else-if="licenseStatus === 'rejected'" class="status-section rejected">
          <i class="fas fa-times-circle status-icon"></i>
          <h4>Verification Failed</h4>
          <p>Unfortunately, your driver's license could not be verified.</p>
          
          <div v-if="licenseData.license_rejection_reason" class="rejection-reason">
            <strong>Reason:</strong> {{ licenseData.license_rejection_reason }}
          </div>
          
          <button @click="resubmit" class="btn-primary">
            <i class="fas fa-redo"></i>
            Upload New License
          </button>
        </div>
      </div>
    </div>

    <!-- Alert Message -->
    <div v-if="alert.show" :class="['alert', alert.type]">
      <i :class="getAlertIcon()"></i>
      <span>{{ alert.message }}</span>
      <button @click="alert.show = false" class="alert-close">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useApiStore } from '@/stores/api'

export default {
  name: 'LicenseVerification',
  setup() {
    const apiStore = useApiStore()
    const fileInput = ref(null)
    const selectedFile = ref(null)
    const previewUrl = ref(null)
    const uploading = ref(false)
    const licenseData = ref({})
    const alert = ref({
      show: false,
      type: 'success',
      message: ''
    })

    const licenseStatus = computed(() => {
      return licenseData.value.license_status || 'not_submitted'
    })

    const getUserId = () => {
      const userInfo = JSON.parse(localStorage.getItem('user_info') || '{}')
      console.log('User info from localStorage:', userInfo)
      return userInfo.id
    }

    const loadLicenseStatus = async () => {
      try {
        const userId = getUserId()
        if (!userId) {
          console.warn('No user ID found in localStorage')
          showAlert('error', 'User not authenticated')
          return
        }

        console.log('Loading license status for user:', userId)
        const response = await apiStore.get(`/users/${userId}/license/status`)
        console.log('License status response:', response)
        licenseData.value = response
      } catch (error) {
        console.error('Error loading license status:', error)
        // Don't show error alert for initial load - user might just not have submitted yet
        if (error.response?.status !== 404) {
          showAlert('error', 'Failed to load license status')
        }
      }
    }

    const triggerFileInput = () => {
      fileInput.value?.click()
    }

    const handleFileSelect = (event) => {
      const file = event.target.files[0]
      if (file) {
        validateAndSetFile(file)
      }
    }

    const handleDrop = (event) => {
      const file = event.dataTransfer.files[0]
      if (file) {
        validateAndSetFile(file)
      }
    }

    const validateAndSetFile = (file) => {
      // Validate file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
      if (!allowedTypes.includes(file.type)) {
        showAlert('error', 'Please upload a valid image file (JPG, PNG, or GIF)')
        return
      }

      // Validate file size (5MB)
      const maxSize = 5 * 1024 * 1024
      if (file.size > maxSize) {
        showAlert('error', 'File size must be less than 5MB')
        return
      }

      selectedFile.value = file
      previewUrl.value = URL.createObjectURL(file)
    }

    const removeFile = () => {
      if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
      }
      selectedFile.value = null
      previewUrl.value = null
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    }

    const uploadLicense = async () => {
      if (!selectedFile.value) {
        showAlert('error', 'Please select a file first')
        return
      }

      uploading.value = true
      
      try {
        const userId = getUserId()
        if (!userId) {
          showAlert('error', 'User not authenticated')
          uploading.value = false
          return
        }

        console.log('Uploading license for user:', userId)
        console.log('File:', selectedFile.value.name, selectedFile.value.type, selectedFile.value.size)

        const formData = new FormData()
        formData.append('license_image', selectedFile.value)

        const response = await apiStore.upload(`/users/${userId}/license/upload`, formData)
        
        console.log('Upload response:', response)
        
        showAlert('success', 'License uploaded successfully! Awaiting verification.')
        
        // Reload license status
        await loadLicenseStatus()
        
        // Clear selected file
        removeFile()
      } catch (error) {
        console.error('Error uploading license:', error)
        console.error('Error details:', error.response)
        
        // More detailed error message
        let errorMessage = 'Failed to upload license'
        if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.message) {
          errorMessage = error.message
        }
        
        showAlert('error', errorMessage)
      } finally {
        uploading.value = false
      }
    }

    const resubmit = () => {
      licenseData.value = { license_status: 'not_submitted' }
      removeFile()
    }

    const getStatusText = () => {
      const statusMap = {
        not_submitted: 'Not Submitted',
        pending: 'Pending Review',
        verified: 'Verified',
        rejected: 'Rejected'
      }
      return statusMap[licenseStatus.value] || 'Unknown'
    }

    const formatDate = (dateString) => {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getImageUrl = (path) => {
      if (!path) return ''
      // Remove 'public/' prefix if it exists and add proper base URL
      const cleanPath = path.replace(/^public\//, '')
      // Use the full backend URL
      return `https://vehiclerental.infinityfreeapp.com/${cleanPath}`
    }

    const showAlert = (type, message) => {
      alert.value = {
        show: true,
        type,
        message
      }

      setTimeout(() => {
        alert.value.show = false
      }, 5000)
    }

    const getAlertIcon = () => {
      const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
      }
      return icons[alert.value.type] || icons.info
    }

    onMounted(() => {
      loadLicenseStatus()
    })

    return {
      fileInput,
      selectedFile,
      previewUrl,
      uploading,
      licenseData,
      licenseStatus,
      alert,
      triggerFileInput,
      handleFileSelect,
      handleDrop,
      removeFile,
      uploadLicense,
      resubmit,
      getStatusText,
      formatDate,
      getImageUrl,
      getAlertIcon
    }
  }
}
</script>

<style scoped>
.license-verification {
  position: relative;
}

.license-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.card-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-content i {
  font-size: 2rem;
}

.header-content h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1.25rem;
}

.header-content p {
  margin: 0;
  opacity: 0.9;
  font-size: 0.875rem;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.not_submitted {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.status-badge.pending {
  background: #fed7aa;
  color: #c05621;
}

.status-badge.verified {
  background: #c6f6d5;
  color: #38a169;
}

.status-badge.rejected {
  background: #fed7d7;
  color: #c53030;
}

.card-body {
  padding: 2rem;
}

/* Upload Section */
.upload-info {
  display: flex;
  gap: 0.75rem;
  padding: 1rem;
  background: #ebf8ff;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.upload-info i {
  color: #3182ce;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.upload-info p {
  margin: 0;
  color: #2c5282;
  font-size: 0.875rem;
  line-height: 1.5;
}

.upload-area {
  border: 2px dashed #cbd5e0;
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-bottom: 1.5rem;
}

.upload-area:hover {
  border-color: #667eea;
  background: #f7fafc;
}

.upload-placeholder i {
  font-size: 3rem;
  color: #a0aec0;
  margin-bottom: 1rem;
}

.upload-text {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
  font-weight: 500;
}

.upload-hint {
  margin: 0;
  color: #718096;
  font-size: 0.875rem;
}

.file-preview {
  position: relative;
  display: inline-block;
}

.preview-image {
  max-width: 100%;
  max-height: 300px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.remove-btn {
  position: absolute;
  top: -10px;
  right: -10px;
  background: #e53e3e;
  color: white;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

.remove-btn:hover {
  background: #c53030;
  transform: scale(1.1);
}

.btn-upload {
  width: 100%;
  justify-content: center;
}

/* Status Sections */
.status-section {
  text-align: center;
  padding: 2rem;
}

.status-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.status-section.pending .status-icon {
  color: #d69e2e;
}

.status-section.verified .status-icon {
  color: #38a169;
}

.status-section.rejected .status-icon {
  color: #e53e3e;
}

.status-section h4 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
  font-size: 1.5rem;
}

.status-section p {
  margin: 0 0 1rem 0;
  color: #718096;
}

.submitted-date,
.verified-date {
  font-size: 0.875rem;
  color: #a0aec0;
  margin-bottom: 1.5rem;
}

.submitted-preview {
  margin-top: 1.5rem;
}

.submitted-preview img {
  max-width: 100%;
  max-height: 300px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.rejection-reason {
  background: #fff5f5;
  border-left: 4px solid #e53e3e;
  padding: 1rem;
  border-radius: 4px;
  text-align: left;
  margin: 1.5rem 0;
  color: #742a2a;
}

.rejection-reason strong {
  display: block;
  margin-bottom: 0.5rem;
}

/* Buttons */
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Alert */
.alert {
  position: fixed;
  top: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  z-index: 1000;
  animation: slideIn 0.3s ease;
  max-width: 400px;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.alert.success {
  background: #c6f6d5;
  color: #22543d;
}

.alert.error {
  background: #fed7d7;
  color: #742a2a;
}

.alert.warning {
  background: #feebc8;
  color: #7c2d12;
}

.alert.info {
  background: #bee3f8;
  color: #2c5282;
}

.alert-close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0.25rem;
  margin-left: auto;
}

/* Responsive */
@media (max-width: 768px) {
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .card-body {
    padding: 1.5rem;
  }

  .upload-area {
    padding: 1.5rem;
  }

  .alert {
    top: 1rem;
    right: 1rem;
    left: 1rem;
    max-width: none;
  }
}
</style>

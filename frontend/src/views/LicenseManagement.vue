<template>
  <div class="license-management">
    <div class="page-title">
      <h1><i class="fas fa-id-card"></i> Driver's License Verification</h1>
      <p>Review and verify user driver's licenses</p>
    </div>
    
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon orange">
          <i class="fas fa-clock"></i>
        </div>
        <span class="stat-value">{{ stats.pending }}</span>
        <span class="stat-label">Pending Review</span>
      </div>
      <div class="stat-card" @click="activeTab = 'verified'" style="cursor: pointer;">
        <div class="stat-icon green">
          <i class="fas fa-check-circle"></i>
        </div>
        <span class="stat-value">{{ stats.verified }}</span>
        <span class="stat-label">Verified</span>
      </div>
      <div class="stat-card">
        <div class="stat-icon gray">
          <i class="fas fa-users"></i>
        </div>
        <span class="stat-value">{{ stats.not_submitted }}</span>
        <span class="stat-label">Not Submitted</span>
      </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs">
      <button 
        :class="['tab-btn', { active: activeTab === 'pending' }]" 
        @click="activeTab = 'pending'"
      >
        <i class="fas fa-clock"></i> Pending ({{ stats.pending }})
      </button>
      <button 
        :class="['tab-btn', { active: activeTab === 'verified' }]" 
        @click="activeTab = 'verified'; loadVerifiedLicenses()"
      >
        <i class="fas fa-check-circle"></i> Verified ({{ stats.verified }})
      </button>
    </div>
    
    <div v-show="activeTab === 'pending'" class="pending-section">
      <div class="section-header">
        <h3>Pending Verifications</h3>
        <button class="refresh" @click="loadPendingLicenses(); loadStats()"><i class="fas fa-sync"></i> Refresh</button>
      </div>
      
      <div v-if="pendingLicenses.length === 0" class="empty">
        No pending licenses.
      </div>
      
      <div v-else class="license-cards">
        <div v-for="user in pendingLicenses" :key="user.id" class="license-card">
          <div class="card-left">
            <div class="user-avatar">{{ getInitials(user.name) }}</div>
            <div class="user-info">
              <h4>{{ user.name }}</h4>
              <p class="email">{{ user.email }}</p>
              <p class="phone"><i class="fas fa-phone"></i> {{ user.phone }}</p>
              <p class="submitted-date">Submitted: {{ formatDate(user.license_submitted_at) }}</p>
            </div>
          </div>
          
          <div class="card-center">
            <img
              v-if="user.license_image"
              :src="getImageUrl(user.license_image)"
              :alt="`${user.name}'s license`"
              class="license-img"
              @click="openImageModal(user)"
            />
            <button class="view-full" @click="openImageModal(user)">
              <i class="fas fa-search"></i> View Full Size
            </button>
          </div>
          
          <div class="card-right">
            <button class="verify" @click="verifyLicense(user.id)" :disabled="processing">
              <i class="fas fa-check"></i> Verify
            </button>
            <button class="reject" @click="rejectLicense(user.id)" :disabled="processing">
              <i class="fas fa-times"></i> Reject
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Verified Users Section -->
    <div v-show="activeTab === 'verified'" class="verified-section">
      <div class="section-header">
        <h3>Verified Licenses</h3>
        <button class="refresh" @click="loadVerifiedLicenses(); loadStats()">
          <i class="fas fa-sync"></i> Refresh
        </button>
      </div>
      
      <div v-if="verifiedLicenses.length === 0" class="empty">
        No verified licenses yet.
      </div>
      
      <div v-else class="license-cards">
        <div v-for="user in verifiedLicenses" :key="user.id" class="license-card verified-card">
          <div class="card-left">
            <div class="user-avatar verified-avatar">
              <i class="fas fa-check"></i>
            </div>
            <div class="user-info">
              <h4>{{ user.name }}</h4>
              <p class="email">{{ user.email }}</p>
              <p class="phone"><i class="fas fa-phone"></i> {{ user.phone }}</p>
              <p class="verified-date">
                <i class="fas fa-calendar-check"></i> Verified: {{ formatDate(user.license_verified_at) }}
              </p>
            </div>
          </div>
          
          <div class="card-center">
            <img
              v-if="user.license_image"
              :src="getImageUrl(user.license_image)"
              :alt="`${user.name}'s license`"
              class="license-img"
              @click="openImageModal(user)"
            />
            <button class="view-full" @click="openImageModal(user)">
              <i class="fas fa-search"></i> View Full Size
            </button>
          </div>
          
          <div class="card-right">
            <div class="verified-badge">
              <i class="fas fa-shield-check"></i>
              <span>Verified</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="imageModal.show" class="modal" @click.self="closeImageModal">
      <div class="modal-content">
        <button class="modal-close" @click="closeImageModal">
          <i class="fas fa-times"></i>
        </button>
        <img :src="getImageUrl(imageModal.license?.license_image)" alt="Full Size License" />
        <div class="modal-info">
          <h3>{{ imageModal.license?.name }}</h3>
          <p>{{ imageModal.license?.email }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useApiStore } from '@/stores/api'

export default {
  name: 'LicenseManagement',
  setup() {
    const apiStore = useApiStore()
    const loading = ref(false)
    const processing = ref(false)
    const pendingLicenses = ref([])
    const verifiedLicenses = ref([])
    const activeTab = ref('pending')
    const stats = ref({
      pending: 0,
      verified: 0,
      rejected: 0,
      not_submitted: 0
    })

    const verifyModal = ref({
      show: false,
      license: null
    })

    const rejectModal = ref({
      show: false,
      license: null,
      reason: ''
    })

    const imageModal = ref({
      show: false,
      license: null
    })

    const alert = ref({
      show: false,
      type: 'success',
      message: ''
    })

    const loadPendingLicenses = async () => {
      loading.value = true
      try {
        const response = await apiStore.get('/admin/licenses/pending')
        pendingLicenses.value = response.licenses || []
      } catch (error) {
        console.error('Error loading pending licenses:', error)
        showAlert('error', 'Failed to load pending licenses')
      } finally {
        loading.value = false
      }
    }

    const loadStats = async () => {
      try {
        const response = await apiStore.get('/admin/licenses/stats')
        stats.value = response
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    }

    const loadVerifiedLicenses = async () => {
      loading.value = true
      try {
        const response = await apiStore.get('/admin/licenses/verified')
        verifiedLicenses.value = response.licenses || []
      } catch (error) {
        console.error('Error loading verified licenses:', error)
        showAlert('error', 'Failed to load verified licenses')
      } finally {
        loading.value = false
      }
    }

    const openVerifyModal = (license) => {
      verifyModal.value = {
        show: true,
        license
      }
    }

    const closeVerifyModal = () => {
      verifyModal.value = {
        show: false,
        license: null
      }
    }

    const verifyLicense = async (userId) => {
      console.log('verifyLicense called with userId:', userId)
      if (!userId) return

      processing.value = true
      try {
        const adminInfo = JSON.parse(localStorage.getItem('user_info') || '{}')
        console.log('Admin info:', adminInfo)
        
        const result = await apiStore.post(`/admin/licenses/${userId}/verify`, {
          admin_id: adminInfo.id
        })
        console.log('Verify result:', result)

        await loadPendingLicenses()
        await loadStats()
      } catch (error) {
        console.error('Error verifying license:', error)
      } finally {
        processing.value = false
      }
    }

    const openRejectModal = (license) => {
      rejectModal.value = {
        show: true,
        license,
        reason: ''
      }
    }

    const closeRejectModal = () => {
      rejectModal.value = {
        show: false,
        license: null,
        reason: ''
      }
    }

    const rejectLicense = async (userId) => {
      console.log('rejectLicense called with userId:', userId)
      if (!userId) return
      
      const reason = prompt('Please provide a reason for rejection:')
      console.log('Rejection reason:', reason)
      if (!reason || !reason.trim()) return

      processing.value = true
      try {
        const adminInfo = JSON.parse(localStorage.getItem('user_info') || '{}')
        console.log('Admin info:', adminInfo)
        
        const result = await apiStore.post(`/admin/licenses/${userId}/reject`, {
          admin_id: adminInfo.id,
          reason: reason.trim()
        })
        console.log('Reject result:', result)

        await loadPendingLicenses()
        await loadStats()
      } catch (error) {
        console.error('Error rejecting license:', error)
      } finally {
        processing.value = false
      }
    }

    const openImageModal = (license) => {
      imageModal.value = {
        show: true,
        license
      }
    }

    const closeImageModal = () => {
      imageModal.value = {
        show: false,
        license: null
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getImageUrl = (path) => {
      if (!path) return ''
      // Check if path already includes 'public/' or starts with 'images/'
      if (path.startsWith('public/')) {
        return `https://vehiclerental.infinityfreeapp.com/${path}`
      } else if (path.startsWith('images/')) {
        return `https://vehiclerental.infinityfreeapp.com/public/${path}`
      }
      // Fallback: assume it's just the filename
      return `https://vehiclerental.infinityfreeapp.com/public/images/licenses/${path}`
    }
    
    const getInitials = (name) => {
      if (!name) return ''
      return name.split(' ').map(n => n[0]).join('').toUpperCase()
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
      loadPendingLicenses()
      loadStats()
    })

    return {
      loading,
      processing,
      pendingLicenses,
      verifiedLicenses,
      activeTab,
      stats,
      verifyModal,
      rejectModal,
      imageModal,
      alert,
      loadPendingLicenses,
      loadVerifiedLicenses,
      loadStats,
      openVerifyModal,
      closeVerifyModal,
      verifyLicense,
      openRejectModal,
      closeRejectModal,
      rejectLicense,
      openImageModal,
      closeImageModal,
      formatDate,
      getImageUrl,
      getInitials,
      getAlertIcon
    }
  }
}
</script>

<style scoped>
.license-management {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-title {
  margin-bottom: 1.5rem;
}

.page-title h1 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.page-title p {
  color: #7f8c8d;
  font-size: 1rem;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: #fff;
  padding: 1.25rem;
  border-radius: 8px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  border: 1px solid #e8e8e8;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.75rem;
  font-size: 1.5rem;
  color: #fff;
}

.stat-icon.orange {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.stat-icon.green {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
}

.stat-icon.red {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.stat-icon.gray {
  background: linear-gradient(135deg, #95a5a6, #7f8c8d);
}

.stat-value {
  font-size: 2rem;
  font-weight: bold;
  display: block;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.stat-label {
  color: #7f8c8d;
  font-size: 0.875rem;
}

.pending-section {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  padding: 1.5rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  font-size: 1.25rem;
  color: #2c3e50;
  margin: 0;
}

.refresh {
  background: #6c5ce7;
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: background 0.2s;
}

.refresh:hover {
  background: #5f4dd1;
}

.license-cards {
  display: grid;
  gap: 1rem;
}

.license-card {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1.25rem;
  border: 1px solid #e8e8e8;
  display: grid;
  grid-template-columns: 2fr 1fr auto;
  gap: 1.5rem;
  align-items: center;
}

.card-left {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.user-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  font-weight: bold;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
}

.user-info h4 {
  margin: 0 0 0.25rem;
  color: #2c3e50;
  font-size: 1rem;
  font-weight: 600;
}

.user-info p {
  margin: 0.15rem 0;
  color: #7f8c8d;
  font-size: 0.875rem;
}

.user-info .email {
  color: #495057;
}

.user-info .phone {
  color: #6c757d;
}

.submitted-date {
  color: #95a5a6 !important;
  font-size: 0.75rem !important;
  margin-top: 0.5rem !important;
}

.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 2px solid #e8e8e8;
}

.tab-btn {
  background: transparent;
  border: none;
  padding: 1rem 1.5rem;
  cursor: pointer;
  color: #7f8c8d;
  font-size: 0.9rem;
  font-weight: 500;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tab-btn:hover {
  color: #6c5ce7;
}

.tab-btn.active {
  color: #6c5ce7;
  border-bottom-color: #6c5ce7;
}

.verified-section {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  padding: 1.5rem;
}

.verified-card {
  background: #f0fdf4 !important;
  border: 1px solid #86efac !important;
}

.verified-avatar {
  background: linear-gradient(135deg, #10b981, #059669) !important;
}

.verified-date {
  color: #059669 !important;
  font-weight: 500;
}

.verified-badge {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.verified-badge i {
  font-size: 1.5rem;
}

.verified-badge span {
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.modal-content {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.modal-close {
  position: absolute;
  top: -40px;
  right: 0;
  background: #fff;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.2rem;
  color: #2c3e50;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  transition: all 0.2s;
}

.modal-close:hover {
  background: #e74c3c;
  color: #fff;
  transform: scale(1.1);
}

.modal-content img {
  max-width: 100%;
  max-height: 70vh;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}

.modal-info {
  background: #fff;
  padding: 1rem 2rem;
  border-radius: 8px;
  text-align: center;
}

.modal-info h3 {
  margin: 0 0 0.25rem 0;
  color: #2c3e50;
}

.modal-info p {
  margin: 0;
  color: #7f8c8d;
  font-size: 0.9rem;
}

.card-center {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.license-img {
  max-width: 150px;
  max-height: 100px;
  width: auto;
  height: auto;
  border-radius: 4px;
  border: 2px solid #e8e8e8;
  cursor: pointer;
  display: block;
  object-fit: contain;
}

.license-img:hover {
  border-color: #6c5ce7;
}

.view-full {
  background: transparent;
  border: 1px solid #6c5ce7;
  color: #6c5ce7;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.75rem;
  transition: all 0.2s;
}

.view-full:hover {
  background: #6c5ce7;
  color: #fff;
}

.card-right {
  display: flex;
  gap: 0.5rem;
  flex-direction: column;
}

.verify {
  background: #28a745;
  color: #fff;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: background 0.2s;
  white-space: nowrap;
}

.verify:hover {
  background: #218838;
}

.reject {
  background: #dc3545;
  color: #fff;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: background 0.2s;
  white-space: nowrap;
}

.reject:hover {
  background: #c82333;
}

.page-title h1 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.reject:hover {
  background: #c82333;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal img {
  max-width: 90vw;
  max-height: 90vh;
  border-radius: 8px;
}

.empty {
  color: #888;
  text-align: center;
  padding: 3rem 0;
  font-size: 1rem;
}
</style>

<template>
  <div class="payment-management">
    <!-- Page Header -->
    <div class="page-header">
      <h1>Payment Management</h1>
      <div class="header-actions">
        <button class="action-btn" @click="exportReport">
          <i class="fas fa-download"></i> Export Report
        </button>
        <button class="action-btn secondary" @click="refreshData" :disabled="loading">
          <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Payment Overview Cards -->
    <div class="stats-grid" style="margin-bottom: 40px;">
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-card">
          <h3>₱{{ formatAmount(stats.total_revenue) }}</h3>
          <p>Total Revenue</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.paid_count }}</h3>
          <p>Paid Invoices</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.pending_count }}</h3>
          <p>Pending Payments</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.overdue_count }}</h3>
          <p>Overdue</p>
        </div>
      </div>
    </div>

    <!-- Payments List -->
    <div class="data-card">
      <div class="card-header">
        <h2>Payment Records</h2>
        <button class="action-btn primary" @click="showCreateModal = true">
          <i class="fas fa-plus"></i> Add Payment
        </button>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading payments...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="payments.length === 0" class="empty-state">
        <i class="fas fa-receipt"></i>
        <h3>No Payments Found</h3>
        <p>No payment records have been created yet.</p>
        <button class="action-btn primary" @click="showCreateModal = true">
          <i class="fas fa-plus"></i> Create First Payment
        </button>
      </div>

      <!-- Payments Table -->
      <div v-else class="modern-table">
        <div class="table-row header-row">
          <span>Booking Ref</span>
          <span>Customer</span>
          <span>Vehicle</span>
          <span>Amount</span>
          <span>Method</span>
          <span>Status</span>
          <span>Date</span>
          <span>Actions</span>
        </div>
        <div v-for="payment in payments" :key="payment.id" class="table-row">
          <span class="booking-ref">{{ payment.booking_reference || 'N/A' }}</span>
          <span>{{ getCustomerName(payment) }}</span>
          <span class="vehicle-info">{{ getVehicleInfo(payment) }}</span>
          <span class="amount">₱{{ formatAmount(payment.amount) }}</span>
          <span class="payment-method">{{ payment.payment_method }}</span>
          <span>
            <span :class="['status-badge', getStatusClass(payment.status)]">
              {{ getStatusText(payment.status) }}
            </span>
          </span>
          <span>{{ formatDate(payment.payment_date) }}</span>
          <span class="actions">
            <button class="action-btn-sm" @click="viewPayment(payment)" title="View Details">
              <i class="fas fa-eye"></i>
            </button>
            <button 
              v-if="payment.status === 'pending'" 
              class="action-btn-sm success" 
              @click="markAsPaid(payment)"
              title="Mark as Paid"
            >
              <i class="fas fa-check"></i>
            </button>
            <button class="action-btn-sm danger" @click="deletePayment(payment)" title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- Create Payment Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Add New Payment</h3>
          <button class="close-btn" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createPayment">
            <div class="form-group">
              <label>Booking Reference</label>
              <select v-model="newPayment.booking_id" required>
                <option value="">Select a booking...</option>
                <option v-for="booking in availableBookings" :key="booking.id" :value="booking.id">
                  {{ booking.booking_reference }} - {{ booking.customer_name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Amount</label>
              <input v-model="newPayment.amount" type="number" step="0.01" required />
            </div>
            <div class="form-group">
              <label>Payment Method</label>
              <select v-model="newPayment.payment_method" required>
                <option value="cash">Cash</option>
                <option value="credit_card">Credit Card</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="check">Check</option>
              </select>
            </div>
            <div class="form-group">
              <label>Payment Date</label>
              <input v-model="newPayment.payment_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="newPayment.status">
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div class="modal-actions">
              <button type="button" class="action-btn secondary" @click="closeModal">Cancel</button>
              <button type="submit" class="action-btn primary" :disabled="createLoading">
                <i v-if="createLoading" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-plus"></i>
                {{ createLoading ? 'Creating...' : 'Create Payment' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, reactive } from 'vue'

export default {
  name: 'PaymentManagement',
  setup() {
    const loading = ref(false)
    const createLoading = ref(false)
    const payments = ref([])
    const availableBookings = ref([])
    const showCreateModal = ref(false)
    
    const stats = reactive({
      total_revenue: '0.00',
      paid_count: 0,
      pending_count: 0,
      overdue_count: 0
    })

    const newPayment = reactive({
      booking_id: '',
      amount: '',
      payment_method: 'cash',
      payment_date: new Date().toISOString().split('T')[0],
      status: 'pending'
    })

    const loadPayments = async () => {
      try {
        const response = await apiStore.get('/payments')
        // Handle both {payments: [...]} and {data: {payments: [...]}} formats
        payments.value = response.payments || response.data?.payments || []
      } catch (error) {
        console.error('Error loading payments:', error)
        payments.value = []
      }
    }

    const loadStats = async () => {
      try {
        const response = await apiStore.get('/payments/stats')
        Object.assign(stats, response.data || response)
      } catch (error) {
        console.error('Error loading payment stats:', error)
      }
    }

    const loadAvailableBookings = async () => {
      try {
        const response = await apiStore.get('/bookings')
        const bookings = response.bookings || response.data?.bookings || []
          
        // Filter out cancelled bookings - only show pending, confirmed, or completed bookings
        const activeBookings = bookings.filter(booking => {
          const status = booking.status ? booking.status.toLowerCase() : ''
          return status !== 'cancelled'
        })
        
        availableBookings.value = activeBookings.map(booking => ({
          id: booking.id,
          booking_reference: booking.booking_reference,
          customer_name: `${booking.first_name || ''} ${booking.last_name || ''}`.trim() || 'Unknown Customer'
        }))
      } catch (error) {
        console.error('Error loading bookings:', error)
        availableBookings.value = []
      }
    }

    const refreshData = async () => {
      loading.value = true
      try {
        await Promise.all([loadPayments(), loadStats()])
      } finally {
        loading.value = false
      }
    }

    const createPayment = async () => {
      createLoading.value = true
      try {
        await apiStore.post('/payments', newPayment)
        closeModal()
        await refreshData()
      } catch (error) {
        console.error('Error creating payment:', error)
        
      } finally {
        createLoading.value = false
      }
    }

    const markAsPaid = async (payment) => {
      try {
        await apiStore.put(`/payments/${payment.id}`, { status: 'completed' })
        await refreshData()
      } catch (error) {
        console.error('Error updating payment:', error)
        
      }
    }

    const deletePayment = async (payment) => {
      try {
        await apiStore.delete(`/payments/${payment.id}`)
        await refreshData()
      } catch (error) {
        console.error('Error deleting payment:', error)
        
      }
    }

    const closeModal = () => {
      showCreateModal.value = false
      // Reset form
      Object.assign(newPayment, {
        booking_id: '',
        amount: '',
        payment_method: 'cash',
        payment_date: new Date().toISOString().split('T')[0],
        status: 'pending'
      })
    }

    const viewPayment = (payment) => {
      alert(`Payment Details:\n\nBooking: ${payment.booking_reference}\nCustomer: ${getCustomerName(payment)}\nAmount: ₱${formatAmount(payment.amount)}\nMethod: ${payment.payment_method}\nStatus: ${getStatusText(payment.status)}`)
    }

    const exportReport = () => {
      
    }

    // Helper functions
    const formatAmount = (amount) => {
      return parseFloat(amount || 0).toFixed(2)
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const getCustomerName = (payment) => {
      return `${payment.first_name || ''} ${payment.last_name || ''}`.trim() || 'Unknown Customer'
    }

    const getVehicleInfo = (payment) => {
      return `${payment.brand || ''} ${payment.model || ''}`.trim() || 'N/A'
    }

    const getStatusClass = (status) => {
      return {
        'pending': 'pending',
        'completed': 'confirmed'
      }[status] || 'pending'
    }

    const getStatusText = (status) => {
      return {
        'pending': 'Pending',
        'completed': 'Paid'
      }[status] || 'Unknown'
    }

    onMounted(async () => {
      await Promise.all([
        refreshData(),
        loadAvailableBookings()
      ])
    })

    return {
      loading,
      createLoading,
      payments,
      availableBookings,
      stats,
      showCreateModal,
      newPayment,
      refreshData,
      createPayment,
      markAsPaid,
      deletePayment,
      closeModal,
      viewPayment,
      exportReport,
      formatAmount,
      formatDate,
      getCustomerName,
      getVehicleInfo,
      getStatusClass,
      getStatusText
    }
  }
}
</script>

<style scoped>
/* Loading and Empty States */
.loading-state, .empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.loading-state i, .empty-state i {
  font-size: 48px;
  margin-bottom: 16px;
  color: #3498db;
}

.empty-state h3 {
  margin: 16px 0 8px 0;
  color: #333;
}

.empty-state p {
  margin-bottom: 24px;
}

/* Enhanced Table Styles */
.vehicle-info {
  font-weight: 500;
  color: #2c3e50;
}

.payment-method {
  text-transform: capitalize;
  color: #666;
}

/* Action Buttons */
.action-btn-sm.success {
  background: #27ae60;
  color: white;
}

.action-btn-sm.success:hover {
  background: #219a52;
}

.action-btn-sm.danger {
  background: #e74c3c;
  color: white;
}

.action-btn-sm.danger:hover {
  background: #c0392b;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #999;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  color: #333;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #3498db;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

/* Card Header Enhancement */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.card-header h2 {
  margin: 0;
}

/* Status Badge Colors */
.status-badge.confirmed {
  background: #27ae60;
  color: white;
}

.status-badge.pending {
  background: #f39c12;
  color: white;
}

/* Animation for spinning refresh icon */
.fa-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Disabled button state */
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
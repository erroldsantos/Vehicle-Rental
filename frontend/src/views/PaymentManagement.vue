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
          <span>Type</span>
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
          <span class="payment-type">
            <span :class="['type-badge', payment.payment_type === 'downpayment' ? 'downpayment' : 'full']">
              {{ payment.payment_type === 'downpayment' ? 'Down (30%)' : 'Full' }}
            </span>
          </span>
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
              <select v-model="newPayment.booking_id" required @change="onBookingChange">
                <option value="">Select a booking...</option>
                <option v-for="booking in availableBookings" :key="booking.id" :value="booking.id">
                  {{ booking.booking_reference }} - {{ booking.customer_name }} 
                  ({{ booking.reason === 'downpayment' ? 'Balance Payment' : 'Damage Repair' }})
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Amount</label>
              <input v-model="newPayment.amount" type="text" readonly class="readonly-input" />
              <small style="color: #6c757d; margin-top: 0.25rem; display: block;">Amount is automatically set based on selected booking</small>
            </div>
            <div class="form-group">
              <label>Payment Method</label>
              <select v-model="newPayment.payment_method" required>
                <option value="cash">Cash</option>
                <option value="gcash">GCash</option>
              </select>
            </div>
            <div class="form-group">
              <label>Payment Type</label>
              <select v-model="newPayment.payment_type" required>
                <option value="full">Full Payment</option>
                <option value="downpayment">Down Payment (30%)</option>
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

    <!-- Payment Receipt Modal -->
    <div v-if="showReceiptModal && selectedPayment" class="modal-overlay" @click="closeReceiptModal">
      <div class="modal-content receipt-modal" @click.stop>
        <div class="modal-header">
          <h3>Payment Receipt</h3>
          <button class="close-btn" @click="closeReceiptModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="receipt-container">
            <!-- Receipt Header -->
            <div class="receipt-header">
              <div class="company-info">
                <h2><i class="fas fa-car"></i> Vehicle Rental</h2>
                <p>Payment Receipt</p>
              </div>
              <div class="receipt-number">
                <p>Receipt #{{ selectedPayment.id }}</p>
                <p>{{ formatDate(selectedPayment.payment_date) }}</p>
              </div>
            </div>

            <div class="receipt-divider"></div>

            <!-- Customer & Booking Info -->
            <div class="receipt-section">
              <h4><i class="fas fa-user"></i> Customer Information</h4>
              <div class="info-grid">
                <div class="info-item">
                  <span class="label">Name:</span>
                  <span class="value">{{ getCustomerName(selectedPayment) }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Booking Reference:</span>
                  <span class="value">{{ selectedPayment.booking_reference || 'N/A' }}</span>
                </div>
              </div>
            </div>

            <div class="receipt-divider"></div>

            <!-- Vehicle Info -->
            <div class="receipt-section">
              <h4><i class="fas fa-car"></i> Vehicle Details</h4>
              <div class="info-grid">
                <div class="info-item">
                  <span class="label">Vehicle:</span>
                  <span class="value">{{ getVehicleInfo(selectedPayment) }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Plate Number:</span>
                  <span class="value">{{ selectedPayment.plate_number || 'N/A' }}</span>
                </div>
              </div>
            </div>

            <div class="receipt-divider"></div>

            <!-- Payment Details -->
            <div class="receipt-section">
              <h4><i class="fas fa-credit-card"></i> Payment Details</h4>
              <div class="payment-details">
                <div class="detail-row">
                  <span class="label">Payment Method:</span>
                  <span class="value method">{{ selectedPayment.payment_method }}</span>
                </div>
                <div class="detail-row">
                  <span class="label">Payment Type:</span>
                  <span class="value type">{{ selectedPayment.payment_type === 'downpayment' ? 'Down Payment (30%)' : 'Full Payment' }}</span>
                </div>
                <div class="detail-row">
                  <span class="label">Payment Status:</span>
                  <span>
                    <span :class="['status-badge', getStatusClass(selectedPayment.status)]">
                      {{ getStatusText(selectedPayment.status) }}
                    </span>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="label">Payment Date:</span>
                  <span class="value">{{ formatDate(selectedPayment.payment_date) }}</span>
                </div>
              </div>
            </div>

            <div class="receipt-divider"></div>

            <!-- Total Amount -->
            <div class="receipt-total">
              <div class="total-row">
                <span class="label">Total Amount:</span>
                <span class="amount">₱{{ formatAmount(selectedPayment.amount) }}</span>
              </div>
            </div>

            <!-- Receipt Footer -->
            <div class="receipt-footer">
              <p><i class="fas fa-check-circle"></i> Thank you for your payment!</p>
              <small>This is a computer-generated receipt</small>
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="modal-actions">
            <button class="action-btn secondary" @click="closeReceiptModal">
              <i class="fas fa-times"></i> Close
            </button>
            <button class="action-btn primary" @click="printReceipt">
              <i class="fas fa-print"></i> Print Receipt
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, reactive } from 'vue'
import { useApiStore } from '@/stores/api'

export default {
  name: 'PaymentManagement',
  setup() {
    const apiStore = useApiStore()
    const loading = ref(false)
    const createLoading = ref(false)
    const payments = ref([])
    const availableBookings = ref([])
    const showCreateModal = ref(false)
    const showReceiptModal = ref(false)
    const selectedPayment = ref(null)
    
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
      payment_type: 'full',
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
        const response = await apiStore.get('/payments/needs-payment')
        const bookings = response.bookings || response.data?.bookings || []
        
        availableBookings.value = bookings.map(booking => ({
          id: booking.id,
          booking_reference: booking.booking_reference,
          customer_name: booking.customer_name || 'Unknown Customer',
          reason: booking.reason,
          outstanding_amount: parseFloat(booking.total_amount) - parseFloat(booking.paid_amount || 0)
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
        // Remove ₱ sign and convert to number before sending
        const paymentData = {
          ...newPayment,
          amount: parseFloat(newPayment.amount.replace('₱', ''))
        }
        await apiStore.post('/payments', paymentData)
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
        payment_type: 'full',
        payment_date: new Date().toISOString().split('T')[0],
        status: 'pending'
      })
    }

    const onBookingChange = () => {
      const selectedBooking = availableBookings.value.find(b => b.id === newPayment.booking_id)
      if (selectedBooking) {
        newPayment.amount = '₱' + selectedBooking.outstanding_amount.toFixed(2)
      } else {
        newPayment.amount = ''
      }
    }

    const viewPayment = (payment) => {
      selectedPayment.value = payment
      showReceiptModal.value = true
    }

    const closeReceiptModal = () => {
      showReceiptModal.value = false
      selectedPayment.value = null
    }

    const printReceipt = () => {
      window.print()
    }

    // Helper functions (defined before exportReport so they can be used)
    const formatAmount = (amount) => {
      return parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
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

    const exportReport = () => {
      try {
        // Prepare CSV data
        let csv = 'Booking Reference,Customer,Vehicle,Amount,Method,Type,Status,Date\n'
        
        payments.value.forEach(payment => {
          const row = [
            payment.booking_reference || 'N/A',
            getCustomerName(payment),
            getVehicleInfo(payment),
            formatAmount(payment.amount),
            payment.payment_method || 'N/A',
            payment.payment_type === 'downpayment' ? 'Down (30%)' : 'Full',
            getStatusText(payment.status),
            formatDate(payment.payment_date)
          ]
          csv += row.map(field => `"${field}"`).join(',') + '\n'
        })
        
        // Add summary at the end
        csv += '\n'
        csv += 'Summary\n'
        csv += `Total Revenue,₱${formatAmount(stats.total_revenue)}\n`
        csv += `Paid Invoices,${stats.paid_count}\n`
        csv += `Pending Payments,${stats.pending_count}\n`
        csv += `Overdue Payments,${stats.overdue_count}\n`
        
        // Create download link
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
        const link = document.createElement('a')
        const url = URL.createObjectURL(blob)
        
        const date = new Date().toISOString().split('T')[0]
        link.setAttribute('href', url)
        link.setAttribute('download', `payment-report-${date}.csv`)
        link.style.visibility = 'hidden'
        
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
      } catch (error) {
        console.error('Export error:', error)
        alert('Failed to export report: ' + error.message)
      }
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
      showReceiptModal,
      selectedPayment,
      newPayment,
      refreshData,
      createPayment,
      markAsPaid,
      deletePayment,
      closeModal,
      closeReceiptModal,
      viewPayment,
      printReceipt,
      exportReport,
      onBookingChange,
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

.readonly-input {
  background-color: #f8f9fa !important;
  cursor: not-allowed;
  color: #495057;
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

/* Payment Type Badge Styles */
.type-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.type-badge.full {
  background: #27ae60;
  color: white;
}

.type-badge.downpayment {
  background: #3498db;
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

/* Receipt Modal Styles */
.receipt-modal {
  max-width: 650px;
}

.receipt-container {
  background: white;
  padding: 20px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
}

.receipt-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.company-info h2 {
  margin: 0 0 8px 0;
  color: #667eea;
  font-size: 24px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.company-info p {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.receipt-number {
  text-align: right;
}

.receipt-number p {
  margin: 0 0 4px 0;
  color: #666;
  font-size: 13px;
}

.receipt-divider {
  height: 1px;
  background: linear-gradient(to right, #e0e0e0, transparent);
  margin: 20px 0;
}

.receipt-section {
  margin-bottom: 20px;
}

.receipt-section h4 {
  margin: 0 0 12px 0;
  color: #333;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.receipt-section h4 i {
  color: #667eea;
  font-size: 16px;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-item .label {
  font-size: 12px;
  color: #999;
  font-weight: 500;
}

.info-item .value {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.payment-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.detail-row .label {
  font-size: 13px;
  color: #666;
}

.detail-row .value {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.detail-row .value.method {
  text-transform: capitalize;
}

.receipt-total {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 4px;
  margin: 20px 0;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-row .label {
  font-size: 16px;
  color: #333;
  font-weight: 600;
}

.total-row .amount {
  font-size: 24px;
  color: #667eea;
  font-weight: 700;
}

.receipt-footer {
  text-align: center;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 2px dashed #e0e0e0;
}

.receipt-footer p {
  margin: 0 0 8px 0;
  color: #27ae60;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.receipt-footer small {
  color: #999;
  font-size: 11px;
}

/* Print Styles */
@media print {
  .modal-overlay {
    background: white !important;
    position: static !important;
  }

  .modal-content {
    box-shadow: none !important;
    max-width: 100% !important;
    border: none !important;
  }

  .modal-header,
  .modal-actions {
    display: none !important;
  }

  .receipt-container {
    border: none !important;
  }
}
</style>
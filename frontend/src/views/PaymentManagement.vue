<template>
  <div class="payment-management">
    <!-- Page Header -->
    <div class="page-header">
      <h1>Payment Management</h1>
      <div class="header-actions">
        <button class="action-btn">
          <i class="fas fa-download"></i> Export Report
        </button>
        <button class="action-btn secondary">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Payment Overview Cards -->
    <div class="stats-grid" style="margin-bottom: 40px;">
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
          <h3>$5,480</h3>
          <p>Total Revenue</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>15</h3>
          <p>Paid Invoices</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>3</h3>
          <p>Pending Payments</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
          <h3>1</h3>
          <p>Overdue</p>
        </div>
      </div>
    </div>

    <!-- Payments List -->
    <div class="data-card">
      <div class="card-header">
        <h2>Recent Payments</h2>
      </div>
      
      <div class="modern-table">
        <div class="table-row header-row">
          <span>Booking Ref</span>
          <span>Customer</span>
          <span>Amount</span>
          <span>Status</span>
          <span>Date</span>
          <span>Actions</span>
        </div>
        <div class="table-row">
          <span class="booking-ref">BK-001234</span>
          <span>John Smith</span>
          <span class="amount">$250.00</span>
          <span><span class="status-badge confirmed">Paid</span></span>
          <span>2025-10-20</span>
          <span class="actions">
            <button class="action-btn-sm">
              <i class="fas fa-eye"></i> View
            </button>
          </span>
        </div>
        <div class="table-row">
          <span class="booking-ref">BK-001235</span>
          <span>Sarah Johnson</span>
          <span class="amount">$150.00</span>
          <span><span class="status-badge pending">Pending</span></span>
          <span>2025-10-22</span>
          <span class="actions">
            <button class="action-btn-sm">
              <i class="fas fa-credit-card"></i> Process
            </button>
          </span>
        </div>
        <div class="table-row">
          <span class="booking-ref">BK-001236</span>
          <span>Mike Wilson</span>
          <span class="amount">$320.00</span>
          <span><span class="status-badge confirmed">Paid</span></span>
          <span>2025-10-19</span>
          <span class="actions">
            <button class="action-btn-sm">
              <i class="fas fa-eye"></i> View
            </button>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PaymentManagement'
}
</script>
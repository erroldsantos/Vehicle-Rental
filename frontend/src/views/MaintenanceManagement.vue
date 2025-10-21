<template>
  <div class="maintenance-management">
    <!-- Page Header -->
    <div class="page-header">
      <h1>Maintenance Management</h1>
      <div class="header-actions">
        <button class="action-btn">
          <i class="fas fa-plus"></i> Schedule Maintenance
        </button>
        <button class="action-btn secondary">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Maintenance Overview Cards -->
    <div class="stats-grid" style="margin-bottom: 40px;">
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-wrench"></i>
        </div>
        <div class="stat-content">
          <h3>3</h3>
          <p>Due This Week</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-content">
          <h3>5</h3>
          <p>Scheduled</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
          <h3>2</h3>
          <p>In Progress</p>
        </div>
      </div>
      <div class="mini-stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>12</h3>
          <p>Completed</p>
        </div>
      </div>
    </div>

    <!-- Maintenance List -->
    <div class="data-card">
      <div class="card-header">
        <h2>Maintenance Schedule</h2>
      </div>
      
      <div class="modern-table">
        <div class="table-row header-row">
          <span>Vehicle</span>
          <span>Type</span>
          <span>Due Date</span>
          <span>Status</span>
          <span>Priority</span>
          <span>Actions</span>
        </div>
        <div class="table-row">
          <span class="vehicle-info">Toyota Camry - ABC-1234</span>
          <span>Oil Change</span>
          <span>2025-10-25</span>
          <span><span class="status-badge pending">Pending</span></span>
          <span><span class="priority-badge high">High</span></span>
          <span class="actions">
            <button class="action-btn-sm">
              <i class="fas fa-calendar"></i> Schedule
            </button>
          </span>
        </div>
        <div class="table-row">
          <span class="vehicle-info">Honda CR-V - DEF-5678</span>
          <span>Inspection</span>
          <span>2025-10-30</span>
          <span><span class="status-badge pending">Pending</span></span>
          <span><span class="priority-badge medium">Medium</span></span>
          <span class="actions">
            <button class="action-btn-sm">
              <i class="fas fa-calendar"></i> Schedule
            </button>
          </span>
        </div>
        <div class="table-row">
          <span class="vehicle-info">Ford Transit - GHI-9012</span>
          <span>Brake Service</span>
          <span>2025-11-05</span>
          <span><span class="status-badge maintenance">In Progress</span></span>
          <span><span class="priority-badge high">High</span></span>
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
  name: 'MaintenanceManagement'
}
</script>
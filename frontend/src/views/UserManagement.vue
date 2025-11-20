<template>
  <div class="user-management">
    <div class="page-title">
      <h1>User Management</h1>
      <p>Manage system users and their permissions</p>
    </div>

    <!-- User Statistics -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <h3>Total Users</h3>
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-number">{{ stats.total_users || 0 }}</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <h3>Active Users</h3>
          <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-number">{{ stats.active_users || 0 }}</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <h3>Inactive Users</h3>
          <i class="fas fa-user-times"></i>
        </div>
        <div class="stat-number">{{ stats.inactive_users || 0 }}</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <h3>Admin Users</h3>
          <i class="fas fa-user-shield"></i>
        </div>
        <div class="stat-number">{{ stats.admin_users || 0 }}</div>
      </div>
    </div>

    <div class="table-container">
      <div class="table-header">
        <h3 class="table-title">Users</h3>
        <div class="table-actions">
          <div class="filters">
            <select v-model="filters.role" @change="loadUsers" class="form-control">
              <option value="">All Roles</option>
              <option value="admin">Admin</option>
              <option value="user">User</option>
            </select>
            <select v-model="filters.status" @change="loadUsers" class="form-control">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
            <input v-model="filters.search" @input="debounceSearch" placeholder="Search users..." class="form-control" />
          </div>
          <button class="btn btn-success" @click="showAddForm = true">
            <i class="fas fa-plus"></i> Add User
          </button>
        </div>
      </div>

      <!-- Add User Form -->
      <div v-if="showAddForm" class="form-container">
        <div class="form-header">
          <h4>Add New User</h4>
          <button @click="cancelAdd" class="btn-close">×</button>
        </div>
        <form @submit.prevent="addUser">
          <div class="form-row">
            <div class="form-group">
              <label>First Name:</label>
              <input v-model="newUser.first_name" class="form-control" type="text" required />
            </div>
            <div class="form-group">
              <label>Last Name:</label>
              <input v-model="newUser.last_name" class="form-control" type="text" required />
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Email:</label>
              <input v-model="newUser.email" class="form-control" type="email" required />
            </div>
            <div class="form-group">
              <label>Phone:</label>
              <input v-model="newUser.phone" class="form-control" type="tel" />
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Password:</label>
              <input v-model="newUser.password" class="form-control" type="password" required minlength="6" />
            </div>
            <div class="form-group">
              <label>Role:</label>
              <select v-model="newUser.role" class="form-control" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label>Status:</label>
            <select v-model="newUser.status" class="form-control" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-success" :disabled="!isFormValid">
              <i class="fas fa-save"></i> Add User
            </button>
            <button type="button" @click="cancelAdd" class="btn">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- Edit User Form -->
      <div v-if="showEditForm" class="form-container">
        <div class="form-header">
          <h4>Edit User</h4>
          <button @click="cancelEdit" class="btn-close">×</button>
        </div>
        <form @submit.prevent="updateUser">
          <div class="form-row">
            <div class="form-group">
              <label>First Name:</label>
              <input v-model="editingUser.first_name" class="form-control" type="text" required />
            </div>
            <div class="form-group">
              <label>Last Name:</label>
              <input v-model="editingUser.last_name" class="form-control" type="text" required />
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Email:</label>
              <input v-model="editingUser.email" class="form-control" type="email" required />
            </div>
            <div class="form-group">
              <label>Phone:</label>
              <input v-model="editingUser.phone" class="form-control" type="tel" />
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>New Password (leave blank to keep current):</label>
              <input v-model="editingUser.password" class="form-control" type="password" minlength="6" />
            </div>
            <div class="form-group">
              <label>Role:</label>
              <select v-model="editingUser.role" class="form-control" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label>Status:</label>
            <select v-model="editingUser.status" class="form-control" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save"></i> Update User
            </button>
            <button type="button" @click="cancelEdit" class="btn">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        </form>
      </div>

      <div v-if="loading" class="loading">
        <i class="fas fa-spinner fa-spin"></i> Loading users...
      </div>
      
      <table v-else class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="users.length === 0">
            <td colspan="7" class="no-data">No users found</td>
          </tr>
          <tr v-for="user in users" :key="user.id">
            <td>{{ user.id }}</td>
            <td>{{ user.first_name }} {{ user.last_name }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.phone || 'N/A' }}</td>
            <td>
              <span :class="['badge', 'role-' + user.role]">
                {{ formatRole(user.role) }}
              </span>
            </td>
            <td>
              <span :class="['badge', 'status-' + (user.email_verified == 1 ? 'active' : 'inactive')]">
                {{ user.email_verified == 1 ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="editUser(user)" class="btn btn-sm" title="Edit User">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="deleteUser(user.id)" class="btn btn-sm btn-danger" title="Delete User">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useApiStore } from '../stores/api'

export default {
  name: 'UserManagement',
  setup() {
    const apiStore = useApiStore()
    
    // Reactive data
    const loading = ref(false)
    const users = ref([])
    const stats = ref({})
    const showAddForm = ref(false)
    const showEditForm = ref(false)
    const searchTimeout = ref(null)
    
    const filters = ref({
      role: '',
      status: '',
      search: ''
    })
    
    const newUser = ref({
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      password: '',
      role: 'user',
      status: 'active'
    })
    
    const editingUser = ref({
      id: null,
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      password: '',
      role: 'user',
      status: 'active'
    })
    
    // Computed properties
    const isFormValid = computed(() => {
      return newUser.value.first_name && 
             newUser.value.last_name && 
             newUser.value.email && 
             newUser.value.password
    })
    
    // Methods
    const loadUsers = async () => {
      console.log('Loading users with filters:', filters.value)
      loading.value = true
      
      try {
        const queryParams = new URLSearchParams()
        
        if (filters.value.role) queryParams.append('role', filters.value.role)
        if (filters.value.status) queryParams.append('status', filters.value.status)
        if (filters.value.search) queryParams.append('search', filters.value.search)
        
        const queryString = queryParams.toString()
        const endpoint = `/users${queryString ? '?' + queryString : ''}`
        
        console.log('Fetching from endpoint:', endpoint)
        const response = await apiStore.get(endpoint)
        console.log('Users response:', response)
        
        // Handle both {users: [...]} and {data: {users: [...]}} formats
        users.value = response.users || response.data?.users || []
        stats.value = response.stats || response.data?.stats || {}
        
      } catch (error) {
        console.error('Failed to load users:', error)
        console.error('Error details:', error.response || error)
        
      } finally {
        loading.value = false
      }
    }
    
    const addUser = async () => {
      console.log('Adding user:', newUser.value)
      
      try {
        const userData = {
          first_name: newUser.value.first_name,
          last_name: newUser.value.last_name,
          email: newUser.value.email,
          phone: newUser.value.phone || null,
          password: newUser.value.password,
          role: newUser.value.role,
          status: newUser.value.status
        }
        
        console.log('Sending user data:', userData)
        const response = await apiStore.post('/users', userData)
        console.log('Add user response:', response)
        
        users.value.unshift(response)
        cancelAdd()
        
        await loadUsers() // Reload to get updated stats
        
        
      } catch (error) {
        console.error('Failed to add user:', error)
        console.error('Error details:', error.response || error)
        alert(`Failed to add user. Error: ${error.message}. Check console for details.`)
      }
    }
    
    const cancelAdd = () => {
      showAddForm.value = false
      newUser.value = {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        password: '',
        role: 'user',
        status: 'active'
      }
    }
    
    const editUser = (user) => {
      console.log('Edit user clicked:', user)
      
      editingUser.value = {
        id: user.id,
        first_name: user.first_name,
        last_name: user.last_name,
        email: user.email,
        phone: user.phone || '',
        password: '', // Don't pre-fill password
        role: user.role,
        status: user.status
      }
      
      showEditForm.value = true
      showAddForm.value = false
    }
    
    const updateUser = async () => {
      console.log('Updating user:', editingUser.value)
      
      try {
        const userData = {
          first_name: editingUser.value.first_name,
          last_name: editingUser.value.last_name,
          email: editingUser.value.email,
          phone: editingUser.value.phone || null,
          role: editingUser.value.role,
          status: editingUser.value.status
        }
        
        // Only include password if it's been changed
        if (editingUser.value.password) {
          userData.password = editingUser.value.password
        }
        
        console.log('Sending update data:', userData)
        const response = await apiStore.put(`/users/${editingUser.value.id}`, userData)
        console.log('Update response:', response)
        
        // Update the user in the list
        const index = users.value.findIndex(u => u.id === editingUser.value.id)
        if (index !== -1) {
          users.value[index] = response
        }
        
        cancelEdit()
        await loadUsers() // Reload to get updated stats
        
        
      } catch (error) {
        console.error('Failed to update user:', error)
        console.error('Error details:', error.response || error)
        alert(`Failed to update user. Error: ${error.message}. Check console for details.`)
      }
    }
    
    const cancelEdit = () => {
      showEditForm.value = false
      editingUser.value = {
        id: null,
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        password: '',
        role: 'user',
        status: 'active'
      }
    }
    
    const deleteUser = async (id) => {
      console.log('Delete user clicked for ID:', id)
      
      try {
        console.log('Deleting user with ID:', id)
        const response = await apiStore.delete(`/users/${id}`)
        console.log('Delete response received:', response)
        
        users.value = users.value.filter(u => u.id !== id)
        console.log('Updated users list:', users.value)
        
        await loadUsers() // Reload to get updated stats
        
      } catch (error) {
        console.error('DELETE ERROR:', error)
        console.error('Error message:', error.message)
        console.error('Error response:', error.response)
      }
    }
    
    const debounceSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
      
      searchTimeout.value = setTimeout(() => {
        loadUsers()
      }, 500)
    }
    
    const formatRole = (role) => {
      return role.charAt(0).toUpperCase() + role.slice(1)
    }
    
    const formatStatus = (status) => {
      return status.charAt(0).toUpperCase() + status.slice(1)
    }
    
    // Load data on mount
    onMounted(() => {
      loadUsers()
    })
    
    return {
      loading,
      users,
      stats,
      showAddForm,
      showEditForm,
      filters,
      newUser,
      editingUser,
      isFormValid,
      loadUsers,
      addUser,
      cancelAdd,
      editUser,
      updateUser,
      cancelEdit,
      deleteUser,
      debounceSearch,
      formatRole,
      formatStatus
    }
  }
}
</script>

<style scoped>
.user-management {
  padding: 2rem;
}

.page-title {
  margin-bottom: 2rem;
}

.page-title h1 {
  margin: 0;
  color: #2c3e50;
  font-size: 2rem;
}

.page-title p {
  margin: 0.5rem 0 0 0;
  color: #7f8c8d;
  font-size: 1.1rem;
}

/* Statistics Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  border: 1px solid #e9ecef;
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.stat-header h3 {
  margin: 0;
  color: #495057;
  font-size: 0.9rem;
  font-weight: 600;
  text-transform: uppercase;
}

.stat-header i {
  font-size: 1.2rem;
  color: #6c757d;
}

.stat-number {
  font-size: 2rem;
  font-weight: bold;
  color: #2c3e50;
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  background: #f8f9fa;
}

.table-title {
  margin: 0;
  color: #2c3e50;
  font-size: 1.25rem;
}

.table-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.filters {
  display: flex;
  gap: 0.5rem;
}

.filters select,
.filters input {
  min-width: 150px;
}

/* Form Styles */
.form-container {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  background: #f8f9fa;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.form-header h4 {
  margin: 0;
  color: #2c3e50;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6c757d;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  color: #dc3545;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #495057;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 1rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  outline: none;
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.form-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

/* Table Styles */
.table {
  width: 100%;
  margin: 0;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.table th {
  background: #f8f9fa;
  color: #495057;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.875rem;
  letter-spacing: 0.5px;
}

.table tbody tr:hover {
  background-color: #f8f9fa;
}

.no-data {
  text-align: center;
  color: #6c757d;
  font-style: italic;
  padding: 2rem;
}

/* Badge Styles */
.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  line-height: 1;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.25rem;
  text-transform: uppercase;
}

.badge.role-admin {
  color: #721c24;
  background-color: #f8d7da;
}

.badge.role-user {
  color: #0c5460;
  background-color: #d1ecf1;
}

.badge.status-active {
  color: #155724;
  background-color: #d4edda;
}

.badge.status-inactive {
  color: #856404;
  background-color: #fff3cd;
}

.badge.status-suspended {
  color: #721c24;
  background-color: #f8d7da;
}

/* Button Styles */
.btn {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  margin-bottom: 0;
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.42857143;
  text-align: center;
  white-space: nowrap;
  vertical-align: middle;
  cursor: pointer;
  border: 1px solid #ccc;
  border-radius: 4px;
  text-decoration: none;
  background-color: #fff;
  color: #333;
  transition: all 0.15s ease-in-out;
}

.btn:hover:not(:disabled) {
  background-color: #e6e6e6;
  border-color: #adadad;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-success {
  color: #fff;
  background-color: #28a745;
  border-color: #28a745;
}

.btn-success:hover:not(:disabled) {
  background-color: #218838;
  border-color: #1e7e34;
}

.btn-danger {
  color: #fff;
  background-color: #dc3545;
  border-color: #dc3545;
}

.btn-danger:hover:not(:disabled) {
  background-color: #c82333;
  border-color: #bd2130;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 0.2rem;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

/* Loading Styles */
.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #6c757d;
}

.loading i {
  margin-right: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .user-management {
    padding: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .table-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .table-actions {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .filters {
    flex-direction: column;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .table-container {
    overflow-x: auto;
  }
}
</style>
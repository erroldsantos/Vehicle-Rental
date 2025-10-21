<template>
  <div class="user-management">
    <div class="page-title">
      <h1>User Management</h1>
      <p>Manage system users</p>
    </div>

    <div class="table-container">
      <div class="table-header">
        <h3 class="table-title">Users</h3>
        <button class="btn btn-success" @click="showAddUserForm = !showAddUserForm">
          <i class="fas fa-plus"></i> Add User
        </button>
      </div>

      <!-- Add User Form -->
      <div v-show="showAddUserForm" class="form-container" style="margin: 20px;">
        <h4>Add New User</h4>
        <form @submit.prevent="addUser">
          <div class="form-group">
            <label>Name:</label>
            <input v-model="newUser.name" class="form-control" type="text" required />
          </div>
          <div class="form-group">
            <label>Email:</label>
            <input v-model="newUser.email" class="form-control" type="email" required />
          </div>
          <div class="form-group">
            <label>Role:</label>
            <select v-model="newUser.role" class="form-control">
              <option value="user">User</option>
              <option value="admin">Admin</option>
              <option value="moderator">Moderator</option>
            </select>
          </div>
          <button type="submit" class="btn btn-success" :disabled="!newUser.name || !newUser.email">
            Add User
          </button>
          <button type="button" @click="showAddUserForm = false" class="btn">
            Cancel
          </button>
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
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>{{ user.id }}</td>
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.role }}</td>
            <td>
              <span :class="['badge', user.status]">
                {{ user.status }}
              </span>
            </td>
            <td>
              <button @click="editUser(user)" class="btn" style="margin-right: 5px;">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="deleteUser(user.id)" class="btn btn-danger">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useApiStore } from '../stores/api'

export default {
  name: 'UserManagement',
  setup() {
    const apiStore = useApiStore()
    const loading = ref(false)
    const users = ref([])
    const showAddUserForm = ref(false)
    const newUser = ref({
      name: '',
      email: '',
      role: 'user'
    })

    const loadUsers = async () => {
      loading.value = true
      try {
        const response = await apiStore.get('/admin/users')
        users.value = response.users || response
      } catch (error) {
        console.error('Failed to load users:', error)
        // Fallback data
        users.value = [
          { id: 1, name: 'John Doe', email: 'john@example.com', role: 'admin', status: 'active' },
          { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'user', status: 'active' },
          { id: 3, name: 'Bob Johnson', email: 'bob@example.com', role: 'user', status: 'inactive' }
        ]
      } finally {
        loading.value = false
      }
    }

    const addUser = async () => {
      try {
        const response = await apiStore.post('/admin/users', {
          name: newUser.value.name,
          email: newUser.value.email,
          role: newUser.value.role
        })
        
        users.value.unshift(response)
        showAddUserForm.value = false
        newUser.value = { name: '', email: '', role: 'user' }
        // Show success message (you might want to emit an event here)
      } catch (error) {
        console.error('Failed to add user:', error)
        // Show error message
      }
    }

    const deleteUser = async (id) => {
      if (!confirm('Are you sure you want to delete this user?')) return
      
      try {
        await apiStore.delete(`/admin/users/${id}`)
        users.value = users.value.filter(user => user.id !== id)
        // Show success message
      } catch (error) {
        console.error('Failed to delete user:', error)
        // Show error message
      }
    }

    const editUser = (user) => {
      // Implement edit functionality
      console.log('Edit user:', user)
    }

    onMounted(() => {
      loadUsers()
    })

    return {
      loading,
      users,
      showAddUserForm,
      newUser,
      loadUsers,
      addUser,
      deleteUser,
      editUser
    }
  }
}
</script>
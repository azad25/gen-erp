<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Add Project Member</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Search Users -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name or email..."
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 pl-10"
            @input="searchUsers"
          />
          <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
        </div>
      </div>

      <!-- User Selection -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select User</label>
        <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md">
          <div v-if="searchLoading" class="p-4 text-center">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto"></div>
          </div>
          <div v-else-if="availableUsers.length === 0" class="p-4 text-center text-gray-500 text-sm">
            {{ searchQuery ? 'No users found' : 'Start typing to search users' }}
          </div>
          <div v-else class="divide-y divide-gray-200">
            <div
              v-for="user in availableUsers"
              :key="user.id"
              class="p-3 hover:bg-gray-50 cursor-pointer"
              :class="{ 'bg-indigo-50': selectedUser?.id === user.id }"
              @click="selectUser(user)"
            >
              <div class="flex items-center space-x-3">
                <img
                  class="h-8 w-8 rounded-full"
                  :src="user.avatar || '/default-avatar.png'"
                  :alt="user.name"
                />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                  <p class="text-xs text-gray-500">{{ user.email }}</p>
                </div>
                <div v-if="selectedUser?.id === user.id">
                  <CheckIcon class="h-5 w-5 text-indigo-600" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Role Selection -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
        <select
          v-model="selectedRole"
          required
          class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">Select role</option>
          <option value="manager">Manager</option>
          <option value="developer">Developer</option>
          <option value="designer">Designer</option>
          <option value="tester">Tester</option>
          <option value="viewer">Viewer</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">
          {{ getRoleDescription(selectedRole) }}
        </p>
      </div>

      <!-- Permissions -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
        <div class="space-y-2">
          <label class="flex items-center">
            <input
              v-model="permissions.can_edit_tasks"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Can edit tasks</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="permissions.can_create_tasks"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Can create tasks</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="permissions.can_delete_tasks"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Can delete tasks</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="permissions.can_manage_members"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Can manage members</span>
          </label>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
        >
          Cancel
        </button>
        <button
          @click="addMember"
          :disabled="loading || !selectedUser || !selectedRole"
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 rounded-md"
        >
          {{ loading ? 'Adding...' : 'Add Member' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import {
  XMarkIcon,
  MagnifyingGlassIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'

const props = defineProps({
  projectId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['close', 'saved'])

const { get, post, loading } = useApi()

// Reactive data
const searchQuery = ref('')
const searchLoading = ref(false)
const availableUsers = ref([])
const selectedUser = ref(null)
const selectedRole = ref('')
const permissions = reactive({
  can_edit_tasks: true,
  can_create_tasks: true,
  can_delete_tasks: false,
  can_manage_members: false
})

// Methods
const searchUsers = async () => {
  if (!searchQuery.value.trim()) {
    availableUsers.value = []
    return
  }
  
  searchLoading.value = true
  try {
    const data = await get('/api/v1/users/search', {
      query: searchQuery.value,
      exclude_project: props.projectId
    })
    availableUsers.value = data.data
  } catch (err) {
    console.error('Failed to search users:', err)
  } finally {
    searchLoading.value = false
  }
}

const selectUser = (user) => {
  selectedUser.value = selectedUser.value?.id === user.id ? null : user
}

const getRoleDescription = (role) => {
  const descriptions = {
    manager: 'Full project access, can manage all aspects',
    developer: 'Can create and edit tasks, view project details',
    designer: 'Can create and edit design tasks, view project',
    tester: 'Can create and edit test tasks, view project',
    viewer: 'Read-only access to project information'
  }
  return descriptions[role] || ''
}

const addMember = async () => {
  if (!selectedUser.value || !selectedRole.value) return
  
  try {
    const data = await post(`/api/v1/projects/${props.projectId}/members`, {
      user_id: selectedUser.value.id,
      role: selectedRole.value,
      permissions: permissions
    })
    
    emit('saved', data.data)
  } catch (err) {
    console.error('Failed to add member:', err)
  }
}

// Watch role changes to update default permissions
watch(() => selectedRole.value, (newRole) => {
  switch (newRole) {
    case 'manager':
      Object.assign(permissions, {
        can_edit_tasks: true,
        can_create_tasks: true,
        can_delete_tasks: true,
        can_manage_members: true
      })
      break
    case 'developer':
    case 'designer':
    case 'tester':
      Object.assign(permissions, {
        can_edit_tasks: true,
        can_create_tasks: true,
        can_delete_tasks: false,
        can_manage_members: false
      })
      break
    case 'viewer':
      Object.assign(permissions, {
        can_edit_tasks: false,
        can_create_tasks: false,
        can_delete_tasks: false,
        can_manage_members: false
      })
      break
  }
})
</script>
<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">
          Project Members ({{ members.length }})
        </h3>
        <div class="flex items-center space-x-2">
          <button
            @click="showAddMemberModal = true"
            class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-2 py-1 rounded-md"
          >
            Add Member
          </button>
          <button
            @click="collapsed = !collapsed"
            class="text-gray-400 hover:text-gray-600"
          >
            <ChevronUpIcon v-if="!collapsed" class="h-4 w-4" />
            <ChevronDownIcon v-else class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Members List -->
      <div v-if="members.length > 0" class="space-y-3">
        <div
          v-for="member in members"
          :key="member.id"
          class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
        >
          <div class="flex items-center space-x-3">
            <!-- Avatar -->
            <div class="relative">
              <img
                class="h-10 w-10 rounded-full"
                :src="member.user?.avatar || '/default-avatar.png'"
                :alt="member.user?.name"
              />
              <div
                v-if="member.is_online"
                class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 border-2 border-white rounded-full"
              ></div>
            </div>
            
            <!-- Member Info -->
            <div>
              <div class="flex items-center space-x-2">
                <p class="text-sm font-medium text-gray-900">
                  {{ member.user?.name }}
                </p>
                <span
                  v-if="member.is_owner"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                >
                  Owner
                </span>
                <span
                  v-else
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="getRoleClass(member.role)"
                >
                  {{ member.role }}
                </span>
              </div>
              <p class="text-xs text-gray-500">
                {{ member.user?.email }}
              </p>
              <div class="flex items-center space-x-4 mt-1 text-xs text-gray-500">
                <span>Joined {{ formatDate(member.joined_at) }}</span>
                <span v-if="member.last_activity">
                  Last active {{ formatDate(member.last_activity) }}
                </span>
              </div>
            </div>
          </div>
          
          <!-- Member Actions -->
          <div class="flex items-center space-x-2">
            <!-- Workload Indicator -->
            <div class="text-center">
              <div class="text-xs font-medium text-gray-900">
                {{ member.active_tasks_count || 0 }}
              </div>
              <div class="text-xs text-gray-500">tasks</div>
            </div>
            
            <!-- Actions Menu -->
            <div class="relative">
              <button
                @click="toggleMemberMenu(member.id)"
                class="text-gray-400 hover:text-gray-600"
              >
                <EllipsisVerticalIcon class="h-5 w-5" />
              </button>
              
              <!-- Dropdown Menu -->
              <div
                v-if="activeMemberMenu === member.id"
                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200"
              >
                <div class="py-1">
                  <button
                    @click="viewMemberDetails(member)"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    View Details
                  </button>
                  <button
                    @click="assignTasks(member)"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Assign Tasks
                  </button>
                  <button
                    v-if="!member.is_owner && canManageMembers"
                    @click="changeRole(member)"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Change Role
                  </button>
                  <button
                    v-if="!member.is_owner && canManageMembers"
                    @click="removeMember(member)"
                    class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50"
                  >
                    Remove Member
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <UsersIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No members yet</h3>
        <p class="mt-1 text-sm text-gray-500">Add team members to collaborate on this project.</p>
        <div class="mt-6">
          <button
            @click="showAddMemberModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <UsersIcon class="h-4 w-4 mr-2" />
            Add Members
          </button>
        </div>
      </div>

      <!-- Member Statistics -->
      <div v-if="members.length > 0" class="mt-6 pt-4 border-t border-gray-200">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Team Statistics</h4>
        <div class="grid grid-cols-3 gap-4">
          <div class="text-center">
            <div class="text-lg font-semibold text-gray-900">{{ totalActiveTasks }}</div>
            <div class="text-xs text-gray-500">Active Tasks</div>
          </div>
          <div class="text-center">
            <div class="text-lg font-semibold text-gray-900">{{ onlineMembers }}</div>
            <div class="text-xs text-gray-500">Online Now</div>
          </div>
          <div class="text-center">
            <div class="text-lg font-semibold text-gray-900">{{ averageWorkload }}</div>
            <div class="text-xs text-gray-500">Avg Tasks</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <AddMemberModal
      v-if="showAddMemberModal"
      :project-id="projectId"
      @close="showAddMemberModal = false"
      @saved="handleMemberAdded"
    />

    <!-- Member Details Modal -->
    <MemberDetailsModal
      v-if="selectedMember"
      :member="selectedMember"
      :project-id="projectId"
      @close="selectedMember = null"
      @updated="handleMemberUpdated"
    />

    <!-- Change Role Modal -->
    <ChangeRoleModal
      v-if="changingRoleMember"
      :member="changingRoleMember"
      :project-id="projectId"
      @close="changingRoleMember = null"
      @saved="handleRoleChanged"
    />

    <!-- Task Assignment Modal -->
    <TaskAssignmentModal
      v-if="assigningTasksMember"
      :member="assigningTasksMember"
      :project-id="projectId"
      @close="assigningTasksMember = null"
      @saved="handleTasksAssigned"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onClickOutside } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  UsersIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useAuth } from '@/Composables/useAuth'
import { useToast } from '@/Composables/useToast'
import AddMemberModal from './AddMemberModal.vue'
import MemberDetailsModal from './MemberDetailsModal.vue'
import ChangeRoleModal from './ChangeRoleModal.vue'
import TaskAssignmentModal from './TaskAssignmentModal.vue'

const props = defineProps({
  projectId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['member-added', 'member-removed', 'member-updated'])

const { get, delete: del } = useApi()
const { user: currentUser, hasPermission } = useAuth()
const { showSuccess, showError } = useToast()

// Reactive data
const collapsed = ref(false)
const members = ref([])
const showAddMemberModal = ref(false)
const selectedMember = ref(null)
const changingRoleMember = ref(null)
const assigningTasksMember = ref(null)
const activeMemberMenu = ref(null)

// Computed properties
const canManageMembers = computed(() => {
  return hasPermission('manage_project_members') || 
         members.value.find(m => m.user_id === currentUser.value?.id)?.is_owner
})

const totalActiveTasks = computed(() => {
  return members.value.reduce((total, member) => total + (member.active_tasks_count || 0), 0)
})

const onlineMembers = computed(() => {
  return members.value.filter(member => member.is_online).length
})

const averageWorkload = computed(() => {
  if (members.value.length === 0) return 0
  return Math.round(totalActiveTasks.value / members.value.length)
})

// Methods
const fetchMembers = async () => {
  try {
    const data = await get(`/api/v1/projects/${props.projectId}/members`)
    members.value = data.data
  } catch (err) {
    console.error('Failed to fetch project members:', err)
  }
}

const getRoleClass = (role) => {
  const classes = {
    'manager': 'bg-purple-100 text-purple-800',
    'developer': 'bg-blue-100 text-blue-800',
    'designer': 'bg-green-100 text-green-800',
    'tester': 'bg-yellow-100 text-yellow-800',
    'viewer': 'bg-gray-100 text-gray-800'
  }
  return classes[role] || 'bg-gray-100 text-gray-800'
}

const toggleMemberMenu = (memberId) => {
  activeMemberMenu.value = activeMemberMenu.value === memberId ? null : memberId
}

const viewMemberDetails = (member) => {
  selectedMember.value = member
  activeMemberMenu.value = null
}

const assignTasks = (member) => {
  assigningTasksMember.value = member
  activeMemberMenu.value = null
}

const changeRole = (member) => {
  changingRoleMember.value = member
  activeMemberMenu.value = null
}

const removeMember = async (member) => {
  if (!confirm(`Are you sure you want to remove ${member.user?.name} from this project?`)) {
    return
  }
  
  try {
    await del(`/api/v1/projects/${props.projectId}/members/${member.id}`)
    
    const index = members.value.findIndex(m => m.id === member.id)
    if (index > -1) {
      members.value.splice(index, 1)
    }
    
    showSuccess('Member removed successfully')
    emit('member-removed', member)
  } catch (err) {
    console.error('Failed to remove member:', err)
    showError('Failed to remove member')
  } finally {
    activeMemberMenu.value = null
  }
}

const handleMemberAdded = (member) => {
  members.value.push(member)
  showAddMemberModal.value = false
  showSuccess('Member added successfully')
  emit('member-added', member)
}

const handleMemberUpdated = (updatedMember) => {
  const index = members.value.findIndex(m => m.id === updatedMember.id)
  if (index > -1) {
    members.value[index] = updatedMember
  }
  selectedMember.value = null
  emit('member-updated', updatedMember)
}

const handleRoleChanged = (updatedMember) => {
  const index = members.value.findIndex(m => m.id === updatedMember.id)
  if (index > -1) {
    members.value[index] = updatedMember
  }
  changingRoleMember.value = null
  showSuccess('Member role updated successfully')
  emit('member-updated', updatedMember)
}

const handleTasksAssigned = () => {
  assigningTasksMember.value = null
  showSuccess('Tasks assigned successfully')
  // Refresh member data to update task counts
  fetchMembers()
}

const formatDate = (dateString) => {
  if (!dateString) return 'Never'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays} days ago`
  } else {
    return date.toLocaleDateString()
  }
}

// Close menu when clicking outside
onClickOutside(() => {
  activeMemberMenu.value = null
})

// Lifecycle
onMounted(() => {
  fetchMembers()
})
</script>
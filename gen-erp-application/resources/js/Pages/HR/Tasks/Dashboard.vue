<template>
    <AppLayout title="Employee Task Dashboard">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900">Task Dashboard</h1>
                    <p class="text-gray-600">Manage your assigned tasks and track progress</p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <ClipboardDocumentListIcon class="h-6 w-6 text-blue-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Tasks</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.total_tasks }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <ClockIcon class="h-6 w-6 text-yellow-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">In Progress</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.in_progress_tasks }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <CheckCircleIcon class="h-6 w-6 text-green-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Completed</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.completed_tasks }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <ClockIcon class="h-6 w-6 text-purple-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Hours Logged</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.total_hours }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Task List -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900">My Tasks</h2>
                            <div class="flex space-x-2">
                                <select v-model="filters.status" class="rounded-md border-gray-300 text-sm">
                                    <option value="">All Status</option>
                                    <option value="assigned">Assigned</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="on_hold">On Hold</option>
                                </select>
                                <select v-model="filters.priority" class="rounded-md border-gray-300 text-sm">
                                    <option value="">All Priority</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Task
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Project
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Priority
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Due Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Progress
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="task in filteredTasks" :key="task.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ task.title }}</div>
                                            <div class="text-sm text-gray-500">{{ task.description?.substring(0, 50) }}...</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ task.project?.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusClass(task.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ task.status.replace('_', ' ').toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getPriorityClass(task.priority)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ task.priority?.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ task.due_date ? formatDate(task.due_date) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div 
                                                    class="bg-blue-600 h-2 rounded-full" 
                                                    :style="{ width: task.progress + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-sm text-gray-600">{{ task.progress }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button 
                                            @click="startTask(task)"
                                            v-if="task.status === 'assigned'"
                                            class="text-blue-600 hover:text-blue-900 mr-3"
                                        >
                                            Start
                                        </button>
                                        <button 
                                            @click="logTime(task)"
                                            class="text-green-600 hover:text-green-900 mr-3"
                                        >
                                            Log Time
                                        </button>
                                        <button 
                                            @click="viewTask(task)"
                                            class="text-gray-600 hover:text-gray-900"
                                        >
                                            View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Logging Modal -->
        <TimeLogModal 
            :show="showTimeModal"
            :task="selectedTask"
            @close="showTimeModal = false"
            @saved="handleTimeLogged"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TimeLogModal from '@/Components/HR/TimeLogModal.vue'
import { 
    ClipboardDocumentListIcon, 
    ClockIcon, 
    CheckCircleIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tasks: Array,
    stats: Object
})

const filters = ref({
    status: '',
    priority: ''
})

const showTimeModal = ref(false)
const selectedTask = ref(null)

const filteredTasks = computed(() => {
    let filtered = props.tasks

    if (filters.value.status) {
        filtered = filtered.filter(task => task.status === filters.value.status)
    }

    if (filters.value.priority) {
        filtered = filtered.filter(task => task.priority === filters.value.priority)
    }

    return filtered
})

const getStatusClass = (status) => {
    const classes = {
        assigned: 'bg-gray-100 text-gray-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800',
        on_hold: 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityClass = (priority) => {
    const classes = {
        low: 'bg-blue-100 text-blue-800',
        medium: 'bg-yellow-100 text-yellow-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800'
    }
    return classes[priority] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const startTask = (task) => {
    router.put(`/api/v1/hr/employees/${task.employee_id}/tasks/${task.id}`, {
        status: 'in_progress',
        started_at: new Date().toISOString()
    })
}

const logTime = (task) => {
    selectedTask.value = task
    showTimeModal.value = true
}

const viewTask = (task) => {
    router.visit(`/hr/tasks/${task.id}`)
}

const handleTimeLogged = () => {
    showTimeModal.value = false
    router.reload()
}
</script>
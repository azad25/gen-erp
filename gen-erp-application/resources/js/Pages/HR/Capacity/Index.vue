<template>
    <AppLayout title="Capacity Planning">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900">Capacity Planning</h1>
                    <p class="text-gray-600">Monitor team capacity and workload distribution</p>
                </div>

                <!-- Capacity Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <UsersIcon class="h-6 w-6 text-blue-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Employees</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.total_employees }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <CheckCircleIcon class="h-6 w-6 text-green-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Available</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.available_employees }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <ExclamationTriangleIcon class="h-6 w-6 text-yellow-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Over Capacity</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.over_capacity }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <ClockIcon class="h-6 w-6 text-purple-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Avg Utilization</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.avg_utilization }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Capacity Chart -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Team Capacity Overview</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="employee in employees" :key="employee.id" class="flex items-center">
                                <div class="w-32 flex-shrink-0">
                                    <div class="flex items-center">
                                        <img 
                                            :src="employee.avatar || '/default-avatar.png'" 
                                            :alt="employee.name"
                                            class="h-8 w-8 rounded-full mr-3"
                                        />
                                        <span class="text-sm font-medium text-gray-900">{{ employee.name }}</span>
                                    </div>
                                </div>
                                <div class="flex-1 mx-4">
                                    <div class="flex items-center">
                                        <div class="flex-1 bg-gray-200 rounded-full h-4 relative">
                                            <!-- Available capacity -->
                                            <div 
                                                class="bg-green-500 h-4 rounded-full absolute"
                                                :style="{ width: Math.min(employee.utilization_percentage, 100) + '%' }"
                                            ></div>
                                            <!-- Over capacity -->
                                            <div 
                                                v-if="employee.utilization_percentage > 100"
                                                class="bg-red-500 h-4 rounded-full absolute"
                                                :style="{ 
                                                    left: '100%', 
                                                    width: Math.min(employee.utilization_percentage - 100, 50) + '%' 
                                                }"
                                            ></div>
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-900 w-16 text-right">
                                            {{ employee.utilization_percentage }}%
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>{{ employee.allocated_hours }}h allocated</span>
                                        <span>{{ employee.available_hours }}h capacity</span>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button 
                                        @click="viewEmployee(employee)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm"
                                    >
                                        View
                                    </button>
                                    <button 
                                        @click="adjustCapacity(employee)"
                                        class="text-gray-600 hover:text-gray-900 text-sm"
                                    >
                                        Adjust
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Capacity Planning Tools -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Workload Distribution -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Workload Distribution</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div v-for="project in projects" :key="project.id">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-900">{{ project.name }}</span>
                                        <span class="text-sm text-gray-600">{{ project.allocated_hours }}h</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="bg-blue-600 h-2 rounded-full"
                                            :style="{ width: (project.allocated_hours / stats.total_allocated_hours * 100) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Deadlines -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Upcoming Deadlines</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div v-for="deadline in upcomingDeadlines" :key="deadline.id" class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ deadline.title }}</div>
                                        <div class="text-sm text-gray-600">{{ deadline.project_name }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium" :class="getDeadlineClass(deadline.days_remaining)">
                                            {{ deadline.days_remaining }} days
                                        </div>
                                        <div class="text-xs text-gray-500">{{ formatDate(deadline.due_date) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Capacity Adjustment Modal -->
        <CapacityModal 
            :show="showCapacityModal"
            :employee="selectedEmployee"
            @close="showCapacityModal = false"
            @saved="handleCapacityUpdated"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CapacityModal from '@/Components/HR/CapacityModal.vue'
import { 
    UsersIcon, 
    CheckCircleIcon, 
    ExclamationTriangleIcon, 
    ClockIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    employees: Array,
    projects: Array,
    stats: Object,
    upcomingDeadlines: Array
})

const showCapacityModal = ref(false)
const selectedEmployee = ref(null)

const getDeadlineClass = (daysRemaining) => {
    if (daysRemaining <= 3) return 'text-red-600'
    if (daysRemaining <= 7) return 'text-yellow-600'
    return 'text-green-600'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const viewEmployee = (employee) => {
    router.visit(`/hr/employees/${employee.id}/capacity`)
}

const adjustCapacity = (employee) => {
    selectedEmployee.value = employee
    showCapacityModal.value = true
}

const handleCapacityUpdated = () => {
    showCapacityModal.value = false
    router.reload()
}
</script>
<template>
    <AppLayout title="Availability Calendar">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Availability Calendar</h1>
                        <p class="text-gray-600">Manage employee availability and time off</p>
                    </div>
                    <div class="flex space-x-3">
                        <button 
                            @click="showAddModal = true"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                        >
                            Add Availability
                        </button>
                        <select v-model="selectedEmployee" class="rounded-md border-gray-300">
                            <option value="">All Employees</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                {{ employee.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Calendar Navigation -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <button 
                                    @click="previousMonth"
                                    class="p-2 text-gray-400 hover:text-gray-600"
                                >
                                    <ChevronLeftIcon class="h-5 w-5" />
                                </button>
                                <h2 class="text-lg font-medium text-gray-900">
                                    {{ formatMonth(currentDate) }}
                                </h2>
                                <button 
                                    @click="nextMonth"
                                    class="p-2 text-gray-400 hover:text-gray-600"
                                >
                                    <ChevronRightIcon class="h-5 w-5" />
                                </button>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center space-x-2 text-sm">
                                    <div class="w-3 h-3 bg-green-500 rounded"></div>
                                    <span>Available</span>
                                </div>
                                <div class="flex items-center space-x-2 text-sm">
                                    <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                                    <span>Partial</span>
                                </div>
                                <div class="flex items-center space-x-2 text-sm">
                                    <div class="w-3 h-3 bg-red-500 rounded"></div>
                                    <span>Unavailable</span>
                                </div>
                                <div class="flex items-center space-x-2 text-sm">
                                    <div class="w-3 h-3 bg-blue-500 rounded"></div>
                                    <span>Leave</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="p-6">
                        <div class="grid grid-cols-7 gap-1 mb-4">
                            <div v-for="day in weekDays" :key="day" class="p-2 text-center text-sm font-medium text-gray-500">
                                {{ day }}
                            </div>
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            <div 
                                v-for="date in calendarDates" 
                                :key="date.date"
                                :class="[
                                    'min-h-[120px] border border-gray-200 p-2',
                                    date.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
                                    date.isToday ? 'ring-2 ring-indigo-500' : ''
                                ]"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span :class="[
                                        'text-sm font-medium',
                                        date.isCurrentMonth ? 'text-gray-900' : 'text-gray-400',
                                        date.isToday ? 'text-indigo-600' : ''
                                    ]">
                                        {{ date.day }}
                                    </span>
                                    <button 
                                        @click="addAvailability(date)"
                                        class="text-gray-400 hover:text-gray-600"
                                    >
                                        <PlusIcon class="h-4 w-4" />
                                    </button>
                                </div>
                                <div class="space-y-1">
                                    <div 
                                        v-for="availability in getDateAvailability(date.date)" 
                                        :key="availability.id"
                                        @click="editAvailability(availability)"
                                        :class="[
                                            'text-xs p-1 rounded cursor-pointer truncate',
                                            getAvailabilityClass(availability.status)
                                        ]"
                                        :title="`${availability.employee.name}: ${availability.status} (${availability.start_time} - ${availability.end_time})`"
                                    >
                                        {{ selectedEmployee ? availability.status : availability.employee.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Availability List -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Availability Entries</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Notes
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="availability in filteredAvailability" :key="availability.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img 
                                                :src="availability.employee.avatar || '/default-avatar.png'" 
                                                :alt="availability.employee.name"
                                                class="h-8 w-8 rounded-full mr-3"
                                            />
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ availability.employee.name }}</div>
                                                <div class="text-sm text-gray-500">{{ availability.employee.position }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(availability.date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ availability.start_time }} - {{ availability.end_time }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getAvailabilityClass(availability.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ availability.status.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ availability.notes }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button 
                                            @click="editAvailability(availability)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            @click="deleteAvailability(availability)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Availability Modal -->
        <AvailabilityModal 
            :show="showAddModal"
            :availability="selectedAvailability"
            :employees="employees"
            :date="selectedDate"
            @close="closeModal"
            @saved="handleAvailabilitySaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AvailabilityModal from '@/Components/HR/AvailabilityModal.vue'
import { 
    ChevronLeftIcon, 
    ChevronRightIcon, 
    PlusIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    availability: Array,
    employees: Array
})

const showAddModal = ref(false)
const selectedAvailability = ref(null)
const selectedEmployee = ref('')
const selectedDate = ref(null)
const currentDate = ref(new Date())

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const calendarDates = computed(() => {
    const year = currentDate.value.getFullYear()
    const month = currentDate.value.getMonth()
    
    const firstDay = new Date(year, month, 1)
    const lastDay = new Date(year, month + 1, 0)
    const startDate = new Date(firstDay)
    startDate.setDate(startDate.getDate() - firstDay.getDay())
    
    const dates = []
    const today = new Date()
    
    for (let i = 0; i < 42; i++) {
        const date = new Date(startDate)
        date.setDate(startDate.getDate() + i)
        
        dates.push({
            date: date.toISOString().split('T')[0],
            day: date.getDate(),
            isCurrentMonth: date.getMonth() === month,
            isToday: date.toDateString() === today.toDateString()
        })
    }
    
    return dates
})

const filteredAvailability = computed(() => {
    let filtered = props.availability
    
    if (selectedEmployee.value) {
        filtered = filtered.filter(a => a.employee_id === parseInt(selectedEmployee.value))
    }
    
    return filtered
})

const formatMonth = (date) => {
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const getDateAvailability = (date) => {
    return filteredAvailability.value.filter(a => a.date === date)
}

const getAvailabilityClass = (status) => {
    const classes = {
        available: 'bg-green-100 text-green-800',
        partial: 'bg-yellow-100 text-yellow-800',
        unavailable: 'bg-red-100 text-red-800',
        leave: 'bg-blue-100 text-blue-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const previousMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
}

const nextMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
}

const addAvailability = (date) => {
    selectedDate.value = date.date
    selectedAvailability.value = null
    showAddModal.value = true
}

const editAvailability = (availability) => {
    selectedAvailability.value = availability
    selectedDate.value = availability.date
    showAddModal.value = true
}

const deleteAvailability = (availability) => {
    if (confirm('Are you sure you want to delete this availability entry?')) {
        router.delete(`/api/v1/hr/availability/${availability.id}`)
    }
}

const closeModal = () => {
    showAddModal.value = false
    selectedAvailability.value = null
    selectedDate.value = null
}

const handleAvailabilitySaved = () => {
    closeModal()
    router.reload()
}
</script>
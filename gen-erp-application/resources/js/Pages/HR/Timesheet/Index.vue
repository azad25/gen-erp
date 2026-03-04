<template>
    <AppLayout title="Timesheet">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Timesheet</h1>
                        <p class="text-gray-600">Track and manage your time entries</p>
                    </div>
                    <button 
                        @click="showAddModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                    >
                        Add Time Entry
                    </button>
                </div>

                <!-- Week Navigation -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <button 
                                    @click="previousWeek"
                                    class="p-2 text-gray-400 hover:text-gray-600"
                                >
                                    <ChevronLeftIcon class="h-5 w-5" />
                                </button>
                                <h2 class="text-lg font-medium text-gray-900">
                                    {{ formatWeekRange(currentWeek) }}
                                </h2>
                                <button 
                                    @click="nextWeek"
                                    class="p-2 text-gray-400 hover:text-gray-600"
                                >
                                    <ChevronRightIcon class="h-5 w-5" />
                                </button>
                            </div>
                            <div class="text-sm text-gray-600">
                                Total: {{ weeklyTotal }} hours
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Timesheet Grid -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                                        Project / Task
                                    </th>
                                    <th 
                                        v-for="day in weekDays" 
                                        :key="day.date"
                                        class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        <div>{{ day.name }}</div>
                                        <div class="font-normal">{{ formatDate(day.date) }}</div>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="entry in groupedEntries" :key="entry.key">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ entry.project_name }}</div>
                                            <div class="text-sm text-gray-500">{{ entry.task_title || 'General' }}</div>
                                        </div>
                                    </td>
                                    <td 
                                        v-for="day in weekDays" 
                                        :key="day.date"
                                        class="px-3 py-4 text-center"
                                    >
                                        <div class="relative">
                                            <input 
                                                v-model="entry.days[day.date]"
                                                @blur="updateTimeEntry(entry, day.date)"
                                                type="number"
                                                step="0.25"
                                                min="0"
                                                max="24"
                                                class="w-16 text-center border-gray-300 rounded text-sm"
                                                placeholder="0"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-medium">
                                        {{ entry.total }}h
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">Daily Total</td>
                                    <td 
                                        v-for="day in weekDays" 
                                        :key="day.date"
                                        class="px-3 py-3 text-center text-sm font-medium text-gray-900"
                                    >
                                        {{ getDayTotal(day.date) }}h
                                    </td>
                                    <td class="px-6 py-3 text-center text-sm font-medium text-gray-900">
                                        {{ weeklyTotal }}h
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Time Entries List -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Recent Time Entries</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Project / Task
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="entry in timeEntries" :key="entry.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(entry.entry_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ entry.project?.name }}</div>
                                            <div class="text-sm text-gray-500">{{ entry.task?.title || 'General' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ entry.hours }}h</div>
                                        <div class="text-sm text-gray-500">
                                            {{ entry.start_time }} - {{ entry.end_time }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ entry.description }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ entry.entry_type }}
                                        </span>
                                        <span v-if="entry.is_billable" class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Billable
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button 
                                            @click="editEntry(entry)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            @click="deleteEntry(entry)"
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

        <!-- Add Time Entry Modal -->
        <TimeLogModal 
            :show="showAddModal"
            :task="null"
            @close="showAddModal = false"
            @saved="handleTimeLogged"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TimeLogModal from '@/Components/HR/TimeLogModal.vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    timeEntries: Array,
    currentWeek: String
})

const showAddModal = ref(false)
const currentWeekDate = ref(new Date(props.currentWeek))

const weekDays = computed(() => {
    const days = []
    const startOfWeek = new Date(currentWeekDate.value)
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay()) // Start from Sunday
    
    for (let i = 0; i < 7; i++) {
        const date = new Date(startOfWeek)
        date.setDate(startOfWeek.getDate() + i)
        days.push({
            name: date.toLocaleDateString('en-US', { weekday: 'short' }),
            date: date.toISOString().split('T')[0]
        })
    }
    return days
})

const groupedEntries = computed(() => {
    const groups = {}
    
    props.timeEntries.forEach(entry => {
        const key = `${entry.project_id}-${entry.task_id || 'general'}`
        if (!groups[key]) {
            groups[key] = {
                key,
                project_id: entry.project_id,
                task_id: entry.task_id,
                project_name: entry.project?.name || 'No Project',
                task_title: entry.task?.title,
                days: {},
                total: 0
            }
            
            // Initialize all days with 0
            weekDays.value.forEach(day => {
                groups[key].days[day.date] = 0
            })
        }
        
        if (groups[key].days.hasOwnProperty(entry.entry_date)) {
            groups[key].days[entry.entry_date] += parseFloat(entry.hours)
            groups[key].total += parseFloat(entry.hours)
        }
    })
    
    return Object.values(groups)
})

const weeklyTotal = computed(() => {
    return props.timeEntries.reduce((total, entry) => {
        return total + parseFloat(entry.hours)
    }, 0)
})

const formatWeekRange = (date) => {
    const start = new Date(date)
    start.setDate(start.getDate() - start.getDay())
    const end = new Date(start)
    end.setDate(start.getDate() + 6)
    
    return `${start.toLocaleDateString()} - ${end.toLocaleDateString()}`
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const getDayTotal = (date) => {
    return props.timeEntries
        .filter(entry => entry.entry_date === date)
        .reduce((total, entry) => total + parseFloat(entry.hours), 0)
}

const previousWeek = () => {
    currentWeekDate.value.setDate(currentWeekDate.value.getDate() - 7)
    router.visit(`/hr/timesheet?week=${currentWeekDate.value.toISOString().split('T')[0]}`)
}

const nextWeek = () => {
    currentWeekDate.value.setDate(currentWeekDate.value.getDate() + 7)
    router.visit(`/hr/timesheet?week=${currentWeekDate.value.toISOString().split('T')[0]}`)
}

const updateTimeEntry = (entry, date) => {
    const hours = parseFloat(entry.days[date]) || 0
    
    if (hours > 0) {
        router.post('/api/v1/hr/time-entries/bulk', {
            project_id: entry.project_id,
            task_id: entry.task_id,
            entry_date: date,
            hours: hours,
            description: `Time logged via timesheet`,
            entry_type: 'work',
            is_billable: true
        })
    }
}

const editEntry = (entry) => {
    // Implement edit functionality
    console.log('Edit entry:', entry)
}

const deleteEntry = (entry) => {
    if (confirm('Are you sure you want to delete this time entry?')) {
        router.delete(`/api/v1/hr/time-entries/${entry.id}`)
    }
}

const handleTimeLogged = () => {
    showAddModal.value = false
    router.reload()
}
</script>
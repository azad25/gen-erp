<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ availability ? 'Edit Availability' : 'Add Availability' }}
            </h3>
            
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <!-- Employee -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Employee</label>
                        <select 
                            v-model="form.employee_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="">Select an employee</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                {{ employee.name }} - {{ employee.position }}
                            </option>
                        </select>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <input 
                            v-model="form.date"
                            type="date" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Availability Status</label>
                        <select 
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="">Select status</option>
                            <option value="available">Available</option>
                            <option value="partial">Partially Available</option>
                            <option value="unavailable">Unavailable</option>
                            <option value="leave">On Leave</option>
                        </select>
                    </div>

                    <!-- Time Range (for partial availability) -->
                    <div v-if="form.status === 'partial' || form.status === 'available'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Time</label>
                            <input 
                                v-model="form.start_time"
                                type="time" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Time</label>
                            <input 
                                v-model="form.end_time"
                                type="time" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Reason (for unavailable/leave) -->
                    <div v-if="form.status === 'unavailable' || form.status === 'leave'">
                        <label class="block text-sm font-medium text-gray-700">Reason</label>
                        <select 
                            v-model="form.reason"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Select reason</option>
                            <optgroup label="Leave Types">
                                <option value="vacation">Vacation</option>
                                <option value="sick_leave">Sick Leave</option>
                                <option value="personal_leave">Personal Leave</option>
                                <option value="maternity_leave">Maternity Leave</option>
                                <option value="paternity_leave">Paternity Leave</option>
                                <option value="bereavement">Bereavement</option>
                            </optgroup>
                            <optgroup label="Other Reasons">
                                <option value="training">Training</option>
                                <option value="conference">Conference</option>
                                <option value="client_meeting">Client Meeting</option>
                                <option value="other">Other</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea 
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Additional notes or details..."
                        ></textarea>
                    </div>

                    <!-- Recurring -->
                    <div class="flex items-center">
                        <input 
                            v-model="form.is_recurring"
                            type="checkbox" 
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 block text-sm text-gray-900">
                            Recurring availability
                        </label>
                    </div>

                    <!-- Recurring Options -->
                    <div v-if="form.is_recurring" class="space-y-3 pl-6 border-l-2 border-gray-200">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Repeat</label>
                            <select 
                                v-model="form.recurring_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input 
                                v-model="form.recurring_end_date"
                                type="date" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button 
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        :disabled="processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ processing ? 'Saving...' : (availability ? 'Update' : 'Create') }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    show: Boolean,
    availability: Object,
    employees: Array,
    date: String
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)

const form = ref({
    employee_id: '',
    date: '',
    status: '',
    start_time: '09:00',
    end_time: '17:00',
    reason: '',
    notes: '',
    is_recurring: false,
    recurring_type: 'weekly',
    recurring_end_date: ''
})

watch(() => props.show, (show) => {
    if (show) {
        if (props.availability) {
            // Edit mode - load availability data
            form.value = {
                employee_id: props.availability.employee_id,
                date: props.availability.date,
                status: props.availability.status,
                start_time: props.availability.start_time || '09:00',
                end_time: props.availability.end_time || '17:00',
                reason: props.availability.reason || '',
                notes: props.availability.notes || '',
                is_recurring: props.availability.is_recurring || false,
                recurring_type: props.availability.recurring_type || 'weekly',
                recurring_end_date: props.availability.recurring_end_date || ''
            }
        } else {
            // Add mode - reset form
            form.value = {
                employee_id: '',
                date: props.date || new Date().toISOString().split('T')[0],
                status: '',
                start_time: '09:00',
                end_time: '17:00',
                reason: '',
                notes: '',
                is_recurring: false,
                recurring_type: 'weekly',
                recurring_end_date: ''
            }
        }
    }
})

const submit = () => {
    processing.value = true

    const url = props.availability 
        ? `/api/v1/hr/availability/${props.availability.id}`
        : '/api/v1/hr/availability'
    
    const method = props.availability ? 'put' : 'post'

    router[method](url, form.value, {
        onSuccess: () => {
            emit('saved')
        },
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>
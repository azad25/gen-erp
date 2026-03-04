<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Log Time</h3>
            
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <!-- Task Info -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900">{{ task?.title }}</h4>
                        <p class="text-sm text-gray-600">{{ task?.project?.name }}</p>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <input 
                            v-model="form.entry_date"
                            type="date" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                    </div>

                    <!-- Time Range -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Time</label>
                            <input 
                                v-model="form.start_time"
                                type="time" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="calculateHours"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Time</label>
                            <input 
                                v-model="form.end_time"
                                type="time" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="calculateHours"
                            />
                        </div>
                    </div>

                    <!-- Hours -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hours</label>
                        <input 
                            v-model="form.hours"
                            type="number" 
                            step="0.25"
                            min="0"
                            max="24"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea 
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="What did you work on?"
                        ></textarea>
                    </div>

                    <!-- Entry Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Entry Type</label>
                        <select 
                            v-model="form.entry_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="work">Work</option>
                            <option value="meeting">Meeting</option>
                            <option value="training">Training</option>
                            <option value="research">Research</option>
                            <option value="documentation">Documentation</option>
                        </select>
                    </div>

                    <!-- Billable -->
                    <div class="flex items-center">
                        <input 
                            v-model="form.is_billable"
                            type="checkbox" 
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 block text-sm text-gray-900">
                            Billable hours
                        </label>
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
                        {{ processing ? 'Saving...' : 'Log Time' }}
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
    task: Object
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)

const form = ref({
    entry_date: new Date().toISOString().split('T')[0],
    start_time: '09:00',
    end_time: '17:00',
    hours: 8,
    description: '',
    entry_type: 'work',
    is_billable: true
})

watch(() => props.show, (show) => {
    if (show && props.task) {
        // Reset form when modal opens
        form.value = {
            entry_date: new Date().toISOString().split('T')[0],
            start_time: '09:00',
            end_time: '17:00',
            hours: 8,
            description: '',
            entry_type: 'work',
            is_billable: true
        }
    }
})

const calculateHours = () => {
    if (form.value.start_time && form.value.end_time) {
        const start = new Date(`2000-01-01T${form.value.start_time}:00`)
        const end = new Date(`2000-01-01T${form.value.end_time}:00`)
        
        if (end > start) {
            const diffMs = end - start
            const diffHours = diffMs / (1000 * 60 * 60)
            form.value.hours = Math.round(diffHours * 4) / 4 // Round to nearest 0.25
        }
    }
}

const submit = () => {
    if (!props.task) return

    processing.value = true

    router.post(`/api/v1/hr/employees/${props.task.employee_id}/time-entries`, {
        task_id: props.task.task_id,
        project_id: props.task.project_id,
        ...form.value
    }, {
        onSuccess: () => {
            emit('saved')
        },
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>
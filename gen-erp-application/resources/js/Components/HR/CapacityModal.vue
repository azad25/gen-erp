<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Adjust Capacity</h3>
            
            <div v-if="employee" class="mb-4">
                <div class="flex items-center">
                    <img 
                        :src="employee.avatar || '/default-avatar.png'" 
                        :alt="employee.name"
                        class="h-10 w-10 rounded-full mr-3"
                    />
                    <div>
                        <h4 class="font-medium text-gray-900">{{ employee.name }}</h4>
                        <p class="text-sm text-gray-600">{{ employee.position }}</p>
                    </div>
                </div>
            </div>
            
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <!-- Available Hours -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Available Hours per Week</label>
                        <input 
                            v-model="form.available_hours"
                            type="number" 
                            step="0.5"
                            min="0"
                            max="168"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        <p class="mt-1 text-sm text-gray-500">Standard full-time is 40 hours per week</p>
                    </div>

                    <!-- Skills -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Skills</label>
                        <div class="mt-2 space-y-2">
                            <div v-for="skill in availableSkills" :key="skill.id" class="flex items-center">
                                <input 
                                    :id="`skill-${skill.id}`"
                                    v-model="form.skills"
                                    :value="skill.id"
                                    type="checkbox" 
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <label :for="`skill-${skill.id}`" class="ml-2 block text-sm text-gray-900">
                                    {{ skill.name }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Availability -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Availability Status</label>
                        <select 
                            v-model="form.is_available_for_projects"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="true">Available for new projects</option>
                            <option :value="false">Not available for new projects</option>
                        </select>
                    </div>

                    <!-- Overtime Allowed -->
                    <div class="flex items-center">
                        <input 
                            v-model="form.overtime_allowed"
                            type="checkbox" 
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 block text-sm text-gray-900">
                            Allow overtime assignments
                        </label>
                    </div>

                    <!-- Max Overtime Hours -->
                    <div v-if="form.overtime_allowed">
                        <label class="block text-sm font-medium text-gray-700">Max Overtime Hours per Week</label>
                        <input 
                            v-model="form.max_overtime_hours"
                            type="number" 
                            step="0.5"
                            min="0"
                            max="20"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea 
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Any additional notes about capacity or availability..."
                        ></textarea>
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
                        {{ processing ? 'Saving...' : 'Update Capacity' }}
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
    employee: Object
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)

const availableSkills = ref([
    { id: 1, name: 'Frontend Development' },
    { id: 2, name: 'Backend Development' },
    { id: 3, name: 'Database Design' },
    { id: 4, name: 'UI/UX Design' },
    { id: 5, name: 'Project Management' },
    { id: 6, name: 'Quality Assurance' },
    { id: 7, name: 'DevOps' },
    { id: 8, name: 'Mobile Development' }
])

const form = ref({
    available_hours: 40,
    skills: [],
    is_available_for_projects: true,
    overtime_allowed: false,
    max_overtime_hours: 0,
    notes: ''
})

watch(() => props.show, (show) => {
    if (show && props.employee) {
        // Load employee data into form
        form.value = {
            available_hours: props.employee.available_hours || 40,
            skills: props.employee.skills?.map(s => s.id) || [],
            is_available_for_projects: props.employee.is_available_for_projects ?? true,
            overtime_allowed: props.employee.overtime_allowed || false,
            max_overtime_hours: props.employee.max_overtime_hours || 0,
            notes: props.employee.capacity_notes || ''
        }
    }
})

const submit = () => {
    if (!props.employee) return

    processing.value = true

    router.put(`/api/v1/hr/employees/${props.employee.id}/capacity`, form.value, {
        onSuccess: () => {
            emit('saved')
        },
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>
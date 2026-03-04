<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ skill ? 'Edit Skill' : 'Add New Skill' }}
            </h3>
            
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Skill Name</label>
                        <input 
                            v-model="form.name"
                            type="text" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                            placeholder="e.g., JavaScript, Project Management"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea 
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Brief description of the skill..."
                        ></textarea>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select 
                            v-model="form.category"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="">Select a category</option>
                            <option value="technical">Technical</option>
                            <option value="soft">Soft Skills</option>
                            <option value="management">Management</option>
                            <option value="design">Design</option>
                            <option value="language">Language</option>
                            <option value="certification">Certification</option>
                        </select>
                    </div>

                    <!-- Is Required -->
                    <div class="flex items-center">
                        <input 
                            v-model="form.is_required"
                            type="checkbox" 
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 block text-sm text-gray-900">
                            Required skill for all employees
                        </label>
                    </div>

                    <!-- Is Certifiable -->
                    <div class="flex items-center">
                        <input 
                            v-model="form.is_certifiable"
                            type="checkbox" 
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 block text-sm text-gray-900">
                            Skill can be certified
                        </label>
                    </div>

                    <!-- Certification Details -->
                    <div v-if="form.is_certifiable" class="space-y-3 pl-6 border-l-2 border-gray-200">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Certification Body</label>
                            <input 
                                v-model="form.certification_body"
                                type="text" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="e.g., AWS, Google, Microsoft"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Certification URL</label>
                            <input 
                                v-model="form.certification_url"
                                type="url" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="https://..."
                            />
                        </div>
                    </div>

                    <!-- Tags -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tags</label>
                        <input 
                            v-model="form.tags"
                            type="text" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Comma-separated tags (e.g., frontend, react, javascript)"
                        />
                        <p class="mt-1 text-sm text-gray-500">Separate multiple tags with commas</p>
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
                        {{ processing ? 'Saving...' : (skill ? 'Update Skill' : 'Create Skill') }}
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
    skill: Object
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)

const form = ref({
    name: '',
    description: '',
    category: '',
    is_required: false,
    is_certifiable: false,
    certification_body: '',
    certification_url: '',
    tags: ''
})

watch(() => props.show, (show) => {
    if (show) {
        if (props.skill) {
            // Edit mode - load skill data
            form.value = {
                name: props.skill.name || '',
                description: props.skill.description || '',
                category: props.skill.category || '',
                is_required: props.skill.is_required || false,
                is_certifiable: props.skill.is_certifiable || false,
                certification_body: props.skill.certification_body || '',
                certification_url: props.skill.certification_url || '',
                tags: props.skill.tags?.join(', ') || ''
            }
        } else {
            // Add mode - reset form
            form.value = {
                name: '',
                description: '',
                category: '',
                is_required: false,
                is_certifiable: false,
                certification_body: '',
                certification_url: '',
                tags: ''
            }
        }
    }
})

const submit = () => {
    processing.value = true

    const data = {
        ...form.value,
        tags: form.value.tags ? form.value.tags.split(',').map(tag => tag.trim()) : []
    }

    const url = props.skill 
        ? `/api/v1/hr/skills/${props.skill.id}`
        : '/api/v1/hr/skills'
    
    const method = props.skill ? 'put' : 'post'

    router[method](url, data, {
        onSuccess: () => {
            emit('saved')
        },
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>
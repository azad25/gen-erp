<template>
    <Modal :show="show" @close="$emit('close')" max-width="4xl">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ review ? 'Edit Performance Review' : 'New Performance Review' }}
            </h3>
            
            <form @submit.prevent="submit">
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Review Period</label>
                            <select 
                                v-model="form.review_period"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="">Select period</option>
                                <option value="Q1 2026">Q1 2026</option>
                                <option value="Q2 2026">Q2 2026</option>
                                <option value="Q3 2026">Q3 2026</option>
                                <option value="Q4 2026">Q4 2026</option>
                                <option value="Annual 2026">Annual 2026</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input 
                                v-model="form.due_date"
                                type="date" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select 
                                v-model="form.status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="draft">Draft</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="approved">Approved</option>
                            </select>
                        </div>
                    </div>

                    <!-- Performance Ratings -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-4">Performance Ratings</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="category in ratingCategories" :key="category.key">
                                <label class="block text-sm font-medium text-gray-700">{{ category.label }}</label>
                                <div class="mt-1 flex items-center space-x-2">
                                    <div class="flex">
                                        <button
                                            v-for="rating in 5"
                                            :key="rating"
                                            type="button"
                                            @click="setRating(category.key, rating)"
                                            :class="[
                                                'h-6 w-6',
                                                rating <= (form.ratings[category.key] || 0) ? 'text-yellow-400' : 'text-gray-300'
                                            ]"
                                        >
                                            <StarIcon />
                                        </button>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ form.ratings[category.key] || 0 }}/5</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Goals and Achievements -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Goals Achieved</label>
                        <textarea 
                            v-model="form.goals_achieved"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="List the goals achieved during this review period..."
                        ></textarea>
                    </div>

                    <!-- Areas of Improvement -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Areas for Improvement</label>
                        <textarea 
                            v-model="form.areas_for_improvement"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Identify areas where the employee can improve..."
                        ></textarea>
                    </div>

                    <!-- Strengths -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Key Strengths</label>
                        <textarea 
                            v-model="form.strengths"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Highlight the employee's key strengths..."
                        ></textarea>
                    </div>

                    <!-- Development Plan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Development Plan</label>
                        <textarea 
                            v-model="form.development_plan"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Outline the development plan for the next review period..."
                        ></textarea>
                    </div>

                    <!-- Manager Comments -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Manager Comments</label>
                        <textarea 
                            v-model="form.manager_comments"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Additional comments from the manager..."
                        ></textarea>
                    </div>

                    <!-- Employee Self Assessment -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Employee Self Assessment</label>
                        <textarea 
                            v-model="form.employee_comments"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Employee's self assessment and comments..."
                        ></textarea>
                    </div>

                    <!-- Overall Rating -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Overall Rating</label>
                        <div class="mt-1 flex items-center space-x-2">
                            <div class="flex">
                                <button
                                    v-for="rating in 5"
                                    :key="rating"
                                    type="button"
                                    @click="form.overall_rating = rating"
                                    :class="[
                                        'h-8 w-8',
                                        rating <= (form.overall_rating || 0) ? 'text-yellow-400' : 'text-gray-300'
                                    ]"
                                >
                                    <StarIcon />
                                </button>
                            </div>
                            <span class="text-lg font-medium text-gray-900">{{ form.overall_rating || 0 }}/5</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-3">
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
                        {{ processing ? 'Saving...' : (review ? 'Update Review' : 'Create Review') }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import { StarIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    show: Boolean,
    review: Object,
    employees: Array
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)

const ratingCategories = [
    { key: 'job_knowledge', label: 'Job Knowledge' },
    { key: 'quality_of_work', label: 'Quality of Work' },
    { key: 'productivity', label: 'Productivity' },
    { key: 'communication', label: 'Communication' },
    { key: 'teamwork', label: 'Teamwork' },
    { key: 'leadership', label: 'Leadership' },
    { key: 'problem_solving', label: 'Problem Solving' },
    { key: 'initiative', label: 'Initiative' }
]

const form = ref({
    employee_id: '',
    review_period: '',
    due_date: '',
    status: 'draft',
    ratings: {},
    goals_achieved: '',
    areas_for_improvement: '',
    strengths: '',
    development_plan: '',
    manager_comments: '',
    employee_comments: '',
    overall_rating: 0
})

watch(() => props.show, (show) => {
    if (show) {
        if (props.review) {
            // Edit mode - load review data
            form.value = {
                employee_id: props.review.employee_id,
                review_period: props.review.review_period,
                due_date: props.review.due_date,
                status: props.review.status,
                ratings: props.review.ratings || {},
                goals_achieved: props.review.goals_achieved || '',
                areas_for_improvement: props.review.areas_for_improvement || '',
                strengths: props.review.strengths || '',
                development_plan: props.review.development_plan || '',
                manager_comments: props.review.manager_comments || '',
                employee_comments: props.review.employee_comments || '',
                overall_rating: props.review.overall_rating || 0
            }
        } else {
            // Add mode - reset form
            form.value = {
                employee_id: '',
                review_period: '',
                due_date: '',
                status: 'draft',
                ratings: {},
                goals_achieved: '',
                areas_for_improvement: '',
                strengths: '',
                development_plan: '',
                manager_comments: '',
                employee_comments: '',
                overall_rating: 0
            }
        }
    }
})

const setRating = (category, rating) => {
    form.value.ratings[category] = rating
    
    // Calculate overall rating as average of all ratings
    const ratings = Object.values(form.value.ratings).filter(r => r > 0)
    if (ratings.length > 0) {
        form.value.overall_rating = Math.round(ratings.reduce((a, b) => a + b, 0) / ratings.length)
    }
}

const submit = () => {
    processing.value = true

    const url = props.review 
        ? `/api/v1/hr/performance-reviews/${props.review.id}`
        : '/api/v1/hr/performance-reviews'
    
    const method = props.review ? 'put' : 'post'

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
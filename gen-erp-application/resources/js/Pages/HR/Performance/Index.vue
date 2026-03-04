<template>
    <AppLayout title="Performance Reviews">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Performance Reviews</h1>
                        <p class="text-gray-600">Manage employee performance evaluations</p>
                    </div>
                    <button 
                        @click="showAddModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                    >
                        New Review
                    </button>
                </div>

                <!-- Performance Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <DocumentTextIcon class="h-6 w-6 text-blue-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Reviews</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.total_reviews }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <ClockIcon class="h-6 w-6 text-yellow-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.pending_reviews }}</p>
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
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.completed_reviews }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <StarIcon class="h-6 w-6 text-purple-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Avg Rating</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.avg_rating }}/5</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900">Performance Reviews</h2>
                            <div class="flex space-x-2">
                                <select v-model="filters.status" class="rounded-md border-gray-300 text-sm">
                                    <option value="">All Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="approved">Approved</option>
                                </select>
                                <select v-model="filters.period" class="rounded-md border-gray-300 text-sm">
                                    <option value="">All Periods</option>
                                    <option value="Q1 2026">Q1 2026</option>
                                    <option value="Q2 2026">Q2 2026</option>
                                    <option value="Q3 2026">Q3 2026</option>
                                    <option value="Q4 2026">Q4 2026</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Review Period
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reviewer
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Overall Rating
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Due Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="review in filteredReviews" :key="review.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img 
                                                :src="review.employee.avatar || '/default-avatar.png'" 
                                                :alt="review.employee.name"
                                                class="h-8 w-8 rounded-full mr-3"
                                            />
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ review.employee.name }}</div>
                                                <div class="text-sm text-gray-500">{{ review.employee.position }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ review.review_period }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ review.reviewer?.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusClass(review.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ review.status.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex">
                                                <StarIcon 
                                                    v-for="i in 5" 
                                                    :key="i"
                                                    :class="[
                                                        'h-4 w-4',
                                                        i <= (review.overall_rating || 0) ? 'text-yellow-400' : 'text-gray-300'
                                                    ]"
                                                />
                                            </div>
                                            <span class="ml-2 text-sm text-gray-600">{{ review.overall_rating || 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ review.due_date ? formatDate(review.due_date) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button 
                                            @click="viewReview(review)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            View
                                        </button>
                                        <button 
                                            @click="editReview(review)"
                                            v-if="review.status !== 'approved'"
                                            class="text-gray-600 hover:text-gray-900 mr-3"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            @click="deleteReview(review)"
                                            v-if="review.status === 'draft'"
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

        <!-- Add/Edit Review Modal -->
        <PerformanceReviewModal 
            :show="showAddModal"
            :review="selectedReview"
            :employees="employees"
            @close="closeModal"
            @saved="handleReviewSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PerformanceReviewModal from '@/Components/HR/PerformanceReviewModal.vue'
import { 
    DocumentTextIcon, 
    ClockIcon, 
    CheckCircleIcon, 
    StarIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    reviews: Array,
    employees: Array,
    stats: Object
})

const showAddModal = ref(false)
const selectedReview = ref(null)

const filters = ref({
    status: '',
    period: ''
})

const filteredReviews = computed(() => {
    let filtered = props.reviews

    if (filters.value.status) {
        filtered = filtered.filter(review => review.status === filters.value.status)
    }

    if (filters.value.period) {
        filtered = filtered.filter(review => review.review_period === filters.value.period)
    }

    return filtered
})

const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-blue-100 text-blue-800',
        approved: 'bg-green-100 text-green-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const viewReview = (review) => {
    router.visit(`/hr/performance/${review.id}`)
}

const editReview = (review) => {
    selectedReview.value = review
    showAddModal.value = true
}

const deleteReview = (review) => {
    if (confirm('Are you sure you want to delete this performance review?')) {
        router.delete(`/api/v1/hr/performance-reviews/${review.id}`)
    }
}

const closeModal = () => {
    showAddModal.value = false
    selectedReview.value = null
}

const handleReviewSaved = () => {
    closeModal()
    router.reload()
}
</script>
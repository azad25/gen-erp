<template>
  <AppLayout title="Performance Review Details">
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Performance Review: {{ review?.employee?.name }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">{{ review?.review_period }}</p>
        </div>
        <div class="flex space-x-3">
          <button v-if="canEdit" @click="showEditModal = true"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Edit Review
          </button>
          <Link :href="route('hr.performance.index')" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Back to Reviews
          </Link>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-500">Loading performance review...</p>
        </div>

        <div v-else-if="review" class="space-y-6">
          <!-- Review Overview -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
              <div class="lg:col-span-1">
                <div class="text-center">
                  <div class="w-20 h-20 bg-gray-300 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl font-bold text-gray-600">
                    {{ review.employee.name.charAt(0) }}
                  </div>
                  <h3 class="text-lg font-medium text-gray-900">{{ review.employee.name }}</h3>
                  <p class="text-sm text-gray-500">{{ review.employee.position || 'Employee' }}</p>
                  <p class="text-sm text-gray-500">{{ review.employee.department?.name }}</p>
                </div>
              </div>
              
              <div class="lg:col-span-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600">{{ review.overall_rating }}/5</div>
                    <div class="text-sm text-gray-600">Overall Rating</div>
                    <div class="flex justify-center mt-2">
                      <div class="flex space-x-1">
                        <svg v-for="i in 5" :key="i" 
                             :class="i <= review.overall_rating ? 'text-yellow-400' : 'text-gray-300'"
                             class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                      </div>
                    </div>
                  </div>
                  
                  <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 capitalize">{{ review.status }}</div>
                    <div class="text-sm text-gray-600">Review Status</div>
                    <div class="text-xs text-gray-500 mt-1">{{ formatDate(review.review_date) }}</div>
                  </div>
                  
                  <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">{{ review.goals_met || 0 }}/{{ review.total_goals || 0 }}</div>
                    <div class="text-sm text-gray-600">Goals Achieved</div>
                    <div class="text-xs text-gray-500 mt-1">{{ Math.round((review.goals_met / review.total_goals) * 100) || 0 }}% completion</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Performance Metrics -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">Performance Metrics</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ review.technical_skills_rating }}/5</div>
                <div class="text-sm text-gray-600">Technical Skills</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                  <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${(review.technical_skills_rating / 5) * 100}%`"></div>
                </div>
              </div>
              
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ review.communication_rating }}/5</div>
                <div class="text-sm text-gray-600">Communication</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                  <div class="bg-green-600 h-2 rounded-full" :style="`width: ${(review.communication_rating / 5) * 100}%`"></div>
                </div>
              </div>
              
              <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ review.teamwork_rating }}/5</div>
                <div class="text-sm text-gray-600">Teamwork</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                  <div class="bg-purple-600 h-2 rounded-full" :style="`width: ${(review.teamwork_rating / 5) * 100}%`"></div>
                </div>
              </div>
              
              <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ review.leadership_rating }}/5</div>
                <div class="text-sm text-gray-600">Leadership</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                  <div class="bg-orange-600 h-2 rounded-full" :style="`width: ${(review.leadership_rating / 5) * 100}%`"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Goals and Achievements -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Goals -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Goals & Objectives</h3>
              <div class="space-y-4">
                <div v-for="goal in review.goals" :key="goal.id" 
                     class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                  <div class="flex-shrink-0 mt-1">
                    <svg v-if="goal.achieved" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-else class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                  <div class="flex-1">
                    <h4 class="text-sm font-medium text-gray-900">{{ goal.title }}</h4>
                    <p class="text-sm text-gray-600 mt-1">{{ goal.description }}</p>
                    <div class="flex items-center justify-between mt-2">
                      <span :class="goal.achieved ? 'text-green-600' : 'text-gray-500'" 
                            class="text-xs font-medium">
                        {{ goal.achieved ? 'Achieved' : 'In Progress' }}
                      </span>
                      <span class="text-xs text-gray-500">{{ goal.progress || 0 }}%</span>
                    </div>
                  </div>
                </div>
                
                <div v-if="!review.goals || review.goals.length === 0" 
                     class="text-center py-8 text-gray-500">
                  No goals set for this review period.
                </div>
              </div>
            </div>

            <!-- Achievements -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Key Achievements</h3>
              <div class="space-y-3">
                <div v-for="achievement in review.achievements" :key="achievement.id" 
                     class="p-3 bg-green-50 rounded-lg border-l-4 border-green-400">
                  <h4 class="text-sm font-medium text-gray-900">{{ achievement.title }}</h4>
                  <p class="text-sm text-gray-600 mt-1">{{ achievement.description }}</p>
                  <div class="text-xs text-gray-500 mt-2">{{ formatDate(achievement.date) }}</div>
                </div>
                
                <div v-if="!review.achievements || review.achievements.length === 0" 
                     class="text-center py-8 text-gray-500">
                  No achievements recorded for this review period.
                </div>
              </div>
            </div>
          </div>

          <!-- Feedback Sections -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Strengths -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Strengths</h3>
              <div v-if="review.strengths" class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">{{ review.strengths }}</p>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                No strengths feedback provided.
              </div>
            </div>

            <!-- Areas for Improvement -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Areas for Improvement</h3>
              <div v-if="review.areas_for_improvement" class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">{{ review.areas_for_improvement }}</p>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                No improvement areas identified.
              </div>
            </div>
          </div>

          <!-- Development Plan -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Development Plan</h3>
            <div v-if="review.development_plan" class="prose max-w-none">
              <p class="text-gray-700 whitespace-pre-wrap">{{ review.development_plan }}</p>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              No development plan provided.
            </div>
          </div>

          <!-- Manager Comments -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Manager Comments</h3>
            <div v-if="review.manager_comments" class="prose max-w-none">
              <p class="text-gray-700 whitespace-pre-wrap">{{ review.manager_comments }}</p>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              No manager comments provided.
            </div>
            
            <div v-if="review.reviewer" class="mt-4 pt-4 border-t border-gray-200">
              <div class="flex items-center space-x-2 text-sm text-gray-500">
                <span>Reviewed by:</span>
                <span class="font-medium text-gray-900">{{ review.reviewer.name }}</span>
                <span>•</span>
                <span>{{ formatDate(review.review_date) }}</span>
              </div>
            </div>
          </div>

          <!-- Employee Self-Assessment -->
          <div v-if="review.self_assessment" class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Self-Assessment</h3>
            <div class="prose max-w-none">
              <p class="text-gray-700 whitespace-pre-wrap">{{ review.self_assessment }}</p>
            </div>
          </div>

          <!-- Action Items -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Action Items</h3>
            <div class="space-y-3">
              <div v-for="action in review.action_items" :key="action.id" 
                   class="flex items-start space-x-3 p-3 bg-yellow-50 rounded-lg">
                <div class="flex-shrink-0 mt-1">
                  <svg v-if="action.completed" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  <svg v-else class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <h4 class="text-sm font-medium text-gray-900">{{ action.title }}</h4>
                  <p class="text-sm text-gray-600 mt-1">{{ action.description }}</p>
                  <div class="flex items-center justify-between mt-2">
                    <span :class="action.completed ? 'text-green-600' : 'text-yellow-600'" 
                          class="text-xs font-medium">
                      {{ action.completed ? 'Completed' : 'Pending' }}
                    </span>
                    <span v-if="action.due_date" class="text-xs text-gray-500">
                      Due: {{ formatDate(action.due_date) }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div v-if="!review.action_items || review.action_items.length === 0" 
                   class="text-center py-8 text-gray-500">
                No action items defined.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Review Modal -->
    <EditReviewModal 
      :show="showEditModal"
      :review="review"
      @close="showEditModal = false"
      @updated="handleReviewUpdated"
    />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import EditReviewModal from '@/Components/HR/PerformanceReviewModal.vue'

const props = defineProps({
  reviewId: [String, Number]
})

const review = ref(null)
const loading = ref(true)
const showEditModal = ref(false)

const canEdit = computed(() => {
  // Add logic to determine if current user can edit this review
  return review.value && (
    review.value.status === 'draft' || 
    review.value.status === 'in_progress'
  )
})

onMounted(async () => {
  await fetchReview()
})

const fetchReview = async () => {
  try {
    const response = await fetch(`/api/v1/hr/performance-reviews/${props.reviewId}`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      review.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch performance review:', error)
  } finally {
    loading.value = false
  }
}

const handleReviewUpdated = () => {
  showEditModal.value = false
  fetchReview()
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>
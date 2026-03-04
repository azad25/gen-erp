<template>
  <div class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
          {{ content.heading || 'What Our Clients Say' }}
        </h2>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div
          v-for="testimonial in mockTestimonials"
          :key="testimonial.id"
          class="bg-white p-6 rounded-lg shadow-md"
        >
          <div class="flex items-center mb-4">
            <div class="flex text-yellow-400">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
          </div>
          <p class="text-gray-600 mb-4">{{ testimonial.content }}</p>
          <div class="flex items-center">
            <div class="w-10 h-10 bg-gray-300 rounded-full mr-3"></div>
            <div>
              <p class="font-semibold text-gray-900">{{ testimonial.name }}</p>
              <p class="text-sm text-gray-500">{{ testimonial.company }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Editing Overlay -->
    <div
      v-if="isEditing"
      class="absolute inset-0 bg-blue-500 bg-opacity-5 border border-blue-300 rounded"
    ></div>
  </div>
</template>

<script setup>
defineProps({
  content: {
    type: Object,
    default: () => ({})
  },
  isEditing: {
    type: Boolean,
    default: false
  }
})

const mockTestimonials = [
  {
    id: 1,
    name: 'John Smith',
    company: 'Tech Corp',
    content: 'Excellent service and great results. Highly recommended!'
  },
  {
    id: 2,
    name: 'Sarah Johnson',
    company: 'Design Studio',
    content: 'Professional team with outstanding attention to detail.'
  },
  {
    id: 3,
    name: 'Mike Wilson',
    company: 'Startup Inc',
    content: 'They delivered exactly what we needed on time and on budget.'
  }
]
</script>
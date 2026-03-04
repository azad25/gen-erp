<template>
  <nav v-if="links.length > 3" class="flex items-center justify-between">
    <div class="flex-1 flex justify-between sm:hidden">
      <!-- Mobile pagination -->
      <Link
        v-if="links[0].url"
        :href="links[0].url"
        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
      >
        Previous
      </Link>
      <Link
        v-if="links[links.length - 1].url"
        :href="links[links.length - 1].url"
        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
      >
        Next
      </Link>
    </div>
    
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700">
          Showing
          <span class="font-medium">{{ from }}</span>
          to
          <span class="font-medium">{{ to }}</span>
          of
          <span class="font-medium">{{ total }}</span>
          results
        </p>
      </div>
      <div>
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
          <template v-for="(link, index) in links" :key="index">
            <!-- Previous Button -->
            <Link
              v-if="index === 0 && link.url"
              :href="link.url"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            >
              <span class="sr-only">Previous</span>
              <ChevronLeftIcon class="h-5 w-5" />
            </Link>
            
            <!-- Disabled Previous Button -->
            <span
              v-else-if="index === 0 && !link.url"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed"
            >
              <span class="sr-only">Previous</span>
              <ChevronLeftIcon class="h-5 w-5" />
            </span>
            
            <!-- Next Button -->
            <Link
              v-else-if="index === links.length - 1 && link.url"
              :href="link.url"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            >
              <span class="sr-only">Next</span>
              <ChevronRightIcon class="h-5 w-5" />
            </Link>
            
            <!-- Disabled Next Button -->
            <span
              v-else-if="index === links.length - 1 && !link.url"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed"
            >
              <span class="sr-only">Next</span>
              <ChevronRightIcon class="h-5 w-5" />
            </span>
            
            <!-- Page Numbers -->
            <template v-else>
              <!-- Active Page -->
              <span
                v-if="link.active"
                class="z-10 bg-primary border-primary text-white relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ link.label }}
              </span>
              
              <!-- Clickable Page -->
              <Link
                v-else-if="link.url"
                :href="link.url"
                class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ link.label }}
              </Link>
              
              <!-- Dots -->
              <span
                v-else
                class="bg-white border-gray-300 text-gray-500 relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ link.label }}
              </span>
            </template>
          </template>
        </nav>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  links: {
    type: Array,
    required: true
  },
  from: {
    type: Number,
    default: 0
  },
  to: {
    type: Number,
    default: 0
  },
  total: {
    type: Number,
    default: 0
  }
})
</script>
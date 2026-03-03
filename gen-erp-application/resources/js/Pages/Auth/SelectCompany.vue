<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-primary">
          <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6" />
          </svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Select Company
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Choose which company you'd like to access
        </p>
      </div>
      
      <div class="space-y-4">
        <div 
          v-for="company in companies" 
          :key="company.id"
          class="relative"
        >
          <button
            @click="selectCompany(company)"
            class="group relative w-full flex justify-between items-center px-6 py-4 border border-gray-300 rounded-lg text-left hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
            :class="company.is_current ? 'border-primary bg-primary/5' : ''"
          >
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                  <span class="text-sm font-medium text-primary">
                    {{ company.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
              </div>
              <div class="ml-4">
                <div class="text-sm font-medium text-gray-900">
                  {{ company.name }}
                </div>
                <div class="text-sm text-gray-500 capitalize">
                  {{ company.business_type }}
                </div>
              </div>
            </div>
            
            <div class="flex items-center">
              <span v-if="company.is_current" class="text-xs font-medium text-primary bg-primary/10 px-2 py-1 rounded-full">
                Current
              </span>
              <svg class="ml-2 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </button>
        </div>
      </div>

      <div class="text-center">
        <Link 
          href="/setup/company" 
          class="text-sm text-primary hover:text-primary/80 font-medium"
        >
          Create a new company
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  companies: Array
})

const selectCompany = (company) => {
  if (company.is_current) {
    // Already selected, go to dashboard
    router.visit('/dashboard')
  } else {
    // Switch to this company
    router.post(`/switch-company/${company.id}`)
  }
}
</script>
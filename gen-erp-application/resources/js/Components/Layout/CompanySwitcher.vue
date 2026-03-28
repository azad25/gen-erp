<template>
  <div class="relative">
    <button @click="open=!open" class="flex w-full items-center gap-2.5 rounded-xl border border-stroke bg-gray-50 px-3 py-2.5 hover:bg-gray-100 transition-colors text-left">
      <div class="h-7 w-7 rounded-lg bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">{{ abbr }}</div>
      <div class="flex-1 min-w-0">
        <p class="text-black text-xs font-semibold truncate">{{ company?.name }}</p>
        <p class="text-gray-1 text-[10px]">
          {{ getCompanyTypeLabel() }}
          <span v-if="branch?.name"> • {{ branch.name }}</span>
          <span v-else> • All Branches</span>
        </p>
      </div>
      <span class="text-gray-1">⌄</span>
    </button>
    <Transition enter-from-class="opacity-0 scale-95" enter-active-class="transition duration-100" leave-to-class="opacity-0 scale-95" leave-active-class="transition duration-75">
      <div v-if="open" class="absolute left-0 right-0 top-full z-50 mt-1 rounded-xl border border-stroke bg-white p-1.5 shadow-xl max-h-96 overflow-y-auto">
        <p class="px-2 py-1 text-[10px] font-mono uppercase tracking-widest text-gray-1">Workspace</p>
        
        <!-- Company Hierarchy Display -->
        <div v-for="hierarchy in companyHierarchy" :key="hierarchy.master.id" class="mb-2 last:mb-0">
          <!-- Master Company -->
          <button 
            @click="switchCompany(hierarchy.master.id)"
            type="button"
            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-medium transition-colors"
            :class="hierarchy.master.id === company?.id ? 'bg-primary/10 text-primary' : 'text-gray-1 hover:bg-gray-50 hover:text-black'"
          >
            <div class="h-5 w-5 rounded bg-gradient-to-br from-primary/80 to-accent/80 flex items-center justify-center text-white text-[9px] font-bold">
              {{ hierarchy.master.name.charAt(0).toUpperCase() }}
            </div>
            <span class="flex-1 text-left truncate">{{ hierarchy.master.name }}</span>
            <span class="text-[9px] px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded">MASTER</span>
            <span v-if="hierarchy.master.id === company?.id" class="text-primary">✓</span>
          </button>
          
          <!-- Aggregated View Option -->
          <button 
            v-if="hierarchy.can_aggregate && hierarchy.subsidiaries.length > 0"
            @click="switchToAggregatedView(hierarchy.master.id)"
            type="button"
            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors ml-4 mt-1"
            :class="isAggregatedView && hierarchy.master.id === company?.id ? 'bg-green-50 text-green-700' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700'"
          >
            <div class="h-4 w-4 flex items-center justify-center">📊</div>
            <span class="flex-1 text-left">View All Data ({{ hierarchy.subsidiaries.length }} subsidiaries)</span>
            <span v-if="isAggregatedView && hierarchy.master.id === company?.id" class="text-green-600">✓</span>
          </button>
          
          <!-- Subsidiaries -->
          <div v-if="hierarchy.subsidiaries.length > 0" class="ml-4 mt-1 space-y-1">
            <button 
              v-for="subsidiary in hierarchy.subsidiaries" 
              :key="subsidiary.id"
              @click="switchCompany(subsidiary.id)"
              type="button"
              class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors"
              :class="subsidiary.id === company?.id ? 'bg-primary/10 text-primary' : 'text-gray-1 hover:bg-gray-50 hover:text-black'"
            >
              <div class="h-4 w-4 rounded bg-gradient-to-br from-gray-400 to-gray-500 flex items-center justify-center text-white text-[8px] font-bold">
                {{ subsidiary.name.charAt(0).toUpperCase() }}
              </div>
              <span class="flex-1 text-left truncate">{{ subsidiary.name }}</span>
              <span class="text-[8px] px-1 py-0.5 bg-gray-100 text-gray-600 rounded">SUB</span>
              <span v-if="subsidiary.id === company?.id" class="text-primary">✓</span>
            </button>
          </div>
        </div>
        
        <!-- Fallback for old flat company structure -->
        <div v-if="companyHierarchy.length === 0 && companies.length > 0">
          <button 
            v-for="c in companies" 
            :key="c.id" 
            @click="switchCompany(c.id)"
            type="button"
            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-medium transition-colors"
            :class="c.id===company?.id ? 'bg-primary/10 text-primary' : 'text-gray-1 hover:bg-gray-50 hover:text-black'"
          >
            <div class="h-5 w-5 rounded bg-gradient-to-br from-primary/80 to-accent/80 flex items-center justify-center text-white text-[9px] font-bold">
              {{ c.name.charAt(0).toUpperCase() }}
            </div>
            <span class="flex-1 text-left truncate">{{ c.name }}</span>
            <span v-if="c.id===company?.id" class="text-primary">✓</span>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage()
const open = ref(false)

const company = computed(() => page.props.auth?.company)
const branch = computed(() => page.props.auth?.branch)
const user = computed(() => page.props.auth?.user)
const companies = computed(() => user.value?.companies || [])
const companyHierarchy = computed(() => page.props.auth?.company_hierarchy || [])
const abbr = computed(() => company.value?.name?.charAt(0).toUpperCase() || 'G')
const isAggregatedView = ref(false) // TODO: Get from session/props

const getCompanyTypeLabel = () => {
  if (!company.value) return 'No Company'
  
  if (company.value.is_master_company) {
    return isAggregatedView.value ? 'Master (All Data)' : 'Master Company'
  } else {
    return 'Subsidiary'
  }
}

const switchCompany = (companyId) => {
  if (companyId === company.value?.id && !isAggregatedView.value) {
    open.value = false
    return
  }
  
  isAggregatedView.value = false
  
  // Update sessionStorage for API calls
  sessionStorage.setItem('active_company_id', companyId)
  
  router.post(`/app/switch-company/${companyId}`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false
      // Reload the page to refresh all data with new company context
      window.location.reload()
    }
  })
}

const switchToAggregatedView = (masterCompanyId) => {
  if (masterCompanyId === company.value?.id && isAggregatedView.value) {
    open.value = false
    return
  }
  
  isAggregatedView.value = true
  
  // Update sessionStorage for API calls
  sessionStorage.setItem('active_company_id', masterCompanyId)
  sessionStorage.setItem('aggregated_view', 'true')
  
  router.post(`/app/switch-company/${masterCompanyId}`, { aggregated_view: true }, {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false
      // Reload the page to refresh all data with new company context
      window.location.reload()
    }
  })
}
</script>

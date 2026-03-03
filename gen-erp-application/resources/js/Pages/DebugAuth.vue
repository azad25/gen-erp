<template>
  <div class="p-8 space-y-4">
    <h1 class="text-2xl font-bold">Auth Debug Page</h1>
    
    <div class="bg-gray-100 p-4 rounded">
      <h2 class="font-bold mb-2">Page Props (from server):</h2>
      <pre class="text-xs">{{ JSON.stringify($page.props, null, 2) }}</pre>
    </div>
    
    <div class="bg-gray-100 p-4 rounded">
      <h2 class="font-bold mb-2">Session Storage:</h2>
      <pre class="text-xs">{{ sessionData }}</pre>
    </div>
    
    <div class="bg-gray-100 p-4 rounded">
      <h2 class="font-bold mb-2">Cookies:</h2>
      <pre class="text-xs">{{ document.cookie }}</pre>
    </div>
    
    <button @click="testApiCall" class="bg-blue-500 text-white px-4 py-2 rounded">
      Test API Call
    </button>
    
    <div v-if="apiResult" class="bg-gray-100 p-4 rounded">
      <h2 class="font-bold mb-2">API Test Result:</h2>
      <pre class="text-xs">{{ apiResult }}</pre>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import api from '@/Services/api.js'

const page = usePage()
const apiResult = ref(null)

const sessionData = computed(() => {
  return {
    active_company_id: sessionStorage.getItem('active_company_id'),
    all_keys: Object.keys(sessionStorage)
  }
})

const testApiCall = async () => {
  try {
    const response = await api.get('/invoices')
    apiResult.value = {
      status: 'success',
      data: response.data
    }
  } catch (error) {
    apiResult.value = {
      status: 'error',
      message: error.message,
      response: error.response?.data,
      statusCode: error.response?.status
    }
  }
}
</script>

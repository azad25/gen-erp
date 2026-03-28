<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Configure Integration</h1>
              <p class="text-sm text-gray-1">{{ integration?.integration?.name }}</p>
            </div>
            <Button variant="secondary" size="sm" @click="goBack">Back</Button>
          </div>

          <Card>
            <div class="p-6">
              <h2 class="text-lg font-semibold mb-4">Configuration</h2>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium mb-2">Status</label>
                  <span
                    :class="[
                      'px-3 py-1 rounded text-sm',
                      integration?.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'
                    ]"
                  >
                    {{ integration?.status }}
                  </span>
                </div>

                <div>
                  <label class="block text-sm font-medium mb-2">Last Sync</label>
                  <p class="text-sm text-gray-1">
                    {{ integration?.last_sync_at ? new Date(integration.last_sync_at).toLocaleString() : 'Never' }}
                  </p>
                </div>

                <div v-if="integration?.last_error">
                  <label class="block text-sm font-medium mb-2 text-red-600">Last Error</label>
                  <p class="text-sm text-red-600">{{ integration.last_error }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium mb-2">Configuration (JSON)</label>
                  <textarea
                    v-model="configJson"
                    class="w-full border rounded p-2 font-mono text-sm"
                    rows="10"
                    placeholder='{"api_key": "your-key", "endpoint": "https://api.example.com"}'
                  ></textarea>
                </div>

                <div class="flex gap-2">
                  <Button @click="saveConfig">Save Configuration</Button>
                  <Button variant="secondary" @click="testConnection">Test Connection</Button>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </AppLayout>
    
  
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue"
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import axios from 'axios'

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  }
})

const integration = ref(null)
const configJson = ref('')

const loadIntegration = async () => {
  try {
    const response = await axios.get(`/api/v1/integrations/company/${props.id}`)
    integration.value = response.data.data
    configJson.value = JSON.stringify(integration.value.config || {}, null, 2)
  } catch (error) {
    console.error('Failed to load integration:', error)
  }
}

const saveConfig = async () => {
  try {
    const config = JSON.parse(configJson.value)
    await axios.put(`/api/v1/integrations/company/${props.id}`, { config })
    alert('Configuration saved successfully')
    await loadIntegration()
  } catch (error) {
    console.error('Failed to save configuration:', error)
    alert('Failed to save configuration. Please check your JSON format.')
  }
}

const testConnection = async () => {
  alert('Test connection feature coming soon')
}

const goBack = () => {
  router.visit('/integrations')
}

onMounted(() => {
  loadIntegration()
})
</script>

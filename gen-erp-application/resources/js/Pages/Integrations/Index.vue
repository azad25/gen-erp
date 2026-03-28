<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Integrations</h1>
              <p class="text-sm text-gray-1">Connect your business with external services</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="showAvailable = !showAvailable">
                {{ showAvailable ? 'My Integrations' : 'Browse Available' }}
              </Button>
            </div>
          </div>

          <!-- Installed Integrations -->
          <div v-if="!showAvailable">
            <Card>
              <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Installed Integrations</h2>
                <div v-if="companyIntegrations.length === 0" class="text-center py-12">
                  <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">🔌</span>
                  </div>
                  <p class="text-gray-1 mb-4">No integrations installed yet</p>
                  <Button size="sm" @click="showAvailable = true">Browse Available Integrations</Button>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div
                    v-for="integration in companyIntegrations"
                    :key="integration.id"
                    class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                  >
                    <div class="flex items-start justify-between mb-3">
                      <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                          <span class="text-2xl">{{ integration.integration?.name?.charAt(0) || '?' }}</span>
                        </div>
                        <div>
                          <h3 class="font-semibold">{{ integration.integration?.name }}</h3>
                          <span
                            :class="[
                              'text-xs px-2 py-1 rounded',
                              integration.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'
                            ]"
                          >
                            {{ integration.status }}
                          </span>
                        </div>
                      </div>
                    </div>
                    <p class="text-sm text-gray-1 mb-4">{{ integration.integration?.description }}</p>
                    <div class="flex gap-2">
                      <Button size="sm" variant="secondary" @click="configureIntegration(integration)">Configure</Button>
                      <Button
                        size="sm"
                        :variant="integration.status === 'active' ? 'secondary' : 'primary'"
                        @click="toggleIntegration(integration)"
                      >
                        {{ integration.status === 'active' ? 'Deactivate' : 'Activate' }}
                      </Button>
                      <Button size="sm" variant="secondary" @click="syncIntegration(integration)">Sync</Button>
                    </div>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          <!-- Available Integrations -->
          <div v-else>
            <Card>
              <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Available Integrations</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div
                    v-for="integration in availableIntegrations"
                    :key="integration.id"
                    class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                  >
                    <div class="flex items-start justify-between mb-3">
                      <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                          <span class="text-2xl">{{ integration.name?.charAt(0) || '?' }}</span>
                        </div>
                        <div>
                          <h3 class="font-semibold">{{ integration.name }}</h3>
                          <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-700">
                            {{ integration.category_label }}
                          </span>
                        </div>
                      </div>
                    </div>
                    <p class="text-sm text-gray-1 mb-4">{{ integration.description }}</p>
                    <Button size="sm" @click="installIntegration(integration)" :disabled="isInstalled(integration.id)">
                      {{ isInstalled(integration.id) ? 'Installed' : 'Install' }}
                    </Button>
                  </div>
                </div>
              </div>
            </Card>
          </div>
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

const showAvailable = ref(false)
const availableIntegrations = ref([])
const companyIntegrations = ref([])

const loadAvailableIntegrations = async () => {
  try {
    const response = await axios.get('/api/v1/integrations')
    availableIntegrations.value = response.data.data
  } catch (error) {
    console.error('Failed to load available integrations:', error)
  }
}

const loadCompanyIntegrations = async () => {
  try {
    const response = await axios.get('/api/v1/integrations/company')
    companyIntegrations.value = response.data.data
  } catch (error) {
    console.error('Failed to load company integrations:', error)
  }
}

const isInstalled = (integrationId) => {
  return companyIntegrations.value.some(ci => ci.integration?.id === integrationId)
}

const installIntegration = async (integration) => {
  try {
    await axios.post('/api/v1/integrations/company', {
      integration_id: integration.id,
      config: {}
    })
    await loadCompanyIntegrations()
    showAvailable.value = false
  } catch (error) {
    console.error('Failed to install integration:', error)
    alert('Failed to install integration')
  }
}

const toggleIntegration = async (integration) => {
  try {
    const endpoint = integration.status === 'active' ? 'deactivate' : 'activate'
    await axios.post(`/api/v1/integrations/company/${integration.id}/${endpoint}`)
    await loadCompanyIntegrations()
  } catch (error) {
    console.error('Failed to toggle integration:', error)
    alert('Failed to toggle integration')
  }
}

const syncIntegration = async (integration) => {
  try {
    await axios.post(`/api/v1/integrations/company/${integration.id}/sync`)
    alert('Sync triggered successfully')
  } catch (error) {
    console.error('Failed to sync integration:', error)
    alert('Failed to sync integration')
  }
}

const configureIntegration = (integration) => {
  router.visit(`/integrations/${integration.id}/configure`)
}

onMounted(() => {
  loadAvailableIntegrations()
  loadCompanyIntegrations()
})
</script>

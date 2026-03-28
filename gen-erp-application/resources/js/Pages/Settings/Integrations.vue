<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-black dark:text-white">
                Plugins & Integrations
              </h1>
              <p class="text-sm text-gray-1 dark:text-gray-400">
                Manage plugins and extend system functionality
              </p>
            </div>
            <div class="flex items-center gap-3">
              <Button variant="secondary" size="sm" @click="openMarketplace">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Marketplace
              </Button>
              <Button size="sm" @click="openInstallModal">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Install Plugin
              </Button>
            </div>
          </div>

          <!-- Key Metrics -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <StatCard
              label="Total Plugins"
              :value="metrics.totalPlugins"
              subtitle="Installed plugins"
              color="teal"
            >
              <template #icon>🔌</template>
            </StatCard>
            
            <StatCard
              label="Enabled Plugins"
              :value="metrics.enabledPlugins"
              subtitle="Active plugins"
              color="green"
            >
              <template #icon>✅</template>
            </StatCard>
            
            <StatCard
              label="Disabled Plugins"
              :value="metrics.disabledPlugins"
              subtitle="Inactive plugins"
              color="gray"
            >
              <template #icon>⏸️</template>
            </StatCard>
            
            <StatCard
              label="Error Plugins"
              :value="metrics.errorPlugins"
              subtitle="Plugins with errors"
              color="red"
            >
              <template #icon>⚠️</template>
            </StatCard>
          </div>

          <!-- Plugin List -->
          <Card>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Installed Plugins
                </h3>
                <div class="flex items-center gap-2">
                  <select 
                    v-model="statusFilter" 
                    class="text-sm border rounded-lg px-3 py-1.5"
                  >
                    <option value="">All Status</option>
                    <option value="enabled">Enabled</option>
                    <option value="disabled">Disabled</option>
                    <option value="error">Error</option>
                  </select>
                </div>
              </div>
            </template>

            <div v-if="loading" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>

            <div v-else-if="filteredPlugins.length === 0" class="text-center py-12">
              <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">�</span>
              </div>
              <p class="text-gray-1 dark:text-gray-400">No plugins found</p>
              <Button size="sm" class="mt-4" @click="openInstallModal">
                Install Your First Plugin
              </Button>
            </div>

            <div v-else class="space-y-3">
              <div 
                v-for="plugin in filteredPlugins" 
                :key="plugin.id"
                class="flex items-center justify-between p-4 rounded-lg border border-stroke dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
              >
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                    <span class="text-2xl">🔌</span>
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <h4 class="font-semibold text-black dark:text-white">{{ plugin.name }}</h4>
                      <Badge :variant="getStatusVariant(plugin.status)">
                        {{ plugin.status }}
                      </Badge>
                    </div>
                    <p class="text-sm text-gray-1 dark:text-gray-400">{{ plugin.description }}</p>
                    <div class="flex items-center gap-4 mt-1 text-xs text-gray-1">
                      <span>v{{ plugin.version }}</span>
                      <span>•</span>
                      <span>{{ plugin.author }}</span>
                      <span>•</span>
                      <span>{{ formatDate(plugin.installed_at) }}</span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Button 
                    variant="ghost" 
                    size="sm"
                    @click="viewPlugin(plugin)"
                  >
                    View
                  </Button>
                  <Button 
                    v-if="plugin.status === 'disabled'"
                    variant="primary" 
                    size="sm"
                    @click="enablePlugin(plugin)"
                  >
                    Enable
                  </Button>
                  <Button 
                    v-if="plugin.status === 'enabled'"
                    variant="secondary" 
                    size="sm"
                    @click="disablePlugin(plugin)"
                  >
                    Disable
                  </Button>
                  <Button 
                    variant="danger" 
                    size="sm"
                    @click="uninstallPlugin(plugin)"
                  >
                    Uninstall
                  </Button>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Install Plugin Modal -->
        <Modal v-if="showInstallModal" @close="closeInstallModal" title="Install Plugin">
          <form @submit.prevent="handleInstall" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Plugin Name</label>
              <input 
                v-model="installForm.name" 
                type="text" 
                required 
                class="w-full border rounded-lg px-3 py-2"
                placeholder="My Plugin"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Plugin Slug</label>
              <input 
                v-model="installForm.slug" 
                type="text" 
                required 
                class="w-full border rounded-lg px-3 py-2"
                placeholder="my-plugin"
              />
              <p class="text-xs text-gray-1 mt-1">Lowercase letters, numbers, and hyphens only</p>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Version</label>
              <input 
                v-model="installForm.version" 
                type="text" 
                required 
                class="w-full border rounded-lg px-3 py-2"
                placeholder="1.0.0"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Author</label>
              <input 
                v-model="installForm.author" 
                type="text" 
                class="w-full border rounded-lg px-3 py-2"
                placeholder="Plugin Author"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Description</label>
              <textarea 
                v-model="installForm.description" 
                rows="3"
                class="w-full border rounded-lg px-3 py-2"
                placeholder="Plugin description"
              ></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Hooks (JSON)</label>
              <textarea 
                v-model="installForm.hooks" 
                rows="5"
                class="w-full border rounded-lg px-3 py-2 font-mono text-sm"
                placeholder='{"invoice.created": "App\\\\Plugins\\\\MyPlugin\\\\InvoiceHandler"}'
              ></textarea>
              <p class="text-xs text-gray-1 mt-1">Define hooks for plugin events</p>
            </div>
            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeInstallModal">
                Cancel
              </Button>
              <Button type="submit">
                Install Plugin
              </Button>
            </div>
          </form>
        </Modal>

        <!-- View Plugin Modal -->
        <Modal v-if="showViewModal" @close="showViewModal = false" title="Plugin Details" size="lg">
          <div v-if="selectedPlugin" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Plugin Name</p>
                <p class="font-semibold text-black dark:text-white">{{ selectedPlugin.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedPlugin.status)">
                  {{ selectedPlugin.status }}
                </Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Slug</p>
                <p class="font-mono text-sm">{{ selectedPlugin.slug }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Version</p>
                <p class="font-semibold">{{ selectedPlugin.version }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Author</p>
                <p class="font-semibold">{{ selectedPlugin.author }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Source</p>
                <p class="font-semibold">{{ selectedPlugin.source }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Installed At</p>
                <p class="font-semibold">{{ formatDate(selectedPlugin.installed_at) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Enabled At</p>
                <p class="font-semibold">{{ selectedPlugin.enabled_at ? formatDate(selectedPlugin.enabled_at) : '—' }}</p>
              </div>
            </div>

            <div>
              <h3 class="font-semibold mb-2">Description</h3>
              <p class="text-sm text-gray-1">{{ selectedPlugin.description }}</p>
            </div>

            <div v-if="selectedPlugin.manifest?.hooks">
              <h3 class="font-semibold mb-2">Registered Hooks</h3>
              <div class="space-y-2">
                <div 
                  v-for="(handler, event) in selectedPlugin.manifest.hooks" 
                  :key="event"
                  class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                >
                  <p class="text-sm font-medium text-black dark:text-white">{{ event }}</p>
                  <p class="text-xs text-gray-1 font-mono">{{ handler }}</p>
                </div>
              </div>
            </div>

            <div v-if="selectedPlugin.error_message">
              <h3 class="font-semibold mb-2 text-red-500">Error Message</h3>
              <p class="text-sm text-red-500">{{ selectedPlugin.error_message }}</p>
            </div>
          </div>
        </Modal>
      </AppLayout>
    
  
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import StatCard from '@/Components/UI/StatCard.vue'
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'

const loading = ref(false)
const statusFilter = ref('')
const showInstallModal = ref(false)
const showViewModal = ref(false)
const selectedPlugin = ref(null)

const plugins = ref([])
const metrics = ref({
  totalPlugins: 0,
  enabledPlugins: 0,
  disabledPlugins: 0,
  errorPlugins: 0
})

const installForm = ref({
  name: '',
  slug: '',
  version: '1.0.0',
  author: '',
  description: '',
  hooks: '{}'
})

const filteredPlugins = computed(() => {
  if (!statusFilter.value) return plugins.value
  return plugins.value.filter(p => p.status === statusFilter.value)
})

const fetchPlugins = async () => {
  loading.value = true
  try {
    // TODO: Fetch from API when backend is ready
    // const response = await api.get('/plugins')
    // plugins.value = response.data
    
    // Mock data for now
    plugins.value = []
    metrics.value = {
      totalPlugins: 0,
      enabledPlugins: 0,
      disabledPlugins: 0,
      errorPlugins: 0
    }
  } catch (error) {
    console.error('Failed to fetch plugins:', error)
  } finally {
    loading.value = false
  }
}

const openInstallModal = () => {
  installForm.value = {
    name: '',
    slug: '',
    version: '1.0.0',
    author: '',
    description: '',
    hooks: '{}'
  }
  showInstallModal.value = true
}

const closeInstallModal = () => {
  showInstallModal.value = false
}

const handleInstall = async () => {
  try {
    // TODO: Call API to install plugin
    console.log('Installing plugin:', installForm.value)
    alert('Plugin installation coming soon!')
    closeInstallModal()
    await fetchPlugins()
  } catch (error) {
    console.error('Failed to install plugin:', error)
  }
}

const viewPlugin = (plugin) => {
  selectedPlugin.value = plugin
  showViewModal.value = true
}

const enablePlugin = async (plugin) => {
  if (!confirm(`Are you sure you want to enable "${plugin.name}"?`)) return
  try {
    // TODO: Call API to enable plugin
    console.log('Enabling plugin:', plugin.slug)
    await fetchPlugins()
  } catch (error) {
    console.error('Failed to enable plugin:', error)
  }
}

const disablePlugin = async (plugin) => {
  if (!confirm(`Are you sure you want to disable "${plugin.name}"?`)) return
  try {
    // TODO: Call API to disable plugin
    console.log('Disabling plugin:', plugin.slug)
    await fetchPlugins()
  } catch (error) {
    console.error('Failed to disable plugin:', error)
  }
}

const uninstallPlugin = async (plugin) => {
  if (!confirm(`Are you sure you want to uninstall "${plugin.name}"? This action cannot be undone.`)) return
  try {
    // TODO: Call API to uninstall plugin
    console.log('Uninstalling plugin:', plugin.slug)
    await fetchPlugins()
  } catch (error) {
    console.error('Failed to uninstall plugin:', error)
  }
}

const openMarketplace = () => {
  alert('Plugin marketplace coming soon!')
}

const getStatusVariant = (status) => {
  const variants = {
    'enabled': 'success',
    'disabled': 'secondary',
    'error': 'danger'
  }
  return variants[status] || 'secondary'
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-BD', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  fetchPlugins()
})
</script>

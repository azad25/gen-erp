<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b">
          <h3 class="text-lg font-medium text-gray-900">Notification Settings</h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600"
          >
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Settings Form -->
        <form @submit.prevent="saveSettings" class="mt-6 space-y-6">
          <!-- General Settings -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-4">General Settings</h4>
            
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700">Email Notifications</label>
                  <p class="text-sm text-gray-500">Receive notifications via email</p>
                </div>
                <input
                  v-model="settings.email_notifications"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </div>
              
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700">Browser Notifications</label>
                  <p class="text-sm text-gray-500">Show desktop notifications in your browser</p>
                </div>
                <input
                  v-model="settings.browser_notifications"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </div>
              
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700">Sound Notifications</label>
                  <p class="text-sm text-gray-500">Play sound when receiving notifications</p>
                </div>
                <input
                  v-model="settings.sound_notifications"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </div>
            </div>
          </div>

          <!-- Notification Types -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-4">Notification Types</h4>
            
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700">CRM Updates</label>
                  <p class="text-sm text-gray-500">Lead assignments, opportunity changes, activity reminders</p>
                </div>
                <input
                  v-model="settings.crm_notifications"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </div>
              
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700">Logistics Updates</label>
                  <p class="text-sm text-gray-500">Shipment status changes, delivery updates, COD notifications</p>
                </div>
                <input
                  v-model="settings.logistics_notifications"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </div>
              
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700">System Notifications</label>
                  <p class="text-sm text-gray-500">System maintenance, security alerts, account updates</p>
                </div>
                <input
                  v-model="settings.system_notifications"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </div>
              
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700">Marketing Notifications</label>
                  <p class="text-sm text-gray-500">Product updates, feature announcements, tips</p>
                </div>
                <input
                  v-model="settings.marketing_notifications"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </div>
            </div>
          </div>

          <!-- Frequency Settings -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-4">Email Frequency</h4>
            
            <div class="space-y-3">
              <label class="flex items-center">
                <input
                  v-model="settings.email_frequency"
                  type="radio"
                  value="immediate"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Immediate - Send emails as notifications arrive</span>
              </label>
              
              <label class="flex items-center">
                <input
                  v-model="settings.email_frequency"
                  type="radio"
                  value="hourly"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Hourly - Send digest every hour</span>
              </label>
              
              <label class="flex items-center">
                <input
                  v-model="settings.email_frequency"
                  type="radio"
                  value="daily"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Daily - Send daily digest</span>
              </label>
              
              <label class="flex items-center">
                <input
                  v-model="settings.email_frequency"
                  type="radio"
                  value="weekly"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Weekly - Send weekly digest</span>
              </label>
              
              <label class="flex items-center">
                <input
                  v-model="settings.email_frequency"
                  type="radio"
                  value="never"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Never - Disable email notifications</span>
              </label>
            </div>
          </div>

          <!-- Quiet Hours -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-4">Quiet Hours</h4>
            <p class="text-sm text-gray-500 mb-4">Set hours when you don't want to receive notifications</p>
            
            <div class="flex items-center space-x-4">
              <div class="flex items-center">
                <input
                  v-model="settings.quiet_hours_enabled"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <label class="ml-2 text-sm text-gray-700">Enable quiet hours</label>
              </div>
            </div>
            
            <div v-if="settings.quiet_hours_enabled" class="mt-4 grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Start Time</label>
                <input
                  v-model="settings.quiet_hours_start"
                  type="time"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">End Time</label>
                <input
                  v-model="settings.quiet_hours_end"
                  type="time"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          <!-- Language Preference -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-4">Language Preference</h4>
            
            <select
              v-model="settings.language"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="en">English</option>
              <option value="bn">বাংলা (Bengali)</option>
            </select>
          </div>

          <!-- Form Actions -->
          <div class="flex items-center justify-end space-x-3 pt-6 border-t">
            <button
              type="button"
              @click="$emit('close')"
              class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md text-sm font-medium disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save Settings' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const emit = defineEmits(['close', 'updated'])
const { showToast } = useToast()

// Reactive data
const saving = ref(false)

const settings = reactive({
  email_notifications: true,
  browser_notifications: true,
  sound_notifications: false,
  crm_notifications: true,
  logistics_notifications: true,
  system_notifications: true,
  marketing_notifications: false,
  email_frequency: 'immediate',
  quiet_hours_enabled: false,
  quiet_hours_start: '22:00',
  quiet_hours_end: '08:00',
  language: 'en'
})

// Methods
const fetchSettings = async () => {
  try {
    const response = await fetch('/api/v1/notifications/preferences', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      Object.assign(settings, data.data)
    }
  } catch (error) {
    console.error('Failed to fetch settings:', error)
  }
}

const saveSettings = async () => {
  saving.value = true
  
  try {
    const response = await fetch('/api/v1/notifications/preferences', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(settings)
    })
    
    if (response.ok) {
      showToast('Notification settings saved successfully', 'success')
      emit('updated')
      emit('close')
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to save settings', 'error')
    }
  } catch (error) {
    console.error('Failed to save settings:', error)
    showToast('Failed to save settings', 'error')
  } finally {
    saving.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchSettings()
})
</script>
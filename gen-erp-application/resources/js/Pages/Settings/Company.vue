<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Company Settings</h1>
              <p class="text-sm text-gray-1">Manage company settings for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="resetForm">Cancel</Button>
              <Button size="sm" @click="saveSettings">Save Changes</Button>
            </div>
          </div>

          <div v-if="loading" class="flex items-center justify-center py-16">
            <p class="text-sm text-gray-1">Loading...</p>
          </div>

          <div v-else class="space-y-6">
            <Card>
              <h3 class="font-semibold mb-4">General Information</h3>
              <form class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium mb-1">Company Name *</label>
                    <input type="text" v-model="form.name" required class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Business Type</label>
                    <input type="text" v-model="form.business_type" class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Tax ID</label>
                    <input type="text" v-model="form.tax_id" class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Registration Number</label>
                    <input type="text" v-model="form.registration_number" class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Address</label>
                    <input type="text" v-model="form.address" class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">City</label>
                    <input type="text" v-model="form.city" class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Country</label>
                    <input type="text" v-model="form.country" class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Phone</label>
                    <input type="text" v-model="form.phone" class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" v-model="form.email" class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Website</label>
                    <input type="url" v-model="form.website" class="w-full border rounded-lg px-3 py-2">
                  </div>
                </div>
              </form>
            </Card>

            <Card>
              <h3 class="font-semibold mb-4">Financial Settings</h3>
              <form class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium mb-1">Currency</label>
                    <select v-model="form.currency" class="w-full border rounded-lg px-3 py-2">
                      <option value="USD">USD - US Dollar</option>
                      <option value="EUR">EUR - Euro</option>
                      <option value="GBP">GBP - British Pound</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Tax Rate (%)</label>
                    <input type="number" v-model="form.tax_rate" step="0.1" class="w-full border rounded-lg px-3 py-2">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Fiscal Year Start</label>
                    <select v-model="form.fiscal_year_start" class="w-full border rounded-lg px-3 py-2">
                      <option value="1">January</option>
                      <option value="4">April</option>
                      <option value="7">July</option>
                      <option value="10">October</option>
                    </select>
                  </div>
                </div>
              </form>
            </Card>

            <Card>
              <h3 class="font-semibold mb-4">Logo & Branding</h3>
              <form class="space-y-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Logo URL</label>
                  <input type="url" v-model="form.logo_url" class="w-full border rounded-lg px-3 py-2">
                </div>
              </form>
            </Card>
          </div>
        </div>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

const loading = ref(false)
const form = ref({
  name: '',
  business_type: '',
  tax_id: '',
  registration_number: '',
  address: '',
  city: '',
  country: '',
  phone: '',
  email: '',
  website: '',
  currency: 'USD',
  tax_rate: 0,
  fiscal_year_start: 1,
  logo_url: ''
})

const fetchSettings = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/company/settings')
    form.value = response.data
  } catch (error) {
    console.error('Failed to fetch settings:', error)
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  try {
    await axios.put('/api/v1/company/settings', form.value)
    alert('Settings saved successfully!')
  } catch (error) {
    console.error('Failed to save settings:', error)
    alert('Failed to save settings')
  }
}

const resetForm = () => {
  fetchSettings()
}

onMounted(() => {
  fetchSettings()
})
</script>

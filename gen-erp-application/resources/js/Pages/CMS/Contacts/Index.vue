<template>
  <div>
    <Head title="Contact Forms" />
    
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Contact Form Submissions</h1>
              <div class="flex space-x-2">
                <button
                  @click="exportContacts"
                  class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                  Export CSV
                </button>
                <button
                  @click="markAllRead"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                  Mark All Read
                </button>
              </div>
            </div>

            <div class="mb-4 flex space-x-4">
              <select
                v-model="selectedStatus"
                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Status</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
                <option value="replied">Replied</option>
              </select>
              
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by name or email..."
                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Contact
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Subject
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Submitted
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="contact in filteredContacts" :key="contact.id" :class="{ 'bg-blue-50': contact.status === 'unread' }">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div class="text-sm font-medium text-gray-900">
                          {{ contact.name }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ contact.email }}
                        </div>
                        <div v-if="contact.phone" class="text-sm text-gray-500">
                          {{ contact.phone }}
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-900">
                        {{ contact.subject }}
                      </div>
                      <div class="text-sm text-gray-500 truncate max-w-xs">
                        {{ contact.message }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="{
                          'bg-red-100 text-red-800': contact.status === 'unread',
                          'bg-yellow-100 text-yellow-800': contact.status === 'read',
                          'bg-green-100 text-green-800': contact.status === 'replied'
                        }"
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                      >
                        {{ contact.status }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(contact.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex space-x-2">
                        <button
                          @click="viewContact(contact)"
                          class="text-blue-600 hover:text-blue-900"
                        >
                          View
                        </button>
                        <button
                          @click="replyToContact(contact)"
                          class="text-green-600 hover:text-green-900"
                        >
                          Reply
                        </button>
                        <button
                          @click="deleteContact(contact.id)"
                          class="text-red-600 hover:text-red-900"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="filteredContacts.length === 0" class="text-center py-12">
              <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No contact submissions</h3>
                <p class="mt-1 text-sm text-gray-500">Contact form submissions will appear here.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const selectedStatus = ref('')
const searchQuery = ref('')

const contacts = ref([
  {
    id: 1,
    name: 'John Smith',
    email: 'john@example.com',
    phone: '+1234567890',
    subject: 'Product Inquiry',
    message: 'I would like to know more about your products and pricing...',
    status: 'unread',
    created_at: '2024-01-15T10:30:00Z'
  },
  {
    id: 2,
    name: 'Sarah Johnson',
    email: 'sarah@example.com',
    phone: null,
    subject: 'Support Request',
    message: 'I am having trouble with my account login...',
    status: 'replied',
    created_at: '2024-01-14T14:20:00Z'
  }
])

const filteredContacts = computed(() => {
  return contacts.value.filter(contact => {
    const statusMatch = !selectedStatus.value || contact.status === selectedStatus.value
    const searchMatch = !searchQuery.value || 
      contact.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      contact.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    return statusMatch && searchMatch
  })
})

const viewContact = (contact) => {
  // TODO: Implement contact viewing modal
  alert(`View contact: ${contact.name}`)
}

const replyToContact = (contact) => {
  // TODO: Implement reply functionality
  alert(`Reply to: ${contact.email}`)
}

const deleteContact = (contactId) => {
  if (confirm('Are you sure you want to delete this contact submission?')) {
    // TODO: Implement contact deletion
    alert('Contact deletion functionality will be implemented')
  }
}

const exportContacts = () => {
  // TODO: Implement CSV export
  alert('CSV export functionality will be implemented')
}

const markAllRead = () => {
  // TODO: Implement mark all as read
  alert('Mark all read functionality will be implemented')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>
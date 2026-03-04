<template>
  <div>
    <Head title="CMS Menus" />
    
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Navigation Menus</h1>
              <button
                @click="createMenu"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
              >
                Create Menu
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div
                v-for="menu in menus"
                :key="menu.id"
                class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow"
              >
                <div class="p-6">
                  <div class="flex justify-between items-start mb-4">
                    <div>
                      <h3 class="text-lg font-medium text-gray-900">{{ menu.name }}</h3>
                      <p class="text-sm text-gray-500">{{ menu.location || 'No location set' }}</p>
                    </div>
                    <span
                      :class="{
                        'bg-green-100 text-green-800': menu.is_active,
                        'bg-gray-100 text-gray-800': !menu.is_active
                      }"
                      class="px-2 py-1 text-xs font-semibold rounded-full"
                    >
                      {{ menu.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                  
                  <div class="mb-4">
                    <p class="text-sm text-gray-600">
                      {{ menu.items_count || 0 }} menu items
                    </p>
                  </div>

                  <div class="flex justify-between items-center">
                    <button
                      @click="editMenu(menu)"
                      class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                    >
                      Edit Menu
                    </button>
                    <button
                      @click="deleteMenu(menu.id)"
                      class="text-red-600 hover:text-red-900 text-sm font-medium"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="menus.length === 0" class="text-center py-12">
              <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No menus</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first navigation menu.</p>
                <div class="mt-6">
                  <button
                    @click="createMenu"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                  >
                    Create Menu
                  </button>
                </div>
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
import { ref } from 'vue'

const menus = ref([
  {
    id: 1,
    name: 'Main Navigation',
    location: 'header',
    is_active: true,
    items_count: 5
  },
  {
    id: 2,
    name: 'Footer Links',
    location: 'footer',
    is_active: true,
    items_count: 3
  }
])

const createMenu = () => {
  // TODO: Implement menu creation modal or redirect
  alert('Menu creation functionality will be implemented')
}

const editMenu = (menu) => {
  // TODO: Implement menu editing modal or redirect
  alert(`Edit menu: ${menu.name}`)
}

const deleteMenu = (menuId) => {
  if (confirm('Are you sure you want to delete this menu?')) {
    // TODO: Implement menu deletion
    alert('Menu deletion functionality will be implemented')
  }
}
</script>
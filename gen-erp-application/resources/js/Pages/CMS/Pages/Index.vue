<template>
  <div>
    <Head title="CMS Pages" />
    
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">
                Pages
                <span v-if="site" class="text-lg text-gray-600">for {{ site.name }}</span>
              </h1>
              <div class="flex space-x-2">
                <Link
                  :href="site ? route('cms.sites.pages.create', site.id) : route('cms.pages.create')"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                  Create Page
                </Link>
                <Link
                  v-if="site"
                  :href="route('cms.sites.index')"
                  class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                  Back to Sites
                </Link>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Page
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      URL
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Template
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Updated
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="page in pages.data" :key="page.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div class="text-sm font-medium text-gray-900">
                          {{ page.title }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ page.slug }}
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        /{{ page.slug }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="{
                          'bg-green-100 text-green-800': page.status === 'published',
                          'bg-yellow-100 text-yellow-800': page.status === 'draft',
                          'bg-red-100 text-red-800': page.status === 'archived'
                        }"
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                      >
                        {{ page.status }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ page.template || 'Default' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(page.updated_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex space-x-2">
                        <Link
                          :href="site ? route('cms.sites.pages.builder', [site.id, page.id]) : route('cms.pages.builder', page.id)"
                          class="text-purple-600 hover:text-purple-900"
                        >
                          Builder
                        </Link>
                        <Link
                          :href="site ? route('cms.sites.pages.edit', [site.id, page.id]) : route('cms.pages.edit', page.id)"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          Edit
                        </Link>
                        <button
                          @click="deletePage(page.id)"
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

            <div v-if="pages.data.length === 0" class="text-center py-12">
              <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No pages</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first page.</p>
                <div class="mt-6">
                  <Link
                    :href="site ? route('cms.sites.pages.create', site.id) : route('cms.pages.create')"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                  >
                    Create Page
                  </Link>
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
import { Head, Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

defineProps({
  pages: Object,
  site: Object
})

const deletePage = (pageId) => {
  if (confirm('Are you sure you want to delete this page?')) {
    router.delete(route('cms.pages.destroy', pageId))
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>
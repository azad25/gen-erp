<template>
  <div>
    <Head title="CMS Sites" />
    
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">CMS Sites</h1>
              <Link
                :href="route('cms.sites.create')"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
              >
                Create Site
              </Link>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Site
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Domain
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Pages
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="site in sites.data" :key="site.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                          <img
                            v-if="site.logo"
                            class="h-10 w-10 rounded-full object-cover"
                            :src="site.logo"
                            :alt="site.name"
                          />
                          <div
                            v-else
                            class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center"
                          >
                            <span class="text-sm font-medium text-gray-700">
                              {{ site.name.charAt(0).toUpperCase() }}
                            </span>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">
                            {{ site.name }}
                          </div>
                          <div class="text-sm text-gray-500">
                            {{ site.slug }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        {{ site.domain || `${site.subdomain}.yourplatform.com` }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="{
                          'bg-green-100 text-green-800': site.status === 'published',
                          'bg-yellow-100 text-yellow-800': site.status === 'draft',
                          'bg-red-100 text-red-800': site.status === 'maintenance'
                        }"
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                      >
                        {{ site.status }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ site.pages_count || 0 }} pages
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex space-x-2">
                        <Link
                          :href="route('cms.sites.pages.index', site.id)"
                          class="text-blue-600 hover:text-blue-900"
                        >
                          Pages
                        </Link>
                        <Link
                          :href="route('cms.sites.edit', site.id)"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          Edit
                        </Link>
                        <button
                          @click="deleteSite(site.id)"
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

            <div v-if="sites.data.length === 0" class="text-center py-12">
              <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No sites</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first site.</p>
                <div class="mt-6">
                  <Link
                    :href="route('cms.sites.create')"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                  >
                    Create Site
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
  sites: Object
})

const deleteSite = (siteId) => {
  if (confirm('Are you sure you want to delete this site?')) {
    router.delete(route('cms.sites.destroy', siteId))
  }
}
</script>
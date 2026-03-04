<template>
  <div>
    <Head title="Edit Site" />
    
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Edit Site: {{ site.name }}</h1>
              <div class="flex space-x-2">
                <Link
                  :href="route('cms.sites.pages.index', site.id)"
                  class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                  Manage Pages
                </Link>
                <Link
                  :href="route('cms.sites.index')"
                  class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                  Back to Sites
                </Link>
              </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="name" class="block text-sm font-medium text-gray-700">Site Name</label>
                  <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    required
                  />
                  <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                  <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
                  <input
                    id="slug"
                    v-model="form.slug"
                    type="text"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    required
                  />
                  <div v-if="form.errors.slug" class="text-red-600 text-sm mt-1">{{ form.errors.slug }}</div>
                </div>

                <div>
                  <label for="domain" class="block text-sm font-medium text-gray-700">Custom Domain (Optional)</label>
                  <input
                    id="domain"
                    v-model="form.domain"
                    type="text"
                    placeholder="example.com"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  />
                  <div v-if="form.errors.domain" class="text-red-600 text-sm mt-1">{{ form.errors.domain }}</div>
                </div>

                <div>
                  <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                  <select
                    id="status"
                    v-model="form.status"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="maintenance">Maintenance</option>
                  </select>
                  <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">{{ form.errors.status }}</div>
                </div>
              </div>

              <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea
                  id="description"
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                ></textarea>
                <div v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</div>
              </div>

              <div class="flex justify-end space-x-3">
                <Link
                  :href="route('cms.sites.index')"
                  class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                >
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                >
                  Update Site
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  site: Object
})

const form = useForm({
  name: props.site.name,
  slug: props.site.slug,
  domain: props.site.domain,
  description: props.site.description,
  status: props.site.status
})

const submit = () => {
  form.put(route('cms.sites.update', props.site.id))
}
</script>
<template>
  <div>
    <Head title="Create Page" />
    
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">
                Create New Page
                <span v-if="site" class="text-lg text-gray-600">for {{ site.name }}</span>
              </h1>
              <Link
                :href="site ? route('cms.sites.pages.index', site.id) : route('cms.pages.index')"
                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
              >
                Back to Pages
              </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="title" class="block text-sm font-medium text-gray-700">Page Title</label>
                  <input
                    id="title"
                    v-model="form.title"
                    type="text"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    required
                  />
                  <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</div>
                </div>

                <div>
                  <label for="slug" class="block text-sm font-medium text-gray-700">URL Slug</label>
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
                  <label for="template" class="block text-sm font-medium text-gray-700">Template</label>
                  <select
                    id="template"
                    v-model="form.template"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="default">Default</option>
                    <option value="landing">Landing Page</option>
                    <option value="blog">Blog Post</option>
                    <option value="product">Product Page</option>
                    <option value="contact">Contact Page</option>
                  </select>
                  <div v-if="form.errors.template" class="text-red-600 text-sm mt-1">{{ form.errors.template }}</div>
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
                    <option value="archived">Archived</option>
                  </select>
                  <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">{{ form.errors.status }}</div>
                </div>
              </div>

              <div>
                <label for="meta_description" class="block text-sm font-medium text-gray-700">Meta Description</label>
                <textarea
                  id="meta_description"
                  v-model="form.meta_description"
                  rows="2"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Brief description for search engines..."
                ></textarea>
                <div v-if="form.errors.meta_description" class="text-red-600 text-sm mt-1">{{ form.errors.meta_description }}</div>
              </div>

              <div>
                <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                <textarea
                  id="content"
                  v-model="form.content"
                  rows="10"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Page content..."
                ></textarea>
                <div v-if="form.errors.content" class="text-red-600 text-sm mt-1">{{ form.errors.content }}</div>
              </div>

              <div class="flex justify-end space-x-3">
                <Link
                  :href="site ? route('cms.sites.pages.index', site.id) : route('cms.pages.index')"
                  class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                >
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                >
                  Create Page
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
  title: '',
  slug: '',
  template: 'default',
  status: 'draft',
  meta_description: '',
  content: ''
})

const submit = () => {
  if (props.site) {
    form.post(route('cms.sites.pages.store', props.site.id))
  } else {
    form.post(route('cms.pages.store'))
  }
}
</script>
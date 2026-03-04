<template>
  <div>
    <Head title="Edit Blog Post" />
    
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Edit Blog Post: {{ post.title }}</h1>
              <div class="flex space-x-2">
                <Link
                  v-if="post.status === 'published'"
                  :href="`/blog/${post.slug}`"
                  target="_blank"
                  class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                  View Post
                </Link>
                <Link
                  :href="route('cms.blog.index')"
                  class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                  Back to Posts
                </Link>
              </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                  <label for="title" class="block text-sm font-medium text-gray-700">Post Title</label>
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
                  <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                  <select
                    id="category_id"
                    v-model="form.category_id"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="">Select Category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                  <div v-if="form.errors.category_id" class="text-red-600 text-sm mt-1">{{ form.errors.category_id }}</div>
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

                <div>
                  <label for="published_at" class="block text-sm font-medium text-gray-700">Publish Date</label>
                  <input
                    id="published_at"
                    v-model="form.published_at"
                    type="datetime-local"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  />
                  <div v-if="form.errors.published_at" class="text-red-600 text-sm mt-1">{{ form.errors.published_at }}</div>
                </div>
              </div>

              <div>
                <label for="excerpt" class="block text-sm font-medium text-gray-700">Excerpt</label>
                <textarea
                  id="excerpt"
                  v-model="form.excerpt"
                  rows="3"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Brief description of the post..."
                ></textarea>
                <div v-if="form.errors.excerpt" class="text-red-600 text-sm mt-1">{{ form.errors.excerpt }}</div>
              </div>

              <div>
                <label for="featured_image" class="block text-sm font-medium text-gray-700">Featured Image</label>
                <ImageUpload
                  v-model="form.featured_image"
                  @update="updateFeaturedImage"
                />
                <div v-if="form.errors.featured_image" class="text-red-600 text-sm mt-1">{{ form.errors.featured_image }}</div>
              </div>

              <div>
                <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                <RichTextEditor
                  v-model="form.content"
                  @update="updateContent"
                />
                <div v-if="form.errors.content" class="text-red-600 text-sm mt-1">{{ form.errors.content }}</div>
              </div>

              <div>
                <label for="meta_description" class="block text-sm font-medium text-gray-700">Meta Description</label>
                <textarea
                  id="meta_description"
                  v-model="form.meta_description"
                  rows="2"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="SEO meta description..."
                ></textarea>
                <div v-if="form.errors.meta_description" class="text-red-600 text-sm mt-1">{{ form.errors.meta_description }}</div>
              </div>

              <div>
                <label for="tags" class="block text-sm font-medium text-gray-700">Tags</label>
                <input
                  id="tags"
                  v-model="form.tags"
                  type="text"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="tag1, tag2, tag3"
                />
                <div v-if="form.errors.tags" class="text-red-600 text-sm mt-1">{{ form.errors.tags }}</div>
                <p class="text-sm text-gray-500 mt-1">Separate tags with commas</p>
              </div>

              <div class="flex items-center">
                <input
                  id="is_featured"
                  v-model="form.is_featured"
                  type="checkbox"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="is_featured" class="ml-2 block text-sm text-gray-900">
                  Featured Post
                </label>
              </div>

              <div class="flex justify-end space-x-3">
                <Link
                  :href="route('cms.blog.index')"
                  class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                >
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                >
                  Update Post
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
import ImageUpload from '@/Components/UI/ImageUpload.vue'
import RichTextEditor from '@/Components/UI/RichTextEditor.vue'

const props = defineProps({
  post: Object,
  categories: Array
})

const form = useForm({
  title: props.post.title,
  slug: props.post.slug,
  category_id: props.post.category_id,
  status: props.post.status,
  published_at: props.post.published_at ? props.post.published_at.slice(0, 16) : '',
  excerpt: props.post.excerpt || '',
  featured_image: props.post.featured_image || '',
  content: props.post.content || '',
  meta_description: props.post.meta_description || '',
  tags: props.post.tags ? props.post.tags.join(', ') : '',
  is_featured: props.post.is_featured || false
})

const submit = () => {
  form.put(route('cms.blog.update', props.post.id))
}

const updateFeaturedImage = () => {
  // Trigger form update
}

const updateContent = () => {
  // Trigger form update
}
</script>
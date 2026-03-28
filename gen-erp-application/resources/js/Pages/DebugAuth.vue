<template>
  <AppLayout title="Debug Auth">
    <div class="p-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
        Debug Authentication
      </h1>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold mb-4">Authentication Debug Info</h2>
        <div class="space-y-4">
          <div>
            <strong>User:</strong>
            <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-700 rounded text-sm">{{ JSON.stringify($page.props.auth.user, null, 2) }}</pre>
          </div>
          <div>
            <strong>Company:</strong>
            <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-700 rounded text-sm">{{ JSON.stringify($page.props.auth.company, null, 2) }}</pre>
          </div>
          <div>
            <strong>Permissions:</strong>
            <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-700 rounded text-sm">{{ JSON.stringify($page.props.auth.permissions, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const { $page } = usePage()
</script>
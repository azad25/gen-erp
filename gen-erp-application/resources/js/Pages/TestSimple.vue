<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold">Test Page</h1>
    <p>If you can see this, Inertia is working!</p>
    <p>Current URL: {{ $page.url }}</p>
    <p>Component: {{ $page.component }}</p>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'

const page = usePage()
console.log('[TestSimple] Page loaded successfully')
console.log('[TestSimple] URL:', page.url)
console.log('[TestSimple] Props:', page.props)
</script>

<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold text-red-500">SIMPLE TEST PAGE</h1>
    <p>If you can see this, the page loaded successfully!</p>
    <p>URL: {{ $page.url }}</p>
  </div>
</template>

<script setup>
console.log('[SimpleTest] Page loaded!')
</script>
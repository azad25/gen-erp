<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <SiteHeader :tenant="tenant" />
    
    <!-- Main Content -->
    <main class="flex-1">
      <slot />
    </main>
    
    <!-- Footer -->
    <SiteFooter :tenant="tenant" />
  </div>
</template>

<script setup>
// Get tenant data
const { tenant } = useTenant()
const { fetchMenus } = useMenu()

// Initialize menus when layout loads
onMounted(async () => {
  await fetchMenus()
})
</script>
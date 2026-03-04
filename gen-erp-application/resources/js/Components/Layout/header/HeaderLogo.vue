<template>
  <Link href="/" class="lg:hidden flex items-center gap-2">
    <Logo class="w-8 h-8" />
    <span class="text-lg font-extrabold text-black dark:text-white tracking-tight">GenERP BD</span>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Logo from '@/Components/Home/Logo.vue'
</script>

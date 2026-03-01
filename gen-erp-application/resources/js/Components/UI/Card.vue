<template>
  <div class="rounded-[14px] border border-stroke bg-white shadow-[0_1px_4px_rgba(0,0,0,0.06)] hover:shadow-[0_6px_24px_rgba(15,118,110,0.10)] transition-shadow duration-200">
    <div v-if="$slots.header" class="flex items-center justify-between border-b border-stroke px-5 py-4">
      <slot name="header" />
    </div>
    <div :class="noPadding?'':'p-5'"><slot /></div>
    <div v-if="$slots.footer" class="rounded-b-[14px] border-t border-stroke bg-gray-3/40 px-5 py-3">
      <slot name="footer" />
    </div>
  </div>
</template>
<script setup>
defineProps({ noPadding: Boolean })
</script>

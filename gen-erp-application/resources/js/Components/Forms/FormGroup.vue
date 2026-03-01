<template>
  <div class="space-y-1.5">
    <label v-if="label" class="block text-[12.5px] font-semibold text-black/80">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>
    <slot />
    <p v-if="error" class="text-[11.5px] text-danger">⚠ {{ error }}</p>
    <p v-else-if="hint" class="text-[11.5px] text-gray-1">{{ hint }}</p>
  </div>
</template>
<script setup>
defineProps({ label:String, error:String, hint:String, required:Boolean })
</script>

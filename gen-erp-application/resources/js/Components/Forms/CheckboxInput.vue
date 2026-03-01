<template>
  <div class="flex items-center gap-2">
    <input
      type="checkbox"
      :id="id"
      v-model="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
      class="rounded border-stroke text-primary focus:ring-2 focus:ring-primary/10 cursor-pointer"
    />
    <label v-if="label" :for="id" class="text-[12.5px] font-medium text-black/80 cursor-pointer">
      {{ label }}
    </label>
  </div>
</template>

<script setup>
defineProps({
  id: String,
  label: String,
  modelValue: Boolean,
})

defineEmits(['update:modelValue'])
</script>

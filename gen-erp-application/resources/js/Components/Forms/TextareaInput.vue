<template>
  <div class="relative">
    <textarea
      v-bind="$attrs"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="w-full rounded-[8px] border border-stroke bg-white px-3 py-2 text-[13.5px] text-black placeholder-gray-1/50 outline-none transition-all resize-y min-h-[80px]"
      :class="[error?'border-danger':'focus:border-primary focus:ring-2 focus:ring-primary/10']"
      :rows="rows"
    />
  </div>
</template>

<script setup>
defineProps({
  modelValue: String,
  error: String,
  rows: { type: Number, default: 3 },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="relative">
    <input
      type="date"
      v-bind="$attrs"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="w-full rounded-[8px] border border-stroke bg-white px-3 h-9 text-[13.5px] text-black outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all cursor-pointer"
      :class="[error?'border-danger':'']"
    />
  </div>
</template>

<script setup>
defineProps({
  modelValue: String,
  error: String,
})

defineEmits(['update:modelValue'])
</script>

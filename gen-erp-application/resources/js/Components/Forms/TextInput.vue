<template>
  <div class="relative">
    <span v-if="prefix" class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px] font-bangla text-gray-1 pointer-events-none">{{ prefix }}</span>
    <input v-bind="$attrs" :value="modelValue" @input="$emit('update:modelValue',$event.target.value)"
      class="w-full rounded-[8px] border border-stroke bg-white px-3 h-9 text-[13.5px] text-black placeholder-gray-1/50 outline-none transition-all"
      :class="[error?'border-danger':'focus:border-primary focus:ring-2 focus:ring-primary/10', prefix?'pl-7':'']" />
  </div>
</template>
<script setup>
defineProps({ modelValue:[String,Number], prefix:String, error:String })
defineEmits(['update:modelValue'])
</script>

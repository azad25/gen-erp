<template>
  <select v-bind="$attrs" :value="modelValue" @change="$emit('update:modelValue',$event.target.value)"
    class="w-full rounded-[8px] border border-stroke bg-white px-3 h-9 text-[13.5px] text-black outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all appearance-none cursor-pointer">
    <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
    <option v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</option>
  </select>
</template>
<script setup>
defineProps({ modelValue:[String,Number], options:Array, placeholder:String })
defineEmits(['update:modelValue'])
</script>

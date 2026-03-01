<template>
  <time class="font-mono text-[12px]" :datetime="iso">{{ formatted }}</time>
</template>
<script setup>
import { computed } from 'vue'
import dayjs from 'dayjs'
const p = defineProps({ date:[String,Date] })
const iso       = computed(()=>dayjs(p.date).toISOString())
const formatted = computed(()=>dayjs(p.date).format('DD MMM YYYY'))
</script>

<template>
  <span class="font-mono" :class="colored&&amount<0?'text-danger':''">
    <span class="font-bangla mr-px">৳</span>{{ fmt }}
  </span>
</template>
<script setup>
import { computed } from 'vue'
const p = defineProps({ amount:{type:Number,default:0}, colored:Boolean })
const fmt = computed(()=>new Intl.NumberFormat('en-BD',{minimumFractionDigits:2}).format((p.amount||0)/100))
</script>

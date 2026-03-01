<template><apexchart type="bar" :height="height" :options="opts" :series="series" /></template>
<script setup>
import { computed } from 'vue'
const p = defineProps({ series:Array, categories:Array, height:{type:Number,default:300} })
const opts = computed(()=>({
  chart:{ toolbar:{show:false }, animations:{ enabled:false }},
  plotOptions:{ bar:{ borderRadius:4, columnWidth:'60%' } },
  colors:['#0F766E'],
  xaxis:{ categories:p.categories, labels:{ style:{ colors:'#6B7280', fontSize:'11px' } } },
  yaxis:{ labels:{ style:{ colors:'#6B7280', fontSize:'11px' } } },
  grid:{ borderColor:'#E5E7EB', strokeDashArray:4 },
  dataLabels:{ enabled:false },
  tooltip:{ theme:'light', style:{ fontSize:'12px' } },
}))
</script>

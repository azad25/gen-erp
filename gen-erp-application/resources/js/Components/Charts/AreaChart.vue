<template><apexchart type="area" :height="height" :options="opts" :series="series" /></template>
<script setup>
import { computed } from 'vue'
const p = defineProps({ series:Array, categories:Array, height:{type:Number,default:300} })
const opts = computed(()=>({
  chart:{ toolbar:{show:false }, animations:{ enabled:false }},
  stroke:{ curve:'smooth', width:2 },
  colors:['#0F766E'],
  fill:{ type:'gradient', gradient:{ shadeIntensity:1, opacityFrom:0.7, opacityTo:0.1, stops:[0,90,100] } },
  xaxis:{ categories:p.categories, labels:{ style:{ colors:'#6B7280', fontSize:'11px' } } },
  yaxis:{ labels:{ style:{ colors:'#6B7280', fontSize:'11px' } } },
  grid:{ borderColor:'#E5E7EB', strokeDashArray:4 },
  dataLabels:{ enabled:false },
  tooltip:{ theme:'light', style:{ fontSize:'12px' } },
}))
</script>

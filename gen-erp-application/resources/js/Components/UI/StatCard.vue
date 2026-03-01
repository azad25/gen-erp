<template>
  <div class="group rounded-[14px] border border-stroke bg-white p-5 shadow-[0_1px_4px_rgba(0,0,0,0.06)] hover:shadow-[0_6px_24px_rgba(15,118,110,0.10)] transition-all cursor-default">
    <div class="mb-4 flex items-start justify-between">
      <div>
        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-1">{{ label }}</p>
        <h4 class="font-mono text-[26px] font-bold leading-none tracking-tight" :class="vc">
          <span v-if="isCurrency" class="font-bangla mr-0.5 text-[20px]">৳</span>{{ dv }}
        </h4>
      </div>
      <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl text-xl" :class="ic">
        <slot name="icon">📊</slot>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <span v-if="delta!==undefined" class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
        :class="delta>=0?'bg-success/10 text-success':'bg-danger/10 text-danger'">
        {{ delta>=0?'↑':'↓' }} {{ Math.abs(delta) }}%
      </span>
      <span class="text-[11px] text-gray-1">{{ subtitle }}</span>
    </div>
    <div v-if="sparkline?.length" class="mt-3 flex h-7 items-end gap-0.5">
      <div v-for="(v,i) in sparkline" :key="i"
        class="flex-1 rounded-sm transition-colors"
        :style="{height:(v/Math.max(...sparkline)*100)+'%'}"
        :class="i===sparkline.length-1?'bg-primary':'bg-primary/20 group-hover:bg-primary/30'" />
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
const p = defineProps({ label:String, value:[Number,String], subtitle:String, delta:Number, isCurrency:Boolean, sparkline:Array, color:{type:String,default:'teal'} })
const m = { teal:['text-primary','bg-primary/8'], green:['text-success','bg-success/8'], amber:['text-warning','bg-warning/8'], red:['text-danger','bg-danger/8'] }
const vc = computed(()=>(m[p.color]||m.teal)[0])
const ic = computed(()=>(m[p.color]||m.teal)[1])
const dv = computed(()=>p.isCurrency?new Intl.NumberFormat('en-BD',{maximumFractionDigits:0}).format((p.value||0)/100):p.value)
</script>

<template>
  <Card :no-padding="true">
    <!-- Toolbar -->
    <div class="flex items-center gap-3 border-b border-stroke px-5 py-3.5">
      <div class="flex h-9 w-64 items-center gap-2 rounded-lg border border-stroke bg-gray-3 px-3 focus-within:border-primary/40 focus-within:ring-2 focus-within:ring-primary/10 transition-all">
        <span class="text-gray-2 text-sm">⌕</span>
        <input v-model="q" :placeholder="placeholder" class="flex-1 bg-transparent text-[12.5px] text-text outline-none placeholder-gray-1/50" />
        <button v-if="q" @click="q=''" class="text-gray-2 hover:text-gray-1 text-xs">✕</button>
      </div>
      <div class="ml-auto flex items-center gap-2">
        <slot name="toolbar" />
        <Button v-if="createRoute" :href="route(createRoute)" size="sm">+ {{ createLabel }}</Button>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b border-stroke bg-gray-3/40">
            <th v-if="selectable" class="w-10 px-5 py-3">
              <input type="checkbox" @change="e=>selected=e.target.checked?rows.map(r=>r.id):[]" :checked="selected.length===rows?.length" class="rounded border-stroke text-primary" />
            </th>
            <th v-for="col in columns" :key="col.key" @click="sortBy(col.key)"
              class="cursor-pointer select-none whitespace-nowrap px-5 py-3 text-[10.5px] font-semibold uppercase tracking-wide text-gray-1"
              :class="[col.right?'text-right':'text-left']">
              {{ col.label }}
              <span v-if="sk===col.key" class="ml-0.5 text-primary">{{ sd==='asc'?'↑':'↓' }}</span>
            </th>
            <th v-if="$slots.actions" class="w-24 px-5 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!rows?.length">
            <td :colspan="columns.length+(selectable?1:0)+($slots.actions?1:0)" class="px-5 py-14 text-center text-sm text-gray-1">
              No records found
            </td>
          </tr>
          <tr v-else v-for="(row,i) in rows" :key="row.id||i"
            class="group border-t border-stroke hover:bg-gray-3/30 transition-colors"
            :class="onRowClick?'cursor-pointer':''">
            <td v-if="selectable" class="px-5 py-3">
              <input type="checkbox" :value="row.id" v-model="selected" class="rounded border-stroke text-primary" />
            </td>
            <td v-for="col in columns" :key="col.key"
              class="px-5 py-3 text-[13px]"
              :class="[col.mono?'font-mono text-[12px]':'', col.bold?'font-semibold text-black':'text-black-2', col.right?'text-right':'']"
              @click="onRowClick&&onRowClick(row)">
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                {{ row[col.key]??'—' }}
              </slot>
            </td>
            <td v-if="$slots.actions" class="px-5 py-3">
              <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <slot name="actions" :row="row" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination" class="flex items-center justify-between border-t border-stroke px-5 py-3.5">
      <p class="text-[12px] text-gray-1">
        Showing <span class="font-semibold text-black">{{ pagination.from }}</span>–<span class="font-semibold text-black">{{ pagination.to }}</span> of <span class="font-semibold text-black">{{ pagination.total }}</span>
      </p>
      <div class="flex items-center gap-1">
        <button v-for="l in pagination.links" :key="l.label"
          :disabled="!l.url" @click="l.url&&$inertia.get(l.url)"
          class="flex h-8 w-8 items-center justify-center rounded-lg text-[12px] font-medium disabled:opacity-30 transition-colors"
          :class="l.active?'bg-primary text-white':'text-gray-1 hover:bg-gray-3'">
          <span v-html="l.label" />
        </button>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { ref } from 'vue'
import Card from './Card.vue'
import Button from './Button.vue'

defineProps({
  columns:      Array,
  rows:         Array,
  pagination:   Object,
  placeholder:  { type: String, default: 'Search...' },
  createRoute:  String,
  createLabel:  { type: String, default: 'New' },
  selectable:   Boolean,
  onRowClick:   Function,
})

const q = ref(''), sk = ref(''), sd = ref('asc'), selected = ref([])
const sortBy = k => { sk.value===k ? sd.value=sd.value==='asc'?'desc':'asc' : (sk.value=k, sd.value='asc') }
</script>

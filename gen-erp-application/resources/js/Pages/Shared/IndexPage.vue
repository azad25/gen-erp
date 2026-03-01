<template>
  <AppLayout>
    <template #header>
      <h2 class="text-[15px] font-bold text-black tracking-tight">{{ title }}</h2>
      <p class="text-[11px] text-gray-1">{{ subtitle }}</p>
    </template>

    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-black">{{ title }}</h1>
        <Button v-if="createRoute" :href="createRoute" size="sm">+ {{ createLabel }}</Button>
      </div>

      <Card :no-padding="true">
        <DataTable
          :columns="columns"
          :rows="rows"
          :pagination="pagination"
          :placeholder="searchPlaceholder"
          :create-route="createRoute"
          :create-label="createLabel"
          :selectable="selectable"
          :on-row-click="onRowClick"
        >
          <template v-for="col in columns" :key="`cell-${col.key}`" #[`cell-${col.key}`]="{ row, value }">
            <slot :name="`cell-${col.key}`" :row="row" :value="value">
              {{ value ?? '—' }}
            </slot>
          </template>
          <template #actions="{ row }">
            <slot name="actions" :row="row" />
          </template>
        </DataTable>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '../../Components/Layout/AppLayout.vue'
import Card from '../../Components/UI/Card.vue'
import DataTable from '../../Components/UI/DataTable.vue'
import Button from '../../Components/UI/Button.vue'

const props = defineProps({
  title: String,
  subtitle: { type: String, default: '' },
  columns: Array,
  rows: Array,
  pagination: Object,
  searchPlaceholder: { type: String, default: 'Search...' },
  createRoute: String,
  createLabel: { type: String, default: 'New' },
  selectable: Boolean,
  onRowClick: Function,
})

const page = usePage()
const company = computed(() => page.props.auth?.company)
</script>

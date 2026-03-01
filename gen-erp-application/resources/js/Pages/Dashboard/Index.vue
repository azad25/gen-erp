<template>
  <SidebarProvider>
    <AdminLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">
                Good {{ tod }}, {{ firstName }} 👋
              </h1>
              <p class="text-sm text-gray-1">{{ company?.name }}</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm">Export</Button>
              <Button size="sm">+ New Invoice</Button>
            </div>
          </div>

          <!-- Stats Row -->
          <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard
          label="Revenue"
          :value="stats.revenue"
          subtitle="This month"
          :delta="stats.revenueDelta"
          is-currency
          color="teal"
          :sparkline="[32,45,28,67,54,78,92]"
        >
          <template #icon>💰</template>
        </StatCard>
        <StatCard
          label="Outstanding"
          :value="stats.outstanding"
          subtitle="Unpaid invoices"
          is-currency
          color="teal"
        >
          <template #icon>🔄</template>
        </StatCard>
        <StatCard
          label="Low Stock"
          :value="stats.lowStock"
          subtitle="Products below threshold"
          color="amber"
        >
          <template #icon>⚠️</template>
        </StatCard>
        <StatCard
          label="Pending Approvals"
          :value="stats.pending"
          subtitle="Awaiting your action"
          :color="stats.pending > 0 ? 'red' : 'green'"
        >
          <template #icon>⏳</template>
        </StatCard>
      </div>

      <!-- Charts Row -->
      <div class="grid gap-4 md:grid-cols-3">
        <div class="md:col-span-2">
          <Card>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-black">Revenue Trend</h3>
                <div class="flex gap-1">
                  <button v-for="p in ['7d','30d','90d']" :key="p"
                    class="px-2 py-1 text-xs font-medium rounded"
                    :class="period===p?'bg-primary text-white':'text-gray-1 hover:bg-gray-3'">
                    {{ p }}
                  </button>
                </div>
              </div>
            </template>
            <AreaChart :series="[{name:'Revenue',data:chartData}]" :categories="chartLabels" :height="280" />
          </Card>
        </div>
        <Card>
          <template #header>
            <h3 class="text-sm font-semibold text-black">Revenue by Type</h3>
          </template>
          <DonutChart 
            :series="revenueByType.series" 
            :labels="revenueByType.labels"
            :height="280"
          />
        </Card>
      </div>

      <!-- Bottom Row -->
      <div class="grid gap-4 md:grid-cols-3">
        <div class="md:col-span-2">
          <Card :no-padding="true">
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-black">Recent Invoices</h3>
                <a href="/invoices" class="text-xs text-primary hover:underline">View all →</a>
              </div>
            </template>
            <table class="w-full">
              <thead>
                <tr class="border-b border-stroke bg-gray-3/40">
                  <th class="px-5 py-3 text-[10.5px] font-semibold uppercase tracking-wide text-gray-1 text-left">Invoice #</th>
                  <th class="px-5 py-3 text-[10.5px] font-semibold uppercase tracking-wide text-gray-1 text-left">Customer</th>
                  <th class="px-5 py-3 text-[10.5px] font-semibold uppercase tracking-wide text-gray-1 text-right">Amount</th>
                  <th class="px-5 py-3 text-[10.5px] font-semibold uppercase tracking-wide text-gray-1 text-left">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in invoices" :key="inv.id"
                  class="group border-t border-stroke hover:bg-gray-3/25 cursor-pointer"
                  @click="$inertia.visit(`/invoices/${inv.id}`)">
                  <td class="px-5 py-3 font-mono text-[12px] font-semibold text-black">{{ inv.invoice_number }}</td>
                  <td class="px-5 py-3 text-sm text-black-2">{{ inv.customer_name }}</td>
                  <td class="px-5 py-3 text-right">
                    <BanglaAmount :amount="inv.total_amount" />
                  </td>
                  <td class="px-5 py-3">
                    <Badge :variant="inv.status">{{ inv.status }}</Badge>
                  </td>
                </tr>
              </tbody>
            </table>
          </Card>
        </div>
        <Card :no-padding="true">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-black">Activity</h3>
              <Badge variant="success">● LIVE</Badge>
            </div>
          </template>
          <ul class="divide-y divide-stroke">
            <li v-for="act in activity" :key="act.id" class="px-5 py-3">
              <div class="flex items-start gap-3">
                <div class="mt-1 h-2 w-2 rounded-full" :class="`bg-${act.color}`" />
                <div class="flex-1 min-w-0">
                  <p class="text-xs text-black-2" v-html="act.description" />
                  <p class="text-[10px] text-gray-1 font-mono mt-0.5">{{ act.time_ago }}</p>
                </div>
              </div>
            </li>
          </ul>
        </Card>
      </div>
    </div>
  </AdminLayout>
  </SidebarProvider>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import StatCard from '@/Components/ui/StatCard.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import AreaChart from '@/Components/charts/AreaChart.vue'
import DonutChart from '@/Components/charts/DonutChart.vue'
import BanglaAmount from '@/Components/Bangla/BanglaAmount.vue'

const page = usePage()
const company = computed(() => page.props.auth?.company)
const user = computed(() => page.props.auth?.user)

const firstName = computed(() => user.value?.name?.split(' ')[0] || 'User')

const tod = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'morning'
  if (hour < 18) return 'afternoon'
  return 'evening'
})

const period = ref('30d')

const stats = computed(() => page.props.stats || {})
const invoices = computed(() => page.props.invoices || [])
const activity = computed(() => page.props.activity || [])
const chartData = computed(() => page.props.chartData || [])
const chartLabels = computed(() => page.props.chartLabels || [])
const revenueByType = computed(() => page.props.revenueByType || { series: [44, 30, 15, 11], labels: ['Retail', 'Wholesale', 'Service', 'Other'] })
</script>

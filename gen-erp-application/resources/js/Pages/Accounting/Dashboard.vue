<template>
  
    <AppLayout>
      <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">
              Accounting Dashboard
            </h1>
            <p class="text-sm text-gray-1 dark:text-gray-400">
              Monitor financial health and accounting activities
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Button variant="secondary" size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Financial Report
            </Button>
            <Button size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              New Journal Entry
            </Button>
          </div>
        </div>

        <!-- Key Financial Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard
            label="Total Revenue"
            :value="metrics.totalRevenue"
            subtitle="This month"
            :delta="metrics.revenueDelta"
            is-currency
            color="teal"
            :sparkline="metrics.revenueSparkline"
          >
            <template #icon>💰</template>
          </StatCard>
          
          <StatCard
            label="Total Expenses"
            :value="metrics.totalExpenses"
            subtitle="This month"
            :delta="metrics.expensesDelta"
            is-currency
            color="red"
            :sparkline="metrics.expensesSparkline"
          >
            <template #icon>💸</template>
          </StatCard>
          
          <StatCard
            label="Net Profit"
            :value="metrics.netProfit"
            subtitle="This month"
            :delta="metrics.profitDelta"
            is-currency
            :color="metrics.netProfit >= 0 ? 'green' : 'red'"
          >
            <template #icon>📈</template>
          </StatCard>
          
          <StatCard
            label="Cash Flow"
            :value="metrics.cashFlow"
            subtitle="Current balance"
            :delta="metrics.cashFlowDelta"
            is-currency
            :color="metrics.cashFlow >= 0 ? 'teal' : 'amber'"
          >
            <template #icon>🏦</template>
          </StatCard>
        </div>

        <!-- Charts Row -->
        <div class="grid gap-6 lg:grid-cols-3">
          <!-- Profit & Loss Trend -->
          <div class="lg:col-span-2">
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-black dark:text-white">
                    Profit & Loss Trend
                  </h3>
                  <div class="flex gap-1">
                    <button 
                      v-for="period in ['7d', '30d', '90d']" 
                      :key="period"
                      @click="selectedPeriod = period"
                      :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-lg transition-colors',
                        selectedPeriod === period 
                          ? 'bg-primary text-white' 
                          : 'text-gray-1 hover:bg-gray-3 dark:hover:bg-gray-800'
                      ]"
                    >
                      {{ period }}
                    </button>
                  </div>
                </div>
              </template>
              <AreaChart 
                :series="[
                  {name: 'Revenue', data: chartData.revenue},
                  {name: 'Expenses', data: chartData.expenses}
                ]" 
                :categories="chartData.labels" 
                :height="320" 
              />
            </Card>
          </div>

          <!-- Account Balances -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Account Balances
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="account in accountBalances" 
                :key="account.id"
                class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800"
              >
                <div class="flex items-center gap-3">
                  <div 
                    class="w-3 h-3 rounded-full"
                    :style="{ backgroundColor: account.color }"
                  ></div>
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ account.name }}</p>
                    <p class="text-xs text-gray-1">{{ account.type }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatCurrency(account.balance) }}
                  </p>
                  <div 
                    :class="[
                      'text-xs',
                      account.change >= 0 ? 'text-success' : 'text-danger'
                    ]"
                  >
                    {{ account.change >= 0 ? '+' : '' }}{{ account.change }}%
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Financial Ratios & Recent Transactions -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Financial Ratios -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Financial Ratios
              </h3>
            </template>
            <div class="grid grid-cols-2 gap-4">
              <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="text-2xl font-bold text-primary">{{ financialRatios.currentRatio }}</div>
                <div class="text-sm text-gray-1">Current Ratio</div>
                <div class="text-xs text-success mt-1">Healthy</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="text-2xl font-bold text-primary">{{ financialRatios.quickRatio }}</div>
                <div class="text-sm text-gray-1">Quick Ratio</div>
                <div class="text-xs text-success mt-1">Good</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="text-2xl font-bold text-primary">{{ financialRatios.debtToEquity }}%</div>
                <div class="text-sm text-gray-1">Debt to Equity</div>
                <div class="text-xs text-warning mt-1">Moderate</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="text-2xl font-bold text-primary">{{ financialRatios.profitMargin }}%</div>
                <div class="text-sm text-gray-1">Profit Margin</div>
                <div class="text-xs text-success mt-1">Excellent</div>
              </div>
            </div>
          </Card>

          <!-- Recent Transactions -->
          <Card>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Recent Transactions
                </h3>
                <Link 
                  href="/accounting/journal-entries" 
                  class="text-sm text-primary hover:text-primary-dark font-medium"
                >
                  View All
                </Link>
              </div>
            </template>
            <div class="space-y-3">
              <div 
                v-for="transaction in recentTransactions" 
                :key="transaction.id"
                class="flex items-center justify-between p-3 rounded-lg border border-stroke dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
              >
                <div class="flex items-center gap-3">
                  <div 
                    :class="[
                      'w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-semibold',
                      transaction.type === 'credit' ? 'bg-success' : 'bg-danger'
                    ]"
                  >
                    {{ transaction.type === 'credit' ? '+' : '-' }}
                  </div>
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ transaction.description }}</p>
                    <p class="text-sm text-gray-1">{{ transaction.account }} • {{ formatDate(transaction.date) }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatCurrency(transaction.amount) }}
                  </p>
                  <Badge :variant="getTransactionVariant(transaction.status)">
                    {{ transaction.status }}
                  </Badge>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Expense Categories & Tax Summary -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Expense Categories -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Expense Categories
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="category in expenseCategories" 
                :key="category.name"
                class="flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: category.color }"></div>
                  <span class="text-sm font-medium text-black dark:text-white">{{ category.name }}</span>
                </div>
                <div class="text-right">
                  <p class="text-sm font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatCurrency(category.amount) }}
                  </p>
                  <div class="flex items-center gap-2 mt-1">
                    <div class="w-16 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div 
                        class="h-full rounded-full transition-all"
                        :style="{ width: `${category.percentage}%`, backgroundColor: category.color }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-1">{{ category.percentage }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Tax Summary -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Tax Summary
              </h3>
            </template>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-primary">
                    <span class="font-bangla">৳</span>{{ formatCurrency(taxSummary.vatCollected) }}
                  </div>
                  <div class="text-sm text-gray-1">VAT Collected</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-primary">
                    <span class="font-bangla">৳</span>{{ formatCurrency(taxSummary.vatPaid) }}
                  </div>
                  <div class="text-sm text-gray-1">VAT Paid</div>
                </div>
              </div>
              
              <div class="p-4 rounded-lg bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-teal-800 dark:text-teal-200">Net VAT Payable</span>
                  <span class="text-lg font-bold text-teal-800 dark:text-teal-200">
                    <span class="font-bangla">৳</span>{{ formatCurrency(taxSummary.netVatPayable) }}
                  </span>
                </div>
                <div class="text-xs text-teal-600 dark:text-teal-400 mt-1">
                  Due: {{ formatDate(taxSummary.dueDate) }}
                </div>
              </div>

              <div class="space-y-2">
                <h4 class="font-medium text-black dark:text-white">Upcoming Tax Obligations</h4>
                <div 
                  v-for="obligation in taxSummary.upcomingObligations" 
                  :key="obligation.id"
                  class="flex items-center justify-between p-2 rounded bg-yellow-50 dark:bg-yellow-900/20"
                >
                  <div>
                    <p class="text-sm font-medium text-black dark:text-white">{{ obligation.type }}</p>
                    <p class="text-xs text-gray-1">Due: {{ formatDate(obligation.dueDate) }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-warning">
                      <span class="font-bangla">৳</span>{{ formatCurrency(obligation.amount) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </AppLayout>
  
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/UI/StatCard.vue'
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import AreaChart from '@/Components/Charts/AreaChart.vue'

// Props from backend
const props = defineProps({
  metrics: Object,
  chartData: Object,
  accountBalances: Array,
  financialRatios: Object,
  recentTransactions: Array,
  expenseCategories: Array,
  taxSummary: Object,
})

// Reactive state
const selectedPeriod = ref('30d')

// Helper functions
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', {
    maximumFractionDigits: 0
  }).format(amount / 100)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-BD', {
    month: 'short',
    day: 'numeric'
  })
}

const getTransactionVariant = (status) => {
  const variants = {
    'posted': 'success',
    'pending': 'warning',
    'draft': 'secondary',
    'rejected': 'danger'
  }
  return variants[status.toLowerCase()] || 'secondary'
}
</script>
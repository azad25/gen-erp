<template>
  
    <AppLayout>
      <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">
              HR Dashboard
            </h1>
            <p class="text-sm text-gray-1 dark:text-gray-400">
              Monitor workforce performance and HR activities
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Button variant="secondary" size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              HR Report
            </Button>
            <Button size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add Employee
            </Button>
          </div>
        </div>

        <!-- Key HR Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard
            label="Total Employees"
            :value="metrics.totalEmployees"
            subtitle="Active workforce"
            :delta="metrics.employeesDelta"
            color="teal"
            :sparkline="metrics.employeesSparkline"
          >
            <template #icon>👥</template>
          </StatCard>
          
          <StatCard
            label="Attendance Rate"
            :value="`${metrics.attendanceRate}%`"
            subtitle="This month"
            :delta="metrics.attendanceDelta"
            color="green"
          >
            <template #icon>✅</template>
          </StatCard>
          
          <StatCard
            label="Pending Leaves"
            :value="metrics.pendingLeaves"
            subtitle="Awaiting approval"
            color="amber"
          >
            <template #icon>📅</template>
          </StatCard>
          
          <StatCard
            label="Payroll Cost"
            :value="metrics.payrollCost"
            subtitle="This month"
            :delta="metrics.payrollDelta"
            is-currency
            color="teal"
          >
            <template #icon>💰</template>
          </StatCard>
        </div>

        <!-- Charts Row -->
        <div class="grid gap-6 lg:grid-cols-3">
          <!-- Attendance Trend -->
          <div class="lg:col-span-2">
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-black dark:text-white">
                    Attendance Trend
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
                  {name: 'Present', data: chartData.present},
                  {name: 'Absent', data: chartData.absent},
                  {name: 'Late', data: chartData.late}
                ]" 
                :categories="chartData.labels" 
                :height="320" 
              />
            </Card>
          </div>

          <!-- Department Overview -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Department Overview
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="department in departments" 
                :key="department.id"
                class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800"
              >
                <div class="flex items-center justify-between mb-2">
                  <h4 class="font-medium text-black dark:text-white">{{ department.name }}</h4>
                  <span class="text-sm text-gray-1">{{ department.employeeCount }} employees</span>
                </div>
                <div class="space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-1">Attendance</span>
                    <span class="text-black dark:text-white">{{ department.attendanceRate }}%</span>
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                      class="h-2 rounded-full transition-all"
                      :class="getAttendanceColor(department.attendanceRate)"
                      :style="{ width: `${department.attendanceRate}%` }"
                    ></div>
                  </div>
                  <div class="flex justify-between text-xs text-gray-1">
                    <span>{{ department.presentToday }} present today</span>
                    <span>{{ department.onLeave }} on leave</span>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Employee Performance & Leave Requests -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Top Performers -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Top Performers
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="(employee, index) in topPerformers" 
                :key="employee.id"
                class="flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-sm">
                    {{ index + 1 }}
                  </div>
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-primary to-teal-400 flex items-center justify-center text-white font-semibold">
                      {{ employee.name.charAt(0) }}
                    </div>
                    <div>
                      <p class="font-medium text-black dark:text-white">{{ employee.name }}</p>
                      <p class="text-sm text-gray-1">{{ employee.department }} • {{ employee.position }}</p>
                    </div>
                  </div>
                </div>
                <div class="text-right">
                  <div class="flex items-center gap-2">
                    <div class="text-right">
                      <p class="text-sm font-semibold text-black dark:text-white">{{ employee.score }}%</p>
                      <p class="text-xs text-gray-1">Performance</p>
                    </div>
                    <div class="w-12 h-12 relative">
                      <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                        <path
                          class="text-gray-200 dark:text-gray-700"
                          stroke="currentColor"
                          stroke-width="3"
                          fill="none"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <path
                          class="text-primary"
                          stroke="currentColor"
                          stroke-width="3"
                          fill="none"
                          stroke-linecap="round"
                          :stroke-dasharray="`${employee.score}, 100`"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Recent Leave Requests -->
          <Card>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Recent Leave Requests
                </h3>
                <Link 
                  href="/hr/leave" 
                  class="text-sm text-primary hover:text-primary-dark font-medium"
                >
                  View All
                </Link>
              </div>
            </template>
            <div class="space-y-3">
              <div 
                v-for="request in leaveRequests" 
                :key="request.id"
                class="flex items-center justify-between p-3 rounded-lg border border-stroke dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-gradient-to-r from-primary to-teal-400 flex items-center justify-center text-white font-semibold">
                    {{ request.employeeName.charAt(0) }}
                  </div>
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ request.employeeName }}</p>
                    <p class="text-sm text-gray-1">{{ request.leaveType }} • {{ request.duration }}</p>
                    <p class="text-xs text-gray-1">{{ formatDateRange(request.startDate, request.endDate) }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <Badge :variant="getLeaveStatusVariant(request.status)">
                    {{ request.status }}
                  </Badge>
                  <p class="text-xs text-gray-1 mt-1">{{ formatDate(request.requestDate) }}</p>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Payroll Summary & Upcoming Events -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Payroll Summary -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Payroll Summary
              </h3>
            </template>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-primary">
                    <span class="font-bangla">৳</span>{{ formatCurrency(payrollSummary.grossPay) }}
                  </div>
                  <div class="text-sm text-gray-1">Gross Pay</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-primary">
                    <span class="font-bangla">৳</span>{{ formatCurrency(payrollSummary.netPay) }}
                  </div>
                  <div class="text-sm text-gray-1">Net Pay</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-primary">
                    <span class="font-bangla">৳</span>{{ formatCurrency(payrollSummary.deductions) }}
                  </div>
                  <div class="text-sm text-gray-1">Deductions</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-primary">
                    <span class="font-bangla">৳</span>{{ formatCurrency(payrollSummary.benefits) }}
                  </div>
                  <div class="text-sm text-gray-1">Benefits</div>
                </div>
              </div>
              
              <div class="space-y-3">
                <h4 class="font-medium text-black dark:text-white">Payroll Breakdown</h4>
                <div 
                  v-for="item in payrollSummary.breakdown" 
                  :key="item.category"
                  class="flex items-center justify-between"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: item.color }"></div>
                    <span class="text-sm font-medium text-black dark:text-white">{{ item.category }}</span>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-black dark:text-white">
                      <span class="font-bangla">৳</span>{{ formatCurrency(item.amount) }}
                    </p>
                    <p class="text-xs text-gray-1">{{ item.percentage }}%</p>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Upcoming HR Events -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Upcoming HR Events
              </h3>
            </template>
            <div class="space-y-4">
              <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="text-center p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                  <div class="text-xl font-bold text-blue-600">{{ upcomingEvents.birthdays }}</div>
                  <div class="text-xs text-gray-1">Birthdays</div>
                </div>
                <div class="text-center p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
                  <div class="text-xl font-bold text-green-600">{{ upcomingEvents.anniversaries }}</div>
                  <div class="text-xs text-gray-1">Anniversaries</div>
                </div>
                <div class="text-center p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20">
                  <div class="text-xl font-bold text-purple-600">{{ upcomingEvents.reviews }}</div>
                  <div class="text-xs text-gray-1">Reviews Due</div>
                </div>
              </div>
              
              <div class="space-y-3">
                <h4 class="font-medium text-black dark:text-white">This Week</h4>
                <div 
                  v-for="event in upcomingEvents.thisWeek" 
                  :key="event.id"
                  class="flex items-center justify-between p-3 rounded-lg"
                  :class="getEventBg(event.type)"
                >
                  <div class="flex items-center gap-3">
                    <div 
                      class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm"
                      :class="getEventIconBg(event.type)"
                    >
                      {{ getEventIcon(event.type) }}
                    </div>
                    <div>
                      <p class="font-medium text-black dark:text-white">{{ event.title }}</p>
                      <p class="text-sm text-gray-1">{{ event.employee }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-black dark:text-white">{{ formatDate(event.date) }}</p>
                    <p class="text-xs text-gray-1">{{ event.type }}</p>
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
  departments: Array,
  topPerformers: Array,
  leaveRequests: Array,
  payrollSummary: Object,
  upcomingEvents: Object,
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

const formatDateRange = (startDate, endDate) => {
  const start = new Date(startDate).toLocaleDateString('en-BD', { month: 'short', day: 'numeric' })
  const end = new Date(endDate).toLocaleDateString('en-BD', { month: 'short', day: 'numeric' })
  return `${start} - ${end}`
}

const getAttendanceColor = (rate) => {
  if (rate >= 95) return 'bg-success'
  if (rate >= 85) return 'bg-warning'
  return 'bg-danger'
}

const getLeaveStatusVariant = (status) => {
  const variants = {
    'approved': 'success',
    'pending': 'warning',
    'rejected': 'danger'
  }
  return variants[status.toLowerCase()] || 'secondary'
}

const getEventBg = (type) => {
  const backgrounds = {
    'birthday': 'bg-blue-50 dark:bg-blue-900/20',
    'anniversary': 'bg-green-50 dark:bg-green-900/20',
    'review': 'bg-purple-50 dark:bg-purple-900/20'
  }
  return backgrounds[type] || 'bg-gray-50 dark:bg-gray-800'
}

const getEventIconBg = (type) => {
  const backgrounds = {
    'birthday': 'bg-blue-500',
    'anniversary': 'bg-green-500',
    'review': 'bg-purple-500'
  }
  return backgrounds[type] || 'bg-gray-500'
}

const getEventIcon = (type) => {
  const icons = {
    'birthday': '🎂',
    'anniversary': '🎉',
    'review': '📋'
  }
  return icons[type] || '📅'
}
</script>
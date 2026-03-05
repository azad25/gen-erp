<template>
  <Card class="overflow-x-auto">
    <table class="w-full">
      <thead>
        <tr class="border-b">
          <th class="p-4 text-left font-semibold text-black dark:text-white">Features</th>
          <th 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center font-semibold text-black dark:text-white"
          >
            {{ plan.name }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr class="border-b">
          <td class="p-4 text-black dark:text-white">Monthly Price</td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center font-semibold text-black dark:text-white"
          >
            <span class="font-bangla">৳</span>{{ formatPrice(plan.monthly_price) }}
          </td>
        </tr>
        <tr class="border-b">
          <td class="p-4 text-black dark:text-white">Annual Price</td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center font-semibold text-black dark:text-white"
          >
            <span class="font-bangla">৳</span>{{ formatPrice(plan.annual_price) }}
          </td>
        </tr>
        <tr class="border-b">
          <td class="p-4 text-black dark:text-white">Products</td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center text-black dark:text-white"
          >
            {{ plan.limits?.products || 'Unlimited' }}
          </td>
        </tr>
        <tr class="border-b">
          <td class="p-4 text-black dark:text-white">Users</td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center text-black dark:text-white"
          >
            {{ plan.limits?.users || 'Unlimited' }}
          </td>
        </tr>
        <tr class="border-b">
          <td class="p-4 text-black dark:text-white">Branches</td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center text-black dark:text-white"
          >
            {{ plan.limits?.branches || 'Unlimited' }}
          </td>
        </tr>
        <tr class="border-b">
          <td class="p-4 text-black dark:text-white">Storage</td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center text-black dark:text-white"
          >
            {{ formatStorage(plan.limits?.storage_bytes) }}
          </td>
        </tr>
        <tr class="border-b">
          <td class="p-4 text-black dark:text-white">API Access</td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center"
          >
            <span v-if="plan.feature_flags?.api_access" class="text-green-500">✓</span>
            <span v-else class="text-gray-400">✗</span>
          </td>
        </tr>
        <tr class="border-b">
          <td class="p-4 text-black dark:text-white">Integrations</td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center text-black dark:text-white"
          >
            {{ plan.feature_flags?.integrations || 0 }}
          </td>
        </tr>
        <tr class="border-b">
          <td class="p-4 text-black dark:text-white">Plugin SDK</td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center"
          >
            <span v-if="plan.feature_flags?.plugin_sdk" class="text-green-500">✓</span>
            <span v-else class="text-gray-400">✗</span>
          </td>
        </tr>
        <tr>
          <td class="p-4"></td>
          <td 
            v-for="plan in plans" 
            :key="plan.id"
            class="p-4 text-center"
          >
            <Button 
              :variant="currentPlan?.id === plan.id ? 'secondary' : 'primary'"
              size="sm"
              @click="$emit('selectPlan', plan)"
            >
              {{ currentPlan?.id === plan.id ? 'Current' : 'Select' }}
            </Button>
          </td>
        </tr>
      </tbody>
    </table>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/ui/Button.vue'

const props = defineProps({
  plans: {
    type: Array,
    required: true
  },
  currentPlan: {
    type: Object,
    default: null
  }
})

defineEmits(['selectPlan'])

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('en-BD').format(price / 100)
}

const formatStorage = (bytes) => {
  if (!bytes) return 'Unlimited'
  const gb = bytes / (1024 * 1024 * 1024)
  return `${gb.toFixed(1)} GB`
}
</script>

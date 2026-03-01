<template>
  <ShowPage
    title="Customer Details"
    :subtitle="customer?.name"
    edit-route="/customers/{{ customer?.id }}/edit"
    :delete-action="handleDelete"
  >
    <div class="space-y-6">
      <div class="grid grid-cols-2 gap-6">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-gray-1 mb-2">Contact Information</p>
          <div class="space-y-3">
            <div>
              <p class="text-[11px] text-gray-1">Email</p>
              <p class="text-sm text-black">{{ customer?.email || '—' }}</p>
            </div>
            <div>
              <p class="text-[11px] text-gray-1">Phone</p>
              <p class="text-sm text-black">{{ customer?.phone || '—' }}</p>
            </div>
          </div>
        </div>
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-gray-1 mb-2">Credit Information</p>
          <div class="space-y-3">
            <div>
              <p class="text-[11px] text-gray-1">Credit Limit</p>
              <p class="text-sm text-black">
                <BanglaAmount v-if="customer?.credit_limit" :amount="customer.credit_limit" />
              </p>
            </div>
          </div>
        </div>
      </div>

      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-1 mb-2">Address</p>
        <p class="text-sm text-black">{{ customer?.address || 'No address provided' }}</p>
        <p v-if="customer?.district" class="text-sm text-gray-1">{{ customer.district }}</p>
      </div>

      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-1 mb-2">Activity</p>
        <p class="text-xs text-gray-1">No recent activity</p>
      </div>
    </div>
  </ShowPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ShowPage from '../Shared/ShowPage.vue'
import BanglaAmount from '../../Components/Bangla/BanglaAmount.vue'
import { useApi } from '../../Composables/useApi.js'

const page = usePage()
const { get, del } = useApi()

const customer = ref(null)

onMounted(async () => {
  const response = await get(`/customers/${page.props.customer.id}`)
  customer.value = response.data
})

const handleDelete = async () => {
  if (confirm('Are you sure you want to delete this customer?')) {
    await del(`/customers/${page.props.customer.id}`)
    window.location.href = '/customers'
  }
}
</script>

<template>
  <div class="p-4 md:p-6 space-y-4">

    <PageHeader title="Shop Orders" subtitle="Orders placed via the website boutique" />

    <!-- Filters -->
    <div class="px-4 md:px-6 flex flex-wrap gap-2">
      <input v-model="search" @input="debouncedLoad" placeholder="Order # or customer…"
        class="input-base text-xs w-52">
      <select v-model="filterStatus" @change="load" class="input-base text-xs w-36">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="processing">Processing</option>
        <option value="shipped">Shipped</option>
        <option value="delivered">Delivered</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select v-model="filterPayment" @change="load" class="input-base text-xs w-36">
        <option value="">All Payments</option>
        <option value="paid">Paid</option>
        <option value="pending">Pending</option>
        <option value="failed">Failed</option>
      </select>
    </div>

    <!-- Orders table -->
    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-10 text-center text-gray-400 text-xs">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading orders…
      </div>
      <div v-else-if="!orders.length" class="p-10 text-center text-gray-400 text-xs">No orders found.</div>
      <div v-else>
        <div class="divide-y divide-gray-50">
          <div v-for="o in orders" :key="o.id"
            class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50/60 cursor-pointer transition-colors"
            @click="openDetail(o)">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold text-gray-900 font-mono">{{ o.order_number }}</span>
                <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded-full', statusBadge(o.status)]">{{ o.status }}</span>
                <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded-full', paymentBadge(o.payment_status)]">{{ o.payment_status }}</span>
              </div>
              <div class="text-[10px] text-gray-500 mt-0.5">
                {{ o.delivery_first_name }} {{ o.delivery_last_name }} · {{ o.contact_email }}
              </div>
              <div class="text-[10px] text-gray-400 mt-0.5">
                {{ o.items?.length ?? 0 }} item{{ (o.items?.length ?? 0) !== 1 ? 's' : '' }}
                · {{ o.delivery_city }}
                · {{ formatDate(o.created_at) }}
              </div>
            </div>
            <div class="text-right shrink-0">
              <div class="text-xs font-bold text-gray-900">KES {{ Number(o.total).toLocaleString() }}</div>
              <div class="text-[9px] text-gray-400 mt-0.5">{{ o.payment_method?.toUpperCase() }}</div>
            </div>
          </div>
        </div>
        <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-50">
          <span class="text-[10px] text-gray-400">{{ meta.from }}–{{ meta.to }} of {{ meta.total }}</span>
          <div class="flex gap-1">
            <button v-for="p in meta.last_page" :key="p" @click="page = p; load()"
              :class="['w-7 h-7 rounded-lg text-xs font-semibold', page === p ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
              {{ p }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ ORDER DETAIL MODAL ══ -->
    <Modal v-model="showDetail" :title="detail?.order_number" size="md">
      <div v-if="detail" class="space-y-4">

        <!-- Status -->
        <div class="flex items-center gap-3 flex-wrap">
          <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', statusBadge(detail.status)]">{{ detail.status }}</span>
          <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', paymentBadge(detail.payment_status)]">{{ detail.payment_status }}</span>
          <span v-if="detail.mpesa_transaction_id" class="text-[10px] text-green-700 bg-green-50 px-2 py-0.5 rounded-full font-mono">{{ detail.mpesa_transaction_id }}</span>
        </div>

        <!-- Items -->
        <div class="border border-gray-100 rounded-xl overflow-hidden">
          <div class="bg-gray-50 px-3 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wide">Items</div>
          <div class="divide-y divide-gray-50">
            <div v-for="item in detail.items" :key="item.id" class="flex items-center gap-3 px-3 py-2.5">
              <div class="w-9 h-9 rounded-lg overflow-hidden border border-gray-100 shrink-0 bg-gray-50">
                <img v-if="item.product?.image" :src="websiteBase + '/' + item.product.image" class="w-full h-full object-cover">
                <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-sm">📦</div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-gray-800">{{ item.product_name }}</div>
                <div class="text-[10px] text-gray-400">× {{ item.qty }} @ KES {{ Number(item.unit_price).toLocaleString() }}</div>
              </div>
              <div class="text-xs font-bold text-gray-700 shrink-0">KES {{ Number(item.subtotal).toLocaleString() }}</div>
            </div>
          </div>
          <div class="border-t border-gray-100 px-3 py-2 flex justify-between text-xs font-bold text-gray-800">
            <span>Total</span>
            <span>KES {{ Number(detail.total).toLocaleString() }}</span>
          </div>
        </div>

        <!-- Customer & Delivery -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
          <div class="bg-gray-50 rounded-xl p-3 space-y-1">
            <div class="font-bold text-gray-700 text-[10px] uppercase tracking-wide mb-1.5">Customer</div>
            <div class="text-gray-800 font-semibold">{{ detail.delivery_first_name }} {{ detail.delivery_last_name }}</div>
            <div class="text-gray-500">{{ detail.contact_email }}</div>
            <div class="text-gray-500">{{ detail.delivery_phone }}</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-3 space-y-1">
            <div class="font-bold text-gray-700 text-[10px] uppercase tracking-wide mb-1.5">Delivery</div>
            <div class="text-gray-800">{{ detail.delivery_address }}</div>
            <div class="text-gray-500">{{ detail.delivery_city }}</div>
          </div>
        </div>

        <!-- Update status -->
        <div class="border-t border-gray-100 pt-3 flex items-center gap-3">
          <label class="text-xs font-semibold text-gray-600 shrink-0">Update Status:</label>
          <select v-model="newStatus" class="input-base text-xs flex-1">
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <button @click="updateStatus" :disabled="savingStatus"
            class="px-3 py-2 text-xs font-bold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60 shrink-0">
            {{ savingStatus ? '…' : 'Save' }}
          </button>
        </div>
      </div>
      <template #footer>
        <button @click="showDetail = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Close</button>
      </template>
    </Modal>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import PageHeader        from '@/components/PageHeader.vue'
import Modal             from '@/components/Modal.vue'

const { get, patch } = useApi()
const toast = useToastStore()

const websiteBase = (import.meta.env.VITE_WEBSITE_URL ?? 'http://localhost:8006').replace(/\/$/, '')

const orders       = ref([])
const meta         = ref(null)
const loading      = ref(false)
const page         = ref(1)
const search       = ref('')
const filterStatus  = ref('')
const filterPayment = ref('')

const showDetail    = ref(false)
const detail        = ref(null)
const newStatus     = ref('')
const savingStatus  = ref(false)

const formatDate = d => d ? new Date(d).toLocaleDateString('en-KE', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : ''

function statusBadge(s) {
  return { pending:'bg-yellow-100 text-yellow-700', processing:'bg-blue-100 text-blue-700', shipped:'bg-purple-100 text-purple-700', delivered:'bg-green-100 text-green-700', cancelled:'bg-gray-100 text-gray-500' }[s] ?? 'bg-gray-100 text-gray-500'
}
function paymentBadge(s) {
  return { paid:'bg-green-100 text-green-700', pending:'bg-yellow-100 text-yellow-700', failed:'bg-red-100 text-red-600' }[s] ?? 'bg-gray-100 text-gray-500'
}

let debounceTimer
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; load() }, 350)
}

async function load() {
  loading.value = true
  try {
    const params = { page: page.value, per_page: 20 }
    if (search.value)        params.search         = search.value
    if (filterStatus.value)  params.status         = filterStatus.value
    if (filterPayment.value) params.payment_status = filterPayment.value
    const res = await get('/admin/orders', params)
    orders.value = res?.data ?? []
    meta.value   = res?.meta ?? null
  } catch { toast.error('Failed to load orders.') }
  finally { loading.value = false }
}

function openDetail(o) {
  detail.value = o; newStatus.value = o.status; showDetail.value = true
}

async function updateStatus() {
  if (!detail.value) return
  savingStatus.value = true
  try {
    const u = await patch(`/admin/orders/${detail.value.id}`, { status: newStatus.value })
    detail.value = { ...detail.value, ...u }
    const i = orders.value.findIndex(x => x.id === detail.value.id)
    if (i > -1) orders.value[i] = { ...orders.value[i], status: newStatus.value }
    toast.success('Order status updated.')
  } catch { toast.error('Failed to update.') }
  finally { savingStatus.value = false }
}

onMounted(load)
</script>

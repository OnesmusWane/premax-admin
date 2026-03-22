<template>
  <div class="p-4 md:p-6 space-y-4">

    <PageHeader title="Payments" subtitle="Invoices and payment records">
      <div class="flex items-center gap-2">
        <!-- Today summary chips -->
        <div v-if="todaySummary.count" class="hidden md:flex items-center gap-2">
          <span class="text-[10px] bg-green-50 text-green-700 font-bold px-3 py-1.5 rounded-full">
            Today: KES {{ todaySummary.total?.toLocaleString() }}
          </span>
          <span class="text-[10px] bg-blue-50 text-blue-700 font-semibold px-3 py-1.5 rounded-full">
            {{ todaySummary.count }} invoice{{ todaySummary.count !== 1 ? 's' : '' }}
          </span>
        </div>
      </div>
    </PageHeader>

    <!-- ── TOOLBAR ── -->
    <div class="flex flex-col md:flex-row items-start md:items-center md:justify-between gap-3 px-4 md:px-6">
      <SearchInput v-model="search" placeholder="Search invoice, customer, ref…" class="flex-1 max-w-xs" />

      <select v-model="statusFilter"
        class="border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">
        <option value="">All Statuses</option>
        <option value="paid">Paid</option>
        <option value="pending">Pending</option>
        <option value="cancelled">Cancelled</option>
      </select>

      <select v-model="methodFilter"
        class="border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">
        <option value="">All Methods</option>
        <option value="cash">Cash</option>
        <option value="mpesa">M-Pesa</option>
        <option value="card">Card</option>
        <option value="bank_transfer">Bank Transfer</option>
      </select>

      <input type="date" v-model="dateFilter"
        class="border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">

      <button v-if="statusFilter || methodFilter || dateFilter || search" @click="clearFilters"
        class="flex items-center gap-1 text-xs font-semibold text-gray-500 hover:text-gray-900 border border-gray-200 rounded-xl px-3 py-2 hover:bg-gray-50">
        <XMarkIcon class="w-3.5 h-3.5" /> Clear
      </button>
    </div>

    <!-- ── TABLE ── -->
    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm table-wrap">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th v-for="h in headers" :key="h"
              class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">{{ h }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading">
            <td :colspan="headers.length" class="px-4 py-12 text-center text-gray-400">
              <div class="flex items-center justify-center gap-2">
                <div class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin" />
                Loading…
              </div>
            </td>
          </tr>
          <tr v-for="inv in invoices.data" :key="inv.id"
            class="hover:bg-gray-50/60 transition-colors cursor-pointer" @click="openView(inv)">
            <td class="px-4 py-3.5">
              <div class="font-mono font-bold text-gray-900 text-xs">{{ inv.invoice_number }}</div>
              <div v-if="inv.booking_reference" class="text-[10px] text-red-500 font-semibold mt-0.5">
                {{ inv.booking_reference }}
              </div>
            </td>
            <td class="px-4 py-3.5">
              <div class="text-xs font-semibold text-gray-900">{{ inv.customer_name }}</div>
              <div class="text-[10px] text-gray-400">{{ inv.vehicle_reg }}</div>
            </td>
            <td class="px-4 py-3.5 text-xs font-bold text-gray-900">
              KES {{ inv.total?.toLocaleString() }}
            </td>
            <td class="px-4 py-3.5">
              <div class="flex items-center gap-1.5">
                <span :class="['inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full capitalize',
                  methodBadge(inv.payment_method).class]">
                  {{ inv.payment_method }}
                </span>
              </div>
              <div v-if="inv.mpesa_reference" class="text-[10px] text-gray-400 font-mono mt-0.5">{{ inv.mpesa_reference }}</div>
            </td>
            <td class="px-4 py-3.5 text-xs text-gray-500">{{ fmtDate(inv.paid_at ?? inv.created_at) }}</td>
            <td class="px-4 py-3.5">
              <span :class="['text-[10px] font-bold px-2.5 py-1 rounded-full',
                inv.status === 'paid'      ? 'bg-green-100 text-green-700'  :
                inv.status === 'cancelled' ? 'bg-red-100 text-red-700'      :
                'bg-yellow-100 text-yellow-700']">
                {{ inv.status }}
              </span>
            </td>
            <td class="px-4 py-3.5" @click.stop>
              <div class="flex items-center gap-2">
                <button @click="openView(inv)" class="text-xs font-semibold text-red-600 hover:underline">View</button>
                <span class="text-gray-200">·</span>
                <button @click="printInvoice(inv.id)" class="text-xs font-semibold text-gray-500 hover:text-gray-900">Print</button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && !invoices.data?.length">
            <td :colspan="headers.length" class="px-4 py-16 text-center">
              <div class="flex flex-col items-center gap-2 text-gray-400">
                <ReceiptPercentIcon class="w-8 h-8 text-gray-200" />
                <p class="text-sm">No invoices found.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="invoices.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
        <span class="text-xs text-gray-500">Showing {{ invoices.from }}–{{ invoices.to }} of {{ invoices.total }}</span>
        <div class="flex items-center gap-1">
          <button @click="page--" :disabled="page===1" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">Prev</button>
          <button v-for="p in pageNumbers" :key="p" @click="page=p"
            :class="['px-3 py-1.5 text-xs border rounded-lg', p===page ? 'bg-red-600 text-white border-red-600' : 'border-gray-200 hover:bg-gray-50']">{{ p }}</button>
          <button @click="page++" :disabled="page===invoices.last_page" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         MODAL: INVOICE DETAIL
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showView" title="Invoice Details" size="lg">
      <div v-if="detailLoading" class="py-12 text-center text-gray-400">
        <div class="w-6 h-6 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>

      <div v-else-if="selected" class="space-y-4">

        <!-- Invoice number + status -->
        <div class="flex items-center justify-between bg-gray-900 rounded-xl px-4 py-3">
          <div>
            <div class="text-[10px] text-gray-400 uppercase tracking-widest">Invoice</div>
            <div class="font-mono font-extrabold text-white text-base">{{ selected.invoice_number }}</div>
          </div>
          <span :class="['text-xs font-bold px-3 py-1.5 rounded-full',
            selected.status==='paid'      ? 'bg-green-500 text-white' :
            selected.status==='cancelled' ? 'bg-red-500 text-white'   :
            'bg-yellow-400 text-gray-900']">
            {{ selected.status?.toUpperCase() }}
          </span>
        </div>

        <!-- Customer + vehicle + booking -->
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-2">Customer</div>
            <div class="text-xs font-bold text-gray-900">{{ selected.customer?.name ?? '—' }}</div>
            <div class="text-[10px] text-gray-500">{{ selected.customer?.phone }}</div>
            <div class="text-[10px] text-gray-500">{{ selected.customer?.email }}</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-2">Vehicle</div>
            <div class="font-mono font-bold text-gray-900 text-xs">{{ selected.vehicle?.registration ?? '—' }}</div>
            <div class="text-[10px] text-gray-500">{{ selected.vehicle?.make }} {{ selected.vehicle?.model }}</div>
          </div>
        </div>

        <!-- Booking link -->
        <div class="border border-gray-100 rounded-xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center gap-2">
              <CalendarDaysIcon class="w-4 h-4 text-gray-400" />
              <span class="text-xs font-bold text-gray-700">Linked Booking</span>
            </div>
          </div>
          <div class="px-4 py-3">
            <div v-if="selected.booking" class="flex items-center justify-between">
              <div>
                <div class="font-mono font-bold text-red-600 text-xs">{{ selected.booking.reference }}</div>
                <div class="text-[10px] text-gray-500 mt-0.5">
                  {{ selected.booking.service?.name ?? '—' }} ·
                  {{ fmtDate(selected.booking.scheduled_at) }}
                </div>
              </div>
              <RouterLink :to="`/bookings`"
                class="text-[10px] font-bold text-blue-600 hover:underline">View Booking →</RouterLink>
            </div>
            <div v-else class="flex items-center justify-between">
              <span class="text-xs text-gray-400 italic">No booking linked</span>
              <button @click="showLinkBooking = true"
                class="text-[10px] font-bold text-red-600 border border-red-200 rounded-lg px-2 py-1 hover:bg-red-50">
                + Link Booking
              </button>
            </div>
          </div>
        </div>

        <!-- Payment details -->
        <div class="border border-gray-100 rounded-xl overflow-hidden">
          <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
            <span class="text-xs font-bold text-gray-700">Payment Details</span>
          </div>
          <div class="divide-y divide-gray-50">
            <div class="flex justify-between px-4 py-2.5 text-xs">
              <span class="text-gray-500">Method</span>
              <span class="font-semibold capitalize text-gray-900">{{ selected.payment_method }}</span>
            </div>
            <div v-if="selected.mpesa_reference" class="flex justify-between px-4 py-2.5 text-xs">
              <span class="text-gray-500">M-Pesa Ref</span>
              <span class="font-mono font-bold text-gray-900">{{ selected.mpesa_reference }}</span>
            </div>
            <div v-if="selected.card_reference" class="flex justify-between px-4 py-2.5 text-xs">
              <span class="text-gray-500">Card Ref</span>
              <span class="font-mono font-bold text-gray-900">{{ selected.card_reference }}</span>
            </div>
            <div class="flex justify-between px-4 py-2.5 text-xs">
              <span class="text-gray-500">Date Paid</span>
              <span class="font-semibold text-gray-900">{{ fmtDate(selected.paid_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Line items -->
        <div class="border border-gray-100 rounded-xl overflow-hidden">
          <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
            <span class="text-xs font-bold text-gray-700">Items</span>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-for="item in selected.items" :key="item.id"
              class="flex items-center justify-between px-4 py-2.5">
              <div>
                <div class="text-xs font-semibold text-gray-900">{{ item.description }}</div>
                <div class="text-[10px] text-gray-400">Qty {{ item.quantity }} × KES {{ item.unit_price?.toLocaleString() }}</div>
              </div>
              <div class="text-xs font-bold text-gray-900">KES {{ item.total?.toLocaleString() }}</div>
            </div>
          </div>
          <!-- Totals -->
          <div class="divide-y divide-gray-100 border-t border-gray-100">
            <div class="flex justify-between px-4 py-2 text-xs text-gray-500">
              <span>Subtotal</span><span>KES {{ selected.subtotal?.toLocaleString() }}</span>
            </div>
            <div v-if="selected.discount > 0" class="flex justify-between px-4 py-2 text-xs text-green-600">
              <span>Discount</span><span>− KES {{ selected.discount?.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between px-4 py-2 text-xs text-gray-500">
              <span>VAT ({{ selected.vat_percent }}%)</span><span>KES {{ selected.vat_amount?.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between px-4 py-3 text-sm font-extrabold text-gray-900">
              <span>Total</span><span>KES {{ selected.total?.toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selected.notes" class="bg-gray-50 rounded-xl px-4 py-3">
          <div class="text-[10px] text-gray-400 uppercase font-semibold mb-1">Notes</div>
          <p class="text-xs text-gray-700">{{ selected.notes }}</p>
        </div>

        <!-- Update status -->
        <div class="flex flex-col gap-2">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Update Status</span>
          <div class="flex flex-wrap gap-2">
            <button v-for="s in ['paid','pending','cancelled']" :key="s"
              @click="updateStatus(s)"
              :disabled="selected.status === s || updatingStatus"
              :class="['px-3 py-1.5 text-[10px] font-bold rounded-xl border-2 transition-all capitalize disabled:opacity-40',
                selected.status === s
                  ? s==='paid'      ? 'bg-green-500 border-green-500 text-white'
                  : s==='cancelled' ? 'bg-red-500 border-red-500 text-white'
                  : 'bg-yellow-400 border-yellow-400 text-gray-900'
                  : 'border-gray-200 text-gray-600 hover:border-gray-400']">
              {{ updatingStatus && pendingStatus === s ? 'Saving…' : s }}
            </button>
          </div>
        </div>

      </div>

      <template #footer>
        <div class="flex items-center justify-between w-full">
          <button @click="showView = false"
            class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Close</button>
          <button @click="printInvoice(selected?.id)"
            class="flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-gray-900 text-white rounded-xl hover:bg-gray-700">
            <PrinterIcon class="w-3.5 h-3.5" /> Print Invoice
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: LINK BOOKING
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showLinkBooking" title="Link to Booking" size="sm">
      <div class="space-y-3">
        <p class="text-xs text-gray-500">Search for a booking to link to this invoice.</p>
        <div class="flex gap-2">
          <input v-model="bookingSearch" class="input-base flex-1" placeholder="Booking ref or customer name…"
            @keyup.enter="searchBookings">
          <button @click="searchBookings" :disabled="searchingBookings"
            class="px-4 py-2 text-xs font-bold bg-gray-900 text-white rounded-xl hover:bg-gray-700 disabled:opacity-60 shrink-0">
            {{ searchingBookings ? '…' : 'Search' }}
          </button>
        </div>
        <div v-if="bookingResults.length" class="space-y-2 max-h-64 overflow-y-auto">
          <button v-for="b in bookingResults" :key="b.id"
            @click="linkBooking(b)"
            class="w-full text-left flex items-center justify-between bg-gray-50 hover:bg-red-50 border border-gray-100 hover:border-red-300 rounded-xl px-4 py-3 transition-all">
            <div>
              <div class="font-mono text-xs font-bold text-red-600">{{ b.reference }}</div>
              <div class="text-[10px] text-gray-500">{{ b.customer.name }} · {{ b.vehicle.registration }}</div>
              <div class="text-[10px] text-gray-400">{{ b.service.name }} · {{ b.scheduled_date }}</div>
            </div>
            <span class="text-[10px] font-bold text-blue-600">Link →</span>
          </button>
        </div>
        <p v-if="hasSearched && !bookingResults.length" class="text-xs text-gray-400 text-center py-4">
          No bookings found for "{{ bookingSearch }}"
        </p>
      </div>
      <template #footer>
        <div class="flex justify-end">
          <button @click="showLinkBooking = false"
            class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
        </div>
      </template>
    </Modal>

  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import {
  XMarkIcon, ReceiptPercentIcon, PrinterIcon, CalendarDaysIcon,
} from '@heroicons/vue/24/outline'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import PageHeader        from '@/components/PageHeader.vue'
import SearchInput       from '@/components/SearchInput.vue'
import Modal             from '@/components/Modal.vue'

const { get, patch, loading } = useApi()
const toast = useToastStore()

// ── State ──────────────────────────────────────────────────────────────────────
const invoices      = ref({ data:[], last_page:1, total:0, from:1, to:0 })
const todaySummary  = ref({ total:0, count:0, cash:0, mpesa:0 })
const search        = ref('')
const statusFilter  = ref('')
const methodFilter  = ref('')
const dateFilter    = ref('')
const page          = ref(1)

const showView       = ref(false)
const detailLoading  = ref(false)
const selected       = ref(null)
const updatingStatus = ref(false)
const pendingStatus  = ref('')

const showLinkBooking   = ref(false)
const bookingSearch     = ref('')
const bookingResults    = ref([])
const searchingBookings = ref(false)
const hasSearched       = ref(false)

const headers     = ['Invoice #','Customer / Vehicle','Amount','Method','Date','Status','Actions']
const pageNumbers = computed(() => Array.from({length:Math.min(invoices.value.last_page,5)},(_,i)=>i+1))

const fmtDate = d => d ? new Date(d).toLocaleDateString('en-KE',{day:'2-digit',month:'short',year:'numeric'}) : '—'

const methodBadge = method => ({
  cash:          { class:'bg-green-100 text-green-700' },
  mpesa:         { class:'bg-emerald-100 text-emerald-700' },
  card:          { class:'bg-blue-100 text-blue-700' },
  bank_transfer: { class:'bg-purple-100 text-purple-700' },
}[method] ?? { class:'bg-gray-100 text-gray-600' })

// ── Load ───────────────────────────────────────────────────────────────────────
async function load() {
  const data = await get('/admin/invoices', {
    page:    page.value,
    search:  search.value,
    status:  statusFilter.value,
    method:  methodFilter.value,
    date:    dateFilter.value,
  })
  invoices.value = data
}

async function loadTodaySummary() {
  todaySummary.value = await get('/admin/invoices/today') ?? {}
}

function clearFilters() {
  search.value=''; statusFilter.value=''; methodFilter.value=''; dateFilter.value=''
  page.value=1; load()
}

// ── View invoice detail ────────────────────────────────────────────────────────
async function openView(inv) {
  showView.value    = true
  detailLoading.value = true
  selected.value    = null
  try {
    selected.value = await get(`/admin/invoices/${inv.id}`)
  } catch { toast.error('Failed to load invoice.') }
  finally { detailLoading.value = false }
}

function printInvoice(id) {
  if (id) window.open(`/print/invoice/${id}`, '_blank')
}

// ── Update status ──────────────────────────────────────────────────────────────
async function updateStatus(status) {
  updatingStatus.value = true
  pendingStatus.value  = status
  try {
    const updated = await patch(`/admin/invoices/${selected.value.id}/status`, { status })
    selected.value.status = updated.status
    // Update in table too
    const idx = invoices.value.data.findIndex(i => i.id === selected.value.id)
    if (idx > -1) invoices.value.data[idx].status = updated.status
    toast.success(`Invoice marked as ${status}.`)
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to update status.')
  } finally { updatingStatus.value = false; pendingStatus.value = '' }
}

// ── Link booking ───────────────────────────────────────────────────────────────
async function searchBookings() {
  if (!bookingSearch.value.trim()) return
  searchingBookings.value = true; hasSearched.value = true
  try {
    const data = await get('/admin/bookings', { search: bookingSearch.value, per_page: 10 })
    bookingResults.value = data?.data ?? []
  } catch { toast.error('Failed to search bookings.') }
  finally { searchingBookings.value = false }
}

async function linkBooking(booking) {
  try {
    await patch(`/admin/invoices/${selected.value.id}/link-booking`, { booking_id: booking.id })
    selected.value.booking = booking
    showLinkBooking.value  = false
    bookingSearch.value    = ''
    bookingResults.value   = []
    hasSearched.value      = false
    toast.success('Invoice linked to booking.')
    load()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to link booking.')
  }
}

watch([page, statusFilter, methodFilter, dateFilter], load)
watch(search, () => { page.value=1; load() })
onMounted(() => { load(); loadTodaySummary() })
</script>
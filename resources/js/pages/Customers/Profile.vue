<template>
  <div class="p-4 md:p-6 space-y-4">

    <!-- Profile header -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
      <div v-if="loading && !customer" class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full animate-pulse bg-gray-200" />
        <div class="space-y-2 flex-1">
          <div class="h-5 w-40 animate-pulse bg-gray-200 rounded" />
          <div class="h-3 w-64 animate-pulse bg-gray-100 rounded" />
        </div>
      </div>

      <div v-else-if="customer" class="flex items-start justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-full flex items-center justify-center text-white font-extrabold text-xl shrink-0"
            :style="`background:${avatarColor(customer.name)}`">
            {{ initials(customer.name) }}
          </div>
          <div>
            <h2 class="text-xl font-extrabold text-gray-900">{{ customer.name }}</h2>
            <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-gray-500">
              <span class="flex items-center gap-1"><PhoneIcon class="w-3.5 h-3.5" /> {{ customer.phone }}</span>
              <span v-if="customer.email" class="flex items-center gap-1"><EnvelopeIcon class="w-3.5 h-3.5" /> {{ customer.email }}</span>
              <span v-if="customer.member_since" class="flex items-center gap-1">
                <CalendarIcon class="w-3.5 h-3.5" /> Member since {{ fmtDate(customer.member_since) }}
              </span>
            </div>
            <div v-if="customer.notes" class="text-xs text-gray-400 mt-1 italic">{{ customer.notes }}</div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button @click="openBookingModal"
            class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-2 rounded-xl transition-colors">
            <CalendarIcon class="w-3.5 h-3.5" /> New Booking
          </button>
          <button @click="openEditCustomer"
            class="flex items-center gap-1.5 border border-gray-200 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-gray-50">
            <PencilIcon class="w-3.5 h-3.5" /> Edit Profile
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex items-center gap-0 mt-5 border-b border-gray-100">
        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
          :class="['px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors',
            activeTab === tab.key ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-800']">
          {{ tab.label }}
          <span v-if="tab.count" class="ml-1 text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">{{ tab.count }}</span>
        </button>
      </div>
    </div>

    <!-- ── TAB: Overview ── -->
    <div v-if="activeTab === 'overview'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
          <CalendarIcon class="w-5 h-5 text-red-600" />
        </div>
        <div>
          <div class="text-xs text-gray-500">Total Visits</div>
          <div class="text-2xl font-extrabold text-gray-900">{{ stats.total_visits ?? 0 }}</div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
          <BanknotesIcon class="w-5 h-5 text-green-600" />
        </div>
        <div>
          <div class="text-xs text-gray-500">Total Spent</div>
          <div class="text-2xl font-extrabold text-gray-900">KES {{ stats.total_spent?.toLocaleString() ?? 0 }}</div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
          <WrenchScrewdriverIcon class="w-5 h-5 text-purple-600" />
        </div>
        <div>
          <div class="text-xs text-gray-500">Favourite Service</div>
          <div class="text-base font-extrabold text-gray-900 leading-tight">{{ stats.favorite_service ?? '—' }}</div>
        </div>
      </div>
    </div>

    <!-- ── TAB: Vehicles ── -->
    <div v-if="activeTab === 'vehicles'">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="v in vehicles" :key="v.id"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-all cursor-pointer group"
          @click="$router.push(`/vehicles/${v.id}`)">
          <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 bg-gray-900 text-white rounded-xl flex items-center justify-center">
              <TruckIcon class="w-5 h-5" />
            </div>
            <div class="text-right">
              <div class="font-mono font-bold text-sm text-gray-900">{{ v.registration }}</div>
              <div class="text-[10px] text-gray-400 capitalize">{{ v.color }}</div>
            </div>
          </div>
          <div class="font-semibold text-gray-900 text-sm">{{ v.make }} {{ v.model }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ v.year ?? '—' }}</div>
          <div class="flex items-center justify-between mt-3">
            <span v-if="v.last_service_at" class="text-[10px] text-green-600 font-semibold">
              Last: {{ fmtDate(v.last_service_at) }}
            </span>
            <span v-else class="text-[10px] text-gray-400">Never serviced</span>
            <span class="text-[10px] text-blue-600 font-semibold group-hover:underline">View →</span>
          </div>
        </div>

        <!-- Add vehicle card -->
        <button @click="openAddVehicle"
          class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-5 flex flex-col items-center justify-center gap-2 text-gray-400 hover:border-red-400 hover:text-red-500 transition-colors min-h-[140px]">
          <PlusCircleIcon class="w-8 h-8" />
          <span class="text-xs font-semibold">Add Vehicle</span>
        </button>
      </div>
    </div>

    <!-- ── TAB: Service History (Bookings) ── -->
    <div v-if="activeTab === 'history'">
      <div v-if="loadingHistory" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading history…
      </div>
      <div v-else-if="!history.length" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400">
        No service history found.
      </div>
      <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-xs">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th v-for="h in ['Reference','Date','Vehicle','Service','Invoice','Status']" :key="h"
                class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase">{{ h }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="b in history" :key="b.id" class="hover:bg-gray-50/60">
              <td class="px-4 py-3 font-mono font-bold text-red-600">{{ b.reference }}</td>
              <td class="px-4 py-3 text-gray-600">{{ fmtDate(b.scheduled_at) }}</td>
              <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ b.vehicle?.registration ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-700">{{ b.service?.name ?? '—' }}</td>
              <td class="px-4 py-3">
                <div v-if="b.invoices?.length" class="space-y-0.5">
                  <div v-for="inv in b.invoices" :key="inv.id" class="flex items-center gap-1.5">
                    <span class="font-semibold text-gray-900">KES {{ inv.total?.toLocaleString() }}</span>
                    <span class="text-gray-400 capitalize">· {{ inv.payment_method }}</span>
                    <button @click="printInvoice(inv.id)"
                      class="text-[10px] text-blue-500 hover:underline">Print</button>
                  </div>
                </div>
                <span v-else class="text-gray-400 italic">—</span>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
                  :style="`background:${statusColor(b.status?.slug)}20; color:${statusColor(b.status?.slug)}`">
                  <span class="w-1.5 h-1.5 rounded-full" :style="`background:${statusColor(b.status?.slug)}`" />
                  {{ b.status?.name ?? '—' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── TAB: Invoices ── -->
    <div v-if="activeTab === 'invoices'">
      <div v-if="loadingInvoices" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading invoices…
      </div>
      <div v-else-if="!invoices.length" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400">
        No invoices found.
      </div>
      <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-xs">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th v-for="h in ['Invoice #','Booking Ref','Amount','Method','Date','Status','Actions']" :key="h"
                class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase">{{ h }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-gray-50/60">
              <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ inv.invoice_number }}</td>
              <td class="px-4 py-3 font-mono text-red-600 font-semibold">{{ inv.booking?.reference ?? '—' }}</td>
              <td class="px-4 py-3 font-semibold text-gray-900">KES {{ inv.total?.toLocaleString() }}</td>
              <td class="px-4 py-3 capitalize text-gray-600">
                {{ inv.payment_method }}
                <div v-if="inv.mpesa_reference" class="text-[10px] text-gray-400">{{ inv.mpesa_reference }}</div>
              </td>
              <td class="px-4 py-3 text-gray-500">{{ fmtDate(inv.paid_at) }}</td>
              <td class="px-4 py-3">
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full',
                  inv.status==='paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700']">
                  {{ inv.status }}
                </span>
              </td>
              <td class="px-4 py-3">
                <button @click="printInvoice(inv.id)" class="text-[10px] font-bold text-blue-600 hover:underline">Print</button>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- Totals footer -->
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50">
          <span class="text-xs text-gray-500">{{ invoices.length }} invoice{{ invoices.length !== 1 ? 's' : '' }}</span>
          <span class="text-xs font-extrabold text-gray-900">
            Total: KES {{ invoices.filter(i=>i.status==='paid').reduce((s,i)=>s+(i.total??0),0).toLocaleString() }}
          </span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         MODAL: EDIT CUSTOMER
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showEditCustomer" title="Edit Customer Profile">
      <form class="space-y-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Full Name <span class="text-red-500">*</span></label>
          <input v-model="editForm.name" required class="input-base" placeholder="John Doe">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Phone Number <span class="text-red-500">*</span></label>
          <input v-model="editForm.phone" required class="input-base" placeholder="+254 700 000000">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Email <span class="text-gray-400 font-normal">(Optional)</span></label>
          <input v-model="editForm.email" type="email" class="input-base" placeholder="john@example.com">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
          <textarea v-model="editForm.notes" rows="2" class="input-base resize-none" placeholder="Any notes about this customer…" />
        </div>
        <div v-if="editError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ editError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showEditCustomer = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="saveCustomer" :disabled="savingCustomer"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ savingCustomer ? 'Saving…' : 'Save Changes' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: ADD / EDIT VEHICLE
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showVehicleModal" :title="editingVehicle ? 'Edit Vehicle' : 'Add Vehicle'" size="md">
      <form class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5 col-span-2">
            <label class="text-xs font-semibold text-gray-600">Registration <span class="text-red-500">*</span></label>
            <input v-model="vehicleForm.registration" required class="input-base font-mono uppercase" placeholder="KCA 123A">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Make <span class="text-red-500">*</span></label>
            <input v-model="vehicleForm.make" required class="input-base" placeholder="Toyota">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Model <span class="text-red-500">*</span></label>
            <input v-model="vehicleForm.model" required class="input-base" placeholder="Hilux">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Year</label>
            <input v-model.number="vehicleForm.year" type="number" class="input-base" placeholder="2020">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Color</label>
            <input v-model="vehicleForm.color" class="input-base" placeholder="White">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Engine Number</label>
            <input v-model="vehicleForm.engine_number" class="input-base font-mono" placeholder="Optional">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Chassis Number</label>
            <input v-model="vehicleForm.chassis_number" class="input-base font-mono" placeholder="Optional">
          </div>
        </div>
        <div v-if="vehicleError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ vehicleError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showVehicleModal = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="saveVehicle" :disabled="savingVehicle"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ savingVehicle ? 'Saving…' : editingVehicle ? 'Save Vehicle' : 'Add Vehicle' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: NEW BOOKING
    ══════════════════════════════════════════════════ -->
    <QuickBookingModal v-model="showBookingModal" :customer="customer" @saved="onBookingSaved" />

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  PhoneIcon, EnvelopeIcon, CalendarIcon, PencilIcon,
  BanknotesIcon, TruckIcon, PlusCircleIcon, WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import Modal             from '@/components/Modal.vue'
import QuickBookingModal from '@/components/QuickBookingModal.vue'

const route = useRoute()
const { get, post, put, loading } = useApi()
const toast = useToastStore()

// ── State ──────────────────────────────────────────────────────────────────────
const customer        = ref(null)
const stats           = ref({ total_visits:0, total_spent:0, favorite_service:null })
const vehicles        = ref([])
const history         = ref([])
const invoices        = ref([])
const activeTab       = ref('overview')
const loadingHistory  = ref(false)
const loadingInvoices = ref(false)

// Edit customer
const showEditCustomer = ref(false)
const savingCustomer   = ref(false)
const editError        = ref(null)
const editForm         = ref({ name:'', phone:'', email:'', notes:'' })

// Vehicle modal
const showVehicleModal = ref(false)
const savingVehicle    = ref(false)
const vehicleError     = ref(null)
const editingVehicle   = ref(null)
const vehicleForm      = ref({ registration:'', make:'', model:'', year:'', color:'', engine_number:'', chassis_number:'' })

// Booking modal
const showBookingModal = ref(false)

// ── Helpers ────────────────────────────────────────────────────────────────────
const fmtDate  = d => d ? new Date(d).toLocaleDateString('en-KE',{day:'2-digit',month:'short',year:'numeric'}) : '—'
const initials = name => name?.split(' ').slice(0,2).map(w=>w[0]?.toUpperCase()).join('') ?? '?'
const colors   = ['#EF4444','#3B82F6','#22C55E','#A855F7','#F97316','#EC4899','#14B8A6','#EAB308']
const avatarColor = name => colors[(name?.charCodeAt(0)??0) % colors.length]
const statusColors = { pending:'#EAB308',confirmed:'#3B82F6',in_progress:'#F97316',completed:'#22C55E',cancelled:'#EF4444',no_show:'#6B7280' }
const statusColor  = slug => statusColors[slug] ?? '#6B7280'
const printInvoice = id => window.open(`/print/invoice/${id}`, '_blank')

const tabs = computed(() => [
  { key:'overview',  label:'Overview' },
  { key:'vehicles',  label:'Vehicles',        count: vehicles.value.length || null },
  { key:'history',   label:'Service History', count: history.value.length  || null },
  { key:'invoices',  label:'Invoices',        count: invoices.value.length  || null },
])

// ── Load data ──────────────────────────────────────────────────────────────────
async function loadProfile() {
  const data = await get(`/admin/customers/${route.params.id}`)
  customer.value  = data
  vehicles.value  = data.vehicles ?? []
  stats.value = {
    total_visits:    data.bookings_count  ?? 0,
    total_spent:     data.total_spent     ?? 0,
    favorite_service: data.favorite_service ?? null,
  }
}

async function loadHistory() {
  loadingHistory.value = true
  try {
    history.value = await get(`/admin/customers/${route.params.id}/service-history`) ?? []
  } finally { loadingHistory.value = false }
}

async function loadInvoices() {
  loadingInvoices.value = true
  try {
    invoices.value = await get(`/admin/customers/${route.params.id}/invoices`) ?? []
  } finally { loadingInvoices.value = false }
}

watch(activeTab, tab => {
  if (tab === 'history'  && !history.value.length)  loadHistory()
  if (tab === 'invoices' && !invoices.value.length)  loadInvoices()
})

onMounted(async () => {
  await loadProfile()
  loadHistory()   // load eagerly
})

// ── Edit customer ──────────────────────────────────────────────────────────────
function openEditCustomer() {
  editForm.value = { name:customer.value.name, phone:customer.value.phone, email:customer.value.email??'', notes:customer.value.notes??'' }
  editError.value = null
  showEditCustomer.value = true
}

async function saveCustomer() {
  savingCustomer.value = true; editError.value = null
  try {
    const updated = await put(`/admin/customers/${route.params.id}`, editForm.value)
    customer.value = { ...customer.value, ...updated }
    showEditCustomer.value = false
    toast.success('Customer profile updated.')
  } catch (e) { editError.value = e.response?.data?.message ?? 'Failed to save.' }
  finally { savingCustomer.value = false }
}

// ── Vehicles ───────────────────────────────────────────────────────────────────
function openAddVehicle() {
  editingVehicle.value = null
  vehicleForm.value = { registration:'', make:'', model:'', year:'', color:'', engine_number:'', chassis_number:'' }
  vehicleError.value = null
  showVehicleModal.value = true
}

function openEditVehicle(v) {
  editingVehicle.value = v
  vehicleForm.value = {
    registration: v.registration,
    make:         v.make,
    model:        v.model,
    year:         v.year ?? '',
    color:        v.color ?? '',
    engine_number:  v.engine_number  ?? '',
    chassis_number: v.chassis_number ?? '',
  }
  vehicleError.value = null
  showVehicleModal.value = true
}

async function saveVehicle() {
  savingVehicle.value = true; vehicleError.value = null
  try {
    if (editingVehicle.value) {
      const updated = await put(`/admin/vehicles/${editingVehicle.value.id}`, vehicleForm.value)
      const idx = vehicles.value.findIndex(v => v.id === editingVehicle.value.id)
      if (idx > -1) vehicles.value[idx] = { ...vehicles.value[idx], ...updated }
      toast.success('Vehicle updated.')
    } else {
      const created = await post('/admin/vehicles', {
        ...vehicleForm.value,
        customer_id: route.params.id,
      })
      vehicles.value.push(created)
      toast.success('Vehicle added.')
    }
    showVehicleModal.value = false
  } catch (e) { vehicleError.value = e.response?.data?.message ?? 'Failed to save vehicle.' }
  finally { savingVehicle.value = false }
}

// ── Booking ────────────────────────────────────────────────────────────────────
function openBookingModal() {
  showBookingModal.value = true
}

async function onBookingSaved() {
  showBookingModal.value = false
  toast.success('Booking created.')
  // Refresh history
  history.value = []
  loadHistory()
  loadProfile()
}
</script>
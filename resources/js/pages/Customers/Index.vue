<!-- ═══════════════════════════════════════════════════════
     resources/js/pages/Customers/Index.vue
═══════════════════════════════════════════════════════ -->
<template>
  <div class="p-4 md:p-6 space-y-4">
    <PageHeader title="Customers" subtitle="Manage your customer database">
      <button @click="openAdd"
        class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors">
        <PlusIcon class="w-4 h-4" /> Add Customer
      </button>
    </PageHeader>

    <div class="flex flex-wrap items-center gap-3 px-4 md:px-6">
      <SearchInput v-model="search" placeholder="Search by name, phone, email..." class="flex-1 max-w-xs" />
    </div>

    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm  table-wrap">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th v-for="h in ['Customer','Contact','Vehicles','Total Visits','Member Since','Actions']" :key="h"
              class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">{{ h }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading">
            <td colspan="6" class="px-4 py-12 text-center text-gray-400">
              <div class="flex items-center justify-center gap-2">
                <div class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin" />
                Loading…
              </div>
            </td>
          </tr>
          <tr v-for="c in customers.data" :key="c.id"
            class="hover:bg-gray-50/60 transition-colors cursor-pointer" @click="$router.push(`/customers/${c.id}`)">
            <td class="px-4 py-3.5">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                  :style="`background:${avatarColor(c.name)}`">
                  {{ initials(c.name) }}
                </div>
                <div>
                  <div class="font-semibold text-gray-900 text-xs">{{ c.name }}</div>
                  <div v-if="c.notes" class="text-[10px] text-gray-400 truncate max-w-[140px]">{{ c.notes }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3.5">
              <div class="text-xs text-gray-700">{{ c.phone }}</div>
              <div class="text-[10px] text-gray-400">{{ c.email ?? '—' }}</div>
            </td>
            <td class="px-4 py-3.5">
              <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 text-gray-700 rounded-full text-xs font-bold">
                {{ c.vehicles_count ?? 0 }}
              </span>
            </td>
            <td class="px-4 py-3.5 text-xs text-gray-700">{{ c.bookings_count ?? '—' }}</td>
            <td class="px-4 py-3.5 text-xs text-gray-500">{{ c.member_since ? fmtDate(c.member_since) : '—' }}</td>
            <td class="px-4 py-3.5" @click.stop>
              <div class="flex items-center gap-2">
                <RouterLink :to="`/customers/${c.id}`" class="text-xs font-semibold text-red-600 hover:underline">View</RouterLink>
                <span class="text-gray-200">·</span>
                <button @click="openEdit(c)" class="text-xs font-semibold text-gray-500 hover:text-gray-900">Edit</button>
                <span class="text-gray-200">·</span>
                <button @click="openBooking(c)" class="text-xs font-semibold text-green-600 hover:underline">+ Book</button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && !customers.data?.length">
            <td colspan="6" class="px-4 py-12 text-center text-gray-400">No customers found.</td>
          </tr>
        </tbody>
      </table>
      <div v-if="customers.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
        <span class="text-xs text-gray-500">Showing {{ customers.from }}–{{ customers.to }} of {{ customers.total }}</span>
        <div class="flex gap-1">
          <button @click="page--" :disabled="page===1" class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Prev</button>
          <button @click="page++" :disabled="page===customers.last_page" class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>

    <!-- Add / Edit Customer Modal -->
    <Modal v-model="showForm" :title="editingCustomer ? 'Edit Customer' : 'Add Customer'">
      <form class="space-y-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Full Name <span class="text-red-500">*</span></label>
          <input v-model="form.name" required class="input-base" placeholder="John Doe">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Phone Number <span class="text-red-500">*</span></label>
          <input v-model="form.phone" required class="input-base" placeholder="+254 700 000000">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Email <span class="text-gray-400 font-normal">(Optional)</span></label>
          <input v-model="form.email" type="email" class="input-base" placeholder="john@example.com">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
          <textarea v-model="form.notes" rows="2" class="input-base resize-none" placeholder="Any notes about this customer…" />
        </div>
        <div v-if="formError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ formError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showForm = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="saveCustomer" :disabled="saving"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ saving ? 'Saving…' : editingCustomer ? 'Save Changes' : 'Add Customer' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- Quick Booking Modal -->
    <QuickBookingModal v-model="showBooking" :customer="selectedCustomer" @saved="load" />

  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import PageHeader        from '@/components/PageHeader.vue'
import SearchInput       from '@/components/SearchInput.vue'
import Modal             from '@/components/Modal.vue'
import QuickBookingModal from '@/components/QuickBookingModal.vue'

const { get, post, put, loading } = useApi()
const toast = useToastStore()

const customers       = ref({ data:[], last_page:1, total:0, from:1, to:0 })
const search          = ref('')
const page            = ref(1)
const showForm        = ref(false)
const showBooking     = ref(false)
const saving          = ref(false)
const formError       = ref(null)
const editingCustomer = ref(null)
const selectedCustomer = ref(null)
const form            = ref({ name:'', phone:'', email:'', notes:'' })

const fmtDate  = d => d ? new Date(d).toLocaleDateString('en-KE',{day:'2-digit',month:'short',year:'numeric'}) : '—'
const initials = name => name?.split(' ').slice(0,2).map(w=>w[0]?.toUpperCase()).join('') ?? '?'
const colors   = ['#EF4444','#3B82F6','#22C55E','#A855F7','#F97316','#EC4899','#14B8A6','#EAB308']
const avatarColor = name => colors[(name?.charCodeAt(0) ?? 0) % colors.length]

async function load() {
  showBooking.value = false
  customers.value = await get('/admin/customers', { page:page.value, search:search.value })
}

function openAdd() {
  editingCustomer.value = null
  form.value = { name:'', phone:'', email:'', notes:'' }
  formError.value = null
  showForm.value = true
}

function openEdit(c) {
  editingCustomer.value = c
  form.value = { name:c.name, phone:c.phone, email:c.email??'', notes:c.notes??'' }
  formError.value = null
  showForm.value = true
}

function openBooking(c) {
  selectedCustomer.value = c
  showBooking.value = true
}

async function saveCustomer() {
  saving.value = true; formError.value = null
  try {
    if (editingCustomer.value) {
      await put(`/admin/customers/${editingCustomer.value.id}`, form.value)
      toast.success('Customer updated.')
    } else {
      await post('/admin/customers', form.value)
      toast.success('Customer added.')
    }
    showForm.value = false
    load()
  } catch(e) { formError.value = e.response?.data?.message ?? 'Failed to save.' }
  finally { saving.value = false }
}

watch([page], load)
watch(search, ()=>{ page.value=1; load() })
onMounted(load)
</script>
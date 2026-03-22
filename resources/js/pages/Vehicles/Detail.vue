<template>
  <div class="p-4 md:p-6 space-y-4">

    <!-- Vehicle header card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
      <div v-if="loading && !vehicle" class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-2xl animate-pulse bg-gray-100" />
        <div class="space-y-2 flex-1">
          <div class="h-5 w-40 animate-pulse bg-gray-200 rounded" />
          <div class="h-3 w-64 animate-pulse bg-gray-100 rounded" />
        </div>
      </div>

      <div v-else-if="vehicle" class="flex items-start justify-between flex-wrap gap-4">
        <!-- Reg plate + make/model -->
        <div class="flex items-center gap-4">
          <div class="bg-gray-900 text-white font-mono font-extrabold px-4 py-3 rounded-2xl leading-tight text-center shrink-0">
            <div class="text-base tracking-widest">{{ regParts[0] }}</div>
            <div v-if="regParts[1]" class="text-sm tracking-wider">{{ regParts[1] }}</div>
          </div>
          <div>
            <h2 class="text-xl font-extrabold text-gray-900">
              {{ vehicle.make }} {{ vehicle.model }}
              <span v-if="vehicle.year" class="text-gray-400 font-normal text-sm">({{ vehicle.year }})</span>
            </h2>
            <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-gray-500">
              <span v-if="vehicle.color" class="capitalize">🎨 {{ vehicle.color }}</span>
              <span>Owner:
                <RouterLink :to="`/customers/${vehicle.customer?.id}`" class="text-red-600 hover:underline font-semibold">
                  {{ vehicle.customer?.name }}
                </RouterLink>
              </span>
              <span v-if="vehicle.customer?.phone">📞 {{ vehicle.customer.phone }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-3 mt-2">
              <!-- Stats chips -->
              <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-1 rounded-full font-semibold">
                {{ totalBookings }} service{{ totalBookings !== 1 ? 's' : '' }}
              </span>
              <span v-if="vehicle.last_service_at"
                class="text-[10px] bg-green-50 text-green-700 px-2 py-1 rounded-full font-semibold">
                Last: {{ fmtDate(vehicle.last_service_at) }}
              </span>
              <span v-else class="text-[10px] bg-yellow-50 text-yellow-700 px-2 py-1 rounded-full font-semibold">
                Never serviced
              </span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2">
          <button @click="showEditVehicle = true"
            class="flex items-center gap-1.5 border border-gray-200 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-gray-50">
            <PencilIcon class="w-3.5 h-3.5" /> Edit
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex items-center gap-0 mt-5 border-b border-gray-100">
        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
          :class="['px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors',
            activeTab === tab.key ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-800']">
          {{ tab.label }}
          <span v-if="tab.count !== undefined"
            class="ml-1.5 text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full font-bold">
            {{ tab.count }}
          </span>
        </button>
      </div>
    </div>

    <!-- ── TAB: OVERVIEW ── -->
    <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Vehicle details -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Vehicle Details</h3>
        <dl class="space-y-3">
          <div v-for="[label, val] in detailRows" :key="label"
            class="flex items-center justify-between border-b border-gray-50 pb-2.5 last:border-0">
            <dt class="text-xs text-gray-500">{{ label }}</dt>
            <dd class="text-xs font-bold text-gray-900">{{ val || '—' }}</dd>
          </div>
        </dl>
      </div>

      <!-- Summary stats -->
      <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <h3 class="text-sm font-bold text-gray-900 mb-4">Service Summary</h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-50 rounded-xl p-4 text-center">
              <div class="text-2xl font-extrabold text-gray-900">{{ totalBookings }}</div>
              <div class="text-[10px] text-gray-500 mt-1">Total Visits</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 text-center">
              <div class="text-2xl font-extrabold text-gray-900">{{ totalChecklists }}</div>
              <div class="text-[10px] text-gray-500 mt-1">Checklists</div>
            </div>
            <div class="bg-green-50 rounded-xl p-4 text-center col-span-2">
              <div class="text-xl font-extrabold text-green-800">KES {{ totalSpent.toLocaleString() }}</div>
              <div class="text-[10px] text-green-600 mt-1">Total Spent</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── TAB: SERVICE HISTORY ── -->
    <div v-if="activeTab === 'history'" class="space-y-3">

      <div v-if="loadingHistory" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading history…
      </div>

      <div v-else-if="!history.length" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400">
        No service history recorded.
      </div>

      <!-- Each booking as a history card -->
      <div v-for="b in history" :key="b.id"
        class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        <!-- Card header -->
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 bg-gray-50/50">
          <div class="flex items-center gap-3">
            <span class="font-mono text-xs font-bold text-red-600">{{ b.reference }}</span>
            <span class="text-xs text-gray-500">{{ fmtDate(b.scheduled_at) }}</span>
            <span class="text-xs text-gray-500">{{ fmtTime(b.scheduled_at) }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full"
              :style="`background:${statusColor(b.status?.slug)}20; color:${statusColor(b.status?.slug)}`">
              <span class="w-1.5 h-1.5 rounded-full" :style="`background:${statusColor(b.status?.slug)}`" />
              {{ b.status?.name }}
            </span>
          </div>
        </div>

        <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">

          <!-- Service info -->
          <div>
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-1">Service</div>
            <div class="text-sm font-bold text-gray-900">{{ b.service?.name ?? '—' }}</div>
            <div v-if="b.source" class="text-[10px] text-gray-400 mt-0.5 capitalize">via {{ b.source }}</div>
          </div>

          <!-- Invoice/Payment -->
          <div>
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-1">Payment</div>
            <div v-if="b.invoice" class="space-y-0.5">
              <div class="text-xs font-bold text-gray-900">{{ b.invoice.invoice_number }}</div>
              <div class="text-xs text-green-700 font-semibold">KES {{ b.invoice.total?.toLocaleString() }}</div>
              <div class="text-[10px] text-gray-500 capitalize">{{ b.invoice.payment_method }}</div>
            </div>
            <div v-else class="text-xs text-gray-400 italic">No payment</div>
          </div>

          <!-- Checklist -->
          <div>
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-1">Checklist</div>
            <div v-if="b.checklist" class="space-y-1.5">
              <div class="flex items-center gap-2">
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full',
                  b.checklist.status === 'check_out' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700']">
                  {{ b.checklist.status === 'check_out' ? '✓ Checked Out' : '⏳ Checked In' }}
                </span>
              </div>
              <div class="text-[10px] text-gray-400 font-mono">{{ b.checklist.sn }}</div>
              <!-- Flagged items summary -->
              <div v-if="b.checklist.flagged_count > 0"
                class="text-[10px] text-red-600 font-semibold">
                ⚠ {{ b.checklist.flagged_count }} item{{ b.checklist.flagged_count !== 1 ? 's' : '' }} flagged
              </div>
              <div class="flex items-center gap-2 mt-1">
                <button @click="viewChecklist(b.checklist.id)"
                  class="text-[10px] font-bold text-blue-600 hover:underline">View →</button>
                <button @click="printChecklist(b.checklist.id)"
                  class="text-[10px] font-bold text-gray-400 hover:text-gray-700 flex items-center gap-0.5">
                  <PrinterIcon class="w-3 h-3" /> Print
                </button>
              </div>
            </div>
            <div v-else class="text-xs text-gray-400 italic">No checklist</div>
          </div>

        </div>

        <!-- Notes if any -->
        <div v-if="b.notes" class="px-5 pb-3.5">
          <div class="text-[10px] text-gray-400 uppercase font-semibold mb-1">Notes</div>
          <div class="text-xs text-gray-600 bg-gray-50 rounded-lg px-3 py-2">{{ b.notes }}</div>
        </div>

      </div>
    </div>

    <!-- ── TAB: CHECKLISTS ── -->
    <div v-if="activeTab === 'checklists'" class="space-y-3">

      <div v-if="loadingChecklists" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading checklists…
      </div>

      <div v-else-if="!checklists.length" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400">
        No checklists recorded for this vehicle.
      </div>

      <div v-for="cl in checklists" :key="cl.id"
        class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-3.5 bg-gray-50 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <ClipboardDocumentCheckIcon class="w-4 h-4 text-gray-400" />
            <span class="font-mono text-xs font-bold text-gray-700">{{ cl.sn }}</span>
            <span class="text-xs text-gray-500">{{ fmtDate(cl.checked_in_at) }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span :class="['text-[10px] font-bold px-2.5 py-1 rounded-full',
              cl.status === 'check_out' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700']">
              {{ cl.status === 'check_out' ? 'Checked Out' : 'Checked In' }}
            </span>
            <button @click="viewChecklist(cl.id)"
              class="text-xs font-bold text-blue-600 hover:underline">View →</button>
            <button @click="printChecklist(cl.id)"
              class="flex items-center gap-1 text-xs font-semibold border border-gray-200 rounded-lg px-2 py-1 hover:bg-gray-50">
              <PrinterIcon class="w-3 h-3" /> Print
            </button>
          </div>
        </div>

        <!-- Summary row -->
        <div class="px-5 py-3.5 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div>
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Fuel Level</div>
            <div class="font-bold text-gray-900">{{ cl.fuel_level ?? '—' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Odometer</div>
            <div class="font-bold text-gray-900">{{ cl.odometer ? cl.odometer.toLocaleString() + ' km' : '—' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Checked In By</div>
            <div class="font-bold text-gray-900">{{ cl.checked_in_by?.name ?? '—' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Checked Out</div>
            <div class="font-bold text-gray-900">{{ cl.checked_out_at ? fmtDate(cl.checked_out_at) : '—' }}</div>
          </div>
        </div>

        <!-- Flagged items -->
        <div v-if="getFlaggedItems(cl).length" class="px-5 pb-4">
          <div class="text-[10px] text-gray-400 uppercase font-semibold mb-2">Flagged Items</div>
          <div class="flex flex-wrap gap-2">
            <span v-for="item in getFlaggedItems(cl)" :key="item.key"
              :class="['inline-flex items-center gap-1 text-[10px] font-semibold px-2.5 py-1 rounded-full',
                item.status === 'damaged' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">
              {{ item.status === 'damaged' ? '!' : '−' }}
              {{ item.label }}
              <span v-if="item.note" class="font-normal">— {{ item.note }}</span>
            </span>
          </div>
        </div>

        <!-- Remarks -->
        <div v-if="cl.exterior_remarks || cl.interior_remarks" class="px-5 pb-4 space-y-1.5">
          <div v-if="cl.exterior_remarks">
            <span class="text-[10px] text-gray-400 font-semibold uppercase mr-2">Exterior Remarks:</span>
            <span class="text-xs text-gray-600">{{ cl.exterior_remarks }}</span>
          </div>
          <div v-if="cl.interior_remarks">
            <span class="text-[10px] text-gray-400 font-semibold uppercase mr-2">Interior Remarks:</span>
            <span class="text-xs text-gray-600">{{ cl.interior_remarks }}</span>
          </div>
        </div>

      </div>
    </div>

    <!-- ── VIEW CHECKLIST MODAL ── -->
    <Modal v-model="showChecklistView" title="Vehicle Checklist" size="2xl">
      <div v-if="checklistLoading" class="py-12 text-center text-gray-400">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>
      <div v-else-if="activeChecklist" class="space-y-4">

        <!-- Meta -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50 rounded-xl p-4 text-xs">
          <div><div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">SN</div><div class="font-mono font-bold">{{ activeChecklist.sn }}</div></div>
          <div><div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Fuel</div><div class="font-bold">{{ activeChecklist.fuel_level ?? '—' }}</div></div>
          <div><div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Odometer</div><div class="font-bold">{{ activeChecklist.odometer ? activeChecklist.odometer.toLocaleString() + ' km' : '—' }}</div></div>
          <div><div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Status</div>
            <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full',
              activeChecklist.status==='check_out'?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700']">
              {{ activeChecklist.status === 'check_out' ? 'Checked Out' : 'Checked In' }}
            </span>
          </div>
        </div>

        <!-- All sections -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <ChecklistViewSection title="Exterior" :items="activeChecklist.exterior" :labels="exteriorLabels" />
          <ChecklistViewSection title="Interior" :items="activeChecklist.interior" :labels="interiorLabels" />
          <div class="space-y-3">
            <ChecklistViewSection title="Engine Compartment" :items="activeChecklist.engine_compartment" :labels="engineLabels" />
            <ChecklistViewSection title="Extras" :items="activeChecklist.extras" :labels="extrasLabels" />
          </div>
        </div>

        <!-- Remarks -->
        <div v-if="activeChecklist.exterior_remarks || activeChecklist.interior_remarks"
          class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div v-if="activeChecklist.exterior_remarks" class="bg-gray-50 rounded-xl p-4">
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-1">Exterior Remarks</div>
            <p class="text-xs text-gray-700">{{ activeChecklist.exterior_remarks }}</p>
          </div>
          <div v-if="activeChecklist.interior_remarks" class="bg-gray-50 rounded-xl p-4">
            <div class="text-[10px] text-gray-400 uppercase font-semibold mb-1">Interior Remarks</div>
            <p class="text-xs text-gray-700">{{ activeChecklist.interior_remarks }}</p>
          </div>
        </div>

      </div>
      <template #footer>
        <div class="flex justify-between w-full">
          <button @click="showChecklistView = false"
            class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Close</button>
          <button @click="printChecklist(activeChecklist?.id)"
            class="flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-gray-900 text-white rounded-xl hover:bg-gray-700">
            <PrinterIcon class="w-3.5 h-3.5" /> Print Checklist
          </button>
        </div>
      </template>
    </Modal>

    <!-- ── EDIT VEHICLE MODAL ── -->
    <Modal v-model="showEditVehicle" title="Edit Vehicle" size="md">
      <form v-if="vehicle" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5 col-span-2">
            <label class="text-xs font-semibold text-gray-600">Registration</label>
            <input v-model="editForm.registration" class="input-base font-mono uppercase">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Make</label>
            <input v-model="editForm.make" class="input-base" placeholder="Toyota">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Model</label>
            <input v-model="editForm.model" class="input-base" placeholder="Hilux">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Year</label>
            <input v-model.number="editForm.year" type="number" class="input-base" placeholder="2020">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Color</label>
            <input v-model="editForm.color" class="input-base" placeholder="White">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Engine Number</label>
            <input v-model="editForm.engine_number" class="input-base font-mono">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Chassis Number</label>
            <input v-model="editForm.chassis_number" class="input-base font-mono">
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showEditVehicle = false"
            class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="saveVehicle" :disabled="savingVehicle"
            class="px-5 py-2 text-xs font-bold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60 flex items-center gap-2">
            <span v-if="savingVehicle" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
            {{ savingVehicle ? 'Saving…' : 'Save Changes' }}
          </button>
        </div>
      </template>
    </Modal>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ClipboardDocumentCheckIcon, TruckIcon, PrinterIcon, PencilIcon } from '@heroicons/vue/24/outline'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import Modal             from '@/components/Modal.vue'

const route = useRoute()
const { get, put, loading } = useApi()
const toast = useToastStore()

// ── State ──────────────────────────────────────────────────────────────────────
const vehicle          = ref(null)
const history          = ref([])
const checklists       = ref([])
const activeTab        = ref('overview')
const loadingHistory   = ref(false)
const loadingChecklists = ref(false)

const showChecklistView = ref(false)
const checklistLoading  = ref(false)
const activeChecklist   = ref(null)

const showEditVehicle = ref(false)
const savingVehicle   = ref(false)
const editForm        = ref({})

// ── Checklist labels ───────────────────────────────────────────────────────────
const exteriorLabels = { front_windscreen:'Front Wind Screen',rear_windscreen:'Rear Wind Screen',insurance_sticker:'Insurance Sticker',front_number_plate:'Front Number Plate',headlights:'Headlights',tail_lights:'Tail Lights',front_bumper:'Front Bumper',rear_bumper:'Rear Bumper',grille:'Grille',grille_badge:'Grille Badge',front_wiper:'Front Wiper',rear_wiper:'Rear Wiper',side_mirror:'Side Mirror',door_glasses:'Door Glasses',fuel_tank_cap:'Fuel Tank Cap',front_tyres:'Front Tyres',rear_tyres:'Rear Tyres',front_rims:'Front Rims',rear_rims:'Rear Rims',hub_wheel_caps:'Hub/Wheel Caps',roof_rails:'Roof Rails',body_moulding:'Body Moulding',emblems:'Emblems',weather_stripes:'Weather Stripes',mud_guard:'Mud Guard' }
const interiorLabels = { rear_view_mirror:'Rear View Mirror',radio:'Radio',radio_face:'Radio Face',equalizer:'Equalizer',amplifier:'Amplifier',tuner:'Tuner',speaker:'Speaker',cigar_lighter:'Cigar Lighter',door_switches:'Door Switches',rubber_mats:'Rubber Mats',carpets:'Carpets',seat_covers:'Seat Covers',boot_mat:'Boot Mat',boot_board:'Boot Board',aircon_knobs:'Air Con Knobs',keys_remotes:'No. of Keys/Remotes',seat_belts:'Seat Belts' }
const engineLabels   = { battery:'Battery',computer_control_box:'Computer/Control Box',ignition_coils:'Ignition Coils',wiper_panel_finisher_covers:'Wiper Panel Covers',horn:'Horn',engine_caps:'Engine Caps',dip_sticks:'Dip Sticks',starter:'Starter',alternator:'Alternator',fog_lights:'Fog Lights',reverse_camera:'Reverse Camera',relays:'Relays',radiator:'Radiator' }
const extrasLabels   = { jack_handle:'Jack & Handle',wheel_spanner:'Wheel Spanner',towing_pin:'Towing Pin',towing_cable_rope:'Towing Cable/Rope',first_aid_kit:'First Aid Kit',fire_extinguisher:'Fire Extinguisher',spare_wheel:'Spare Wheel',life_savers:'Life Savers' }

const allLabels = { ...exteriorLabels, ...interiorLabels, ...engineLabels, ...extrasLabels }

// ── Computed ───────────────────────────────────────────────────────────────────
const regParts = computed(() => {
  const reg = vehicle.value?.registration ?? ''
  const parts = reg.split(' ')
  return parts.length > 1 ? [parts.slice(0,-1).join(' '), parts[parts.length-1]] : [reg, '']
})

const totalBookings   = computed(() => history.value.length)
const totalChecklists = computed(() => checklists.value.length)
const totalSpent      = computed(() => history.value.reduce((s, b) => s + (b.invoice?.total ?? 0), 0))

const tabs = computed(() => [
  { key: 'overview',    label: 'Overview' },
  { key: 'history',     label: 'Service History', count: totalBookings.value },
  { key: 'checklists',  label: 'Checklists',      count: totalChecklists.value },
])

const detailRows = computed(() => vehicle.value ? [
  ['Registration',   vehicle.value.registration],
  ['Make',           vehicle.value.make],
  ['Model',          vehicle.value.model],
  ['Year',           vehicle.value.year],
  ['Color',          vehicle.value.color],
  ['Engine Number',  vehicle.value.engine_number],
  ['Chassis Number', vehicle.value.chassis_number],
  ['Last Service',   vehicle.value.last_service_at ? fmtDate(vehicle.value.last_service_at) : 'Never'],
  ['Customer',       vehicle.value.customer?.name],
] : [])

// ── Helpers ────────────────────────────────────────────────────────────────────
const statusColors = { pending:'#EAB308',confirmed:'#3B82F6',in_progress:'#F97316',completed:'#22C55E',cancelled:'#EF4444',no_show:'#6B7280' }
const statusColor  = slug => statusColors[slug] ?? '#6B7280'
const fmtDate = d => d ? new Date(d).toLocaleDateString('en-KE',{day:'2-digit',month:'short',year:'numeric'}) : '—'
const fmtTime = d => d ? new Date(d).toLocaleTimeString('en-KE',{hour:'2-digit',minute:'2-digit'}) : ''

function getFlaggedItems(cl) {
  const flagged = []
  const allSections = {
    ...(cl.exterior ?? {}), ...(cl.interior ?? {}),
    ...(cl.engine_compartment ?? {}), ...(cl.extras ?? {})
  }
  for (const [key, val] of Object.entries(allSections)) {
    if (val?.status && val.status !== 'ok') {
      flagged.push({ key, label: allLabels[key] ?? key, status: val.status, note: val.note ?? '' })
    }
  }
  return flagged
}

// ── API ────────────────────────────────────────────────────────────────────────
async function loadVehicle() {
  vehicle.value = await get(`/admin/vehicles/${route.params.id}`)
}

async function loadHistory() {
  loadingHistory.value = true
  try {
    history.value = await get(`/admin/vehicles/${route.params.id}/history`) ?? []
  } finally { loadingHistory.value = false }
}

async function loadChecklists() {
  loadingChecklists.value = true
  try {
    const data = await get('/admin/checklists', { vehicle_id: route.params.id, per_page: 50 })
    checklists.value = data?.data ?? []
  } finally { loadingChecklists.value = false }
}

async function viewChecklist(id) {
  showChecklistView.value = true
  checklistLoading.value  = true
  try {
    activeChecklist.value = await get(`/admin/checklists/${id}`)
  } catch { toast.error('Failed to load checklist.') }
  finally { checklistLoading.value = false }
}

function printChecklist(id) {
  if (id) window.open(`/print/checklist/${id}`, '_blank')
}

function openEdit() {
  editForm.value = {
    registration:   vehicle.value.registration,
    make:           vehicle.value.make,
    model:          vehicle.value.model,
    year:           vehicle.value.year,
    color:          vehicle.value.color,
    engine_number:  vehicle.value.engine_number,
    chassis_number: vehicle.value.chassis_number,
  }
  showEditVehicle.value = true
}

async function saveVehicle() {
  savingVehicle.value = true
  try {
    await put(`/admin/vehicles/${route.params.id}`, editForm.value)
    await loadVehicle()
    showEditVehicle.value = false
    toast.success('Vehicle updated.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to update vehicle.')
  } finally { savingVehicle.value = false }
}

watch(activeTab, tab => {
  if (tab === 'history'    && !history.value.length)    loadHistory()
  if (tab === 'checklists' && !checklists.value.length) loadChecklists()
})

onMounted(async () => {
  await loadVehicle()
  // Load history eagerly — it's the main purpose of this page
  loadHistory()
  loadChecklists()
})
</script>


<!-- ═══════════════════════════════════════════════════════
     Inline sub-component: ChecklistViewSection
     Used only in this file to render a checklist section read-only
═══════════════════════════════════════════════════════ -->
<script>
// ChecklistViewSection — read-only display of one checklist section
export const ChecklistViewSection = {
  props: { title: String, items: Object, labels: Object },
  template: `
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="bg-gray-900 text-white px-4 py-2.5 text-xs font-bold uppercase tracking-wide flex items-center justify-between">
        <span>{{ title }}</span>
        <div class="flex gap-2 text-[10px] font-normal">
          <span v-if="damagedCount" class="bg-red-500 text-white rounded-full px-1.5 py-0.5 font-bold">{{ damagedCount }} damaged</span>
          <span v-if="missingCount" class="bg-yellow-500 text-white rounded-full px-1.5 py-0.5 font-bold">{{ missingCount }} missing</span>
        </div>
      </div>
      <div class="divide-y divide-gray-50 px-1">
        <div v-for="(label, key) in labels" :key="key" class="flex items-center justify-between px-3 py-1.5">
          <span class="text-[10px] text-gray-600 flex-1">{{ label }}</span>
          <span :class="['text-[10px] font-bold px-1.5 py-0.5 rounded',
            items?.[key]?.status === 'damaged' ? 'bg-red-100 text-red-700' :
            items?.[key]?.status === 'missing' ? 'bg-yellow-100 text-yellow-700' :
            'text-green-600']">
            {{ items?.[key]?.status === 'damaged' ? '! ' + (items[key].note||'') :
               items?.[key]?.status === 'missing' ? '− ' + (items[key].note||'') : '✓' }}
          </span>
        </div>
      </div>
    </div>
  `,
  computed: {
    damagedCount() { return Object.values(this.items||{}).filter(v=>v?.status==='damaged').length },
    missingCount()  { return Object.values(this.items||{}).filter(v=>v?.status==='missing').length },
  }
}
</script>
<template>
  <Modal :modelValue="modelValue" @update:modelValue="$emit('update:modelValue', $event)"
    title="New Booking" size="2xl">
    <form @submit.prevent="save" class="space-y-5">

      <!-- Customer info -->
      <div v-if="customer" class="bg-gray-50 rounded-xl px-4 py-3 flex items-center gap-3">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
          :style="`background:${avatarColor(customer?.name)}`">
          {{ initials(customer?.name) }}
        </div>
        <div>
          <div class="text-xs font-bold text-gray-900">{{ customer?.name }}</div>
          <div class="text-[10px] text-gray-500">{{ customer?.phone }}</div>
        </div>
      </div>
      <section v-else>
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Customer Information</h4>
          <label class="flex items-center gap-2 text-xs font-semibold text-gray-600">
            <input v-model="form.is_anonymous" type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
            Save anonymously
          </label>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Phone Number <span v-if="!form.is_anonymous" class="text-red-500">*</span></label>
            <input v-model="form.customer_phone" :disabled="form.is_anonymous" class="input-base disabled:bg-gray-50 disabled:text-gray-400" placeholder="+254 700 000000">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Client Name <span v-if="!form.is_anonymous" class="text-red-500">*</span></label>
            <input v-model="form.customer_name" class="input-base" :placeholder="form.is_anonymous ? 'Anonymous Client' : 'John Doe'">
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Email</label>
            <input v-model="form.customer_email" :disabled="form.is_anonymous" type="email" class="input-base disabled:bg-gray-50 disabled:text-gray-400" placeholder="john@example.com">
          </div>
        </div>
      </section>

      <div class="h-px bg-gray-100" />

      <!-- ── Vehicle ── -->
      <section>
        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Vehicle</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Registration <span class="text-red-500">*</span></label>
            <!-- Dropdown if customer has existing vehicles -->
            <div v-if="customer?.vehicles?.length">
              <select v-model="form.vehicle_reg" class="input-base font-mono" @change="onVehicleSelect">
                <option value="">Select vehicle…</option>
                <option v-for="v in customer.vehicles" :key="v.id" :value="v.registration">
                  {{ v.registration }} — {{ v.make }} {{ v.model }}
                </option>
                <option value="__new__">+ New vehicle registration</option>
              </select>
            </div>
            <input v-else v-model="form.vehicle_reg" required class="input-base font-mono uppercase" placeholder="KCA 123A">
            <!-- Custom reg when __new__ selected -->
            <input v-if="form.vehicle_reg === '__new__'" v-model="form.custom_reg"
              class="input-base font-mono uppercase mt-2" placeholder="Enter new registration e.g. KCA 123A">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Make</label>
            <input v-model="form.vehicle_make" class="input-base" placeholder="Toyota" :disabled="vehicleFromList">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Model</label>
            <input v-model="form.vehicle_model" class="input-base" placeholder="Hilux" :disabled="vehicleFromList">
          </div>
        </div>
      </section>

      <div class="h-px bg-gray-100" />

      <!-- ── Service & Schedule ── -->
      <section>
        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Service & Schedule</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Service <span class="text-red-500">*</span></label>
            <select v-model="form.service_id" required class="input-base">
              <option value="">Select service…</option>
              <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Source</label>
            <select v-model="form.source" class="input-base">
              <option value="walk_in">Walk-in</option>
              <option value="phone">Phone</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="website">Website</option>
              <option value="referral">Referral</option>
            </select>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Date <span class="text-red-500">*</span></label>
            <input v-model="form.date" type="date" required :min="today" class="input-base">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Time <span class="text-red-500">*</span></label>
            <select v-model="form.time" required class="input-base">
              <option value="">Select time…</option>
              <option v-for="slot in timeSlots" :key="slot" :value="slot">{{ slot }}</option>
            </select>
          </div>
        </div>
      </section>

      <section v-if="selectedService?.requires_deposit" class="space-y-4">
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
          <div class="text-xs font-bold text-amber-800">Down Payment Required</div>
          <p class="text-xs text-amber-700 mt-1">
            {{ selectedService.name }} requires a {{ selectedService.deposit_percent }}% down payment.
            Collect KES {{ requiredDepositAmount.toLocaleString() }} before saving this booking.
          </p>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <button v-for="method in ['cash','mpesa','card']" :key="method" type="button" @click="form.deposit_payment.payment_method = method"
            :class="['px-3 py-2 rounded-xl text-xs font-bold border transition-colors capitalize',
              form.deposit_payment.payment_method === method ? 'bg-red-600 border-red-600 text-white' : 'border-gray-200 text-gray-600 hover:border-red-300']">
            {{ method }}
          </button>
        </div>
        <div v-if="form.deposit_payment.payment_method === 'cash'" class="text-xs text-gray-500 bg-gray-50 rounded-xl px-3 py-2">
          Cash down payment will be marked as collected immediately when the booking is saved.
        </div>
        <div v-if="form.deposit_payment.payment_method === 'mpesa'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Customer Phone</label>
            <input v-model="depositPhone" class="input-base font-mono" placeholder="+254 700 000000">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">M-Pesa Reference</label>
            <input v-model="form.deposit_payment.mpesa_reference" class="input-base font-mono uppercase" placeholder="e.g. QHK9XXXXX">
          </div>
          <div class="sm:col-span-2">
            <button type="button" @click="sendDepositPrompt" :disabled="!depositPhone || depositSending"
              class="px-4 py-2 text-xs font-bold bg-green-600 text-white rounded-xl hover:bg-green-700 disabled:opacity-60">
              {{ depositSending ? 'Sending…' : 'Send Payment Prompt' }}
            </button>
          </div>
        </div>
        <div v-if="form.deposit_payment.payment_method === 'card'" class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Card Reference</label>
          <input v-model="form.deposit_payment.card_reference" class="input-base font-mono uppercase" placeholder="Approval code">
        </div>
      </section>

      <!-- ── Notes ── -->
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-gray-600">Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
        <textarea v-model="form.notes" rows="2" class="input-base resize-none"
          placeholder="Special requests or vehicle details…" />
      </div>

      <div class="h-px bg-gray-100" />

      <!-- ── Vehicle Check-in ── -->
      <section>
        <div class="flex items-center justify-between mb-3">
          <div>
            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Vehicle Check-in</h4>
            <p class="text-[10px] text-gray-400 mt-0.5">Record vehicle condition at time of booking</p>
          </div>
          <!-- Toggle -->
          <button type="button" @click="form.include_checklist = !form.include_checklist"
            :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
              form.include_checklist ? 'bg-red-600' : 'bg-gray-200']">
            <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow',
              form.include_checklist ? 'translate-x-4' : 'translate-x-1']" />
          </button>
        </div>

        <div v-if="form.include_checklist" class="space-y-4">
          <!-- Fuel + Odometer + Colour + Payment -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50 rounded-xl p-4">
            <div class="flex flex-col gap-1.5 col-span-2 sm:col-span-1">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Fuel Level</label>
              <div class="flex gap-1.5 flex-wrap">
                <button v-for="level in ['F','3/4','1/2','1/4','E']" :key="level" type="button"
                  @click="form.checklist.fuel_level = level"
                  :class="['px-2 py-1 text-[10px] font-bold rounded-lg border-2 transition-all',
                    form.checklist.fuel_level === level
                      ? 'bg-red-600 border-red-600 text-white'
                      : 'border-gray-200 text-gray-600 hover:border-red-400']">
                  {{ level }}
                </button>
              </div>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Odometer (km)</label>
              <input v-model.number="form.checklist.odometer" type="number" class="input-base" placeholder="e.g. 45000">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Vehicle Colour</label>
              <input v-model="form.checklist.colour" class="input-base" placeholder="e.g. White">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Payment Option</label>
              <select v-model="form.checklist.payment_option" class="input-base">
                <option value="">Select…</option>
                <option value="mpesa">M-Pesa</option>
                <option value="cash">Cash</option>
                <option value="insurance">Insurance</option>
                <option value="cheque">Cheque</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <!-- Checklist sections -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <ChecklistSection title="Exterior"           :items="exteriorLabels" v-model="form.checklist.exterior" />
            <ChecklistSection title="Interior"           :items="interiorLabels" v-model="form.checklist.interior" />
            <div class="space-y-3">
              <ChecklistSection title="Engine Compartment" :items="engineLabels"   v-model="form.checklist.engine_compartment" />
              <ChecklistSection title="Extras"             :items="extrasLabels"   v-model="form.checklist.extras" />
            </div>
          </div>

          <!-- Remarks -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Exterior Remarks</label>
              <textarea v-model="form.checklist.exterior_remarks" rows="2" class="input-base resize-none"
                placeholder="Any exterior damage or notes…" />
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Interior Remarks</label>
              <textarea v-model="form.checklist.interior_remarks" rows="2" class="input-base resize-none"
                placeholder="Any interior damage or notes…" />
            </div>
          </div>
        </div>

        <!-- Collapsed state -->
        <div v-else class="bg-gray-50 rounded-xl px-4 py-3 flex items-center gap-2 text-gray-400">
          <ClipboardDocumentCheckIcon class="w-4 h-4" />
          <span class="text-xs">Toggle on to record vehicle condition at check-in</span>
        </div>
      </section>

      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3">{{ error }}</div>

    </form>

    <template #footer>
      <div class="flex justify-end gap-2">
        <button @click="$emit('update:modelValue', false)"
          class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
        <button @click="save" :disabled="saving"
          class="px-5 py-2 text-xs font-bold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60 flex items-center gap-2">
          <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
          {{ saving ? 'Creating…' : 'Create Booking' }}
        </button>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import Modal           from '@/components/Modal.vue'
import ChecklistSection from '@/components/ChecklistSection.vue'

const props = defineProps({ modelValue: Boolean, customer: Object })
const emit  = defineEmits(['update:modelValue', 'saved'])
const { get, post } = useApi()

const services = ref([])
const saving   = ref(false)
const error    = ref(null)
const depositSending = ref(false)

const today     = new Date().toISOString().split('T')[0]
const timeSlots = ['08:00 AM','09:00 AM','10:00 AM','11:00 AM','12:00 PM','01:00 PM','02:00 PM','03:00 PM','04:00 PM','05:00 PM']
const timeMap   = { '08:00 AM':'08:00','09:00 AM':'09:00','10:00 AM':'10:00','11:00 AM':'11:00','12:00 PM':'12:00','01:00 PM':'13:00','02:00 PM':'14:00','03:00 PM':'15:00','04:00 PM':'16:00','05:00 PM':'17:00' }

// ── Checklist labels & defaults ────────────────────────────────────────────────
const defaultItems = keys => Object.fromEntries(keys.map(k => [k, { status: 'ok', note: '' }]))

const exteriorLabels = { front_windscreen:'Front Wind Screen',rear_windscreen:'Rear Wind Screen',insurance_sticker:'Insurance Sticker',front_number_plate:'Front Number Plate',headlights:'Headlights',tail_lights:'Tail Lights',front_bumper:'Front Bumper',rear_bumper:'Rear Bumper',grille:'Grille',grille_badge:'Grille Badge',front_wiper:'Front Wiper',rear_wiper:'Rear Wiper',side_mirror:'Side Mirror',door_glasses:'Door Glasses',fuel_tank_cap:'Fuel Tank Cap',front_tyres:'Front Tyres',rear_tyres:'Rear Tyres',front_rims:'Front Rims',rear_rims:'Rear Rims',hub_wheel_caps:'Hub/Wheel Caps',roof_rails:'Roof Rails',body_moulding:'Body Moulding',emblems:'Emblems',weather_stripes:'Weather Stripes',mud_guard:'Mud Guard' }
const interiorLabels = { rear_view_mirror:'Rear View Mirror',radio:'Radio',radio_face:'Radio Face',equalizer:'Equalizer',amplifier:'Amplifier',tuner:'Tuner',speaker:'Speaker',cigar_lighter:'Cigar Lighter',door_switches:'Door Switches',rubber_mats:'Rubber Mats',carpets:'Carpets',seat_covers:'Seat Covers',boot_mat:'Boot Mat',boot_board:'Boot Board',aircon_knobs:'Air Con Knobs',keys_remotes:'No. of Keys/Remotes',seat_belts:'Seat Belts' }
const engineLabels   = { battery:'Battery',computer_control_box:'Computer/Control Box',ignition_coils:'Ignition Coils',wiper_panel_finisher_covers:'Wiper Panel Covers',horn:'Horn',engine_caps:'Engine Caps',dip_sticks:'Dip Sticks',starter:'Starter',alternator:'Alternator',fog_lights:'Fog Lights',reverse_camera:'Reverse Camera',relays:'Relays',radiator:'Radiator' }
const extrasLabels   = { jack_handle:'Jack & Handle',wheel_spanner:'Wheel Spanner',towing_pin:'Towing Pin',towing_cable_rope:'Towing Cable/Rope',first_aid_kit:'First Aid Kit',fire_extinguisher:'Fire Extinguisher',spare_wheel:'Spare Wheel',life_savers:'Life Savers' }

const defaultChecklist = () => ({
  fuel_level: '', odometer: null, colour: '', payment_option: '',
  exterior:           defaultItems(Object.keys(exteriorLabels)),
  interior:           defaultItems(Object.keys(interiorLabels)),
  engine_compartment: defaultItems(Object.keys(engineLabels)),
  extras:             defaultItems(Object.keys(extrasLabels)),
  exterior_remarks: '', interior_remarks: '',
})

const defaultForm = () => ({
  is_anonymous: false,
  customer_name: '', customer_phone: '', customer_email: '',
  vehicle_reg: '', custom_reg: '', vehicle_make: '', vehicle_model: '',
  service_id: '', source: 'walk_in', date: '', time: '', notes: '',
  include_checklist: true,
  deposit_payment: { payment_method:'', mpesa_reference:'', card_reference:'', gateway_reference:'', notes:'' },
  checklist: defaultChecklist(),
})

const form = ref(defaultForm())
const selectedService = computed(() => services.value.find(s => String(s.id) === String(form.value.service_id)))
const requiredDepositAmount = computed(() => {
  if (!selectedService.value?.requires_deposit) return 0
  return Math.round(((selectedService.value.price_from ?? 0) * (selectedService.value.deposit_percent ?? 0)) / 100)
})
const depositPhone = computed({
  get: () => props.customer?.phone ?? '',
  set: () => {},
})

const vehicleFromList = computed(() =>
  !!props.customer?.vehicles?.find(v => v.registration === form.value.vehicle_reg)
)

const initials    = name => name?.split(' ').slice(0,2).map(w => w[0]?.toUpperCase()).join('') ?? '?'
const colors      = ['#EF4444','#3B82F6','#22C55E','#A855F7','#F97316','#EC4899','#14B8A6','#EAB308']
const avatarColor = name => colors[(name?.charCodeAt(0) ?? 0) % colors.length]

function onVehicleSelect() {
  const v = props.customer?.vehicles?.find(v => v.registration === form.value.vehicle_reg)
  if (v) {
    form.value.vehicle_make  = v.make  !== 'Unknown' ? v.make  : ''
    form.value.vehicle_model = v.model !== 'Unknown' ? v.model : ''
    // Pre-fill colour in checklist from vehicle record
    if (v.color) form.value.checklist.colour = v.color
  } else {
    form.value.vehicle_make  = ''
    form.value.vehicle_model = ''
  }
}

// Reset form when modal opens
watch(() => props.modelValue, open => {
  if (open) {
    form.value  = defaultForm()
    error.value = null
    // Pre-select first vehicle if customer has exactly one
    if (props.customer?.vehicles?.length === 1) {
      form.value.vehicle_reg = props.customer.vehicles[0].registration
      onVehicleSelect()
    }
    if (props.customer) {
      form.value.customer_name = props.customer.name ?? ''
      form.value.customer_phone = props.customer.phone ?? ''
      form.value.customer_email = props.customer.email ?? ''
    }
  }
})

async function save() {
  saving.value = true; error.value = null

  const reg = form.value.vehicle_reg === '__new__'
    ? form.value.custom_reg.toUpperCase()
    : form.value.vehicle_reg.toUpperCase()

  if (!reg) { error.value = 'Vehicle registration is required.'; saving.value = false; return }

  const time24      = timeMap[form.value.time] ?? '09:00'
  const scheduledAt = `${form.value.date}T${time24}:00`

  try {
    await post('/admin/bookings', {
      is_anonymous:   !props.customer && form.value.is_anonymous,
      customer_name:  props.customer?.name ?? form.value.customer_name,
      customer_phone: props.customer?.phone ?? form.value.customer_phone,
      customer_email: props.customer?.email || form.value.customer_email || undefined,
      vehicle_reg:    reg,
      vehicle_make:   form.value.vehicle_make  || undefined,
      vehicle_model:  form.value.vehicle_model || undefined,
      service_id:     form.value.service_id    || undefined,
      source:         form.value.source,
      scheduled_at:   scheduledAt,
      notes:          form.value.notes         || undefined,
      ...(selectedService.value?.requires_deposit ? { deposit_payment: {
        payment_method: form.value.deposit_payment.payment_method || undefined,
        mpesa_reference: form.value.deposit_payment.mpesa_reference || undefined,
        card_reference: form.value.deposit_payment.card_reference || undefined,
        gateway_reference: form.value.deposit_payment.gateway_reference || undefined,
        notes: form.value.deposit_payment.notes || undefined,
      }} : {}),
      // Include checklist if toggled on
      ...(form.value.include_checklist ? {
        checklist: {
          fuel_level:          form.value.checklist.fuel_level     || null,
          odometer:            form.value.checklist.odometer       || null,
          colour:              form.value.checklist.colour         || null,
          payment_option:      form.value.checklist.payment_option || null,
          exterior:            form.value.checklist.exterior,
          interior:            form.value.checklist.interior,
          engine_compartment:  form.value.checklist.engine_compartment,
          extras:              form.value.checklist.extras,
          exterior_remarks:    form.value.checklist.exterior_remarks || null,
          interior_remarks:    form.value.checklist.interior_remarks || null,
        }
      } : {}),
    })
    emit('saved')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to create booking.'
  } finally { saving.value = false }
}

async function sendDepositPrompt() {
  depositSending.value = true
  try {
    const res = await post('/admin/mpesa/stk-push', {
      phone: props.customer?.phone || form.value.customer_phone,
      amount: requiredDepositAmount.value,
      reference: `CUSTOMER-BOOKING-${props.customer?.id ?? 'NEW'}`,
      customer_name: props.customer?.name || form.value.customer_name,
    })
    form.value.deposit_payment.gateway_reference = res.checkout_request
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to send payment prompt.'
  } finally {
    depositSending.value = false
  }
}

onMounted(async () => {
  services.value = await get('/admin/services') ?? []
})
</script>

<template>
  <div class="space-y-6">

    <!-- Header info -->
    <div class="bg-gray-50 rounded-2xl border border-gray-100 p-5 grid grid-cols-2 md:grid-cols-4 gap-4">
      <div>
        <div class="text-[10px] text-gray-400 uppercase tracking-wide mb-0.5">SN</div>
        <div class="text-sm font-bold text-red-600">{{ checklist?.sn }}</div>
      </div>
      <div>
        <div class="text-[10px] text-gray-400 uppercase tracking-wide mb-0.5">Registration</div>
        <div class="text-sm font-bold font-mono text-gray-900">{{ checklist?.reg_no }}</div>
      </div>
      <div>
        <div class="text-[10px] text-gray-400 uppercase tracking-wide mb-0.5">Vehicle</div>
        <div class="text-sm font-semibold text-gray-700">{{ checklist?.make }} {{ checklist?.model }}</div>
      </div>
      <div>
        <div class="text-[10px] text-gray-400 uppercase tracking-wide mb-0.5">Customer</div>
        <div class="text-sm font-semibold text-gray-700">{{ checklist?.customer?.name }}</div>
      </div>
    </div>

    <!-- Fuel + Odometer + Payment -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h3 class="text-sm font-bold text-gray-900 mb-4">Vehicle Details</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Fuel gauge -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Fuel Level</label>
          <div class="flex gap-2">
            <button v-for="level in fuelLevels" :key="level"
              @click="form.fuel_level = level"
              :class="['px-3 py-1.5 text-xs font-bold rounded-lg border-2 transition-all',
                form.fuel_level === level
                  ? 'bg-red-600 border-red-600 text-white'
                  : 'border-gray-200 text-gray-600 hover:border-red-400']">
              {{ level }}
            </button>
          </div>
        </div>

        <!-- Odometer -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Odometer Reading (km)</label>
          <input v-model="form.odometer" type="number" class="input-base" placeholder="e.g. 45000">
        </div>

        <!-- Payment -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Payment Option</label>
          <select v-model="form.payment_option" class="input-base">
            <option value="">Select...</option>
            <option value="mpesa">M-Pesa</option>
            <option value="cash">Cash</option>
            <option value="insurance">Insurance</option>
            <option value="cheque">Cheque</option>
            <option value="other">Other</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Checklist sections -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Exterior -->
      <ChecklistSection
        title="Exterior"
        :items="exteriorLabels"
        v-model="form.exterior"
      />

      <!-- Interior -->
      <ChecklistSection
        title="Interior"
        :items="interiorLabels"
        v-model="form.interior"
      />

      <!-- Engine + Extras -->
      <div class="space-y-4">
        <ChecklistSection
          title="Engine Compartment"
          :items="engineLabels"
          v-model="form.engine_compartment"
        />
        <ChecklistSection
          title="Extras"
          :items="extrasLabels"
          v-model="form.extras"
        />
      </div>
    </div>

    <!-- Remarks -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-gray-600">Exterior Remarks</label>
        <textarea v-model="form.exterior_remarks" rows="3" class="input-base resize-none" placeholder="Any exterior damage or notes..."></textarea>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-gray-600">Interior Remarks</label>
        <textarea v-model="form.interior_remarks" rows="3" class="input-base resize-none" placeholder="Any interior damage or notes..."></textarea>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between">
      <button @click="printChecklist"
        class="flex items-center gap-2 border border-gray-200 text-gray-600 text-xs font-semibold px-4 py-2.5 rounded-xl hover:bg-gray-50">
        <PrinterIcon class="w-4 h-4" /> Print Checklist
      </button>
      <div class="flex gap-2">
        <button @click="$emit('cancel')"
          class="px-4 py-2.5 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">
          Cancel
        </button>
        <button @click="save" :disabled="saving"
          class="px-5 py-2.5 text-xs font-bold bg-red-600 hover:bg-red-700 text-white rounded-xl disabled:opacity-60 transition-colors">
          {{ saving ? 'Saving…' : 'Save Checklist' }}
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { PrinterIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import ChecklistSection from './ChecklistSection.vue'

const props = defineProps({ checklist: Object })
const emit  = defineEmits(['saved', 'cancel'])

const { put } = useApi()
const saving = ref(false)

const fuelLevels = ['F', '3/4', '1/2', '1/4', 'E']

const form = ref({
  fuel_level:          props.checklist?.fuel_level ?? '',
  odometer:            props.checklist?.odometer ?? '',
  payment_option:      props.checklist?.payment_option ?? '',
  exterior:            props.checklist?.exterior ?? {},
  interior:            props.checklist?.interior ?? {},
  engine_compartment:  props.checklist?.engine_compartment ?? {},
  extras:              props.checklist?.extras ?? {},
  exterior_remarks:    props.checklist?.exterior_remarks ?? '',
  interior_remarks:    props.checklist?.interior_remarks ?? '',
})

const exteriorLabels = {
  front_windscreen:'Front Wind Screen', rear_windscreen:'Rear Wind Screen',
  insurance_sticker:'Insurance Sticker', front_number_plate:'Front Number Plate',
  headlights:'Headlights', tail_lights:'Tail Lights',
  front_bumper:'Front Bumper', rear_bumper:'Rear Bumper',
  grille:'Grille', grille_badge:'Grille Badge',
  front_wiper:'Front Wiper', rear_wiper:'Rear Wiper',
  side_mirror:'Side Mirror', door_glasses:'Door Glasses',
  fuel_tank_cap:'Fuel Tank Cap', front_tyres:'Front Tyres',
  rear_tyres:'Rear Tyres', front_rims:'Front Rims',
  rear_rims:'Rear Rims', hub_wheel_caps:'Hub/Wheel Caps',
  roof_rails:'Roof Rails', body_moulding:'Body Moulding',
  emblems:'Emblems', weather_stripes:'Weather Stripes', mud_guard:'Mud Guard',
}

const interiorLabels = {
  rear_view_mirror:'Rear View Mirror', radio:'Radio', radio_face:'Radio Face',
  equalizer:'Equalizer', amplifier:'Amplifier', tuner:'Tuner', speaker:'Speaker',
  cigar_lighter:'Cigar Lighter', door_switches:'Door Switches',
  rubber_mats:'Rubber Mats', carpets:'Carpets', seat_covers:'Seat Covers',
  boot_mat:'Boot Mat', boot_board:'Boot Board', aircon_knobs:'Air Con Knobs',
  keys_remotes:'No. of Keys/Remotes', seat_belts:'Seat Belts',
}

const engineLabels = {
  battery:'Battery', computer_control_box:'Computer/Control Box',
  ignition_coils:'Ignition Coils', wiper_panel_finisher_covers:'Wiper Panel Covers',
  horn:'Horn', engine_caps:'Engine Caps', dip_sticks:'Dip Sticks',
  starter:'Starter', alternator:'Alternator', fog_lights:'Fog Lights',
  reverse_camera:'Reverse Camera', relays:'Relays', radiator:'Radiator',
}

const extrasLabels = {
  jack_handle:'Jack & Handle', wheel_spanner:'Wheel Spanner',
  towing_pin:'Towing Pin', towing_cable_rope:'Towing Cable/Rope',
  first_aid_kit:'First Aid Kit', fire_extinguisher:'Fire Extinguisher',
  spare_wheel:'Spare Wheel', life_savers:'Life Savers',
}

async function save() {
  saving.value = true
  try {
    await put(`/admin/checklists/${props.checklist.id}`, form.value)
    emit('saved')
  } finally {
    saving.value = false
  }
}

function printChecklist() {
  window.open(`/print/checklist/${props.checklist.id}`, '_blank')
}
</script>
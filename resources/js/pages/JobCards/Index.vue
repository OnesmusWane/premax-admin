<template>
  <div class="p-4 md:p-6 space-y-4">
    <PageHeader title="Job Cards" subtitle="Live workshop board">
      <SearchInput v-model="search" placeholder="Search vehicles..." class="w-56" />
      <button class="flex items-center gap-1.5 border border-gray-200 text-gray-600 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-gray-50">
        <FunnelIcon class="w-4 h-4" />
      </button>
      <button @click="showNew = true"
        class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors">
        <PlusIcon class="w-4 h-4" /> New Job Card
      </button>
    </PageHeader>

    <!-- Kanban board -->
    <div class="px-4 md:px-6 overflow-x-auto pb-4">
      <div class="flex gap-4 min-w-max">

        <div v-for="col in columns" :key="col.key"
          class="w-[280px] bg-gray-100 rounded-2xl p-3 flex flex-col gap-3 shrink-0">

          <!-- Column header -->
          <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full" :style="`background:${col.color}`"></span>
              <span class="text-xs font-bold text-gray-700">{{ col.label }}</span>
            </div>
            <span class="w-5 h-5 rounded-full bg-white text-gray-600 text-[10px] font-bold flex items-center justify-center">
              {{ board[col.key]?.length ?? 0 }}
            </span>
          </div>

          <!-- Cards -->
          <div class="flex flex-col gap-2.5">
            <div v-for="card in filteredBoard[col.key]" :key="card.id"
              class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
              <!-- Reg + make -->
              <div class="flex items-start justify-between mb-2">
                <span class="font-mono font-bold text-sm text-gray-900">{{ card.vehicle?.registration }}</span>
                <button @click.stop="deleteCard(card)" class="text-gray-300 hover:text-red-500 transition-colors">
                  <XMarkIcon class="w-3.5 h-3.5" />
                </button>
              </div>
              <div class="text-xs text-gray-600">{{ card.vehicle?.make }} {{ card.vehicle?.model }} {{ card.vehicle?.year }}</div>
              <div class="text-xs text-gray-500">{{ card.customer?.name }}</div>

              <!-- Service tag -->
              <div class="mt-3 flex items-center justify-between">
                <span class="text-[10px] font-semibold bg-red-50 text-red-600 px-2 py-0.5 rounded-full">{{ card.service_name }}</span>
                <span v-if="card.estimated_minutes" class="flex items-center gap-1 text-[10px] text-gray-400">
                  <ClockIcon class="w-3 h-3" /> {{ formatDuration(card.estimated_minutes) }}
                </span>
              </div>

              <!-- Stage move buttons -->
              <div class="mt-3 flex items-center gap-1.5">
                <button v-if="col.key !== 'waiting'" @click="moveCard(card, prevStage(col.key))"
                  class="flex-1 text-[10px] font-semibold border border-gray-200 text-gray-600 rounded-lg py-1.5 hover:bg-gray-50 transition-colors">
                  ← Back
                </button>
                <button v-if="col.key !== 'quality_check'" @click="moveCard(card, nextStage(col.key))"
                  class="flex-1 text-[10px] font-semibold bg-gray-900 text-white rounded-lg py-1.5 hover:bg-gray-700 transition-colors">
                  Next →
                </button>
                <button v-if="col.key === 'quality_check'" @click="moveCard(card, 'done')"
                  class="flex-1 text-[10px] font-semibold bg-green-600 text-white rounded-lg py-1.5 hover:bg-green-700 transition-colors">
                  ✓ Done
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- New Job Card Modal -->
    <Modal v-model="showNew" title="New Job Card" size="md">
      <form class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5 col-span-2">
            <label class="text-xs font-semibold text-gray-600">Vehicle Registration</label>
            <input v-model="newForm.vehicle_reg" class="input-base" placeholder="KCA 123A" @blur="lookupVehicle">
            <p v-if="vehicleInfo" class="text-xs text-green-600">{{ vehicleInfo }}</p>
          </div>
          <div class="flex flex-col gap-1.5 col-span-2">
            <label class="text-xs font-semibold text-gray-600">Service</label>
            <select v-model="newForm.service_name" class="input-base">
              <option value="">Select service…</option>
              <option v-for="s in services" :key="s.id" :value="s.name">{{ s.name }}</option>
            </select>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Est. Duration (min)</label>
            <input v-model="newForm.estimated_minutes" type="number" class="input-base" placeholder="45">
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes</label>
          <textarea v-model="newForm.notes" rows="2" class="input-base resize-none" placeholder="Special instructions…"></textarea>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showNew=false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="createCard" :disabled="saving" class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ saving ? 'Creating…' : 'Create Job Card' }}
          </button>
        </div>
      </template>
    </Modal>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { PlusIcon, FunnelIcon, ClockIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import PageHeader  from '@/components/PageHeader.vue'
import SearchInput from '@/components/SearchInput.vue'
import Modal       from '@/components/Modal.vue'

const { get, post, patch, del, loading } = useApi()

const board   = ref({ waiting:[], washing:[], repair:[], quality_check:[] })
const search  = ref('')
const showNew = ref(false)
const saving  = ref(false)
const services     = ref([])
const vehicleInfo  = ref('')
const newForm = ref({ vehicle_reg:'', service_name:'', service_id:null, vehicle_id:null, customer_id:null, estimated_minutes:'', notes:'' })

const columns = [
  { key:'waiting',       label:'Waiting',       color:'#EAB308' },
  { key:'washing',       label:'Washing',       color:'#EF4444' },
  { key:'repair',        label:'Repair',        color:'#A855F7' },
  { key:'quality_check', label:'Quality Check', color:'#3B82F6' },
]

const stageOrder = ['waiting','washing','repair','quality_check']
const nextStage  = s => stageOrder[stageOrder.indexOf(s)+1] ?? 'done'
const prevStage  = s => stageOrder[stageOrder.indexOf(s)-1] ?? 'waiting'

const filteredBoard = computed(()=>{
  if (!search.value) return board.value
  const q = search.value.toLowerCase()
  return Object.fromEntries(
    Object.entries(board.value).map(([k,cards])=>[k, cards.filter(c=>c.vehicle?.registration?.toLowerCase().includes(q) || c.customer?.name?.toLowerCase().includes(q))])
  )
})

const formatDuration = m => m >= 60 ? `${Math.floor(m/60)}h ${m%60 ? m%60+'m' : ''}`.trim() : `${m}m`

async function load() {
  const data = await get('/admin/job-cards')
  board.value = data
}

async function moveCard(card, stage) {
  await patch(`/admin/job-cards/${card.id}/stage`, { stage })
  load()
}

async function deleteCard(card) {
  if (!confirm(`Delete job card for ${card.vehicle?.registration}?`)) return
  await del(`/admin/job-cards/${card.id}`)
  load()
}

async function lookupVehicle() {
  if (!newForm.value.vehicle_reg) return
  try {
    const data = await get('/admin/vehicles', { search: newForm.value.vehicle_reg })
    const v = data.data?.[0]
    if (v) {
      newForm.value.vehicle_id  = v.id
      newForm.value.customer_id = v.customer?.id
      vehicleInfo.value = `${v.make} ${v.model} — ${v.customer?.name}`
    } else { vehicleInfo.value = '' }
  } catch {}
}

async function createCard() {
  saving.value = true
  try {
    await post('/admin/job-cards', { ...newForm.value })
    showNew.value = false
    newForm.value = { vehicle_reg:'', service_name:'', service_id:null, vehicle_id:null, customer_id:null, estimated_minutes:'', notes:'' }
    load()
  } finally { saving.value = false }
}

onMounted(async()=>{
  load()
  services.value = (await get('/admin/services', { per_page: 100 }))?.data ?? []
})
</script>
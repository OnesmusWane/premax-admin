<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="bg-gray-900 text-white px-4 py-2.5 text-xs font-bold uppercase tracking-wide flex items-center justify-between">
      <span>{{ title }}</span>
      <!-- Summary counts -->
      <div class="flex items-center gap-2 text-[10px] font-normal">
        <span v-if="missingCount" class="bg-yellow-500 text-white rounded-full px-1.5 py-0.5 font-bold">
          {{ missingCount }} missing
        </span>
        <span v-if="damagedCount" class="bg-red-500 text-white rounded-full px-1.5 py-0.5 font-bold">
          {{ damagedCount }} damaged
        </span>
      </div>
    </div>

    <div class="divide-y divide-gray-50">
      <div v-for="(label, key) in items" :key="key" class="px-3 py-2">

        <!-- Item row: label + status toggles -->
        <div class="flex items-center justify-between gap-2">
          <span class="text-xs text-gray-700 flex-1 leading-tight">{{ label }}</span>

          <!-- Status toggles -->
          <div class="flex gap-1 shrink-0">
            <button v-for="opt in statusOptions" :key="opt.value" type="button"
              @click="setStatus(key, opt.value)"
              :title="opt.label"
              :class="['w-7 h-7 rounded-lg text-[10px] font-bold border-2 transition-all flex items-center justify-center',
                modelValue[key]?.status === opt.value
                  ? opt.activeClass
                  : 'border-gray-200 text-gray-400 hover:border-gray-400 bg-white']">
              {{ opt.icon }}
            </button>
          </div>
        </div>

        <!-- Note field — shown inline, always visible but subtle -->
        <div class="mt-1.5 relative">
          <input
            :value="modelValue[key]?.note ?? ''"
            @input="setNote(key, $event.target.value)"
            :placeholder="notePlaceholder(key, modelValue[key]?.status)"
            :class="['w-full text-[10px] bg-transparent border-b transition-colors outline-none py-0.5 pr-6 placeholder-gray-300',
              modelValue[key]?.note
                ? 'border-gray-300 text-gray-700'
                : 'border-transparent text-gray-400 focus:border-gray-300',
              modelValue[key]?.status === 'damaged' ? 'placeholder-red-300' :
              modelValue[key]?.status === 'missing' ? 'placeholder-yellow-400' : 'placeholder-gray-300']">
          <!-- Note indicator dot — shows when there's a note -->
          <span v-if="modelValue[key]?.note"
            class="absolute right-1 top-0.5 w-1.5 h-1.5 rounded-full bg-blue-400 shrink-0" />
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title:      String,
  items:      Object,   // { key: 'Label' }
  modelValue: Object,   // { key: { status, note } }
})

const emit = defineEmits(['update:modelValue'])

const statusOptions = [
  { value: 'ok',      icon: '✓', label: 'OK',      activeClass: 'bg-green-500 border-green-500 text-white' },
  { value: 'missing', icon: '−', label: 'Missing',  activeClass: 'bg-yellow-500 border-yellow-500 text-white' },
  { value: 'damaged', icon: '!', label: 'Damaged',  activeClass: 'bg-red-500 border-red-500 text-white' },
]

const missingCount = computed(() =>
  Object.values(props.modelValue ?? {}).filter(v => v?.status === 'missing').length
)
const damagedCount = computed(() =>
  Object.values(props.modelValue ?? {}).filter(v => v?.status === 'damaged').length
)

function setStatus(key, status) {
  const updated = { ...props.modelValue }
  updated[key]  = { ...(updated[key] ?? {}), status }
  emit('update:modelValue', updated)
}

function setNote(key, note) {
  const updated = { ...props.modelValue }
  updated[key]  = { ...(updated[key] ?? {}), note }
  emit('update:modelValue', updated)
}

// Context-aware placeholder based on item status
function notePlaceholder(key, status) {
  if (status === 'damaged') return 'Describe the damage…'
  if (status === 'missing') return 'Note what is missing…'
  return 'Add note…'
}
</script>
<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-2">
    <div class="flex items-center justify-between">
      <span class="text-xs text-gray-500 font-medium">{{ label }}</span>
      <div :class="['w-9 h-9 rounded-xl flex items-center justify-center', iconBg]">
        <component :is="iconComponent" class="w-5 h-5" :class="iconColor" />
      </div>
    </div>
    <div v-if="loading" class="h-7 w-24 animate-pulse bg-gray-100 rounded" />
    <div v-else class="text-2xl font-extrabold text-gray-900">
      <span v-if="prefix" class="text-sm font-semibold text-gray-500 mr-0.5">{{ prefix }}</span>
      {{ formatted }}
    </div>
    <div v-if="prev != null && !loading" class="flex items-center gap-1">
      <span :class="['text-xs font-semibold', trend >= 0 ? 'text-green-600' : 'text-red-600']">
        {{ trend >= 0 ? '↑' : '↓' }} {{ Math.abs(trend) }}%
      </span>
      <span class="text-xs text-gray-400">vs yesterday</span>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
import { TruckIcon, ClipboardIcon, BanknotesIcon, CalendarIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
const props = defineProps({ label:String, value:Number, prev:Number, color:{ default:'red' }, icon:String, prefix:String, loading:Boolean })
const iconMap     = { car:TruckIcon, clipboard:ClipboardIcon, banknote:BanknotesIcon, calendar:CalendarIcon, alert:ExclamationTriangleIcon }
const iconComponent = computed(()=>iconMap[props.icon]??CalendarIcon)
const colorMap = {
  red:    { bg:'bg-red-50',    text:'text-red-600'    },
  green:  { bg:'bg-green-50',  text:'text-green-600'  },
  purple: { bg:'bg-purple-50', text:'text-purple-600' },
  yellow: { bg:'bg-yellow-50', text:'text-yellow-600' },
  blue:   { bg:'bg-blue-50',   text:'text-blue-600'   },
}
const iconBg    = computed(()=>colorMap[props.color]?.bg   ?? 'bg-gray-50')
const iconColor = computed(()=>colorMap[props.color]?.text ?? 'text-gray-600')
const formatted = computed(()=>{
  if (props.value == null) return '—'
  if (props.prefix) return props.value.toLocaleString()
  return props.value
})
const trend = computed(()=>{
  if (props.prev == null || props.prev === 0) return 0
  return Math.round(((props.value - props.prev) / props.prev) * 100)
})
</script>
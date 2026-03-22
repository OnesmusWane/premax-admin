<template>
  <span :class="['inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full', cfg.bg, cfg.text]">
    <span class="w-1.5 h-1.5 rounded-full" :class="cfg.dot"></span>
    {{ cfg.label }}
  </span>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ stage: String })
const map = {
  waiting:       { label:'Waiting',      bg:'bg-yellow-100', text:'text-yellow-700', dot:'bg-yellow-500' },
  washing:       { label:'Washing',      bg:'bg-blue-100',   text:'text-blue-700',   dot:'bg-blue-500' },
  repair:        { label:'Repair',       bg:'bg-purple-100', text:'text-purple-700', dot:'bg-purple-500' },
  quality_check: { label:'Quality Check',bg:'bg-indigo-100', text:'text-indigo-700', dot:'bg-indigo-500' },
  done:          { label:'Done',         bg:'bg-green-100',  text:'text-green-700',  dot:'bg-green-500' },
  pending:       { label:'Pending',      bg:'bg-yellow-100', text:'text-yellow-700', dot:'bg-yellow-500' },
  confirmed:     { label:'Confirmed',    bg:'bg-blue-100',   text:'text-blue-700',   dot:'bg-blue-500' },
  in_progress:   { label:'In Progress',  bg:'bg-orange-100', text:'text-orange-700', dot:'bg-orange-500' },
  completed:     { label:'Completed',    bg:'bg-green-100',  text:'text-green-700',  dot:'bg-green-500' },
  cancelled:     { label:'Cancelled',    bg:'bg-red-100',    text:'text-red-700',    dot:'bg-red-500' },
  paid:          { label:'Paid',         bg:'bg-green-100',  text:'text-green-700',  dot:'bg-green-500' },
  unpaid:        { label:'Unpaid',       bg:'bg-red-100',    text:'text-red-700',    dot:'bg-red-500' },
}
const cfg = computed(()=>map[props.stage] ?? { label:props.stage, bg:'bg-gray-100', text:'text-gray-700', dot:'bg-gray-400' })
</script>
<template>
  <div
    class="group flex min-h-11 w-full items-center gap-3 rounded-2xl border border-gray-200/90 bg-white px-3.5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all duration-200 hover:border-gray-300 hover:shadow-[0_6px_20px_rgba(15,23,42,0.06)] focus-within:border-red-400 focus-within:ring-4 focus-within:ring-red-100/80 focus-within:shadow-[0_10px_30px_rgba(220,38,38,0.08)]"
  >
    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-400 transition-colors group-focus-within:bg-red-50 group-focus-within:text-red-500">
      <MagnifyingGlassIcon class="h-4.5 w-4.5" />
    </span>
    <input :value="modelValue" @input="$emit('update:modelValue',$event.target.value)"
      :placeholder="placeholder ?? 'Search…'"
      class="w-full !border-0 !bg-transparent !px-0 !py-0 text-sm font-medium text-gray-700 !shadow-none outline-none placeholder:font-normal placeholder:text-gray-400 focus:!ring-0">
  </div>
</template>
<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
defineProps({ modelValue:String, placeholder:String })
defineEmits(['update:modelValue'])
</script>

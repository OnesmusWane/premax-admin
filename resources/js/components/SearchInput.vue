<template>
  <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100 transition-all">
    <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 shrink-0" />
    <input :value="modelValue" @input="$emit('update:modelValue',$event.target.value)"
      :placeholder="placeholder ?? 'Search…'"
      class="bg-transparent text-sm outline-none w-full text-gray-700 placeholder-gray-400">
  </div>
</template>
<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
defineProps({ modelValue:String, placeholder:String })
defineEmits(['update:modelValue'])
</script>
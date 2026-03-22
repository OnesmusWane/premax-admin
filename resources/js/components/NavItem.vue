<template>
  <RouterLink :to="item.to"
    :class="['flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm font-medium transition-all duration-150',
      isActive ? 'bg-red-600 text-white' : 'text-gray-400 hover:text-white hover:bg-white/10']">
    <component :is="item.icon" class="w-5 h-5 shrink-0" />
    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
  </RouterLink>
</template>
<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
const props  = defineProps({ item:Object, collapsed:Boolean })
const route  = useRoute()
const isActive = computed(()=>route.path.startsWith(props.item.to))
</script>
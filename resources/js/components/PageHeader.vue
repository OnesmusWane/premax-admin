<template>
  <div class="flex items-center justify-between px-4 md:px-6 pt-5 pb-1">
    <div>
      <h2 class="text-lg font-extrabold text-gray-900">{{ title }}</h2>
      <p v-if="subtitle" class="text-xs text-gray-500 mt-0.5">{{ subtitle }}</p>
    </div>
    <div class="flex items-center gap-2">
      <slot />
    </div>
  </div>
</template>
<script setup>
defineProps({ title:String, subtitle:String })
</script>
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('update:modelValue', false)" />
        <div :class="['relative bg-white rounded-2xl shadow-2xl w-full', sizeClass]">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-900">{{ title }}</h3>
            <button @click="$emit('update:modelValue', false)" class="text-gray-400 hover:text-gray-700">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <!-- Body -->
          <div class="px-6 py-5 overflow-y-auto max-h-[70vh]">
            <slot />
          </div>
          <!-- Footer -->
          <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-100">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
<script setup>
import { computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
defineProps({ modelValue:Boolean, title:String, size:{ default:'md' } })
defineEmits(['update:modelValue'])
const sizeClass = computed(()=>({ sm:'max-w-sm', md:'max-w-4xl', lg:'max-w-lg', xl:'max-w-2xl', '2xl':'max-w-4xl' }['md']))
</script>
<style scoped>
.modal-enter-active,.modal-leave-active{transition:all .2s ease}
.modal-enter-from,.modal-leave-to{opacity:0;transform:scale(0.95)}
</style>
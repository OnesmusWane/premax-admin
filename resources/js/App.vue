<template>
  <RouterView />

  <!-- Global Toast Notifications -->
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
      <Transition
        v-for="toast in toasts"
        :key="toast.id"
        name="toast"
        appear
      >
        <div
          class="pointer-events-auto flex items-start gap-3 bg-white border shadow-lg rounded-2xl px-4 py-3 min-w-[280px] max-w-sm"
          :class="{
            'border-green-200': toast.type === 'success',
            'border-red-200':   toast.type === 'error',
            'border-yellow-200':toast.type === 'warning',
            'border-blue-200':  toast.type === 'info',
          }"
        >
          <!-- Icon -->
          <div :class="['w-5 h-5 shrink-0 mt-0.5', iconColor(toast.type)]">
            <CheckCircleIcon    v-if="toast.type === 'success'" class="w-5 h-5" />
            <XCircleIcon        v-else-if="toast.type === 'error'"   class="w-5 h-5" />
            <ExclamationCircleIcon v-else-if="toast.type === 'warning'" class="w-5 h-5" />
            <InformationCircleIcon v-else                             class="w-5 h-5" />
          </div>

          <!-- Message -->
          <div class="flex-1">
            <p v-if="toast.title" class="text-xs font-bold text-gray-900">{{ toast.title }}</p>
            <p class="text-xs text-gray-600">{{ toast.message }}</p>
          </div>

          <!-- Dismiss -->
          <button @click="removeToast(toast.id)" class="text-gray-300 hover:text-gray-500 shrink-0">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </Transition>
    </div>
  </Teleport>
</template>

<script setup>
import {
  CheckCircleIcon,
  XCircleIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useToastStore } from '@/stores/toast'
import { storeToRefs } from 'pinia'

const toast = useToastStore()
const { toasts } = storeToRefs(toast)
const { removeToast } = toast

const iconColor = type => ({
  success: 'text-green-500',
  error:   'text-red-500',
  warning: 'text-yellow-500',
  info:    'text-blue-500',
}[type] ?? 'text-gray-400')
</script>

<style scoped>
.toast-enter-active { transition: all 0.3s ease; }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(100%); }
.toast-leave-to     { opacity: 0; transform: translateX(100%); }
</style>

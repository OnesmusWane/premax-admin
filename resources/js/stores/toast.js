import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])

  function add(message, type = 'info', title = null, duration = 4000) {
    const id = Date.now()
    toasts.value.push({ id, message, type, title })
    setTimeout(() => removeToast(id), duration)
  }

  function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  // Convenience methods
  const success = (msg, title) => add(msg, 'success', title)
  const error   = (msg, title) => add(msg, 'error',   title)
  const warning = (msg, title) => add(msg, 'warning', title)
  const info    = (msg, title) => add(msg, 'info',    title)

  return { toasts, add, removeToast, success, error, warning, info }
})
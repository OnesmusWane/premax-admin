<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="$emit('close')">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Pick from Media Library</h3>
              <p class="text-xs text-gray-400 mt-0.5">{{ multiple ? 'Select one or more images' : 'Click an image to select it' }}</p>
            </div>
            <div class="flex items-center gap-3">
              <span v-if="selected.length" class="text-xs font-semibold text-custom-primary">
                {{ selected.length }} selected
              </span>
              <button v-if="multiple && selected.length" @click="confirm"
                class="text-xs font-bold px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white transition">
                Use {{ selected.length }} image{{ selected.length !== 1 ? 's' : '' }}
              </button>
              <button @click="$emit('close')" class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 transition">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>
          </div>

          <!-- Search -->
          <div class="px-6 py-3 border-b border-gray-100">
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
              <input v-model="search" @input="onSearch"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-red-400 outline-none transition"
                placeholder="Search images..." />
            </div>
          </div>

          <!-- Grid -->
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="loading" class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3">
              <div v-for="i in 12" :key="i" class="aspect-square animate-pulse rounded-xl bg-gray-100" />
            </div>

            <div v-else-if="!items.length" class="flex flex-col items-center justify-center py-16 text-center">
              <PhotoIcon class="w-10 h-10 text-gray-300 mb-3" />
              <p class="text-sm text-gray-400">No images found</p>
            </div>

            <div v-else class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3">
              <button v-for="item in items" :key="item.id" type="button"
                @click="toggle(item)"
                :class="['relative aspect-square rounded-xl overflow-hidden border-2 transition group focus:outline-none',
                  isSelected(item) ? 'border-red-500 ring-2 ring-red-300' : 'border-transparent hover:border-gray-300']">
                <img :src="item.url" :alt="item.alt_text || item.name"
                  class="w-full h-full object-cover transition group-hover:scale-105" loading="lazy" />
                <div v-if="isSelected(item)"
                  class="absolute inset-0 bg-red-600/20 flex items-center justify-center">
                  <CheckCircleIcon class="w-7 h-7 text-red-600 drop-shadow" />
                </div>
              </button>
            </div>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2 mt-5">
              <button :disabled="meta.current_page === 1" @click="load(meta.current_page - 1)"
                class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 disabled:opacity-40 transition">Prev</button>
              <span class="text-xs text-gray-400">{{ meta.current_page }} / {{ meta.last_page }}</span>
              <button :disabled="meta.current_page === meta.last_page" @click="load(meta.current_page + 1)"
                class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 disabled:opacity-40 transition">Next</button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { CheckCircleIcon, MagnifyingGlassIcon, PhotoIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'

const props = defineProps({
  open:     { type: Boolean, default: false },
  multiple: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'select'])

const { get } = useApi()

const items   = ref([])
const meta    = ref({ current_page: 1, last_page: 1 })
const loading = ref(false)
const search  = ref('')
const selected = ref([])

let searchTimer = null
function onSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => load(1), 300)
}

async function load(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 30, type: 'image' }
    if (search.value.trim()) params.search = search.value.trim()
    const data = await get('/media', params)
    items.value = data.data ?? []
    meta.value  = data.meta ?? meta.value
  } catch { /* silent */ }
  finally { loading.value = false }
}

function isSelected(item) {
  return selected.value.some(s => s.id === item.id)
}

function toggle(item) {
  if (!props.multiple) {
    emit('select', item.url)
    emit('close')
    return
  }
  if (isSelected(item)) {
    selected.value = selected.value.filter(s => s.id !== item.id)
  } else {
    selected.value.push(item)
  }
}

function confirm() {
  emit('select', selected.value.map(s => s.url))
  emit('close')
}

watch(() => props.open, (val) => {
  if (val) { selected.value = []; search.value = ''; load(1) }
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>

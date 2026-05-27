<template>
  <div class="space-y-6">
    <!-- Upload progress bar -->
    <div v-if="uploading" class="overflow-hidden rounded-xl bg-slate-100">
      <div class="h-1.5 animate-pulse rounded-xl bg-custom-primary" style="width:100%" />
    </div>

    <!-- Filters & search -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <div class="relative flex-1">
        <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          v-model="search"
          class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-custom-primary focus:bg-white"
          placeholder="Search by name or tag..."
        />
      </div>
      <div class="flex gap-2">
        <button v-for="t in typeOptions" :key="t.value" @click="typeFilter = t.value"
          class="rounded-xl px-4 py-2.5 text-sm font-bold transition"
          :class="typeFilter === t.value ? 'bg-[rgba(211,30,36,0.08)] text-custom-primary' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'">
          {{ t.label }}
        </button>
      </div>
      <label class="inline-flex cursor-pointer items-center rounded-xl bg-custom-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-custom-primary-dark shrink-0">
        <ArrowUpTrayIcon class="mr-2 h-4 w-4" />
        {{ uploading ? 'Uploading...' : 'Upload' }}
        <input ref="fileInput" type="file" class="hidden"
          accept="image/jpeg,image/png,image/webp,image/avif,video/mp4,video/quicktime"
          @change="handleUpload" />
      </label>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading && !media.length" class="grid gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
      <div v-for="i in 12" :key="i" class="aspect-square animate-pulse rounded-2xl bg-slate-100" />
    </div>

    <!-- Empty state -->
    <div v-else-if="!media.length" class="rounded-2xl border border-dashed border-slate-200 px-6 py-16 text-center">
      <PhotoIcon class="mx-auto h-10 w-10 text-slate-300" />
      <p class="mt-3 text-sm font-semibold text-slate-500">No media yet</p>
      <p class="mt-1 text-xs text-slate-400">Upload images and videos to build your library.</p>
    </div>

    <!-- Grid -->
    <div v-else class="grid gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
      <button v-for="item in media" :key="item.id" @click="openDetail(item)"
        class="group relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 transition hover:border-slate-400 hover:shadow-md focus:outline-none">
        <img v-if="item.type === 'image'" :src="item.url" :alt="item.alt_text || item.name"
          class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
        <div v-else class="flex h-full flex-col items-center justify-center bg-slate-900 text-white">
          <PlayCircleIcon class="h-8 w-8 opacity-70" />
          <span v-if="item.duration" class="mt-1 text-[10px] font-bold opacity-60">{{ formatDuration(item.duration) }}</span>
        </div>
        <div class="absolute inset-0 flex flex-col justify-end bg-linear-to-t from-black/60 to-transparent p-2 opacity-0 transition group-hover:opacity-100">
          <p class="truncate text-[10px] font-bold text-white">{{ item.name }}</p>
          <p class="text-[9px] text-white/70">{{ item.size_formatted }}</p>
        </div>
        <div v-if="item.type === 'video'"
          class="absolute left-1.5 top-1.5 rounded bg-black/70 px-1.5 py-0.5 text-[9px] font-bold text-white">VIDEO</div>
        <div v-if="item.is_published && item.type === 'image'"
          class="absolute right-1.5 top-1.5 rounded-full bg-green-500 w-2 h-2" title="Published to gallery" />
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2">
      <button :disabled="meta.current_page === 1" @click="loadPage(meta.current_page - 1)"
        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40">Previous</button>
      <span class="text-sm text-slate-500">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      <button :disabled="meta.current_page === meta.last_page" @click="loadPage(meta.current_page + 1)"
        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40">Next</button>
    </div>

    <!-- Detail side-panel -->
    <Teleport to="body">
      <Transition name="panel">
        <div v-if="selectedItem" class="fixed inset-0 z-50 flex" @click.self="closeDetail">
          <div class="ml-auto flex h-full w-full max-w-md flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
              <h3 class="text-base font-black text-slate-950">Media Details</h3>
              <button @click="closeDetail" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100">
                <XMarkIcon class="h-5 w-5" />
              </button>
            </div>

            <div class="shrink-0 border-b border-slate-100 bg-slate-50">
              <img v-if="selectedItem.type === 'image'" :src="selectedItem.url" :alt="selectedItem.name"
                class="mx-auto max-h-56 w-full object-contain p-4" />
              <div v-else class="flex h-40 items-center justify-center bg-slate-900">
                <PlayCircleIcon class="h-14 w-14 text-white/60" />
              </div>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
              <!-- File info -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <div class="mb-1 text-xs font-bold uppercase tracking-widest text-slate-400">Type</div>
                  <p class="text-sm text-slate-700 capitalize">{{ selectedItem.type }}</p>
                </div>
                <div>
                  <div class="mb-1 text-xs font-bold uppercase tracking-widest text-slate-400">Size</div>
                  <p class="text-sm text-slate-700">{{ selectedItem.size_formatted }}</p>
                </div>
                <div v-if="selectedItem.width">
                  <div class="mb-1 text-xs font-bold uppercase tracking-widest text-slate-400">Dimensions</div>
                  <p class="text-sm text-slate-700">{{ selectedItem.width }} × {{ selectedItem.height }}</p>
                </div>
                <div v-if="selectedItem.duration">
                  <div class="mb-1 text-xs font-bold uppercase tracking-widest text-slate-400">Duration</div>
                  <p class="text-sm text-slate-700">{{ formatDuration(selectedItem.duration) }}</p>
                </div>
              </div>

              <!-- URL -->
              <div>
                <div class="mb-1.5 text-xs font-bold uppercase tracking-widest text-slate-400">URL</div>
                <div class="flex gap-2">
                  <input :value="selectedItem.url" readonly class="input-base flex-1 truncate text-xs" />
                  <button @click="copyUrl(selectedItem.url)"
                    class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Copy</button>
                </div>
              </div>

              <!-- Name -->
              <div>
                <label class="text-xs font-semibold text-slate-600 block mb-1.5">Name</label>
                <input v-model="localName" class="input-base text-sm" placeholder="Media name…" maxlength="255" />
              </div>

              <!-- ── Gallery section (images only) ─────────────────────────── -->
              <div v-if="selectedItem.type === 'image'" class="rounded-xl border border-slate-200 p-4 space-y-3">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Website Gallery</p>

                <!-- Published toggle -->
                <label class="flex items-center justify-between cursor-pointer">
                  <div>
                    <p class="text-sm font-semibold text-slate-800">Publish to Gallery</p>
                    <p class="text-xs text-slate-400 mt-0.5">Show on the public gallery page</p>
                  </div>
                  <div @click="localPublished = !localPublished"
                    :class="['relative w-10 h-5 rounded-full transition-colors', localPublished ? 'bg-green-500' : 'bg-slate-200']">
                    <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform', localPublished ? 'translate-x-5' : '']" />
                  </div>
                </label>

                <!-- Alt text -->
                <div>
                  <label class="text-xs font-semibold text-slate-600 block mb-1">Alt Text</label>
                  <input v-model="localAltText" class="input-base text-sm" placeholder="Describe the image…" maxlength="160" />
                </div>

                <button v-if="galleryFieldsChanged" @click="saveGalleryFields" :disabled="savingFields"
                  class="w-full rounded-xl bg-green-600 py-2 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-60 transition">
                  {{ savingFields ? 'Saving…' : 'Save' }}
                </button>
              </div>

              <!-- Save for non-image (video name change) -->
              <button v-if="selectedItem.type !== 'image' && galleryFieldsChanged" @click="saveGalleryFields" :disabled="savingFields"
                class="w-full rounded-xl bg-green-600 py-2 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-60 transition">
                {{ savingFields ? 'Saving…' : 'Save' }}
              </button>

              <!-- Tags -->
              <div>
                <div class="mb-1.5 text-xs font-bold uppercase tracking-widest text-slate-400">Tags</div>
                <div class="flex flex-wrap gap-2 mb-2">
                  <span v-for="tag in localTags" :key="tag"
                    class="flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                    {{ tag }}
                    <button @click="removeTag(tag)" class="text-slate-400 hover:text-slate-700">×</button>
                  </span>
                </div>
                <div class="flex gap-2">
                  <input v-model="tagInput" class="input-base flex-1 text-sm" placeholder="Add tag..." maxlength="50"
                    @keydown.enter.prevent="addTag" />
                  <button @click="addTag"
                    class="shrink-0 rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200">Add</button>
                </div>
                <button v-if="tagsChanged" @click="saveTags" :disabled="savingTags"
                  class="mt-3 w-full rounded-xl bg-custom-primary py-2 text-sm font-bold text-white transition hover:bg-custom-primary-dark disabled:opacity-60">
                  {{ savingTags ? 'Saving...' : 'Save Tags' }}
                </button>
              </div>
            </div>

            <div class="border-t border-slate-100 px-6 py-4">
              <button @click="deleteMedia(selectedItem)" :disabled="deleting"
                class="w-full rounded-xl border border-red-200 py-2.5 text-sm font-bold text-red-600 transition hover:bg-red-50 disabled:opacity-60">
                {{ deleting ? 'Deleting...' : 'Delete from Library' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ArrowUpTrayIcon, MagnifyingGlassIcon, PhotoIcon, PlayCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import { useApi } from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const { get, patch, del } = useApi()

const media        = ref([])
const meta         = ref({ current_page: 1, last_page: 1, per_page: 24, total: 0 })
const loading      = ref(false)
const uploading    = ref(false)
const deleting     = ref(false)
const savingTags   = ref(false)
const savingFields = ref(false)
const search       = ref('')
const typeFilter   = ref('')
const fileInput    = ref(null)
const selectedItem = ref(null)
const tagInput     = ref('')
const localTags      = ref([])
const localName      = ref('')
const localPublished = ref(false)
const localAltText   = ref('')

const typeOptions = [
  { value: '',      label: 'All' },
  { value: 'image', label: 'Images' },
  { value: 'video', label: 'Videos' },
]

const tagsChanged = computed(() => {
  if (!selectedItem.value) return false
  return JSON.stringify([...(selectedItem.value.tags || [])].sort()) !==
         JSON.stringify([...localTags.value].sort())
})

const galleryFieldsChanged = computed(() => {
  if (!selectedItem.value) return false
  return localName.value      !== (selectedItem.value.name        ?? '') ||
         localPublished.value !== !!selectedItem.value.is_published      ||
         localAltText.value   !== (selectedItem.value.alt_text    ?? '')
})

let searchTimer = null
watch([search, typeFilter], () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadMedia(), 300)
})

async function loadMedia(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 24 }
    if (typeFilter.value)  params.type   = typeFilter.value
    if (search.value.trim()) params.search = search.value.trim()
    const data = await get('/media', params)
    media.value = data.data || []
    meta.value  = data.meta || meta.value
  } catch {
    toast.error('Failed to load media.')
  } finally {
    loading.value = false
  }
}

function loadPage(page) { loadMedia(page) }

async function handleUpload(event) {
  const file = event.target.files[0]
  if (!file) return
  if (fileInput.value) fileInput.value.value = ''
  uploading.value = true
  try {
    const form = new FormData()
    form.append('file', file)
    await axios.post('/media', form, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.success('Media uploaded.')
    await loadMedia()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Upload failed.')
  } finally {
    uploading.value = false
  }
}

function openDetail(item) {
  selectedItem.value   = item
  localTags.value      = [...(item.tags || [])]
  localName.value      = item.name         ?? ''
  localPublished.value = !!item.is_published
  localAltText.value   = item.alt_text     ?? ''
  tagInput.value = ''
}

function closeDetail() {
  selectedItem.value = null
  localTags.value    = []
  tagInput.value     = ''
}

function addTag() {
  const tag = tagInput.value.trim()
  if (!tag || localTags.value.includes(tag) || localTags.value.length >= 10) return
  localTags.value.push(tag)
  tagInput.value = ''
}

function removeTag(tag) {
  localTags.value = localTags.value.filter(t => t !== tag)
}

async function saveTags() {
  if (!selectedItem.value) return
  savingTags.value = true
  try {
    const updated = await patch(`/media/${selectedItem.value.id}`, { tags: localTags.value })
    Object.assign(selectedItem.value, updated)
    localTags.value = [...(updated.tags || [])]
    media.value = media.value.map(m => m.id === updated.id ? updated : m)
    toast.success('Tags saved.')
  } catch {
    toast.error('Failed to save tags.')
  } finally {
    savingTags.value = false
  }
}

async function saveGalleryFields() {
  if (!selectedItem.value) return
  savingFields.value = true
  try {
    const updated = await patch(`/media/${selectedItem.value.id}`, {
      name:         localName.value.trim() || null,
      is_published: localPublished.value,
      alt_text:     localAltText.value || null,
    })
    Object.assign(selectedItem.value, updated)
    media.value = media.value.map(m => m.id === updated.id ? updated : m)
    toast.success('Saved.')
  } catch {
    toast.error('Failed to save.')
  } finally {
    savingFields.value = false
  }
}

async function deleteMedia(item) {
  if (!confirm(`Delete "${item.name}"?`)) return
  deleting.value = true
  try {
    await del(`/media/${item.id}`)
    media.value = media.value.filter(m => m.id !== item.id)
    closeDetail()
    toast.success('Deleted.')
  } catch {
    toast.error('Failed to delete.')
  } finally {
    deleting.value = false
  }
}

async function copyUrl(url) {
  try {
    await navigator.clipboard.writeText(url)
    toast.success('URL copied.')
  } catch {
    toast.error('Could not copy URL.')
  }
}

function formatDuration(seconds) {
  if (!seconds) return ''
  const m = Math.floor(seconds / 60), s = seconds % 60
  return `${m}:${String(s).padStart(2, '0')}`
}

onMounted(() => { loadMedia() })
</script>

<style scoped>
.panel-enter-active, .panel-leave-active { transition: opacity 0.2s ease }
.panel-enter-from, .panel-leave-to { opacity: 0 }
.panel-enter-active > div, .panel-leave-active > div { transition: transform 0.25s ease }
.panel-enter-from > div, .panel-leave-to > div { transform: translateX(100%) }
</style>

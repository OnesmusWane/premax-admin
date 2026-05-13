<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="text-3xl font-black tracking-tight text-slate-950">Media Library</h2>
        <p class="mt-1 text-sm text-slate-500">All images and videos uploaded to your social posts — stored and reusable.</p>
      </div>
      <label class="inline-flex cursor-pointer items-center rounded-xl bg-[var(--color-custom-primary)] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#b71a1f]">
        <ArrowUpTrayIcon class="mr-2 h-4 w-4" />
        {{ uploading ? 'Uploading...' : 'Upload Media' }}
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          accept="image/jpeg,image/png,video/mp4,video/quicktime"
          @change="handleUpload"
        />
      </label>
    </div>

    <!-- Filters & search -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <div class="relative flex-1">
        <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          v-model="search"
          class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-[var(--color-custom-primary)] focus:bg-white"
          placeholder="Search by filename or tag..."
        />
      </div>
      <div class="flex gap-2">
        <button
          v-for="t in typeOptions"
          :key="t.value"
          @click="typeFilter = t.value"
          class="rounded-xl px-4 py-2.5 text-sm font-bold transition"
          :class="typeFilter === t.value
            ? 'bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]'
            : 'border border-slate-200 text-slate-600 hover:bg-slate-50'"
        >
          {{ t.label }}
        </button>
      </div>
    </div>

    <!-- Upload progress bar -->
    <div v-if="uploading" class="overflow-hidden rounded-xl bg-slate-100">
      <div class="h-1.5 animate-pulse rounded-xl bg-[var(--color-custom-primary)]" style="width:100%" />
    </div>

    <!-- Loading skeleton grid -->
    <div v-if="loading && !media.length" class="grid gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
      <div v-for="i in 12" :key="i" class="aspect-square animate-pulse rounded-2xl bg-slate-100" />
    </div>

    <!-- Empty state -->
    <div v-else-if="!media.length" class="rounded-2xl border border-dashed border-slate-200 px-6 py-16 text-center">
      <PhotoIcon class="mx-auto h-10 w-10 text-slate-300" />
      <p class="mt-3 text-sm font-semibold text-slate-500">No media yet</p>
      <p class="mt-1 text-xs text-slate-400">Upload images and videos to start building your library.</p>
    </div>

    <!-- Grid -->
    <div v-else class="grid gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
      <button
        v-for="item in media"
        :key="item.id"
        @click="openDetail(item)"
        class="group relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 transition hover:border-slate-400 hover:shadow-md focus:outline-none"
      >
        <!-- Image thumbnail -->
        <img
          v-if="item.type === 'image'"
          :src="item.url"
          :alt="item.name"
          class="h-full w-full object-cover transition group-hover:scale-105"
          loading="lazy"
        />

        <!-- Video placeholder -->
        <div v-else class="flex h-full flex-col items-center justify-center bg-slate-900 text-white">
          <PlayCircleIcon class="h-8 w-8 opacity-70" />
          <span v-if="item.duration" class="mt-1 text-[10px] font-bold opacity-60">
            {{ formatDuration(item.duration) }}
          </span>
        </div>

        <!-- Overlay info -->
        <div
          class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 transition group-hover:opacity-100"
        >
          <p class="truncate text-[10px] font-bold text-white">{{ item.name }}</p>
          <p class="text-[9px] text-white/70">{{ item.size_formatted }}</p>
        </div>

        <!-- Video badge -->
        <div
          v-if="item.type === 'video'"
          class="absolute left-1.5 top-1.5 rounded bg-black/70 px-1.5 py-0.5 text-[9px] font-bold text-white"
        >
          VIDEO
        </div>
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2">
      <button
        :disabled="meta.current_page === 1"
        @click="loadPage(meta.current_page - 1)"
        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
      >
        Previous
      </button>
      <span class="text-sm text-slate-500">
        Page {{ meta.current_page }} of {{ meta.last_page }}
      </span>
      <button
        :disabled="meta.current_page === meta.last_page"
        @click="loadPage(meta.current_page + 1)"
        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
      >
        Next
      </button>
    </div>

    <!-- Detail side-panel -->
    <Teleport to="body">
      <Transition name="panel">
        <div v-if="selectedItem" class="fixed inset-0 z-50 flex" @click.self="closeDetail">
          <div class="ml-auto flex h-full w-full max-w-md flex-col bg-white shadow-2xl">
            <!-- Panel header -->
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
              <h3 class="text-base font-black text-slate-950">Media Details</h3>
              <button @click="closeDetail" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100">
                <XMarkIcon class="h-5 w-5" />
              </button>
            </div>

            <!-- Preview -->
            <div class="shrink-0 border-b border-slate-100 bg-slate-50">
              <img
                v-if="selectedItem.type === 'image'"
                :src="selectedItem.url"
                :alt="selectedItem.name"
                class="mx-auto max-h-56 w-full object-contain p-4"
              />
              <div v-else class="flex h-40 items-center justify-center bg-slate-900">
                <PlayCircleIcon class="h-14 w-14 text-white/60" />
              </div>
            </div>

            <!-- Meta -->
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
              <div>
                <div class="mb-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Filename</div>
                <p class="text-sm font-semibold text-slate-800 break-all">{{ selectedItem.name }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <div class="mb-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Type</div>
                  <p class="text-sm text-slate-700 capitalize">{{ selectedItem.type }}</p>
                </div>
                <div>
                  <div class="mb-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Size</div>
                  <p class="text-sm text-slate-700">{{ selectedItem.size_formatted }}</p>
                </div>
                <div v-if="selectedItem.width">
                  <div class="mb-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Dimensions</div>
                  <p class="text-sm text-slate-700">{{ selectedItem.width }} × {{ selectedItem.height }}</p>
                </div>
                <div v-if="selectedItem.duration">
                  <div class="mb-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Duration</div>
                  <p class="text-sm text-slate-700">{{ formatDuration(selectedItem.duration) }}</p>
                </div>
                <div>
                  <div class="mb-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Used</div>
                  <p class="text-sm text-slate-700">{{ selectedItem.used_count }}×</p>
                </div>
                <div v-if="selectedItem.last_used_at">
                  <div class="mb-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Last Used</div>
                  <p class="text-sm text-slate-700">{{ formatDate(selectedItem.last_used_at) }}</p>
                </div>
              </div>

              <!-- URL copy -->
              <div>
                <div class="mb-1.5 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">URL</div>
                <div class="flex gap-2">
                  <input :value="selectedItem.url" readonly class="input-base flex-1 truncate text-xs" />
                  <button
                    @click="copyUrl(selectedItem.url)"
                    class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                  >
                    Copy
                  </button>
                </div>
              </div>

              <!-- Tags -->
              <div>
                <div class="mb-1.5 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Tags</div>
                <div class="flex flex-wrap gap-2 mb-2">
                  <span
                    v-for="tag in (selectedItem.tags || [])"
                    :key="tag"
                    class="flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700"
                  >
                    {{ tag }}
                    <button @click="removeTag(tag)" class="text-slate-400 hover:text-slate-700">×</button>
                  </span>
                </div>
                <div class="flex gap-2">
                  <input
                    v-model="tagInput"
                    class="input-base flex-1 text-sm"
                    placeholder="Add tag..."
                    maxlength="50"
                    @keydown.enter.prevent="addTag"
                  />
                  <button
                    @click="addTag"
                    class="shrink-0 rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200"
                  >
                    Add
                  </button>
                </div>
                <button
                  v-if="tagsChanged"
                  @click="saveTags"
                  :disabled="savingTags"
                  class="mt-3 w-full rounded-xl bg-[var(--color-custom-primary)] py-2 text-sm font-bold text-white transition hover:bg-[#b71a1f] disabled:opacity-60"
                >
                  {{ savingTags ? 'Saving...' : 'Save Tags' }}
                </button>
              </div>
            </div>

            <!-- Delete button -->
            <div class="border-t border-slate-100 px-6 py-4">
              <button
                @click="deleteMedia(selectedItem)"
                :disabled="deleting"
                class="w-full rounded-xl border border-red-200 py-2.5 text-sm font-bold text-red-600 transition hover:bg-red-50 disabled:opacity-60"
              >
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
import {
  ArrowUpTrayIcon,
  MagnifyingGlassIcon,
  PhotoIcon,
  PlayCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'
import { useApi } from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const { get, put, del } = useApi()

const media        = ref([])
const meta         = ref({ current_page: 1, last_page: 1, per_page: 24, total: 0 })
const loading      = ref(false)
const uploading    = ref(false)
const deleting     = ref(false)
const savingTags   = ref(false)
const search       = ref('')
const typeFilter   = ref('')
const fileInput    = ref(null)
const selectedItem = ref(null)
const tagInput     = ref('')
const localTags    = ref([])

const typeOptions = [
  { value: '',      label: 'All' },
  { value: 'image', label: 'Images' },
  { value: 'video', label: 'Videos' },
]

const tagsChanged = computed(() => {
  if (!selectedItem.value) return false
  const orig = JSON.stringify([...(selectedItem.value.tags || [])].sort())
  const curr = JSON.stringify([...localTags.value].sort())
  return orig !== curr
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
    if (typeFilter.value) params.type = typeFilter.value
    if (search.value.trim()) params.search = search.value.trim()
    const data = await get('/media-library', params)
    media.value = data.data || []
    meta.value  = data.meta || meta.value
  } catch {
    toast.error('Failed to load media library.')
  } finally {
    loading.value = false
  }
}

function loadPage(page) {
  loadMedia(page)
}

async function handleUpload(event) {
  const file = event.target.files[0]
  if (!file) return
  if (fileInput.value) fileInput.value.value = ''

  uploading.value = true
  try {
    const form = new FormData()
    form.append('file', file)
    const res = await axios.post('/admin/social-media/media', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    toast.success('Media uploaded successfully.')
    await loadMedia()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Upload failed.')
  } finally {
    uploading.value = false
  }
}

function openDetail(item) {
  selectedItem.value = item
  localTags.value    = [...(item.tags || [])]
  tagInput.value     = ''
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
    const updated = await put(`/media-library/${selectedItem.value.id}/tags`, { tags: localTags.value })
    selectedItem.value.tags = updated.tags
    localTags.value = [...(updated.tags || [])]
    media.value = media.value.map(m => m.id === updated.id ? updated : m)
    toast.success('Tags saved.')
  } catch {
    toast.error('Failed to save tags.')
  } finally {
    savingTags.value = false
  }
}

async function deleteMedia(item) {
  if (!confirm(`Delete "${item.name}" from your library?`)) return
  deleting.value = true
  try {
    await del(`/media-library/${item.id}`)
    media.value = media.value.filter(m => m.id !== item.id)
    closeDetail()
    toast.success('Media deleted.')
  } catch {
    toast.error('Failed to delete media.')
  } finally {
    deleting.value = false
  }
}

async function copyUrl(url) {
  try {
    await navigator.clipboard.writeText(url)
    toast.success('URL copied to clipboard.')
  } catch {
    toast.error('Could not copy URL.')
  }
}

function formatDuration(seconds) {
  if (!seconds) return ''
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${String(s).padStart(2, '0')}`
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(loadMedia)
</script>

<style scoped>
.panel-enter-active,
.panel-leave-active {
  transition: opacity 0.2s ease;
}
.panel-enter-from,
.panel-leave-to {
  opacity: 0;
}
.panel-enter-active > div,
.panel-leave-active > div {
  transition: transform 0.25s ease;
}
.panel-enter-from > div,
.panel-leave-to > div {
  transform: translateX(100%);
}
</style>

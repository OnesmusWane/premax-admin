<template>
  <div class="p-4 md:p-6 space-y-4">
    <PageHeader title="Gallery" subtitle="Upload and manage photos for the website gallery">
      <button @click="triggerPicker"
        class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors">
        <ArrowUpTrayIcon class="w-4 h-4" />
        Upload Images
      </button>
    </PageHeader>

    <div class="mx-4 md:mx-6 grid grid-cols-1 xl:grid-cols-[380px,1fr] gap-4">
      <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
        <div>
          <h3 class="text-sm font-bold text-gray-900">Add Gallery Images</h3>
          <p class="text-xs text-gray-500 mt-1">Upload one or more photos. Published images are immediately available from the public gallery API.</p>
        </div>

        <input ref="fileInput" type="file" accept="image/*" multiple class="hidden" @change="onFileChange">

        <button type="button" @click="triggerPicker"
          class="w-full border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-red-300 hover:bg-red-50/40 transition-colors">
          <PhotoIcon class="w-8 h-8 mx-auto text-gray-300" />
          <div class="mt-3 text-sm font-semibold text-gray-700">Choose gallery images</div>
          <div class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP or AVIF, up to 4MB each, max 2400×1600 px</div>
        </button>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
          Recommended upload limits: up to 12 images at once, each image no larger than 4MB, 2400×1600 pixels, and 3,840,000 total pixels.
        </div>

        <div v-if="selectedFiles.length" class="space-y-3">
          <div class="text-xs font-semibold text-gray-600">{{ selectedFiles.length }} image{{ selectedFiles.length !== 1 ? 's' : '' }} selected</div>
          <div class="grid grid-cols-3 gap-2">
            <div v-for="file in selectedFiles" :key="file.name + file.size" class="aspect-square rounded-xl overflow-hidden bg-gray-100">
              <img :src="file.preview" :alt="file.name" class="w-full h-full object-cover">
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Service</label>
            <select v-model="uploadForm.service_id" class="input-base">
              <option value="">All services / no specific service</option>
              <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
            </select>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Title</label>
            <input v-model="uploadForm.title" class="input-base" placeholder="Optional title for a single image">
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Alt Text</label>
            <input v-model="uploadForm.alt_text" class="input-base" placeholder="Short image description for accessibility">
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Description</label>
            <textarea v-model="uploadForm.description" rows="4" class="input-base resize-none" placeholder="Optional caption or details"></textarea>
          </div>

          <label class="flex items-center gap-2 text-xs font-semibold text-gray-700">
            <input v-model="uploadForm.is_published" type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
            Publish on website immediately
          </label>
        </div>

        <div v-if="uploadError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ uploadError }}</div>
        <div v-if="selectionErrors.length" class="text-xs text-amber-700 bg-amber-50 rounded-xl px-3 py-2 space-y-1">
          <div v-for="message in selectionErrors" :key="message">{{ message }}</div>
        </div>

        <button @click="uploadImages" :disabled="uploading || !selectedFiles.length"
          class="w-full py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl disabled:opacity-60 flex items-center justify-center gap-2 transition-colors">
          <span v-if="uploading" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
          {{ uploading ? 'Uploading…' : 'Save Gallery Images' }}
        </button>
      </section>

      <section class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Gallery Library</h3>
              <p class="text-xs text-gray-500 mt-1">{{ galleryItems.length }} item{{ galleryItems.length !== 1 ? 's' : '' }} in your gallery</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="filter = 'all'"
                :class="filterClass('all')">All</button>
              <button @click="filter = 'published'"
                :class="filterClass('published')">Published</button>
              <button @click="filter = 'draft'"
                :class="filterClass('draft')">Drafts</button>
            </div>
          </div>
        </div>

        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4">
          <div v-for="i in 6" :key="i" class="h-80 bg-white rounded-2xl border border-gray-100 animate-pulse"></div>
        </div>

        <div v-else-if="!filteredItems.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
          <PhotoIcon class="w-10 h-10 mx-auto text-gray-200" />
          <div class="mt-3 text-sm font-semibold text-gray-700">No gallery images yet</div>
          <div class="text-xs text-gray-400 mt-1">Upload photos here and they’ll be ready for website visualization.</div>
        </div>

        <div v-else class="space-y-4">
          <article v-for="group in groupedGallery" :key="group.key"
            class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <button @click="toggleGroup(group.key)"
              class="w-full flex items-center justify-between gap-4 px-5 py-4 bg-gray-50 hover:bg-gray-100/80 transition-colors">
              <div class="min-w-0 text-left">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="text-sm font-bold text-gray-900">{{ group.label }}</h4>
                  <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-600">
                    {{ group.items.length }} image{{ group.items.length !== 1 ? 's' : '' }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Compact admin view grouped by service for easier website organization.</p>
              </div>
              <span class="text-xs font-semibold text-gray-500">{{ expandedGroups[group.key] ? 'Hide' : 'Show' }}</span>
            </button>

            <div v-if="expandedGroups[group.key]" class="p-4 md:p-5">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <article v-for="item in group.items" :key="item.id"
                  class="border border-gray-100 rounded-2xl bg-white p-3">
                  <div class="flex gap-3">
                    <button @click="openPreview(item)"
                      class="w-28 h-24 shrink-0 rounded-xl overflow-hidden bg-gray-100 border border-gray-100 hover:border-red-200 transition-colors">
                      <img :src="item.image_url" :alt="item.alt_text || item.title || 'Gallery image'" class="w-full h-full object-cover">
                    </button>

                    <div class="min-w-0 flex-1 space-y-2">
                      <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                          <input :value="item.title ?? ''" @change="updateItem(item, 'title', $event.target.value)"
                            class="w-full text-xs font-bold text-gray-900 bg-transparent border-0 border-b border-transparent focus:border-red-200 px-0 py-0.5 focus:ring-0"
                            placeholder="Image title">
                          <div class="text-[10px] text-gray-400 mt-1">Sort order: {{ item.sort_order }}</div>
                        </div>
                        <span :class="['text-[10px] font-bold px-2 py-1 rounded-full shrink-0',
                          item.is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                          {{ item.is_published ? 'Published' : 'Draft' }}
                        </span>
                      </div>

                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <select :value="item.service_id ?? ''" @change="updateItem(item, 'service_id', normalizeServiceId($event.target.value))"
                          class="input-base text-xs min-h-0 py-2">
                          <option value="">No linked service</option>
                          <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
                        </select>

                        <input :value="item.alt_text ?? ''" @change="updateItem(item, 'alt_text', $event.target.value)"
                          class="input-base text-xs min-h-0 py-2" placeholder="Alt text">
                      </div>

                      <textarea :value="item.description ?? ''" rows="2"
                        @change="updateItem(item, 'description', $event.target.value)"
                        class="input-base resize-none text-xs min-h-0"
                        placeholder="Caption or short description"></textarea>

                      <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        <button @click="openPreview(item)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors">
                          View
                        </button>
                        <button @click="togglePublished(item)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border transition-colors"
                          :class="item.is_published ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-700'">
                          {{ item.is_published ? 'Unpublish' : 'Publish' }}
                        </button>
                        <button @click="moveItem(item, -1)" :disabled="isMoving(item, -1)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border border-gray-200 text-gray-700 disabled:opacity-50">
                          Up
                        </button>
                        <button @click="moveItem(item, 1)" :disabled="isMoving(item, 1)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border border-gray-200 text-gray-700 disabled:opacity-50">
                          Down
                        </button>
                        <button @click="removeItem(item)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                          Delete
                        </button>
                      </div>
                    </div>
                  </div>
                </article>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>

    <Modal v-model="showPreview" title="Gallery Preview" size="2xl">
      <div v-if="previewItem" class="space-y-4">
        <div class="rounded-2xl overflow-hidden bg-gray-100 border border-gray-100">
          <img :src="previewItem.image_url" :alt="previewItem.alt_text || previewItem.title || 'Gallery image'"
            class="w-full max-h-[70vh] object-contain bg-gray-50">
        </div>
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div>
            <div class="text-sm font-bold text-gray-900">{{ previewItem.title || 'Untitled image' }}</div>
            <div class="text-xs text-gray-500 mt-1">
              {{ previewItem.service?.name || 'No linked service' }} · {{ previewItem.is_published ? 'Published' : 'Draft' }}
            </div>
          </div>
          <div class="text-xs text-gray-500">Sort order: {{ previewItem.sort_order }}</div>
        </div>
        <p v-if="previewItem.description" class="text-sm text-gray-600">{{ previewItem.description }}</p>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { ArrowUpTrayIcon, PhotoIcon } from '@heroicons/vue/24/outline'
import Modal from '@/components/Modal.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useApi } from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'

const { get, post, patch, del } = useApi()
const toast = useToastStore()

const loading = ref(false)
const uploading = ref(false)
const uploadError = ref(null)
const fileInput = ref(null)
const galleryItems = ref([])
const services = ref([])
const filter = ref('all')
const selectedFiles = ref([])
const pendingMoves = ref([])
const selectionErrors = ref([])
const showPreview = ref(false)
const previewItem = ref(null)
const expandedGroups = ref({})

const MAX_FILES = 12
const MAX_FILE_SIZE = 4 * 1024 * 1024
const MAX_WIDTH = 2400
const MAX_HEIGHT = 1600
const MAX_PIXELS = 3_840_000

const uploadForm = ref({
  service_id: '',
  title: '',
  alt_text: '',
  description: '',
  is_published: true,
})

const filteredItems = computed(() => {
  if (filter.value === 'published') return galleryItems.value.filter(item => item.is_published)
  if (filter.value === 'draft') return galleryItems.value.filter(item => !item.is_published)
  return galleryItems.value
})

const groupedGallery = computed(() => {
  const groups = new Map()

  for (const item of filteredItems.value) {
    const key = item.service?.slug || `unassigned-${item.service_id ?? 0}`
    const label = item.service?.name || 'Unassigned Images'

    if (!groups.has(key)) {
      groups.set(key, { key, label, items: [] })
    }

    groups.get(key).items.push(item)
  }

  return Array.from(groups.values()).sort((a, b) => {
    if (a.label === 'Unassigned Images') return 1
    if (b.label === 'Unassigned Images') return -1
    return a.label.localeCompare(b.label)
  })
})

function filterClass(value) {
  return [
    'text-xs font-semibold rounded-xl px-3 py-2 border transition-colors',
    filter.value === value
      ? 'border-red-200 bg-red-50 text-red-600'
      : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50',
  ]
}

function triggerPicker() {
  fileInput.value?.click()
}

async function onFileChange(event) {
  uploadError.value = null
  selectionErrors.value = []
  releasePreviews()
  selectedFiles.value = []

  const files = Array.from(event.target.files ?? [])

  if (!files.length) return

  if (files.length > MAX_FILES) {
    selectionErrors.value.push(`You can upload up to ${MAX_FILES} images at once. Only the first ${MAX_FILES} were checked.`)
  }

  const checkedFiles = files.slice(0, MAX_FILES)
  const validFiles = []

  for (const file of checkedFiles) {
    const validation = await validateImage(file)

    if (!validation.valid) {
      selectionErrors.value.push(`${file.name}: ${validation.message}`)
      continue
    }

    validFiles.push({
      file,
      name: file.name,
      size: file.size,
      preview: URL.createObjectURL(file),
      width: validation.width,
      height: validation.height,
    })
  }

  selectedFiles.value = validFiles

  if (fileInput.value && validFiles.length !== checkedFiles.length) {
    fileInput.value.value = ''
  }
}

function releasePreviews() {
  selectedFiles.value.forEach(file => URL.revokeObjectURL(file.preview))
}

function validateImage(file) {
  return new Promise((resolve) => {
    if (file.size > MAX_FILE_SIZE) {
      resolve({ valid: false, message: 'file is larger than 4MB.' })
      return
    }

    const objectUrl = URL.createObjectURL(file)
    const image = new Image()

    image.onload = () => {
      const width = image.naturalWidth
      const height = image.naturalHeight
      URL.revokeObjectURL(objectUrl)

      if (width > MAX_WIDTH || height > MAX_HEIGHT) {
        resolve({ valid: false, message: `dimensions exceed ${MAX_WIDTH}x${MAX_HEIGHT}px.` })
        return
      }

      if ((width * height) > MAX_PIXELS) {
        resolve({ valid: false, message: `pixel count exceeds ${MAX_PIXELS.toLocaleString()} pixels.` })
        return
      }

      resolve({ valid: true, width, height })
    }

    image.onerror = () => {
      URL.revokeObjectURL(objectUrl)
      resolve({ valid: false, message: 'file could not be read as a valid image.' })
    }

    image.src = objectUrl
  })
}

async function loadGallery() {
  loading.value = true
  try {
    galleryItems.value = await get('/admin/gallery')
    syncExpandedGroups()
  } catch {
    toast.error('Failed to load gallery.')
  } finally {
    loading.value = false
  }
}

async function loadServices() {
  try {
    const data = await get('/admin/services', { per_page: 200, active_only: 1 })
    services.value = data?.data ?? data ?? []
  } catch {
    toast.error('Failed to load services.')
  }
}

async function uploadImages() {
  uploadError.value = null

  if (!selectedFiles.value.length) {
    uploadError.value = 'Please choose at least one image.'
    return
  }

  const formData = new FormData()
  selectedFiles.value.forEach(({ file }) => formData.append('images[]', file))
  formData.append('service_id', uploadForm.value.service_id ?? '')
  formData.append('title', uploadForm.value.title ?? '')
  formData.append('alt_text', uploadForm.value.alt_text ?? '')
  formData.append('description', uploadForm.value.description ?? '')
  formData.append('is_published', uploadForm.value.is_published ? '1' : '0')

  uploading.value = true

  try {
    const created = await post('/admin/gallery', formData)
    galleryItems.value = [...created, ...galleryItems.value].sort((a, b) => a.sort_order - b.sort_order)
    syncExpandedGroups()
    toast.success('Gallery images uploaded.')
    uploadForm.value = { service_id: '', title: '', alt_text: '', description: '', is_published: true }
    releasePreviews()
    selectedFiles.value = []
    if (fileInput.value) fileInput.value.value = ''
  } catch (error) {
    uploadError.value = error.response?.data?.message
      ?? error.response?.data?.errors?.images?.[0]
      ?? error.response?.data?.errors?.['images.0']?.[0]
      ?? 'Upload failed.'
  } finally {
    uploading.value = false
  }
}

async function updateItem(item, field, value) {
  try {
    const updated = await patch(`/admin/gallery/${item.id}`, { [field]: value })
    syncItem(updated)
  } catch {
    toast.error('Failed to update gallery item.')
  }
}

async function togglePublished(item) {
  try {
    const updated = await patch(`/admin/gallery/${item.id}`, { is_published: !item.is_published })
    syncItem(updated)
    toast.success(updated.is_published ? 'Image published.' : 'Image moved to draft.')
  } catch {
    toast.error('Failed to update publish status.')
  }
}

async function moveItem(item, direction) {
  const ordered = [...galleryItems.value].sort((a, b) => a.sort_order - b.sort_order)
  const index = ordered.findIndex(entry => entry.id === item.id)
  const swapIndex = index + direction

  if (index < 0 || swapIndex < 0 || swapIndex >= ordered.length) return

  const target = ordered[swapIndex]
  pendingMoves.value = [item.id, target.id]

  try {
    const [updatedCurrent, updatedTarget] = await Promise.all([
      patch(`/admin/gallery/${item.id}`, { sort_order: target.sort_order }),
      patch(`/admin/gallery/${target.id}`, { sort_order: item.sort_order }),
    ])

    syncItem(updatedCurrent)
    syncItem(updatedTarget)
    galleryItems.value = [...galleryItems.value].sort((a, b) => a.sort_order - b.sort_order)
  } catch {
    toast.error('Failed to reorder gallery items.')
  } finally {
    pendingMoves.value = []
  }
}

function isMoving(item, direction) {
  if (pendingMoves.value.includes(item.id)) return true

  const ordered = [...galleryItems.value].sort((a, b) => a.sort_order - b.sort_order)
  const index = ordered.findIndex(entry => entry.id === item.id)
  const swapIndex = index + direction

  return index < 0 || swapIndex < 0 || swapIndex >= ordered.length
}

async function removeItem(item) {
  if (!window.confirm(`Delete "${item.title || 'this image'}" from the gallery?`)) return

  try {
    await del(`/admin/gallery/${item.id}`)
    galleryItems.value = galleryItems.value.filter(entry => entry.id !== item.id)
    syncExpandedGroups()
    if (previewItem.value?.id === item.id) {
      previewItem.value = null
      showPreview.value = false
    }
    toast.success('Gallery item deleted.')
  } catch {
    toast.error('Failed to delete gallery item.')
  }
}

function syncItem(updated) {
  const index = galleryItems.value.findIndex(item => item.id === updated.id)
  if (index === -1) return

  galleryItems.value[index] = updated
  if (previewItem.value?.id === updated.id) previewItem.value = updated
  syncExpandedGroups()
}

function normalizeServiceId(value) {
  return value === '' ? null : Number(value)
}

function toggleGroup(key) {
  expandedGroups.value = { ...expandedGroups.value, [key]: !expandedGroups.value[key] }
}

function syncExpandedGroups() {
  const next = { ...expandedGroups.value }

  for (const group of groupedGallery.value) {
    if (!(group.key in next)) next[group.key] = true
  }

  for (const key of Object.keys(next)) {
    if (!groupedGallery.value.some(group => group.key === key)) delete next[key]
  }

  expandedGroups.value = next
}

function openPreview(item) {
  previewItem.value = item
  showPreview.value = true
}

onMounted(() => {
  loadGallery()
  loadServices()
})

onBeforeUnmount(() => {
  releasePreviews()
})
</script>

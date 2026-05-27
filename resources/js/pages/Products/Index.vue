<template>
  <div class="p-4 md:p-6 space-y-4">

    <PageHeader title="Products" subtitle="Manage boutique products and stock">
      <button @click="openAdd"
        class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl">
        <PlusIcon class="w-3.5 h-3.5" /> Add Product
      </button>
    </PageHeader>

    <!-- Filters -->
    <div class="px-4 md:px-6 flex flex-wrap gap-2 items-center">
      <input v-model="search" @input="debouncedLoad" placeholder="Search products…"
        class="input-base text-xs w-48">
      <select v-model="filterCategory" @change="load" class="input-base text-xs w-40">
        <option value="">All Categories</option>
        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
      </select>
      <select v-model="filterStatus" @change="load" class="input-base text-xs w-36">
        <option value="">All Stock</option>
        <option value="healthy">Healthy</option>
        <option value="low_stock">Low Stock</option>
        <option value="out_of_stock">Out of Stock</option>
      </select>
    </div>

    <!-- Low stock alerts banner -->
    <div v-if="alerts.length" class="mx-4 md:mx-6 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3 flex items-start gap-3">
      <span class="text-amber-500 text-sm shrink-0">⚠</span>
      <div class="text-xs text-amber-800">
        <span class="font-bold">{{ alerts.length }} product{{ alerts.length !== 1 ? 's' : '' }} need restocking:</span>
        {{ alerts.map(a => a.name).join(', ') }}
      </div>
    </div>

    <!-- Products table -->
    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-10 text-center text-gray-400 text-xs">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading products…
      </div>
      <div v-else-if="!products.length" class="p-10 text-center text-gray-400 text-xs">No products found.</div>
      <div v-else>
        <div class="divide-y divide-gray-50">
          <div v-for="p in products" :key="p.id"
            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50/60 transition-colors">
            <!-- Image -->
            <div class="w-12 h-12 rounded-xl overflow-hidden border border-gray-100 shrink-0 bg-gray-50">
              <img v-if="p.image" :src="imgSrc(p.image)" :alt="p.name" class="w-full h-full object-cover">
              <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-xl">📦</div>
            </div>
            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold text-gray-900">{{ p.name }}</span>
                <span v-if="p.is_featured" class="text-[9px] font-bold bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full">Featured</span>
                <span v-if="!p.is_active" class="text-[9px] font-bold bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">Inactive</span>
                <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded-full', stockBadgeClass(p)]">{{ stockLabel(p) }}</span>
              </div>
              <div class="text-[10px] text-gray-400 mt-0.5">{{ categoryLabel(p.category) }}</div>
              <div class="text-[10px] text-gray-600 mt-0.5 font-semibold">
                KES {{ Number(p.sale_price ?? p.price).toLocaleString() }}
                <span v-if="p.sale_price" class="line-through text-gray-400 font-normal ml-1">{{ Number(p.price).toLocaleString() }}</span>
              </div>
            </div>
            <!-- Stock -->
            <div class="text-center shrink-0 hidden sm:block">
              <div :class="['text-lg font-extrabold', p.stock_qty <= 0 ? 'text-red-500' : p.stock_qty <= p.reorder_level ? 'text-amber-500' : 'text-green-600']">
                {{ p.stock_qty }}
              </div>
              <div class="text-[9px] text-gray-400">in stock</div>
            </div>
            <!-- Actions -->
            <div class="flex items-center gap-1.5 shrink-0">
              <button @click="openStockIn(p)" title="Add stock"
                class="text-[10px] font-bold px-2 py-1 rounded-lg border border-green-200 text-green-600 hover:bg-green-50">+Stock</button>
              <button @click="openStockOut(p)" title="Remove stock"
                class="text-[10px] font-bold px-2 py-1 rounded-lg border border-gray-200 text-gray-500 hover:border-red-200 hover:text-red-600">-Stock</button>
              <button @click="openHistory(p)"
                class="text-[10px] font-semibold text-blue-600 hover:underline">History</button>
              <button @click="openEdit(p)"
                class="text-xs font-semibold text-red-600 hover:underline">Edit</button>
            </div>
          </div>
        </div>
        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-50">
          <span class="text-[10px] text-gray-400">{{ meta.from }}–{{ meta.to }} of {{ meta.total }}</span>
          <div class="flex gap-1">
            <button v-for="p in meta.last_page" :key="p" @click="page = p; load()"
              :class="['w-7 h-7 rounded-lg text-xs font-semibold transition-colors', page === p ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
              {{ p }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ ADD / EDIT PRODUCT MODAL ══ -->
    <Modal v-model="showForm" :title="editing ? 'Edit Product' : 'Add Product'" size="lg">
      <form class="space-y-5">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Product Name <span class="text-red-500">*</span></label>
            <input v-model="form.name" class="input-base" placeholder="e.g. Premax Care Kit Pro">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Category <span class="text-red-500">*</span></label>
            <select v-model="form.category" class="input-base">
              <option value="">Select…</option>
              <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Price (KES) <span class="text-red-500">*</span></label>
            <input v-model.number="form.price" type="number" min="0" class="input-base" placeholder="2500">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Sale Price (KES) <span class="text-gray-400 font-normal">(optional)</span></label>
            <input v-model.number="form.sale_price" type="number" min="0" class="input-base" placeholder="Leave blank if no sale">
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Short Description</label>
            <textarea v-model="form.description" rows="2" class="input-base resize-none" placeholder="Brief description shown on product cards…" />
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Full Description</label>
            <textarea v-model="form.long_description" rows="4" class="input-base resize-none" placeholder="Detailed product description…" />
          </div>

          <!-- Stock -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">{{ editing ? 'Current Stock' : 'Initial Stock' }}</label>
            <input v-model.number="form.stock_qty" type="number" min="0" class="input-base" placeholder="0" :disabled="editing">
            <p v-if="editing" class="text-[10px] text-gray-400">Use +Stock / -Stock buttons to adjust after creation.</p>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Reorder Level</label>
            <input v-model.number="form.reorder_level" type="number" min="0" class="input-base" placeholder="5">
            <p class="text-[10px] text-gray-400">Alert when stock falls at or below this.</p>
          </div>

          <!-- Toggles -->
          <div class="flex items-center gap-3">
            <button type="button" @click="form.is_featured = !form.is_featured"
              :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors', form.is_featured ? 'bg-yellow-500' : 'bg-gray-200']">
              <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow', form.is_featured ? 'translate-x-4' : 'translate-x-1']" />
            </button>
            <label class="text-xs font-semibold text-gray-600">Featured Product</label>
          </div>
          <div class="flex items-center gap-3">
            <button type="button" @click="form.is_active = !form.is_active"
              :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors', form.is_active ? 'bg-red-600' : 'bg-gray-200']">
              <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow', form.is_active ? 'translate-x-4' : 'translate-x-1']" />
            </button>
            <label class="text-xs font-semibold text-gray-600">Active (visible on website)</label>
          </div>
        </div>

        <!-- Main Image -->
        <div class="border-t border-gray-100 pt-4 space-y-2">
          <label class="text-xs font-semibold text-gray-600">Main Image</label>
          <div class="flex items-start gap-3">
            <div v-if="imagePreview || form.image"
              class="w-20 h-20 rounded-xl overflow-hidden border border-gray-200 shrink-0 bg-gray-50">
              <img :src="imagePreview || imgSrc(form.image)" class="w-full h-full object-cover">
            </div>
            <div class="flex flex-col gap-1.5 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <input type="file" accept="image/*" @change="onImageChange"
                  class="text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-600 hover:file:bg-red-100">
                <button type="button" @click="pickerTarget = 'main'; pickerOpen = true"
                  class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50 transition shrink-0">Library</button>
              </div>
              <button v-if="form.image || imagePreview" type="button"
                @click="form.image = null; imagePreview = null; imageFile = null; pickedImageUrl = null"
                class="text-[10px] text-red-500 hover:underline self-start">Remove</button>
            </div>
          </div>
        </div>

        <!-- Gallery Images -->
        <div class="border-t border-gray-100 pt-4 space-y-3">
          <div class="flex items-center justify-between">
            <label class="text-xs font-semibold text-gray-600">Gallery Images</label>
            <span class="text-[10px] text-gray-400">{{ form.gallery.length + galleryPreviews.length }} image{{ form.gallery.length + galleryPreviews.length !== 1 ? 's' : '' }}</span>
          </div>

          <!-- Existing gallery images -->
          <div v-if="form.gallery.length || galleryPreviews.length" class="flex flex-wrap gap-2">
            <div v-for="(img, i) in form.gallery" :key="'existing-' + i"
              class="relative w-16 h-16 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 group">
              <img :src="imgSrc(img)" class="w-full h-full object-cover">
              <button type="button" @click="removeGalleryImage(i)"
                class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-lg font-bold">
                ✕
              </button>
            </div>
            <!-- New image previews -->
            <div v-for="(prev, i) in galleryPreviews" :key="'new-' + i"
              class="relative w-16 h-16 rounded-xl overflow-hidden border border-red-200 bg-gray-50 group">
              <img :src="prev" class="w-full h-full object-cover">
              <div class="absolute top-0.5 right-0.5 bg-red-600 text-white text-[8px] font-bold px-1 rounded">NEW</div>
              <button type="button" @click="removeNewGalleryImage(i)"
                class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-lg font-bold">
                ✕
              </button>
            </div>
          </div>

          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2 flex-wrap">
              <input type="file" accept="image/*" multiple @change="onGalleryChange"
                class="text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-600 hover:file:bg-gray-200">
              <button type="button" @click="pickerTarget = 'gallery'; pickerOpen = true"
                class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50 transition shrink-0">+ Library</button>
            </div>
            <p class="text-[10px] text-gray-400">Select multiple images. Shown as product photo carousel on website.</p>
          </div>
        </div>

        <!-- Features -->
        <div class="border-t border-gray-100 pt-4 space-y-2">
          <div class="flex items-center justify-between">
            <label class="text-xs font-semibold text-gray-600">Key Features</label>
            <button type="button" @click="form.features.push('')" class="text-[10px] font-bold text-red-600 hover:underline">+ Add</button>
          </div>
          <div v-for="(f, i) in form.features" :key="i" class="flex items-center gap-2">
            <input v-model="form.features[i]" class="input-base flex-1 text-xs" :placeholder="`Feature ${i+1}…`">
            <button type="button" @click="form.features.splice(i,1)" class="text-gray-300 hover:text-red-500">✕</button>
          </div>
          <p v-if="!form.features.length" class="text-[10px] text-gray-400 italic">No features added.</p>
        </div>

        <div v-if="formError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ formError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showForm = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="save" :disabled="saving"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ saving ? 'Saving…' : editing ? 'Save Changes' : 'Add Product' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══ STOCK IN MODAL ══ -->
    <Modal v-model="showStockIn" title="Add Stock" size="sm">
      <div class="space-y-4">
        <div class="text-xs text-gray-600 bg-gray-50 rounded-xl px-3 py-2.5">
          <span class="font-bold">{{ stockTarget?.name }}</span> — current: <span class="font-bold text-gray-900">{{ stockTarget?.stock_qty }}</span> units
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Quantity to Add <span class="text-red-500">*</span></label>
          <input v-model.number="stockQty" type="number" min="1" class="input-base">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes</label>
          <input v-model="stockNotes" class="input-base text-xs" placeholder="e.g. Restock from supplier">
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showStockIn = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="doStockIn" :disabled="savingStock"
            class="px-4 py-2 text-xs font-semibold bg-green-600 text-white rounded-xl hover:bg-green-700 disabled:opacity-60">
            {{ savingStock ? 'Saving…' : 'Add Stock' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══ STOCK OUT MODAL ══ -->
    <Modal v-model="showStockOut" title="Remove Stock" size="sm">
      <div class="space-y-4">
        <div class="text-xs text-gray-600 bg-gray-50 rounded-xl px-3 py-2.5">
          <span class="font-bold">{{ stockTarget?.name }}</span> — current: <span class="font-bold text-gray-900">{{ stockTarget?.stock_qty }}</span> units
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Quantity to Remove <span class="text-red-500">*</span></label>
          <input v-model.number="stockQty" type="number" min="1" :max="stockTarget?.stock_qty" class="input-base">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes</label>
          <input v-model="stockNotes" class="input-base text-xs" placeholder="e.g. Damaged stock write-off">
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showStockOut = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="doStockOut" :disabled="savingStock"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ savingStock ? 'Saving…' : 'Remove Stock' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══ MOVEMENT HISTORY MODAL ══ -->
    <Modal v-model="showHistory" :title="historyProduct?.name + ' — Stock History'" size="md">
      <div v-if="loadingHistory" class="py-6 text-center text-xs text-gray-400">
        <div class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading…
      </div>
      <div v-else-if="!movements.length" class="py-6 text-center text-xs text-gray-400">No movements recorded yet.</div>
      <div v-else class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
        <div v-for="m in movements" :key="m.id" class="flex items-start justify-between px-1 py-2.5 gap-3">
          <div class="flex items-center gap-2">
            <span :class="['text-xs font-bold px-2 py-0.5 rounded-full', m.quantity > 0 ? 'bg-green-100 text-green-700' : m.type === 'order' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-600']">
              {{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }}
            </span>
            <div>
              <div class="text-xs font-semibold text-gray-700 capitalize">{{ m.type.replace('_', ' ') }}{{ m.source_ref ? ' — ' + m.source_ref : '' }}</div>
              <div v-if="m.notes" class="text-[10px] text-gray-400">{{ m.notes }}</div>
              <div class="text-[10px] text-gray-400">{{ m.user?.name ?? 'System' }} · {{ formatDate(m.created_at) }}</div>
            </div>
          </div>
          <div class="text-xs font-bold text-gray-600 shrink-0">→ {{ m.balance_after }}</div>
        </div>
      </div>
      <template #footer>
        <button @click="showHistory = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Close</button>
      </template>
    </Modal>

    <MediaPicker :open="pickerOpen" :multiple="pickerTarget === 'gallery'"
      @close="pickerOpen = false" @select="onPickerSelect" />

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { PlusIcon }       from '@heroicons/vue/24/outline'
import { useApi }         from '@/composables/useApi'
import { useToastStore }  from '@/stores/toast'
import PageHeader         from '@/components/PageHeader.vue'
import Modal              from '@/components/Modal.vue'
import MediaPicker        from '@/components/MediaPicker.vue'

const { get, post, destroy } = useApi()
const toast = useToastStore()

const websiteBase = (import.meta.env.VITE_WEBSITE_URL ?? 'http://localhost:8006').replace(/\/$/, '')
const imgSrc = url => url?.startsWith('http') ? url : url ? websiteBase + '/' + url : ''

const categories = [
  { value: 'care_kits',   label: 'Care Kits' },
  { value: 'accessories', label: 'Accessories' },
  { value: 'apparel',     label: 'Apparel' },
  { value: 'lifestyle',   label: 'Lifestyle' },
]

// ── State ──────────────────────────────────────────────────────────────────────
const products      = ref([])
const alerts        = ref([])
const meta          = ref(null)
const loading       = ref(false)
const page          = ref(1)
const search        = ref('')
const filterCategory = ref('')
const filterStatus  = ref('')

// form
const showForm  = ref(false)
const saving    = ref(false)
const editing   = ref(null)
const formError = ref(null)
const imageFile = ref(null)
const imagePreview = ref(null)
const pickedImageUrl = ref(null)
const galleryFiles   = ref([])
const galleryPreviews = ref([])
const pickerOpen   = ref(false)
const pickerTarget = ref(null)
const form = ref(blankForm())

// stock
const showStockIn  = ref(false)
const showStockOut = ref(false)
const savingStock  = ref(false)
const stockTarget  = ref(null)
const stockQty     = ref(1)
const stockNotes   = ref('')

// history
const showHistory    = ref(false)
const loadingHistory = ref(false)
const historyProduct = ref(null)
const movements      = ref([])

// ── Helpers ────────────────────────────────────────────────────────────────────
function blankForm() {
  return { name:'', category:'', description:'', long_description:'', price:null, sale_price:null, stock_qty:0, reorder_level:5, is_featured:false, is_active:true, image:null, features:[], gallery:[] }
}

const categoryLabel = v => categories.find(c => c.value === v)?.label ?? v

function stockLabel(p) {
  if (p.stock_qty <= 0)                    return 'Out of Stock'
  if (p.stock_qty <= p.reorder_level)      return 'Low Stock'
  return 'In Stock'
}
function stockBadgeClass(p) {
  if (p.stock_qty <= 0)                    return 'bg-red-100 text-red-600'
  if (p.stock_qty <= p.reorder_level)      return 'bg-amber-100 text-amber-700'
  return 'bg-green-100 text-green-700'
}

const formatDate = d => d ? new Date(d).toLocaleDateString('en-KE', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : ''

// ── Debounce ───────────────────────────────────────────────────────────────────
let debounceTimer
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; load() }, 350)
}

// ── Load ───────────────────────────────────────────────────────────────────────
async function load() {
  loading.value = true
  try {
    const params = { page: page.value, per_page: 20 }
    if (search.value)         params.search   = search.value
    if (filterCategory.value) params.category = filterCategory.value
    if (filterStatus.value)   params.status   = filterStatus.value
    const res = await get('/admin/products', params)
    products.value = res?.data ?? res ?? []
    meta.value     = res?.meta ?? null
  } catch { toast.error('Failed to load products.') }
  finally { loading.value = false }
}

async function loadAlerts() {
  try { alerts.value = await get('/admin/products/alerts') ?? [] } catch {}
}

// ── CRUD ───────────────────────────────────────────────────────────────────────
function openAdd() {
  editing.value = null
  imageFile.value = null; imagePreview.value = null; pickedImageUrl.value = null
  galleryFiles.value = []; galleryPreviews.value = []
  form.value = blankForm(); formError.value = null; showForm.value = true
}

function openEdit(p) {
  editing.value = p
  imageFile.value = null; imagePreview.value = null; pickedImageUrl.value = null
  galleryFiles.value = []; galleryPreviews.value = []
  form.value = {
    name: p.name, category: p.category,
    description: p.description ?? '', long_description: p.long_description ?? '',
    price: p.price, sale_price: p.sale_price ?? null,
    stock_qty: p.stock_qty, reorder_level: p.reorder_level ?? 5,
    is_featured: p.is_featured, is_active: p.is_active,
    image: p.image ?? null,
    features: Array.isArray(p.features) ? [...p.features] : [],
    gallery: Array.isArray(p.gallery) ? [...p.gallery] : [],
  }
  formError.value = null; showForm.value = true
}

function onImageChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  imageFile.value = file
  imagePreview.value = URL.createObjectURL(file)
  pickedImageUrl.value = null
}

function onPickerSelect(url) {
  if (pickerTarget.value === 'main') {
    pickedImageUrl.value = Array.isArray(url) ? url[0] : url
    imagePreview.value   = pickedImageUrl.value
    imageFile.value      = null
  } else if (pickerTarget.value === 'gallery') {
    const urls = Array.isArray(url) ? url : [url]
    form.value.gallery.push(...urls)
  }
  pickerOpen.value = false
}

function onGalleryChange(e) {
  const files = Array.from(e.target.files ?? [])
  files.forEach(file => {
    galleryFiles.value.push(file)
    galleryPreviews.value.push(URL.createObjectURL(file))
  })
  e.target.value = ''
}

function removeGalleryImage(i) {
  form.value.gallery.splice(i, 1)
}

function removeNewGalleryImage(i) {
  URL.revokeObjectURL(galleryPreviews.value[i])
  galleryPreviews.value.splice(i, 1)
  galleryFiles.value.splice(i, 1)
}

async function save() {
  if (!form.value.name || !form.value.category || !form.value.price) {
    formError.value = 'Name, category and price are required.'; return
  }
  saving.value = true; formError.value = null
  try {
    const fd = new FormData()
    const fields = ['name','category','description','long_description','price','sale_price','stock_qty','reorder_level','sort_order']
    fields.forEach(f => { if (form.value[f] !== null && form.value[f] !== '') fd.append(f, form.value[f]) })
    fd.append('is_featured', form.value.is_featured ? '1' : '0')
    fd.append('is_active',   form.value.is_active   ? '1' : '0')
    form.value.features.forEach((f, i) => fd.append(`features[${i}]`, f))
    if (imageFile.value)      fd.append('image',     imageFile.value)
    else if (pickedImageUrl.value) fd.append('image_url', pickedImageUrl.value)
    // Gallery: always send gallery state on edit so removals are persisted
    if (editing.value) fd.append('gallery_replace', '1')
    form.value.gallery.forEach((g, i) => fd.append(`gallery[${i}]`, g))
    galleryFiles.value.forEach((f, i) => fd.append(`gallery_images[${i}]`, f))
    const config = { headers: { 'Content-Type': 'multipart/form-data' } }

    if (editing.value) {
      fd.append('_method', 'PUT')
      const u = await post(`/admin/products/${editing.value.id}`, fd, config)
      const i = products.value.findIndex(x => x.id === editing.value.id)
      if (i > -1) products.value[i] = u
      toast.success('Product updated.')
    } else {
      const p = await post('/admin/products', fd, config)
      products.value.unshift(p)
      toast.success('Product added.')
    }
    galleryFiles.value = []; galleryPreviews.value = []
    showForm.value = false
    loadAlerts()
  } catch(e) { formError.value = e.response?.data?.message ?? 'Failed to save.' }
  finally { saving.value = false }
}

// ── Stock ──────────────────────────────────────────────────────────────────────
function openStockIn(p)  { stockTarget.value = p; stockQty.value = 1; stockNotes.value = ''; showStockIn.value  = true }
function openStockOut(p) { stockTarget.value = p; stockQty.value = 1; stockNotes.value = ''; showStockOut.value = true }

async function doStockIn() {
  if (!stockQty.value || stockQty.value < 1) return
  savingStock.value = true
  try {
    const u = await post(`/admin/products/${stockTarget.value.id}/stock-in`, { quantity: stockQty.value, notes: stockNotes.value })
    const i = products.value.findIndex(x => x.id === u.id)
    if (i > -1) products.value[i] = u
    showStockIn.value = false; toast.success('Stock added.')
    loadAlerts()
  } catch(e) { toast.error(e.response?.data?.message ?? 'Failed.') }
  finally { savingStock.value = false }
}

async function doStockOut() {
  if (!stockQty.value || stockQty.value < 1) return
  savingStock.value = true
  try {
    const u = await post(`/admin/products/${stockTarget.value.id}/stock-out`, { quantity: stockQty.value, notes: stockNotes.value })
    const i = products.value.findIndex(x => x.id === u.id)
    if (i > -1) products.value[i] = u
    showStockOut.value = false; toast.success('Stock removed.')
    loadAlerts()
  } catch(e) { toast.error(e.response?.data?.message ?? 'Failed.') }
  finally { savingStock.value = false }
}

// ── History ────────────────────────────────────────────────────────────────────
async function openHistory(p) {
  historyProduct.value = p; movements.value = []; showHistory.value = true; loadingHistory.value = true
  try { movements.value = await get(`/admin/products/${p.id}/movements`) ?? [] } catch {}
  finally { loadingHistory.value = false }
}

onMounted(() => { load(); loadAlerts() })
</script>

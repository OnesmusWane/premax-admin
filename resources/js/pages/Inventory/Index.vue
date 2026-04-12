<template>
  <div class="p-4 md:p-6 space-y-4">

    <PageHeader title="Inventory" subtitle="Stock levels and movements">
      <div class="flex flex-col md:flex-row items-start md:items-center md:justify-between gap-2">
        <button @click="openStockIn(null)"
          class="flex items-center gap-1.5 border border-gray-200 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-gray-50">
          <ArrowDownTrayIcon class="w-4 h-4 text-green-600" /> Stock In
        </button>
        <button @click="openStockOut(null)"
          class="flex items-center gap-1.5 border border-gray-200 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-gray-50">
          <ArrowUpTrayIcon class="w-4 h-4 text-red-500" /> Stock Out
        </button>
        <button @click="openAdd"
          class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors">
          <PlusIcon class="w-4 h-4" /> Add Product
        </button>
      </div>
    </PageHeader>

    <!-- Low stock alert bar -->
    <div v-if="alerts.length"
      class="mx-4 md:mx-6 bg-yellow-50 border border-yellow-200 rounded-2xl px-4 py-3 flex items-start gap-3">
      <ExclamationTriangleIcon class="w-4 h-4 text-yellow-600 shrink-0 mt-0.5" />
      <div class="flex-1">
        <div class="text-xs font-bold text-yellow-800 mb-1">{{ alerts.length }} item{{ alerts.length !== 1 ? 's' : '' }} need restocking</div>
        <div class="flex flex-wrap gap-2">
          <button v-for="a in alerts" :key="a.id"
            @click="openStockIn(a)"
            :class="['text-[10px] font-semibold px-2.5 py-1 rounded-full border transition-all',
              a.stock_qty <= 0
                ? 'bg-red-100 border-red-300 text-red-700 hover:bg-red-200'
                : 'bg-yellow-100 border-yellow-300 text-yellow-800 hover:bg-yellow-200']">
            {{ a.name }} ({{ a.stock_qty }} left) → Stock In
          </button>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 px-4 md:px-6">
      <SearchInput v-model="search" placeholder="Search products…" class="w-full md:min-w-[280px] md:max-w-sm md:flex-1" />
      <select v-model="catFilter"
        class="w-full md:w-56 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">
        <option value="">All Categories</option>
        <option v-for="c in categories" :key="c" :value="c" class="capitalize">{{ c }}</option>
      </select>
      <select v-model="statusFilter"
        class="w-full md:w-56 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">
        <option value="">All Stock Levels</option>
        <option value="out_of_stock">Out of Stock</option>
        <option value="critical">Critical</option>
        <option value="low_stock">Low Stock</option>
        <option value="healthy">Healthy</option>
      </select>
      <button v-if="catFilter || statusFilter || search" @click="clearFilters"
        class="flex items-center gap-1 text-xs font-semibold text-gray-500 border border-gray-200 rounded-xl px-3 py-2 hover:bg-gray-50">
        <XMarkIcon class="w-3.5 h-3.5" /> Clear
      </button>
    </div>

    <!-- Table -->
    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm  table-wrap">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th v-for="h in headers" :key="h"
              class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">{{ h }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading">
            <td :colspan="headers.length" class="px-4 py-12 text-center text-gray-400">
              <div class="flex items-center justify-center gap-2">
                <div class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin" />
                Loading…
              </div>
            </td>
          </tr>
          <tr v-for="item in items.data" :key="item.id"
            class="hover:bg-gray-50/60 transition-colors cursor-pointer" @click="openDetail(item)">
            <td class="px-4 py-3.5">
              <div class="font-semibold text-gray-900 text-xs">{{ item.name }}</div>
              <div v-if="item.notes" class="text-[10px] text-gray-400 truncate max-w-[180px]">{{ item.notes }}</div>
            </td>
            <td class="px-4 py-3.5 font-mono text-xs text-gray-500">{{ item.sku }}</td>
            <td class="px-4 py-3.5">
              <span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize',
                catColor(item.category)]">{{ item.category }}</span>
            </td>
            <td class="px-4 py-3.5">
              <!-- Stock level bar -->
              <div class="flex items-center gap-2">
                <span :class="['text-xs font-bold',
                  item.stock_qty <= 0               ? 'text-red-600' :
                  item.stock_qty <= item.reorder_level ? 'text-yellow-600' :
                  'text-gray-900']">
                  {{ item.stock_qty }}
                </span>
                <div class="w-16 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                  <div :class="['h-full rounded-full transition-all',
                    item.stock_qty <= 0               ? 'bg-red-500' :
                    item.stock_qty <= item.reorder_level ? 'bg-yellow-400' :
                    'bg-green-500']"
                    :style="`width:${Math.min((item.stock_qty / Math.max(item.reorder_level * 3, 1)) * 100, 100)}%`" />
                </div>
                <span class="text-[10px] text-gray-400">/ {{ item.reorder_level }}</span>
              </div>
            </td>
            <td class="px-4 py-3.5">
              <div class="text-xs font-bold text-gray-900">KES {{ item.unit_price?.toLocaleString() }}</div>
              <div v-if="item.cost_price" class="text-[10px] text-gray-400">Cost: KES {{ item.cost_price?.toLocaleString() }}</div>
            </td>
            <td class="px-4 py-3.5">
              <span :class="['text-[10px] font-bold px-2.5 py-1 rounded-full',
                item.stock_qty <= 0                  ? 'bg-red-100 text-red-700'    :
                item.stock_qty <= item.reorder_level  ? 'bg-yellow-100 text-yellow-700' :
                'bg-green-100 text-green-700']">
                {{ item.stock_qty <= 0 ? 'Out of Stock' : item.stock_qty <= item.reorder_level ? 'Low Stock' : 'Healthy' }}
              </span>
            </td>
            <td class="px-4 py-3.5" @click.stop>
              <div class="flex items-center gap-2">
                <button @click="openStockIn(item)"
                  class="text-[10px] font-bold text-green-600 border border-green-200 rounded-lg px-2 py-1 hover:bg-green-50">+ In</button>
                <button @click="openStockOut(item)"
                  class="text-[10px] font-bold text-red-500 border border-red-200 rounded-lg px-2 py-1 hover:bg-red-50">− Out</button>
                <button @click="openEdit(item)"
                  class="text-xs font-semibold text-gray-500 hover:text-gray-900">Edit</button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && !items.data?.length">
            <td :colspan="headers.length" class="px-4 py-12 text-center text-gray-400">No inventory items found.</td>
          </tr>
        </tbody>
      </table>
      <div v-if="items.last_page > 1" class="flex justify-between items-center px-4 py-3 border-t border-gray-100">
        <span class="text-xs text-gray-500">{{ items.total }} products</span>
        <div class="flex gap-1">
          <button @click="page--" :disabled="page===1" class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Prev</button>
          <button @click="page++" :disabled="page===items.last_page" class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         MODAL: PRODUCT DETAIL (with movement history)
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showDetail" title="Product Detail" size="lg">
      <div v-if="detailLoading" class="py-12 text-center text-gray-400">
        <div class="w-6 h-6 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>
      <div v-else-if="activeItem" class="space-y-4">

        <!-- Header -->
        <div class="flex items-start justify-between bg-gray-50 rounded-xl p-4">
          <div>
            <div class="font-bold text-gray-900 text-sm">{{ activeItem.name }}</div>
            <div class="font-mono text-[10px] text-gray-400 mt-0.5">{{ activeItem.sku }}</div>
            <div class="text-xs text-gray-500 capitalize mt-1">{{ activeItem.category }}</div>
          </div>
          <div class="text-right">
            <div :class="['text-2xl font-extrabold',
              activeItem.stock_qty <= 0               ? 'text-red-600' :
              activeItem.stock_qty <= activeItem.reorder_level ? 'text-yellow-600' :
              'text-gray-900']">
              {{ activeItem.stock_qty }}
            </div>
            <div class="text-[10px] text-gray-400">units in stock</div>
            <div class="text-[10px] text-gray-400">reorder at {{ activeItem.reorder_level }}</div>
          </div>
        </div>

        <!-- Prices -->
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-white border border-gray-100 rounded-xl p-4 text-center">
            <div class="text-[10px] text-gray-400 mb-1">Selling Price</div>
            <div class="text-lg font-extrabold text-gray-900">KES {{ activeItem.unit_price?.toLocaleString() }}</div>
          </div>
          <div class="bg-white border border-gray-100 rounded-xl p-4 text-center">
            <div class="text-[10px] text-gray-400 mb-1">Cost Price</div>
            <div class="text-lg font-extrabold text-gray-900">
              {{ activeItem.cost_price ? 'KES ' + activeItem.cost_price?.toLocaleString() : '—' }}
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="activeItem.notes" class="bg-gray-50 rounded-xl px-4 py-3 text-xs text-gray-600">
          {{ activeItem.notes }}
        </div>

        <!-- Movement history -->
        <div>
          <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Movement History</div>
          <div v-if="!movements.length" class="text-xs text-gray-400 text-center py-4 bg-gray-50 rounded-xl">
            No movements recorded.
          </div>
          <div v-else class="border border-gray-100 rounded-xl overflow-hidden">
            <div v-for="(m, i) in movements" :key="m.id"
              :class="['flex items-center justify-between px-4 py-3 text-xs',
                i < movements.length - 1 ? 'border-b border-gray-50' : '']">
              <div class="flex items-center gap-3">
                <span :class="['w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0',
                  m.type === 'stock_in'    ? 'bg-green-100 text-green-700' :
                  m.type === 'stock_out'   ? 'bg-red-100 text-red-700'    :
                  'bg-blue-100 text-blue-700']">
                  {{ m.type === 'stock_in' ? '+' : m.type === 'stock_out' ? '−' : '~' }}
                </span>
                <div>
                  <div class="font-semibold text-gray-900 capitalize">{{ m.type.replace('_', ' ') }}</div>
                  <div class="text-[10px] text-gray-400">
                    {{ fmtDate(m.created_at) }}
                    <span v-if="m.reference"> · Ref: {{ m.reference }}</span>
                    <span v-if="m.notes"> · {{ m.notes }}</span>
                  </div>
                </div>
              </div>
              <div class="text-right shrink-0">
                <div :class="['font-bold', m.quantity > 0 ? 'text-green-700' : 'text-red-600']">
                  {{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }}
                </div>
                <div class="text-[10px] text-gray-400">bal: {{ m.balance_after }}</div>
              </div>
            </div>
          </div>
        </div>

      </div>
      <template #footer>
        <div class="flex items-center justify-between w-full">
          <div class="flex gap-2">
            <button @click="openStockIn(activeItem); showDetail = false"
              class="flex items-center gap-1 text-xs font-semibold border border-green-200 text-green-700 rounded-xl px-3 py-1.5 hover:bg-green-50">
              <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Stock In
            </button>
            <button @click="openStockOut(activeItem); showDetail = false"
              class="flex items-center gap-1 text-xs font-semibold border border-red-200 text-red-600 rounded-xl px-3 py-1.5 hover:bg-red-50">
              <ArrowUpTrayIcon class="w-3.5 h-3.5" /> Stock Out
            </button>
          </div>
          <button @click="openEdit(activeItem); showDetail = false"
            class="flex items-center gap-1.5 text-xs font-bold bg-gray-900 text-white rounded-xl px-4 py-2 hover:bg-gray-700">
            <PencilIcon class="w-3.5 h-3.5" /> Edit Product
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: ADD / EDIT PRODUCT
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showForm" :title="editingItem ? 'Edit Product' : 'Add Product'" size="md">
      <form class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5 col-span-2">
            <label class="text-xs font-semibold text-gray-600">Product Name <span class="text-red-500">*</span></label>
            <input v-model="form.name" required class="input-base" placeholder="Castrol GTX 5W-30 1L">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">SKU <span class="text-red-500">*</span></label>
            <input v-model="form.sku" class="input-base font-mono" placeholder="OIL-5W30-1L"
              :disabled="!!editingItem">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Category <span class="text-red-500">*</span></label>
            <select v-model="form.category" class="input-base">
              <option v-for="c in categories" :key="c" :value="c" class="capitalize">{{ c }}</option>
            </select>
          </div>
          <!-- Initial stock only when adding -->
          <div v-if="!editingItem" class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Initial Stock</label>
            <input v-model.number="form.stock_qty" type="number" min="0" class="input-base" placeholder="0">
          </div>
          <div class="flex flex-col gap-1.5" :class="editingItem ? 'col-span-2' : ''">
            <label class="text-xs font-semibold text-gray-600">Reorder Level</label>
            <input v-model.number="form.reorder_level" type="number" min="0" class="input-base" placeholder="10">
            <p class="text-[10px] text-gray-400">Alert triggers when stock falls to or below this number</p>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Selling Price (KES) <span class="text-red-500">*</span></label>
            <input v-model.number="form.unit_price" type="number" min="0" class="input-base" placeholder="1200">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Cost Price (KES)</label>
            <input v-model.number="form.cost_price" type="number" min="0" class="input-base" placeholder="900">
          </div>
          <div class="flex flex-col gap-1.5 col-span-2">
            <label class="text-xs font-semibold text-gray-600">Notes</label>
            <textarea v-model="form.notes" rows="2" class="input-base resize-none" placeholder="e.g. Brand, specs, or supplier info" />
          </div>
        </div>
        <div v-if="formError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ formError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showForm = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="saveProduct" :disabled="saving"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ saving ? 'Saving…' : editingItem ? 'Save Changes' : 'Add Product' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: STOCK IN
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showStockInModal" title="Stock In" size="sm">
      <form class="space-y-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Product <span class="text-red-500">*</span></label>
          <select v-model="stockForm.item_id" class="input-base">
            <option value="">Select product…</option>
            <option v-for="i in allItems" :key="i.id" :value="i.id">
              {{ i.name }} — {{ i.stock_qty }} in stock
            </option>
          </select>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Quantity <span class="text-red-500">*</span></label>
          <input v-model.number="stockForm.quantity" type="number" min="1" class="input-base" placeholder="e.g. 10">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Reference</label>
          <input v-model="stockForm.reference" class="input-base" placeholder="e.g. PO #12345 or Supplier name">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes</label>
          <input v-model="stockForm.notes" class="input-base" placeholder="Any additional notes…">
        </div>
        <!-- New balance preview -->
        <div v-if="stockForm.item_id && stockForm.quantity > 0" class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex justify-between text-xs">
          <span class="text-green-700 font-semibold">New balance after stock in:</span>
          <span class="font-extrabold text-green-800">{{ currentBalance + stockForm.quantity }} units</span>
        </div>
        <div v-if="stockFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ stockFormError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showStockInModal = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="submitStockIn" :disabled="saving || !stockForm.item_id || stockForm.quantity < 1"
            class="px-4 py-2 text-xs font-semibold bg-green-600 text-white rounded-xl hover:bg-green-700 disabled:opacity-60">
            {{ saving ? 'Adding…' : 'Confirm Stock In' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: STOCK OUT
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showStockOutModal" title="Stock Out" size="sm">
      <form class="space-y-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Product <span class="text-red-500">*</span></label>
          <select v-model="stockForm.item_id" class="input-base">
            <option value="">Select product…</option>
            <option v-for="i in allItems" :key="i.id" :value="i.id">
              {{ i.name }} — {{ i.stock_qty }} in stock
            </option>
          </select>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Quantity <span class="text-red-500">*</span></label>
          <input v-model.number="stockForm.quantity" type="number" min="1"
            :max="currentBalance" class="input-base" placeholder="e.g. 2">
          <p v-if="stockForm.item_id" class="text-[10px] text-gray-400">
            {{ currentBalance }} available
          </p>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Reference</label>
          <input v-model="stockForm.reference" class="input-base" placeholder="e.g. Invoice # or job card">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes</label>
          <input v-model="stockForm.notes" class="input-base" placeholder="Reason for stock out…">
        </div>
        <!-- Balance warning -->
        <div v-if="stockForm.item_id && stockForm.quantity > 0"
          :class="['border rounded-xl px-4 py-3 flex justify-between text-xs',
            currentBalance - stockForm.quantity < 0
              ? 'bg-red-50 border-red-200'
              : currentBalance - stockForm.quantity <= selectedItemData?.reorder_level
                ? 'bg-yellow-50 border-yellow-200'
                : 'bg-gray-50 border-gray-200']">
          <span class="font-semibold"
            :class="currentBalance - stockForm.quantity < 0 ? 'text-red-700' : 'text-gray-700'">
            Balance after:
          </span>
          <span class="font-extrabold"
            :class="currentBalance - stockForm.quantity < 0 ? 'text-red-700' : 'text-gray-900'">
            {{ currentBalance - stockForm.quantity }} units
            <span v-if="currentBalance - stockForm.quantity < 0"> ⚠ Insufficient</span>
          </span>
        </div>
        <div v-if="stockFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ stockFormError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showStockOutModal = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="submitStockOut"
            :disabled="saving || !stockForm.item_id || stockForm.quantity < 1 || currentBalance - stockForm.quantity < 0"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ saving ? 'Processing…' : 'Confirm Stock Out' }}
          </button>
        </div>
      </template>
    </Modal>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import {
  PlusIcon, ArrowDownTrayIcon, ArrowUpTrayIcon,
  ExclamationTriangleIcon, XMarkIcon, PencilIcon,
} from '@heroicons/vue/24/outline'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import PageHeader        from '@/components/PageHeader.vue'
import SearchInput       from '@/components/SearchInput.vue'
import Modal             from '@/components/Modal.vue'

const { get, post, put, loading } = useApi()
const toast = useToastStore()

// ── State ──────────────────────────────────────────────────────────────────────
const items        = ref({ data:[], last_page:1, total:0 })
const allItems     = ref([])   // full list for dropdowns
const alerts       = ref([])   // low/out of stock items
const search       = ref('');  const catFilter = ref(''); const statusFilter = ref(''); const page = ref(1)

// Modals
const showDetail      = ref(false)
const showForm        = ref(false)
const showStockInModal  = ref(false)
const showStockOutModal = ref(false)

const detailLoading = ref(false)
const activeItem    = ref(null)
const movements     = ref([])
const editingItem   = ref(null)
const saving        = ref(false)
const formError     = ref(null)
const stockFormError = ref(null)

const form = ref({ name:'',sku:'',category:'parts',stock_qty:0,reorder_level:10,unit_price:0,cost_price:null,notes:'' })
const stockForm = ref({ item_id:'', quantity:1, reference:'', notes:'' })

const categories = ['lubricants','cleaning','parts','tools','other']
const headers    = ['Product','SKU','Category','Stock','Price','Status','Actions']

const fmtDate = d => d ? new Date(d).toLocaleDateString('en-KE',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '—'

const catColor = c => ({
  lubricants: 'bg-blue-100 text-blue-700',
  cleaning:   'bg-purple-100 text-purple-700',
  parts:      'bg-orange-100 text-orange-700',
  tools:      'bg-gray-100 text-gray-700',
  other:      'bg-gray-100 text-gray-600',
}[c] ?? 'bg-gray-100 text-gray-600')

// Current balance for selected item in stock modals
const selectedItemData = computed(() =>
  allItems.value.find(i => i.id === stockForm.value.item_id)
)
const currentBalance = computed(() => selectedItemData.value?.stock_qty ?? 0)

// ── Load ───────────────────────────────────────────────────────────────────────
async function load() {
  items.value = await get('/admin/inventory', {
    page:     page.value,
    search:   search.value,
    category: catFilter.value,
    status:   statusFilter.value,
  })
}

async function loadAllItems() {
  const data = await get('/admin/inventory', { per_page:200 })
  allItems.value = data?.data ?? []
}

async function loadAlerts() {
  alerts.value = await get('/admin/inventory/alerts') ?? []
}

function clearFilters() {
  search.value=''; catFilter.value=''; statusFilter.value=''
  page.value=1; load()
}

// ── Product detail ─────────────────────────────────────────────────────────────
async function openDetail(item) {
  showDetail.value   = true
  detailLoading.value = true
  movements.value    = []
  activeItem.value   = item
  try {
    const [detail, movs] = await Promise.all([
      get(`/admin/inventory/${item.id}`),
      get(`/admin/inventory/${item.id}/movements`),
    ])
    activeItem.value = detail
    movements.value  = movs ?? []
  } catch { toast.error('Failed to load item.') }
  finally { detailLoading.value = false }
}

// ── Add / Edit product ─────────────────────────────────────────────────────────
function openAdd() {
  editingItem.value = null
  form.value = { name:'',sku:'',category:'parts',stock_qty:0,reorder_level:10,unit_price:0,cost_price:null,notes:'' }
  formError.value = null
  showForm.value = true
}

function openEdit(item) {
  editingItem.value = item
  form.value = {
    name:          item.name,
    sku:           item.sku,
    category:      item.category,
    reorder_level: item.reorder_level,
    unit_price:    item.unit_price,
    cost_price:    item.cost_price ?? null,
    notes:         item.notes ?? '',
  }
  formError.value = null
  showForm.value = true
}

async function saveProduct() {
  saving.value = true; formError.value = null
  try {
    if (editingItem.value) {
      await put(`/admin/inventory/${editingItem.value.id}`, form.value)
      toast.success('Product updated.')
    } else {
      await post('/admin/inventory', form.value)
      toast.success('Product added.')
    }
    showForm.value = false
    load(); loadAllItems(); loadAlerts()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save product.'
  } finally { saving.value = false }
}

// ── Stock In ───────────────────────────────────────────────────────────────────
function openStockIn(item) {
  stockForm.value = { item_id: item?.id ?? '', quantity: 1, reference:'', notes:'' }
  stockFormError.value = null
  showStockInModal.value = true
}

async function submitStockIn() {
  saving.value = true; stockFormError.value = null
  try {
    const updated = await post(`/admin/inventory/${stockForm.value.item_id}/stock-in`, {
      quantity:  stockForm.value.quantity,
      reference: stockForm.value.reference || undefined,
      notes:     stockForm.value.notes     || undefined,
    })
    toast.success(`Stock in: +${stockForm.value.quantity} units. New balance: ${updated.stock_qty}`)
    showStockInModal.value = false
    load(); loadAllItems(); loadAlerts()
  } catch (e) {
    stockFormError.value = e.response?.data?.message ?? 'Failed to record stock in.'
  } finally { saving.value = false }
}

// ── Stock Out ──────────────────────────────────────────────────────────────────
function openStockOut(item) {
  stockForm.value = { item_id: item?.id ?? '', quantity: 1, reference:'', notes:'' }
  stockFormError.value = null
  showStockOutModal.value = true
}

async function submitStockOut() {
  saving.value = true; stockFormError.value = null
  try {
    const updated = await post(`/admin/inventory/${stockForm.value.item_id}/stock-out`, {
      quantity:  stockForm.value.quantity,
      reference: stockForm.value.reference || undefined,
      notes:     stockForm.value.notes     || undefined,
    })
    toast.success(`Stock out: −${stockForm.value.quantity} units. New balance: ${updated.stock_qty}`)
    showStockOutModal.value = false
    load(); loadAllItems(); loadAlerts()
  } catch (e) {
    stockFormError.value = e.response?.data?.message ?? 'Failed to record stock out.'
  } finally { saving.value = false }
}

watch([page, catFilter, statusFilter], load)
watch(search, () => { page.value=1; load() })
onMounted(() => { load(); loadAllItems(); loadAlerts() })
</script>

<template>
  <div class="p-4 md:p-6 space-y-4">

    <PageHeader title="Inventory" subtitle="Stock levels, movements and in-store sales">
      <div class="flex items-center gap-2">
        <button @click="openStockIn(null)"
          class="flex items-center gap-1.5 border border-gray-200 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-gray-50">
          <ArrowDownTrayIcon class="w-4 h-4 text-green-600" /> Stock In
        </button>
        <button @click="openSell(null)"
          class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors">
          <ShoppingCartIcon class="w-4 h-4" /> Sell
        </button>
      </div>
    </PageHeader>

    <!-- Low stock alert bar -->
    <div v-if="alerts.length"
      class="mx-4 md:mx-6 bg-yellow-50 border border-yellow-200 rounded-2xl px-4 py-3 flex items-start gap-3">
      <ExclamationTriangleIcon class="w-4 h-4 text-yellow-600 shrink-0 mt-0.5" />
      <div class="flex-1">
        <div class="text-xs font-bold text-yellow-800 mb-1">
          {{ alerts.length }} product{{ alerts.length !== 1 ? 's' : '' }} need restocking
        </div>
        <div class="flex flex-wrap gap-2">
          <button v-for="a in alerts" :key="a.id" @click="openStockIn(a)"
            :class="['text-[10px] font-semibold px-2.5 py-1 rounded-full border transition-all',
              a.stock_qty <= 0
                ? 'bg-red-100 border-red-300 text-red-700 hover:bg-red-200'
                : 'bg-yellow-100 border-yellow-300 text-yellow-800 hover:bg-yellow-200']">
            {{ a.name }} ({{ a.stock_qty }} left) → Restock
          </button>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 px-4 md:px-6">
      <SearchInput v-model="search" placeholder="Search products…"
        class="w-full md:min-w-[260px] md:max-w-sm md:flex-1" />
      <select v-model="catFilter"
        class="w-full md:w-52 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">
        <option value="">All Categories</option>
        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
      </select>
      <select v-model="statusFilter"
        class="w-full md:w-52 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">
        <option value="">All Stock Levels</option>
        <option value="out_of_stock">Out of Stock</option>
        <option value="low_stock">Low Stock</option>
        <option value="healthy">Healthy</option>
      </select>
      <button v-if="catFilter || statusFilter || search" @click="clearFilters"
        class="flex items-center gap-1 text-xs font-semibold text-gray-500 border border-gray-200 rounded-xl px-3 py-2 hover:bg-gray-50">
        <XMarkIcon class="w-3.5 h-3.5" /> Clear
      </button>
    </div>

    <!-- Table -->
    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Product</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide hidden md:table-cell">Category</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Stock</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Price</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Status</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading">
            <td colspan="6" class="px-4 py-12 text-center text-gray-400">
              <div class="flex items-center justify-center gap-2">
                <div class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin" />
                Loading…
              </div>
            </td>
          </tr>
          <tr v-for="p in products" :key="p.id"
            class="hover:bg-gray-50/60 transition-colors cursor-pointer" @click="openDetail(p)">
            <!-- Product -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl overflow-hidden border border-gray-100 shrink-0 bg-gray-50">
                  <img v-if="p.image" :src="websiteBase + '/' + p.image" :alt="p.name" class="w-full h-full object-cover">
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-lg">📦</div>
                </div>
                <div>
                  <div class="text-xs font-bold text-gray-900">{{ p.name }}</div>
                  <div v-if="p.description" class="text-[10px] text-gray-400 truncate max-w-[180px]">{{ p.description }}</div>
                </div>
              </div>
            </td>
            <!-- Category -->
            <td class="px-4 py-3 hidden md:table-cell">
              <span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full', catColor(p.category)]">
                {{ catLabel(p.category) }}
              </span>
            </td>
            <!-- Stock -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span :class="['text-sm font-extrabold',
                  p.stock_qty <= 0                ? 'text-red-600' :
                  p.stock_qty <= p.reorder_level  ? 'text-yellow-600' :
                  'text-gray-900']">{{ p.stock_qty }}</span>
                <div class="w-14 h-1.5 rounded-full bg-gray-100 overflow-hidden hidden sm:block">
                  <div :class="['h-full rounded-full transition-all',
                    p.stock_qty <= 0               ? 'bg-red-500' :
                    p.stock_qty <= p.reorder_level ? 'bg-yellow-400' :
                    'bg-green-500']"
                    :style="`width:${Math.min((p.stock_qty / Math.max(p.reorder_level * 3, 1)) * 100, 100)}%`" />
                </div>
              </div>
            </td>
            <!-- Price -->
            <td class="px-4 py-3 hidden sm:table-cell">
              <div class="text-xs font-bold text-gray-900">
                KES {{ Number(p.sale_price ?? p.price).toLocaleString() }}
              </div>
              <div v-if="p.sale_price" class="text-[10px] text-gray-400 line-through">
                KES {{ Number(p.price).toLocaleString() }}
              </div>
            </td>
            <!-- Status -->
            <td class="px-4 py-3 hidden sm:table-cell">
              <span :class="['text-[10px] font-bold px-2.5 py-1 rounded-full',
                p.stock_qty <= 0               ? 'bg-red-100 text-red-700' :
                p.stock_qty <= p.reorder_level ? 'bg-yellow-100 text-yellow-700' :
                'bg-green-100 text-green-700']">
                {{ p.stock_qty <= 0 ? 'Out of Stock' : p.stock_qty <= p.reorder_level ? 'Low Stock' : 'Healthy' }}
              </span>
            </td>
            <!-- Actions -->
            <td class="px-4 py-3" @click.stop>
              <div class="flex items-center gap-1.5">
                <button @click="openStockIn(p)"
                  class="text-[10px] font-bold text-green-600 border border-green-200 rounded-lg px-2 py-1 hover:bg-green-50">+In</button>
                <button @click="openSell(p)"
                  class="text-[10px] font-bold text-white bg-red-600 rounded-lg px-2 py-1 hover:bg-red-700">Sell</button>
                <button @click="openHistory(p)"
                  class="text-[10px] font-semibold text-blue-600 hover:underline hidden sm:inline">History</button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && !products.length">
            <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-xs">No products found.</td>
          </tr>
        </tbody>
      </table>
      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="flex justify-between items-center px-4 py-3 border-t border-gray-100">
        <span class="text-xs text-gray-500">{{ meta.total }} products</span>
        <div class="flex gap-1">
          <button @click="page--; load()" :disabled="page === 1"
            class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Prev</button>
          <button @click="page++; load()" :disabled="page === meta.last_page"
            class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>

    <!-- ══ DETAIL + HISTORY MODAL ══ -->
    <Modal v-model="showDetail" :title="activeProduct?.name" size="lg">
      <div v-if="detailLoading" class="py-12 text-center">
        <div class="w-6 h-6 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>
      <div v-else-if="activeProduct" class="space-y-4">
        <!-- Summary -->
        <div class="flex gap-4 bg-gray-50 rounded-2xl p-4">
          <div v-if="activeProduct.image" class="w-20 h-20 rounded-xl overflow-hidden border border-gray-100 shrink-0">
            <img :src="websiteBase + '/' + activeProduct.image" class="w-full h-full object-cover">
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full', catColor(activeProduct.category)]">
                {{ catLabel(activeProduct.category) }}
              </span>
              <span v-if="activeProduct.is_featured" class="text-[9px] font-bold bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full">Featured</span>
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ activeProduct.description }}</div>
            <div class="mt-2 flex items-center gap-4 text-xs">
              <div>
                <span class="text-gray-400">Price</span>
                <div class="font-bold text-gray-900">KES {{ Number(activeProduct.sale_price ?? activeProduct.price).toLocaleString() }}</div>
              </div>
              <div>
                <span class="text-gray-400">In Stock</span>
                <div :class="['text-xl font-extrabold', activeProduct.stock_qty <= 0 ? 'text-red-600' : activeProduct.stock_qty <= activeProduct.reorder_level ? 'text-yellow-600' : 'text-gray-900']">
                  {{ activeProduct.stock_qty }}
                </div>
              </div>
              <div>
                <span class="text-gray-400">Reorder at</span>
                <div class="font-bold text-gray-900">{{ activeProduct.reorder_level }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Movement history -->
        <div>
          <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Movement History</div>
          <div v-if="!movements.length" class="text-xs text-gray-400 text-center py-4 bg-gray-50 rounded-xl">
            No movements recorded.
          </div>
          <div v-else class="border border-gray-100 rounded-xl overflow-hidden divide-y divide-gray-50 max-h-64 overflow-y-auto">
            <div v-for="m in movements" :key="m.id" class="flex items-center justify-between px-4 py-3 text-xs">
              <div class="flex items-center gap-3">
                <span :class="['w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0',
                  m.quantity > 0          ? 'bg-green-100 text-green-700' :
                  m.source_ref === 'in_store_sale' ? 'bg-purple-100 text-purple-700' :
                  m.type === 'order'      ? 'bg-blue-100 text-blue-700' :
                  'bg-red-100 text-red-700']">
                  {{ m.quantity > 0 ? '+' : m.source_ref === 'in_store_sale' ? '🛒' : '−' }}
                </span>
                <div>
                  <div class="font-semibold text-gray-900 capitalize">
                    {{ m.source_ref === 'in_store_sale' ? 'In-Store Sale' : m.type.replace('_', ' ') }}
                    <span v-if="m.source_ref && m.source_ref !== 'in_store_sale'" class="text-gray-400 font-normal">— {{ m.source_ref }}</span>
                  </div>
                  <div class="text-[10px] text-gray-400">
                    {{ fmtDate(m.created_at) }} · {{ m.user?.name ?? 'System' }}
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
            <button @click="openStockIn(activeProduct); showDetail = false"
              class="flex items-center gap-1 text-xs font-semibold border border-green-200 text-green-700 rounded-xl px-3 py-1.5 hover:bg-green-50">
              <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Stock In
            </button>
            <button @click="openSell(activeProduct); showDetail = false"
              class="flex items-center gap-1 text-xs font-bold bg-red-600 text-white rounded-xl px-3 py-1.5 hover:bg-red-700">
              <ShoppingCartIcon class="w-3.5 h-3.5" /> Sell
            </button>
          </div>
          <button @click="showDetail = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Close</button>
        </div>
      </template>
    </Modal>

    <!-- ══ STOCK IN MODAL ══ -->
    <Modal v-model="showStockInModal" title="Stock In" size="sm">
      <div class="space-y-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Product <span class="text-red-500">*</span></label>
          <select v-model="stockForm.product_id" class="input-base">
            <option value="">Select product…</option>
            <option v-for="p in allProducts" :key="p.id" :value="p.id">
              {{ p.name }} — {{ p.stock_qty }} in stock
            </option>
          </select>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Quantity <span class="text-red-500">*</span></label>
          <input v-model.number="stockForm.quantity" type="number" min="1" class="input-base" placeholder="e.g. 10">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Reference</label>
          <input v-model="stockForm.reference" class="input-base text-xs" placeholder="e.g. PO #12345 or Supplier name">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes</label>
          <input v-model="stockForm.notes" class="input-base text-xs" placeholder="Any additional notes…">
        </div>
        <div v-if="stockForm.product_id && stockForm.quantity > 0"
          class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex justify-between text-xs">
          <span class="text-green-700 font-semibold">New balance:</span>
          <span class="font-extrabold text-green-800">{{ selectedStockProduct?.stock_qty + stockForm.quantity }} units</span>
        </div>
        <div v-if="stockFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ stockFormError }}</div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showStockInModal = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="submitStockIn" :disabled="saving || !stockForm.product_id || stockForm.quantity < 1"
            class="px-4 py-2 text-xs font-semibold bg-green-600 text-white rounded-xl hover:bg-green-700 disabled:opacity-60">
            {{ saving ? 'Adding…' : 'Confirm Stock In' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══ SELL MODAL ══ -->
    <Modal v-model="showSellModal" title="Sell Product" size="sm">
      <div class="space-y-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Product <span class="text-red-500">*</span></label>
          <select v-model="sellForm.product_id" @change="onSellProductChange" class="input-base">
            <option value="">Select product…</option>
            <option v-for="p in allProducts" :key="p.id" :value="p.id" :disabled="p.stock_qty <= 0">
              {{ p.name }} — {{ p.stock_qty }} in stock
            </option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Quantity <span class="text-red-500">*</span></label>
            <input v-model.number="sellForm.quantity" type="number" min="1"
              :max="selectedSellProduct?.stock_qty" class="input-base" placeholder="1">
            <p v-if="selectedSellProduct" class="text-[10px] text-gray-400">
              {{ selectedSellProduct.stock_qty }} available
            </p>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Unit Price (KES)</label>
            <input v-model.number="sellForm.unit_price" type="number" min="0" class="input-base">
            <p class="text-[10px] text-gray-400">Auto-filled from product</p>
          </div>
        </div>
        <!-- Total preview -->
        <div v-if="sellForm.product_id && sellForm.quantity > 0 && sellForm.unit_price"
          class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex justify-between items-center">
          <span class="text-xs text-red-700 font-semibold">Total</span>
          <span class="text-lg font-extrabold text-red-800">
            KES {{ (sellForm.quantity * sellForm.unit_price).toLocaleString() }}
          </span>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Customer Name</label>
          <input v-model="sellForm.customer" class="input-base text-xs" placeholder="Walk-in customer (optional)">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Payment Method</label>
          <div class="flex gap-2">
            <button v-for="m in ['cash','mpesa','other']" :key="m" type="button"
              @click="sellForm.payment = m"
              :class="['flex-1 text-xs font-bold py-2 rounded-xl border transition-colors capitalize',
                sellForm.payment === m
                  ? 'bg-gray-900 text-white border-gray-900'
                  : 'border-gray-200 text-gray-600 hover:border-gray-400']">
              {{ m === 'mpesa' ? 'M-Pesa' : m.charAt(0).toUpperCase() + m.slice(1) }}
            </button>
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes</label>
          <input v-model="sellForm.notes" class="input-base text-xs" placeholder="Any additional notes…">
        </div>
        <div v-if="sellFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ sellFormError }}</div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showSellModal = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="submitSell"
            :disabled="saving || !sellForm.product_id || sellForm.quantity < 1"
            class="px-4 py-2 text-xs font-bold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ saving ? 'Processing…' : 'Confirm Sale' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══ HISTORY MODAL ══ -->
    <Modal v-model="showHistoryModal" :title="(historyProduct?.name ?? '') + ' — History'" size="md">
      <div v-if="historyLoading" class="py-8 text-center">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>
      <div v-else-if="!movements.length" class="py-8 text-center text-xs text-gray-400">No movements recorded.</div>
      <div v-else class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
        <div v-for="m in movements" :key="m.id" class="flex items-center justify-between px-1 py-3 gap-3">
          <div class="flex items-center gap-2">
            <span :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0',
              m.quantity > 0                   ? 'bg-green-100 text-green-700' :
              m.source_ref === 'in_store_sale' ? 'bg-purple-100 text-purple-700' :
              m.type === 'order'               ? 'bg-blue-100 text-blue-700' :
              'bg-red-100 text-red-700']">
              {{ m.quantity > 0 ? '+' : m.source_ref === 'in_store_sale' ? '🛒' : '−' }}
            </span>
            <div>
              <div class="text-xs font-semibold text-gray-800">
                {{ m.source_ref === 'in_store_sale' ? 'In-Store Sale' : m.type.replace('_', ' ') }}
                <span v-if="m.source_ref && m.source_ref !== 'in_store_sale'" class="text-gray-400 font-normal ml-1">{{ m.source_ref }}</span>
              </div>
              <div class="text-[10px] text-gray-400">
                {{ fmtDate(m.created_at) }} · {{ m.user?.name ?? 'System' }}
              </div>
              <div v-if="m.notes" class="text-[10px] text-gray-500 mt-0.5">{{ m.notes }}</div>
            </div>
          </div>
          <div class="text-right shrink-0">
            <div :class="['text-sm font-bold', m.quantity > 0 ? 'text-green-700' : 'text-red-600']">
              {{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }}
            </div>
            <div class="text-[10px] text-gray-400">→ {{ m.balance_after }}</div>
          </div>
        </div>
      </div>
      <template #footer>
        <button @click="showHistoryModal = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Close</button>
      </template>
    </Modal>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import {
  ArrowDownTrayIcon, ExclamationTriangleIcon,
  XMarkIcon, ShoppingCartIcon,
} from '@heroicons/vue/24/outline'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import PageHeader        from '@/components/PageHeader.vue'
import SearchInput       from '@/components/SearchInput.vue'
import Modal             from '@/components/Modal.vue'

const { get, post } = useApi()
const toast = useToastStore()

const websiteBase = (import.meta.env.VITE_WEBSITE_URL ?? 'http://localhost:8006').replace(/\/$/, '')

const categories = [
  { value: 'care_kits',   label: 'Care Kits' },
  { value: 'accessories', label: 'Accessories' },
  { value: 'apparel',     label: 'Apparel' },
  { value: 'lifestyle',   label: 'Lifestyle' },
]

// ── State ──────────────────────────────────────────────────────────────────────
const products    = ref([])
const allProducts = ref([])
const alerts      = ref([])
const meta        = ref(null)
const loading     = ref(false)
const page        = ref(1)
const search      = ref('')
const catFilter   = ref('')
const statusFilter = ref('')

// Detail
const showDetail    = ref(false)
const detailLoading = ref(false)
const activeProduct = ref(null)
const movements     = ref([])

// History
const showHistoryModal = ref(false)
const historyLoading   = ref(false)
const historyProduct   = ref(null)

// Stock In
const showStockInModal = ref(false)
const stockFormError   = ref(null)
const stockForm = ref({ product_id: '', quantity: 1, reference: '', notes: '' })

// Sell
const showSellModal = ref(false)
const sellFormError = ref(null)
const sellForm = ref({ product_id: '', quantity: 1, unit_price: null, customer: '', payment: 'cash', notes: '' })

const saving = ref(false)

// ── Helpers ────────────────────────────────────────────────────────────────────
const catLabel = v => categories.find(c => c.value === v)?.label ?? v

const catColor = v => ({
  care_kits:   'bg-blue-100 text-blue-700',
  accessories: 'bg-purple-100 text-purple-700',
  apparel:     'bg-pink-100 text-pink-700',
  lifestyle:   'bg-orange-100 text-orange-700',
}[v] ?? 'bg-gray-100 text-gray-600')

const fmtDate = d => d
  ? new Date(d).toLocaleDateString('en-KE', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
  : '—'

const selectedStockProduct = computed(() =>
  allProducts.value.find(p => p.id === stockForm.value.product_id)
)
const selectedSellProduct = computed(() =>
  allProducts.value.find(p => p.id === sellForm.value.product_id)
)

// ── Load ───────────────────────────────────────────────────────────────────────
async function load() {
  loading.value = true
  try {
    const params = { page: page.value, per_page: 20 }
    if (search.value)        params.search   = search.value
    if (catFilter.value)     params.category = catFilter.value
    if (statusFilter.value)  params.status   = statusFilter.value
    const res = await get('/admin/products', params)
    products.value = res?.data ?? []
    meta.value     = res?.meta ?? null
  } catch { toast.error('Failed to load products.') }
  finally { loading.value = false }
}

async function loadAll() {
  try {
    const res = await get('/admin/products', { per_page: 200 })
    allProducts.value = res?.data ?? []
  } catch {}
}

async function loadAlerts() {
  try { alerts.value = await get('/admin/products/alerts') ?? [] } catch {}
}

function clearFilters() {
  search.value = ''; catFilter.value = ''; statusFilter.value = ''
  page.value = 1; load()
}

// ── Detail ─────────────────────────────────────────────────────────────────────
async function openDetail(p) {
  showDetail.value    = true
  detailLoading.value = true
  activeProduct.value = p
  movements.value     = []
  try {
    movements.value = await get(`/admin/products/${p.id}/movements`) ?? []
    activeProduct.value = allProducts.value.find(x => x.id === p.id) ?? p
  } catch {}
  finally { detailLoading.value = false }
}

// ── Stock In ───────────────────────────────────────────────────────────────────
function openStockIn(p) {
  stockForm.value    = { product_id: p?.id ?? '', quantity: 1, reference: '', notes: '' }
  stockFormError.value = null
  showStockInModal.value = true
}

async function submitStockIn() {
  saving.value = true; stockFormError.value = null
  try {
    const updated = await post(`/admin/products/${stockForm.value.product_id}/stock-in`, {
      quantity:  stockForm.value.quantity,
      notes:     [stockForm.value.reference, stockForm.value.notes].filter(Boolean).join(' | ') || undefined,
    })
    const i = products.value.findIndex(x => x.id === updated.id)
    if (i > -1) products.value[i] = updated
    const j = allProducts.value.findIndex(x => x.id === updated.id)
    if (j > -1) allProducts.value[j] = updated
    toast.success(`+${stockForm.value.quantity} units added. Balance: ${updated.stock_qty}`)
    showStockInModal.value = false
    loadAlerts()
  } catch (e) {
    stockFormError.value = e.response?.data?.message ?? 'Failed.'
  } finally { saving.value = false }
}

// ── Sell ───────────────────────────────────────────────────────────────────────
function openSell(p) {
  const price = p ? Number(p.sale_price ?? p.price) : null
  sellForm.value    = { product_id: p?.id ?? '', quantity: 1, unit_price: price, customer: '', payment: 'cash', notes: '' }
  sellFormError.value = null
  showSellModal.value = true
}

function onSellProductChange() {
  const p = selectedSellProduct.value
  if (p) sellForm.value.unit_price = Number(p.sale_price ?? p.price)
}

async function submitSell() {
  saving.value = true; sellFormError.value = null
  try {
    const updated = await post(`/admin/products/${sellForm.value.product_id}/sell`, {
      quantity:   sellForm.value.quantity,
      unit_price: sellForm.value.unit_price,
      customer:   sellForm.value.customer  || undefined,
      payment:    sellForm.value.payment,
      notes:      sellForm.value.notes     || undefined,
    })
    const i = products.value.findIndex(x => x.id === updated.id)
    if (i > -1) products.value[i] = updated
    const j = allProducts.value.findIndex(x => x.id === updated.id)
    if (j > -1) allProducts.value[j] = updated
    const total = (sellForm.value.quantity * sellForm.value.unit_price).toLocaleString()
    toast.success(`Sale recorded — KES ${total}. Stock: ${updated.stock_qty} remaining`)
    showSellModal.value = false
    loadAlerts()
  } catch (e) {
    sellFormError.value = e.response?.data?.message ?? 'Failed to process sale.'
  } finally { saving.value = false }
}

// ── History ────────────────────────────────────────────────────────────────────
async function openHistory(p) {
  historyProduct.value  = p
  movements.value       = []
  showHistoryModal.value = true
  historyLoading.value  = true
  try { movements.value = await get(`/admin/products/${p.id}/movements`) ?? [] } catch {}
  finally { historyLoading.value = false }
}

watch([catFilter, statusFilter], () => { page.value = 1; load() })
watch(search, () => { page.value = 1; load() })
onMounted(() => { load(); loadAll(); loadAlerts() })
</script>

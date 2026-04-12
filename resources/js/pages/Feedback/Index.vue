<template>
  <div class="p-4 md:p-6 space-y-4">

    <PageHeader title="Feedback" subtitle="Customer feedback and reviews" />

    <!-- ── Feedback link generator ── -->
    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-start justify-between flex-wrap gap-4">
        <div class="flex items-start gap-3">
          <span class="text-lg">🔗</span>
          <div>
            <h3 class="text-sm font-bold text-gray-900">Customer Feedback Link</h3>
            <p class="text-xs text-gray-500 mt-0.5">Share this link with customers after service to collect their feedback.</p>
          </div>
        </div>
        <div class="flex items-center gap-2 flex-1 min-w-0 max-w-lg">
          <input :value="feedbackLink" readonly
            class="flex-1 text-xs border border-gray-200 rounded-xl px-3 py-2.5 bg-gray-50 font-mono truncate focus:outline-none" />
          <button @click="generateLink"
            class="flex items-center gap-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-xl shrink-0 transition-colors">
            Regenerate
          </button>
          <button @click="copyLink"
            class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shrink-0 transition-colors">
            <LinkIcon class="w-3.5 h-3.5" />
            {{ copied ? 'Copied!' : 'Copy Link' }}
          </button>
        </div>
      </div>

      <!-- Generate link for specific booking -->
      <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-3 flex-wrap">
        <span class="text-xs text-gray-500 shrink-0">Generate link for a booking:</span>
        <div class="flex gap-2 flex-1">
          <input v-model="bookingSearch" class="input-base flex-1 max-w-xs text-xs"
            placeholder="Booking ref or customer phone…" @keyup.enter="findBooking">
          <button @click="findBooking" :disabled="searchingBooking"
            class="text-xs font-semibold border border-gray-200 rounded-xl px-4 py-2 hover:bg-gray-50 shrink-0">
            {{ searchingBooking ? '…' : 'Find' }}
          </button>
        </div>
        <div v-if="foundBooking" class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2">
          <span class="text-xs font-semibold text-gray-700">{{ foundBooking.customer.name }} · {{ foundBooking.vehicle.registration }}</span>
          <button @click="generateTokenForBooking" :disabled="generatingToken"
            class="text-xs font-bold text-red-600 hover:underline">
            {{ generatingToken ? 'Generating…' : 'Generate Link' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── Main grid: form + recent feedback ── -->
    <div class="mx-4 md:mx-6 grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- ── Record Feedback (manual entry) ── -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <div>
          <h3 class="text-sm font-bold text-gray-900">Record Customer Feedback</h3>
          <p class="text-xs text-gray-500 mt-0.5">Manually enter feedback received in person or over the phone.</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Customer Name</label>
            <input v-model="manualForm.name" class="input-base" placeholder="e.g. John Doe">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Phone Number</label>
            <input v-model="manualForm.phone" class="input-base" placeholder="e.g. 0700123456">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Vehicle Reg</label>
            <input v-model="manualForm.vehicle" class="input-base font-mono uppercase" placeholder="KAA 123A">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Date of Service</label>
            <input v-model="manualForm.service_date" type="date" class="input-base">
          </div>
          <div class="flex flex-col gap-1.5 col-span-2">
            <label class="text-xs font-semibold text-gray-600">Service Type</label>
            <select v-model="manualForm.service" class="input-base">
              <option value="">Select service…</option>
              <option v-for="s in services" :key="s.id" :value="s.name">{{ s.name }}</option>
            </select>
          </div>
        </div>

        <!-- Star rating -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Rating</label>
          <div class="flex items-center gap-2">
            <button v-for="star in 5" :key="star" type="button"
              @click="manualForm.rating = star"
              @mouseover="hoverRating = star"
              @mouseleave="hoverRating = 0"
              class="transition-transform hover:scale-110">
              <StarIcon :class="['w-7 h-7 transition-colors',
                (hoverRating || manualForm.rating) >= star
                  ? 'text-yellow-400 fill-yellow-400'
                  : 'text-gray-300']" />
            </button>
            <span class="text-xs text-gray-400 ml-1">
              {{ manualForm.rating ? ratingLabels[manualForm.rating] : 'Select rating' }}
            </span>
          </div>
        </div>

        <!-- Comments -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Comments</label>
          <textarea v-model="manualForm.liked" rows="4" class="input-base resize-none"
            placeholder="Customer's feedback…" />
        </div>

        <!-- Recommend -->
        <div class="flex items-center gap-4">
          <span class="text-xs font-semibold text-gray-600">Would recommend?</span>
          <div class="flex gap-3">
            <button type="button" @click="manualForm.recommend = 'yes'"
              :class="['text-xs font-bold px-4 py-1.5 rounded-xl border-2 transition-all',
                manualForm.recommend === 'yes'
                  ? 'bg-green-600 border-green-600 text-white'
                  : 'border-gray-200 text-gray-600']">
              👍 Yes
            </button>
            <button type="button" @click="manualForm.recommend = 'no'"
              :class="['text-xs font-bold px-4 py-1.5 rounded-xl border-2 transition-all',
                manualForm.recommend === 'no'
                  ? 'bg-red-600 border-red-600 text-white'
                  : 'border-gray-200 text-gray-600']">
              👎 No
            </button>
          </div>
        </div>

        <div v-if="manualError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ manualError }}</div>

        <button @click="saveFeedback" :disabled="savingFeedback"
          class="w-full py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl disabled:opacity-60 flex items-center justify-center gap-2 transition-colors">
          <span v-if="savingFeedback" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
          {{ savingFeedback ? 'Saving…' : 'Save Feedback' }}
        </button>
      </div>

      <!-- ── Recent Feedback ── -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-gray-900">Recent Feedback</h3>
            <p class="text-xs text-gray-500 mt-0.5">Latest reviews from your customers.</p>
          </div>
          <span class="text-xs font-bold bg-red-100 text-red-600 px-3 py-1 rounded-full">
            {{ totalFeedback }} Total
          </span>
        </div>

        <!-- Summary stats -->
        <div v-if="stats" class="grid grid-cols-3 gap-3">
          <div class="bg-yellow-50 rounded-xl p-3 text-center">
            <div class="text-xl font-extrabold text-gray-900">{{ stats.avg_rating?.toFixed(1) ?? '—' }}</div>
            <div class="flex justify-center mt-0.5">
              <StarIcon v-for="i in 5" :key="i" :class="['w-3 h-3', i <= Math.round(stats.avg_rating) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-200']" />
            </div>
            <div class="text-[10px] text-gray-500 mt-1">Avg Rating</div>
          </div>
          <div class="bg-green-50 rounded-xl p-3 text-center">
            <div class="text-xl font-extrabold text-green-700">{{ stats.recommend_pct ?? 0 }}%</div>
            <div class="text-[10px] text-gray-500 mt-1">Recommend</div>
          </div>
          <div class="bg-blue-50 rounded-xl p-3 text-center">
            <div class="text-xl font-extrabold text-blue-700">{{ stats.this_month ?? 0 }}</div>
            <div class="text-[10px] text-gray-500 mt-1">This Month</div>
          </div>
        </div>

        <!-- Feedback cards -->
        <div v-if="loadingFeedback" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-24 animate-pulse bg-gray-50 rounded-xl" />
        </div>
        <div v-else-if="!recentFeedback.length" class="py-8 text-center text-gray-400 text-xs">
          No feedback received yet.
        </div>
        <div v-else class="space-y-3 max-h-[520px] overflow-y-auto pr-1">
          <div v-for="fb in recentFeedback" :key="fb.id"
            class="bg-gray-50 rounded-xl p-4 space-y-2">
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="text-xs font-bold text-gray-900">{{ fb.name }}</div>
                <div class="flex items-center gap-3 mt-0.5 text-[10px] text-gray-400">
                  <span v-if="fb.vehicle" class="flex items-center gap-1">
                    <TruckIcon class="w-3 h-3" /> {{ fb.vehicle }}
                  </span>
                  <span v-if="fb.created_at" class="flex items-center gap-1">
                    <CalendarIcon class="w-3 h-3" /> {{ fmtDate(fb.created_at) }}
                  </span>
                </div>
              </div>
              <div class="flex shrink-0">
                <StarIcon v-for="i in 5" :key="i"
                  :class="['w-4 h-4', i <= fb.rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-200']" />
              </div>
            </div>
            <div v-if="fb.service"
              class="inline-block text-[10px] font-semibold bg-white border border-gray-200 text-gray-600 px-2.5 py-1 rounded-full">
              {{ fb.service }}
            </div>
            <p v-if="fb.liked" class="text-xs text-gray-600 italic">"{{ fb.liked }}"</p>
            <div v-if="fb.recommend === 'yes'"
              class="text-[10px] text-green-600 font-semibold">✓ Would recommend</div>
          </div>
        </div>

        <button v-if="recentFeedback.length < totalFeedback" @click="loadMore"
          class="w-full text-xs font-semibold text-red-600 hover:underline py-1">
          Load more →
        </button>
      </div>
    </div>

    <!-- ── Full feedback table ── -->
    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h3 class="text-sm font-bold text-gray-900">All Feedback</h3>
        <div class="flex items-center gap-2">
          <SearchInput v-model="search" placeholder="Search name, vehicle…" class="w-48" />
          <select v-model="ratingFilter" class="border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none">
            <option value="">All Ratings</option>
            <option v-for="r in [5,4,3,2,1]" :key="r" :value="r">{{ r }} ★</option>
          </select>
        </div>
      </div>
      <div class="table-wrap">
        <table class="w-full text-xs">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th v-for="h in ['Customer','Vehicle','Service','Rating','Recommend','Comment','Date','Actions']" :key="h"
                class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">{{ h }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loadingAll">
              <td colspan="8" class="px-4 py-10 text-center text-gray-400">Loading…</td>
            </tr>
            <tr v-for="fb in allFeedback.data" :key="fb.id" class="hover:bg-gray-50/60">
              <td class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap">
                {{ fb.name }}
                <div class="text-[10px] text-gray-400 font-normal">{{ fb.phone }}</div>
              </td>
              <td class="px-4 py-3 font-mono whitespace-nowrap">{{ fb.vehicle ?? '—' }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ fb.service ?? '—' }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex">
                  <StarIcon v-for="i in 5" :key="i"
                    :class="['w-3.5 h-3.5', i <= fb.rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-200']" />
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full',
                  fb.recommend === 'yes' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600']">
                  {{ fb.recommend === 'yes' ? 'Yes' : 'No' }}
                </span>
              </td>
              <td class="px-4 py-3 max-w-[200px] truncate text-gray-600">{{ fb.liked ?? '—' }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ fmtDate(fb.created_at) }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <button @click="deleteFeedback(fb)"
                  class="text-xs font-semibold text-red-500 hover:text-red-700">Delete</button>
              </td>
            </tr>
            <tr v-if="!loadingAll && !allFeedback.data?.length">
              <td colspan="8" class="px-4 py-10 text-center text-gray-400">No feedback found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="allFeedback.last_page > 1" class="flex justify-between items-center px-4 py-3 border-t border-gray-100">
        <span class="text-xs text-gray-500">{{ allFeedback.total }} total</span>
        <div class="flex gap-1">
          <button @click="tablePage--" :disabled="tablePage===1" class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Prev</button>
          <button @click="tablePage++" :disabled="tablePage===allFeedback.last_page" class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { StarIcon, LinkIcon, TruckIcon, CalendarIcon } from '@heroicons/vue/24/solid'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import PageHeader        from '@/components/PageHeader.vue'
import SearchInput       from '@/components/SearchInput.vue'

const { get, post, del } = useApi()
const toast = useToastStore()

// ── State ──────────────────────────────────────────────────────────────────────
const feedbackLink    = ref('')
const copied          = ref(false)
const services        = ref([])
const recentFeedback  = ref([])
const allFeedback     = ref({ data:[], last_page:1, total:0 })
const stats           = ref(null)
const totalFeedback   = ref(0)
const loadingFeedback = ref(false)
const loadingAll      = ref(false)
const search          = ref('')
const ratingFilter    = ref('')
const tablePage       = ref(1)
const recentPage      = ref(1)
const hoverRating     = ref(0)

// Booking search for token generation
const bookingSearch   = ref('')
const foundBooking    = ref(null)
const searchingBooking = ref(false)
const generatingToken = ref(false)

// Manual form
const savingFeedback = ref(false)
const manualError    = ref(null)
const manualForm     = ref({
  name:'', phone:'', vehicle:'', service:'', service_date:'',
  rating:0, liked:'', recommend:'yes',
})

const ratingLabels = { 1:'Poor', 2:'Fair', 3:'Good', 4:'Very Good', 5:'Excellent' }
const fmtDate = d => d ? new Date(d).toLocaleDateString('en-KE',{day:'2-digit',month:'short',year:'numeric'}) : '—'

// ── Load ───────────────────────────────────────────────────────────────────────
async function loadRecent() {
  loadingFeedback.value = true
  try {
    const data = await get('/admin/feedback', { page: recentPage.value, per_page: 5 })
    if (recentPage.value === 1) {
      recentFeedback.value = data?.data ?? []
    } else {
      recentFeedback.value.push(...(data?.data ?? []))
    }
    totalFeedback.value = data?.total ?? 0
  } finally { loadingFeedback.value = false }
}

async function loadAll() {
  loadingAll.value = true
  try {
    allFeedback.value = await get('/admin/feedback', {
      page: tablePage.value, per_page: 15,
      search: search.value, rating: ratingFilter.value,
    }) ?? { data:[], last_page:1, total:0 }
  } finally { loadingAll.value = false }
}

async function loadStats() {
  stats.value = await get('/admin/feedback/stats') ?? null
}

async function loadServices() {
  services.value = await get('/admin/services') ?? []
}

// ── Generate link ──────────────────────────────────────────────────────────────
async function generateLink() {
  try {
    const data = await post('/admin/feedback/generate-token', {})
    feedbackLink.value = data.link
  } catch {}
}

async function findBooking() {
  if (!bookingSearch.value.trim()) return
  searchingBooking.value = true
  try {
    const data = await get('/admin/bookings', { search: bookingSearch.value, per_page: 1 })
    foundBooking.value = data?.data?.[0] ?? null
    if (!foundBooking.value) toast.error('No booking found.')
  } finally { searchingBooking.value = false }
}

async function generateTokenForBooking() {
  if (!foundBooking.value) return
  generatingToken.value = true
  try {
    const b = foundBooking.value
    const data = await post('/admin/feedback/generate-token', {
      customer_name:  b.customer.name,
      customer_phone: b.customer.phone,
      vehicle_reg:    b.vehicle.registration,
      service:        b.service.name,
    })
    feedbackLink.value = data.link
    copied.value = false
    foundBooking.value = null
    bookingSearch.value = ''
    toast.success('Feedback link generated.')
  } catch { toast.error('Failed to generate link.') }
  finally { generatingToken.value = false }
}

function copyLink() {
  if (!feedbackLink.value) return
  navigator.clipboard.writeText(feedbackLink.value)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

// ── Save manual feedback ───────────────────────────────────────────────────────
async function saveFeedback() {
  if (!manualForm.value.name) { manualError.value = 'Customer name is required.'; return }
  if (!manualForm.value.rating) { manualError.value = 'Please select a rating.'; return }
  savingFeedback.value = true; manualError.value = null
  try {
    await post('/admin/feedback', manualForm.value)
    toast.success('Feedback saved.')
    manualForm.value = { name:'', phone:'', vehicle:'', service:'', service_date:'', rating:0, liked:'', recommend:'yes' }
    recentPage.value = 1
    loadRecent(); loadAll(); loadStats()
  } catch (e) {
    manualError.value = e.response?.data?.message ?? 'Failed to save feedback.'
  } finally { savingFeedback.value = false }
}

// ── Delete ─────────────────────────────────────────────────────────────────────
async function deleteFeedback(fb) {
  if (!confirm(`Delete feedback from ${fb.name}?`)) return
  try {
    await del(`/admin/feedback/${fb.id}`)
    toast.success('Feedback deleted.')
    loadAll(); loadRecent(); loadStats()
  } catch { toast.error('Failed to delete.') }
}

function loadMore() {
  recentPage.value++
  loadRecent()
}

watch([tablePage, ratingFilter], loadAll)
watch(search, () => { tablePage.value = 1; loadAll() })

onMounted(() => {
  loadRecent()
  loadAll()
  loadStats()
  loadServices()
  generateLink()
})
</script>

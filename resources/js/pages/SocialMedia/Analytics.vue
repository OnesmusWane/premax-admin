<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="text-3xl font-black tracking-tight text-slate-950">Post Analytics</h2>
        <p class="mt-1 text-sm text-slate-500">Discover your best posting times and track engagement trends.</p>
      </div>

      <!-- Controls -->
      <div class="flex flex-wrap items-center gap-3">
        <select v-model="platform" @change="loadAll" class="input-base max-w-[160px] text-sm">
          <option value="all">All Platforms</option>
          <option value="facebook">Facebook</option>
          <option value="instagram">Instagram</option>
          <option value="tiktok">TikTok</option>
        </select>
        <select v-model="days" @change="loadAll" class="input-base max-w-[140px] text-sm">
          <option :value="30">Last 30 days</option>
          <option :value="60">Last 60 days</option>
          <option :value="90">Last 90 days</option>
          <option :value="180">Last 6 months</option>
        </select>
        <button
          @click="loadAll"
          :disabled="loadingHeatmap || loadingSummary"
          class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
        >
          Refresh
        </button>
      </div>
    </div>

    <!-- Engagement Summary Cards -->
    <section>
      <h3 class="mb-4 text-base font-black text-slate-950">Engagement Summary</h3>
      <div v-if="loadingSummary" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-24 animate-pulse rounded-2xl bg-slate-100" />
      </div>
      <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Total Posts</div>
          <div class="mt-2 text-3xl font-black text-slate-950">{{ summary.total_posts ?? 0 }}</div>
          <div class="mt-1 text-xs text-slate-400">In the last {{ days }} days</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Total Likes</div>
          <div class="mt-2 text-3xl font-black text-slate-950">{{ fmtNum(summary.total_likes) }}</div>
          <div class="mt-1 text-xs text-slate-400">Avg {{ summary.avg_likes_per_post ?? 0 }} per post</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Total Comments</div>
          <div class="mt-2 text-3xl font-black text-slate-950">{{ fmtNum(summary.total_comments) }}</div>
          <div class="mt-1 text-xs text-slate-400">Avg {{ summary.avg_comments_per_post ?? 0 }} per post</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Total Shares</div>
          <div class="mt-2 text-3xl font-black text-slate-950">{{ fmtNum(summary.total_shares) }}</div>
          <div class="mt-1 text-xs text-slate-400">Avg {{ summary.avg_shares_per_post ?? 0 }} per post</div>
        </div>
      </div>
    </section>

    <!-- Engagement Trend Chart -->
    <section v-if="!loadingSummary" class="rounded-2xl border border-slate-200 bg-white p-6">
      <h3 class="mb-5 text-base font-black text-slate-950">Daily Engagement Trend</h3>
      <div v-if="!trendData.labels.length" class="py-10 text-center text-sm text-slate-400">
        No trend data for this period.
      </div>
      <div v-else class="h-52">
        <Line :data="trendData" :options="trendOptions" />
      </div>
    </section>

    <!-- Top Posts -->
    <section v-if="!loadingSummary && (summary.top_performing_posts || []).length">
      <h3 class="mb-4 text-base font-black text-slate-950">Top Performing Posts</h3>
      <div class="rounded-2xl border border-slate-200 bg-white divide-y divide-slate-100">
        <div
          v-for="(p, i) in summary.top_performing_posts"
          :key="p.post_id"
          class="flex items-center gap-4 px-5 py-4"
        >
          <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-black text-slate-600">
            {{ i + 1 }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="truncate text-sm font-bold text-slate-900">{{ p.title }}</p>
            <p class="text-xs text-slate-400">{{ formatDate(p.published_at) }}</p>
          </div>
          <div class="hidden sm:flex gap-4 text-right">
            <div>
              <div class="text-xs text-slate-400">Likes</div>
              <div class="text-sm font-black text-slate-800">{{ fmtNum(p.likes) }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-400">Comments</div>
              <div class="text-sm font-black text-slate-800">{{ fmtNum(p.comments) }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-400">Shares</div>
              <div class="text-sm font-black text-slate-800">{{ fmtNum(p.shares) }}</div>
            </div>
          </div>
          <div class="shrink-0 rounded-xl bg-[rgba(211,30,36,0.08)] px-3 py-1.5 text-center">
            <div class="text-xs text-slate-500">Total</div>
            <div class="text-sm font-black text-[var(--color-custom-primary)]">{{ fmtNum(p.total_engagement) }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Best Time To Post section -->
    <section>
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-base font-black text-slate-950">Best Time to Post</h3>
        <span v-if="heatmapStats.total_posts_analyzed" class="text-xs text-slate-400">
          Based on {{ heatmapStats.total_posts_analyzed }} published post(s)
        </span>
      </div>

      <!-- Best slot cards -->
      <div v-if="!loadingHeatmap && heatmapStats.best_slot" class="mb-5 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center">
          <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Best Day</div>
          <div class="mt-2 text-2xl font-black text-slate-950">{{ heatmapStats.best_day }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center">
          <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Best Time</div>
          <div class="mt-2 text-2xl font-black text-slate-950">{{ heatmapStats.best_hour }}</div>
        </div>
        <div class="rounded-2xl border-2 border-[var(--color-custom-primary)] bg-[rgba(211,30,36,0.04)] p-5 text-center">
          <div class="text-xs font-bold uppercase tracking-[0.16em] text-[var(--color-custom-primary)]">Best Slot</div>
          <div class="mt-2 text-lg font-black text-slate-950 leading-tight">{{ heatmapStats.best_slot }}</div>
        </div>
      </div>

      <!-- Heatmap -->
      <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div v-if="loadingHeatmap" class="space-y-2">
          <div v-for="i in 7" :key="i" class="flex gap-1">
            <div class="h-7 w-16 animate-pulse rounded-lg bg-slate-100 shrink-0" />
            <div class="flex flex-1 gap-1">
              <div v-for="j in 24" :key="j" class="h-7 flex-1 animate-pulse rounded bg-slate-100" />
            </div>
          </div>
        </div>

        <div v-else-if="!heatmapData.length" class="py-10 text-center text-sm text-slate-400">
          No data yet. Publish some posts and come back.
        </div>

        <div v-else class="overflow-x-auto">
          <!-- Hour header -->
          <div class="flex gap-1 mb-1 pl-[68px]">
            <div
              v-for="h in hourHeaders"
              :key="h.hour"
              class="flex-1 text-center text-[9px] font-bold text-slate-400"
              :style="{ minWidth: '28px' }"
            >
              {{ h.label }}
            </div>
          </div>

          <!-- Day rows -->
          <div v-for="dayRow in heatmapData" :key="dayRow.day" class="flex items-center gap-1 mb-1">
            <div class="w-16 shrink-0 text-right pr-2 text-[11px] font-bold text-slate-500">
              {{ dayRow.day_name.slice(0, 3) }}
            </div>
            <button
              v-for="cell in dayRow.hours"
              :key="cell.hour"
              class="h-7 flex-1 rounded transition hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-[var(--color-custom-primary)]"
              :style="{ minWidth: '28px', backgroundColor: cellColor(cell.avg_engagement), opacity: cellOpacity(cell.avg_engagement) }"
              :title="`${dayRow.day_name} ${cell.label}: ${cell.avg_engagement} avg engagement (${cell.post_count} posts)`"
            />
          </div>

          <!-- Legend -->
          <div class="mt-4 flex items-center justify-end gap-2">
            <span class="text-xs text-slate-400">Low</span>
            <div class="flex gap-1">
              <div
                v-for="l in [0.15, 0.3, 0.5, 0.7, 0.9]"
                :key="l"
                class="h-3 w-6 rounded"
                :style="{ backgroundColor: `rgba(211,30,36,${l})` }"
              />
            </div>
            <span class="text-xs text-slate-400">High</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Recommendations -->
    <section v-if="!loadingHeatmap && heatmapStats.recommendations?.length">
      <h3 class="mb-4 text-base font-black text-slate-950">Recommendations</h3>
      <div class="rounded-2xl border border-slate-200 bg-white divide-y divide-slate-100">
        <div v-for="(rec, i) in heatmapStats.recommendations" :key="i" class="flex items-start gap-4 px-5 py-4">
          <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[rgba(211,30,36,0.08)]">
            <LightBulbIcon class="h-3.5 w-3.5 text-[var(--color-custom-primary)]" />
          </div>
          <p class="text-sm text-slate-700">{{ rec }}</p>
        </div>
      </div>
    </section>

    <!-- Top Slots table -->
    <section v-if="!loadingHeatmap && heatmapStats.top_slots?.length">
      <h3 class="mb-4 text-base font-black text-slate-950">Top 5 Time Slots</h3>
      <div class="rounded-2xl border border-slate-200 bg-white divide-y divide-slate-100">
        <div
          v-for="(slot, i) in heatmapStats.top_slots"
          :key="i"
          class="flex items-center justify-between px-5 py-4"
        >
          <div class="flex items-center gap-3">
            <div
              class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black"
              :class="i === 0 ? 'bg-[var(--color-custom-primary)] text-white' : 'bg-slate-100 text-slate-600'"
            >
              {{ i + 1 }}
            </div>
            <div>
              <p class="text-sm font-bold text-slate-900">{{ slot.day }}, {{ slot.hour }}</p>
              <p class="text-xs text-slate-400">{{ slot.post_count }} post(s) analyzed</p>
            </div>
          </div>
          <div class="text-right">
            <div class="text-sm font-black text-slate-900">{{ slot.avg_engagement.toFixed(1) }}</div>
            <div class="text-xs text-slate-400">avg engagement</div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { LightBulbIcon } from '@heroicons/vue/24/outline'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  Filler,
} from 'chart.js'
import { Line } from 'vue-chartjs'
import { useApi } from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler)

const toast = useToastStore()
const { get } = useApi()

const platform       = ref('all')
const days           = ref(90)
const loadingHeatmap = ref(false)
const loadingSummary = ref(false)

const heatmapData  = ref([])
const heatmapStats = ref({})
const summary      = ref({})

// Hour column headers — show label every 6 hours to avoid crowding
const hourHeaders = Array.from({ length: 24 }, (_, h) => ({
  hour:  h,
  label: h % 6 === 0 ? fmtHour(h) : '',
}))

// Precompute max engagement for color scaling
const maxEngagement = computed(() => {
  let max = 0
  for (const day of heatmapData.value) {
    for (const h of day.hours) {
      if (h.avg_engagement > max) max = h.avg_engagement
    }
  }
  return max || 1
})

function cellColor(avg) {
  return 'rgb(211,30,36)'
}

function cellOpacity(avg) {
  if (!avg) return 0.07
  return 0.12 + (avg / maxEngagement.value) * 0.85
}

// Chart data
const trendData = computed(() => {
  const trend = summary.value.engagement_trend || []
  return {
    labels:   trend.map(t => formatDate(t.date)),
    datasets: [
      {
        label:           'Engagement',
        data:            trend.map(t => t.engagement),
        borderColor:     'rgb(211,30,36)',
        backgroundColor: 'rgba(211,30,36,0.08)',
        borderWidth:     2,
        pointRadius:     3,
        pointBackgroundColor: 'rgb(211,30,36)',
        tension:         0.35,
        fill:            true,
      },
    ],
  }
})

const trendOptions = {
  responsive:          true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: {
      grid:  { display: false },
      ticks: { color: '#94a3b8', font: { size: 10 }, maxTicksLimit: 8 },
    },
    y: {
      grid:  { color: '#f1f5f9' },
      ticks: { color: '#94a3b8', font: { size: 10 } },
      beginAtZero: true,
    },
  },
}

async function loadHeatmap() {
  loadingHeatmap.value = true
  try {
    const params = { platform: platform.value, days: days.value }
    const data   = await get('/analytics/best-time-to-post', params)
    heatmapData.value  = data.heatmap || []
    heatmapStats.value = data
  } catch {
    toast.error('Failed to load heatmap data.')
  } finally {
    loadingHeatmap.value = false
  }
}

async function loadSummary() {
  loadingSummary.value = true
  try {
    const params = { platform: platform.value, days: days.value }
    const data   = await get('/analytics/engagement-summary', params)
    summary.value = data
  } catch {
    toast.error('Failed to load engagement summary.')
  } finally {
    loadingSummary.value = false
  }
}

function loadAll() {
  loadHeatmap()
  loadSummary()
}

function fmtHour(h) {
  if (h === 0)  return '12am'
  if (h === 12) return '12pm'
  return h < 12 ? `${h}am` : `${h - 12}pm`
}

function fmtNum(n) {
  return new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 }).format(Number(n || 0))
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleDateString([], { month: 'short', day: 'numeric' })
}

onMounted(loadAll)
</script>

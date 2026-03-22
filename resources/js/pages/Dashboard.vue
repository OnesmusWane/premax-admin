<template>
  <div class="p-4 md:p-6 space-y-5">

    <!-- ── STAT CARDS ── -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
      <div v-for="card in statCards" :key="card.label"
        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-col gap-2">
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide leading-tight">{{ card.label }}</span>
          <div :class="['w-7 h-7 rounded-xl flex items-center justify-center shrink-0', card.iconBg]">
            <component :is="card.icon" :class="['w-4 h-4', card.iconColor]" />
          </div>
        </div>
        <div v-if="dashLoading" class="h-7 w-20 animate-pulse bg-gray-100 rounded" />
        <div v-else>
          <div class="text-xl font-extrabold text-gray-900 leading-tight">
            {{ card.prefix ?? '' }}{{ card.value ?? 0 }}
          </div>
          <div v-if="card.sub" class="text-[10px] text-gray-400 mt-0.5">{{ card.sub }}</div>
          <div v-if="card.prev !== undefined" class="flex items-center gap-1 mt-0.5 hidden">
            <span :class="['text-[10px] font-bold',
              Number(card.rawValue) >= Number(card.prev) ? 'text-green-600' : 'text-red-500']">
              {{ Number(card.rawValue) >= Number(card.prev) ? '▲' : '▼' }}
              {{ Math.abs(Number(card.rawValue) - Number(card.prev)) }}
            </span>
            <span class="text-[10px] text-gray-400">vs yesterday</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── CHARTS ROW ── -->
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-4">

      <!-- Weekly Revenue Bar Chart -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-bold text-gray-900">Revenue This Week</h3>
          <span class="text-[10px] text-gray-400 bg-gray-50 px-2 py-1 rounded-lg">KES</span>
        </div>
        <div v-if="dashLoading" class="h-48 animate-pulse bg-gray-50 rounded-xl" />
        <Bar v-else-if="weeklyData.length" :data="weeklyChartData" :options="barOptions" class="max-h-48" />
        <div v-else class="h-48 flex items-center justify-center text-gray-400 text-xs">No revenue data yet</div>
      </div>

      <!-- Service Popularity Doughnut -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Service Mix</h3>
        <div v-if="dashLoading" class="h-32 w-32 mx-auto animate-pulse bg-gray-50 rounded-full" />
        <template v-else-if="popularityData.length">
          <div class="flex justify-center">
            <Doughnut :data="doughnutData" :options="doughnutOptions" class="max-h-32" />
          </div>
          <div class="flex flex-wrap gap-x-3 gap-y-1 mt-3">
            <span v-for="(item, i) in popularityData" :key="i"
              class="flex items-center gap-1 text-[10px] text-gray-600">
              <span class="w-2 h-2 rounded-full shrink-0"
                :style="`background:${chartColors[i % chartColors.length]}`" />
              {{ item.service_name }}
            </span>
          </div>
        </template>
        <div v-else class="h-32 flex items-center justify-center text-gray-400 text-xs">No data yet</div>
      </div>
    </div>

    <!-- ── BOTTOM ROW ── -->
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-4">

      <!-- Today's Bookings Table -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 table-wrap">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-bold text-gray-900">Today's Bookings</h3>
          <RouterLink to="/bookings" class="text-xs font-semibold text-red-600 hover:underline">View All →</RouterLink>
        </div>
        <div v-if="dashLoading" class="space-y-2">
          <div v-for="i in 5" :key="i" class="h-8 animate-pulse bg-gray-50 rounded-xl" />
        </div>
        <div v-else-if="!recentBookings.length"
          class="py-8 text-center text-gray-400 text-xs">No bookings today.</div>
        <table v-else class="w-full text-xs">
          <thead>
            <tr class="text-[10px] text-gray-400 uppercase tracking-wide border-b border-gray-100">
              <th class="text-left pb-2.5 font-semibold">Customer</th>
              <th class="text-left pb-2.5 font-semibold">Vehicle</th>
              <th class="text-left pb-2.5 font-semibold">Service</th>
              <th class="text-left pb-2.5 font-semibold">Time</th>
              <th class="text-left pb-2.5 font-semibold">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="b in recentBookings" :key="b.id" class="hover:bg-gray-50/50">
              <td class="py-2.5 font-semibold text-gray-900">{{ b?.customer?.name }}</td>
              <td class="py-2.5 font-mono text-gray-600">{{ b?.vehicle?.registration }}</td>
              <td class="py-2.5 text-gray-600 max-w-[100px] truncate">{{ b?.service?.name }}</td>
              <td class="py-2.5 text-gray-400">  {{ b?.scheduled_at ? new Date(b.scheduled_at).toLocaleString() : '' }}</td>
              <td class="py-2.5">
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
                  :style="`background:${statusColor(b.status_slug)}20;color:${statusColor(b.status_slug)}`">
                  {{ b?.status?.name }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Right column -->
      <div class="space-y-4">

        <!-- Today's Payments -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <h3 class="text-sm font-bold text-gray-900 mb-3">Today's Payments</h3>
          <div v-if="dashLoading" class="space-y-2">
            <div v-for="i in 3" :key="i" class="h-5 animate-pulse bg-gray-50 rounded" />
          </div>
          <div v-else class="space-y-2">
            <div v-for="row in paymentBreakdown" :key="row.label"
              class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-2">
                <span :class="['w-2 h-2 rounded-full', row.dot]" />
                <span class="text-gray-600">{{ row.label }}</span>
              </div>
              <span class="font-bold text-gray-900">KES {{ (row.value ?? 0).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-xs font-extrabold text-gray-900 border-t border-gray-100 pt-2">
              <span>Total</span>
              <span>KES {{ (todayPayments.total ?? 0).toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <!-- Stock Alerts -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-gray-900">Stock Alerts</h3>
            <span v-if="inventoryAlerts.length"
              class="w-5 h-5 bg-red-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
              {{ inventoryAlerts.length }}
            </span>
          </div>
          <div v-if="dashLoading" class="space-y-2">
            <div v-for="i in 3" :key="i" class="h-7 animate-pulse bg-gray-50 rounded-xl" />
          </div>
          <div v-else-if="!inventoryAlerts.length"
            class="py-3 text-center text-gray-400 text-xs">All stock levels healthy 🎉</div>
          <div v-else class="space-y-2.5">
            <div v-for="item in inventoryAlerts.slice(0, 5)" :key="item.id"
              class="flex items-center justify-between">
              <div>
                <div class="text-xs font-semibold text-gray-900 leading-tight">{{ item.name }}</div>
                <div class="text-[10px] text-gray-400">{{ item.stock_qty }} left</div>
              </div>
              <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0',
                item.stock_qty <= 0 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">
                {{ item.stock_qty <= 0 ? 'Out' : 'Low' }}
              </span>
            </div>
          </div>
          <RouterLink to="/inventory"
            class="mt-3 flex items-center justify-center w-full border border-gray-200 text-xs font-semibold text-gray-600 py-2 rounded-xl hover:bg-gray-50 transition-colors">
            Manage Inventory →
          </RouterLink>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  BarElement, ArcElement, Tooltip, Legend,
} from 'chart.js'
import {
  TruckIcon, ClipboardDocumentListIcon, BanknotesIcon,
  CalendarDaysIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend)

const { get } = useApi()

const dashLoading     = ref(true)
const stats           = ref({})
const weeklyData      = ref([])
const popularityData  = ref([])
const recentBookings  = ref([])
const inventoryAlerts = ref([])
const todayPayments   = ref({})

const chartColors = ['#3B82F6','#22C55E','#A855F7','#F97316','#EAB308','#EF4444','#14B8A6']

const statusColors = {
  pending:'#EAB308', confirmed:'#3B82F6', in_progress:'#F97316',
  completed:'#22C55E', cancelled:'#EF4444', no_show:'#6B7280',
}
const statusColor = slug => statusColors[slug] ?? '#6B7280'

const statCards = computed(() => [
  {
    label:'Cars Serviced Today', sub:'Completed services',
    rawValue: stats.value.cars_today,
    value:    stats.value.cars_today,
    prev:     stats.value.cars_today,
    icon:TruckIcon, iconBg:'bg-red-50', iconColor:'text-red-600',
  },
  {
    label:'Active Jobs', sub:'In workshop now',
    value: stats.value.active_jobs,
    icon:ClipboardDocumentListIcon, iconBg:'bg-purple-50', iconColor:'text-purple-600',
  },
  {
    label:'Daily Revenue', sub:'Paid invoices today', prefix:'KES ',
    rawValue: stats.value.revenue_today,
    value:    stats.value.revenue_today?.toLocaleString(),
    prev:     stats.value.revenue_today,
    icon:BanknotesIcon, iconBg:'bg-green-50', iconColor:'text-green-600',
  },
  {
    label:'Pending Bookings', sub:'Awaiting service',
    value: stats.value.pending_bookings,
    icon:CalendarDaysIcon, iconBg:'bg-yellow-50', iconColor:'text-yellow-600',
  },
  {
    label:'Low Stock Items', sub:'Need restocking',
    value: stats.value.low_stock,
    icon:ExclamationTriangleIcon, iconBg:'bg-red-50', iconColor:'text-red-500',
  },
])

const weeklyChartData = computed(() => ({
  labels: weeklyData.value.map(d => d.label),
  datasets: [{
    data:            weeklyData.value.map(d => d.total),
    backgroundColor: weeklyData.value.map((_, i) =>
      i === weeklyData.value.length - 1 ? '#EF4444' : '#E5E7EB'
    ),
    borderRadius: 6,
    borderSkipped: false,
  }],
}))

const barOptions = {
  responsive: true,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
    y: {
      grid: { color: '#f9fafb' },
      ticks: { font: { size: 10 }, callback: v => `${(v/1000).toFixed(0)}k` },
    },
  },
}

const doughnutData = computed(() => ({
  labels:   popularityData.value.map(d => d.service_name),
  datasets: [{
    data:            popularityData.value.map(d => d.count),
    backgroundColor: chartColors,
    borderWidth:     0,
    hoverOffset:     6,
  }],
}))

const doughnutOptions = {
  responsive: true, cutout: '65%',
  plugins: { legend: { display: false } },
}

const paymentBreakdown = computed(() => [
  { label:'Cash',   dot:'bg-green-500',   value: todayPayments.value.cash  },
  { label:'M-Pesa', dot:'bg-emerald-500', value: todayPayments.value.mpesa },
  { label:'Card',   dot:'bg-blue-500',    value: todayPayments.value.card  },
])

onMounted(async () => {
  try {
    const [dash, payments] = await Promise.all([
      get('/admin/dashboard'),
      get('/admin/invoices/today'),
    ])
    stats.value           = dash.stats              ?? {}
    weeklyData.value      = dash.weekly_revenue     ?? []
    popularityData.value  = dash.service_popularity ?? []
    recentBookings.value  = dash.recent_bookings    ?? []
    inventoryAlerts.value = dash.inventory_alerts   ?? []
    todayPayments.value   = payments                ?? {}
  } finally {
    dashLoading.value = false
  }
})
</script>
<template>
  <div class="p-4 md:p-6 space-y-4">
    <PageHeader title="Analytics & Reports">
      <div class="flex items-center gap-2">
        <select v-model="days" @change="load" class="border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white focus:outline-none">
          <option :value="7">Last 7 Days</option>
          <option :value="30">Last 30 Days</option>
          <option :value="90">Last 90 Days</option>
        </select>
        <button class="flex items-center gap-1.5 border border-gray-200 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-gray-50">
          <ArrowDownTrayIcon class="w-4 h-4" /> Export
        </button>
      </div>
    </PageHeader>
 
    <!-- Summary cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 px-4 md:px-6">
      <div v-for="card in metaCards" :key="card.label" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="text-xs text-gray-500 mb-1">{{ card.label }}</div>
        <div class="text-xl font-extrabold text-gray-900">{{ card.value }}</div>
        <div v-if="card.sub" class="text-[10px] text-green-600 mt-0.5">{{ card.sub }}</div>
      </div>
    </div>
 
    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 px-4 md:px-6">
      <!-- Revenue over time -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Revenue Over Time</h3>
        <Line v-if="revenueData" :data="revenueChartData" :options="lineOptions" class="max-h-[220px]" />
        <div v-else class="h-[220px] animate-pulse bg-gray-100 rounded-xl" />
      </div>
      <!-- Services performed -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Services Performed</h3>
        <Bar v-if="servicesData" :data="servicesChartData" :options="barOptions" class="max-h-[220px]" />
        <div v-else class="h-[220px] animate-pulse bg-gray-100 rounded-xl" />
      </div>
    </div>
  </div>
</template>
 
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Line, Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement, PointElement, Filler, Tooltip, Legend } from 'chart.js'
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import PageHeader from '@/components/PageHeader.vue'
 
ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Filler, Tooltip, Legend)
 
const { get } = useApi()
const days = ref(30); const summary = ref({}); const revenueData = ref(null); const servicesData = ref(null)
 
const metaCards = computed(()=>[
  { label:'Total Revenue',       value:`KES ${(summary.value.total_revenue??0).toLocaleString()}`, sub:`+${summary.value.revenue_growth??0}% from last month` },
  { label:'Avg Daily Revenue',   value:`KES ${(summary.value.avg_daily??0).toLocaleString()}`, sub:`+${summary.value.daily_growth??0}% from last month` },
  { label:'Total Customers',     value:(summary.value.total_customers??0).toLocaleString(), sub:`+${summary.value.new_customers??0} new this month` },
  { label:'Most Popular Service',value:summary.value.most_popular?.service_name??'—', sub:`${summary.value.most_popular?.count??0} jobs` },
])
 
const revenueChartData = computed(()=>({
  labels:   revenueData.value?.map((_,i)=>`Week ${i+1}`) ?? [],
  datasets: [{ data:revenueData.value?.map(d=>d.total)??[], fill:true, borderColor:'#3B82F6', backgroundColor:'rgba(59,130,246,0.1)', tension:0.4, pointRadius:3 }]
}))
 
const servicesChartData = computed(()=>({
  labels:   servicesData.value?.map(d=>d.service_name) ?? [],
  datasets: [{ data:servicesData.value?.map(d=>d.count)??[], backgroundColor:'#22C55E', borderRadius:4, barThickness:16 }]
}))
 
const lineOptions = { responsive:true, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}}, y:{grid:{color:'#f3f4f6'}} } }
const barOptions  = { responsive:true, indexAxis:'y', plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}}, y:{grid:{display:false}} } }
 
async function load() {
  const data = await get('/admin/reports', { days:days.value })
  summary.value     = data.summary
  revenueData.value  = data.revenue_over_time
  servicesData.value = data.services_breakdown
}
onMounted(load)
</script>
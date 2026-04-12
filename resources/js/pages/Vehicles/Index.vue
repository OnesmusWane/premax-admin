<!-- ═══════════════════════════════════════════════════════
     resources/js/pages/Vehicles/Index.vue
═══════════════════════════════════════════════════════ -->
<template>
  <div class="p-4 md:p-6 space-y-4">
    <PageHeader title="Vehicles" subtitle="All registered vehicles" class="flex flex-col md:flex-row items-start md:items-center md:justify-between">
      <SearchInput v-model="search" placeholder="Search by reg, make, owner..." class="w-full md:w-[340px]" />
    </PageHeader>

    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden table-wrap">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th v-for="h in ['Registration','Make & Model','Year · Color','Owner','Last Service','Visits','Actions']" :key="h"
              class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">{{ h }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-12 text-center text-gray-400">
              <div class="flex items-center justify-center gap-2">
                <div class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin" />
                Loading…
              </div>
            </td>
          </tr>
          <tr v-for="v in vehicles.data" :key="v.id"
            class="hover:bg-gray-50/60 transition-colors cursor-pointer" @click="$router.push(`/vehicles/${v.id}`)">
            <td class="px-4 py-3.5">
              <div class="font-mono font-bold text-gray-900 text-xs">{{ v.registration }}</div>
            </td>
            <td class="px-4 py-3.5">
              <div class="font-semibold text-gray-900 text-xs">{{ v.make }} {{ v.model }}</div>
            </td>
            <td class="px-4 py-3.5 text-xs text-gray-600">
              {{ v.year ?? '—' }} · <span class="capitalize">{{ v.color ?? '—' }}</span>
            </td>
            <td class="px-4 py-3.5">
              <RouterLink :to="`/customers/${v.customer?.id}`" @click.stop
                class="text-xs text-red-600 hover:underline font-medium">
                {{ v.customer?.name ?? '—' }}
              </RouterLink>
              <div class="text-[10px] text-gray-400">{{ v.customer?.phone }}</div>
            </td>
            <td class="px-4 py-3.5">
              <div v-if="v.last_service_at" class="text-xs text-gray-700">{{ fmtDate(v.last_service_at) }}</div>
              <div v-else class="text-xs text-gray-400">Never serviced</div>
            </td>
            <td class="px-4 py-3.5">
              <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 text-gray-700 rounded-full text-xs font-bold">
                {{ v.bookings_count ?? 0 }}
              </span>
            </td>
            <td class="px-4 py-3.5" @click.stop>
              <RouterLink :to="`/vehicles/${v.id}`"
                class="text-xs font-semibold text-red-600 hover:underline">View →</RouterLink>
            </td>
          </tr>
          <tr v-if="!loading && !vehicles.data?.length">
            <td colspan="7" class="px-4 py-12 text-center text-gray-400">No vehicles found.</td>
          </tr>
        </tbody>
      </table>
      <div v-if="vehicles.last_page > 1" class="flex justify-between items-center px-4 py-3 border-t border-gray-100">
        <span class="text-xs text-gray-500">{{ vehicles.total }} vehicles total</span>
        <div class="flex gap-1">
          <button @click="page--" :disabled="page===1" class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Prev</button>
          <button @click="page++" :disabled="page===vehicles.last_page" class="px-3 py-1.5 text-xs border rounded-lg disabled:opacity-40 hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'
import PageHeader  from '@/components/PageHeader.vue'
import SearchInput from '@/components/SearchInput.vue'

const { get, loading } = useApi()
const vehicles = ref({ data: [], last_page: 1, total: 0 })
const search   = ref('')
const page     = ref(1)

const fmtDate = d => new Date(d).toLocaleDateString('en-KE', { day: '2-digit', month: 'short', year: 'numeric' })

async function load() {
  vehicles.value = await get('/admin/vehicles', { page: page.value, search: search.value })
}

watch([page], load)
watch(search, () => { page.value = 1; load() })
onMounted(load)
</script>

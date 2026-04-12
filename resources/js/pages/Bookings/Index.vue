<template>
  <div class="p-4 md:p-6 space-y-4">

    <PageHeader title="Bookings" subtitle="Manage all service appointments">
      <button @click="openNew"
        class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors">
        <PlusIcon class="w-4 h-4" /> New Booking
      </button>
    </PageHeader>

    <!-- ── TOOLBAR ── -->
    <div class="flex flex-wrap items-center gap-3 px-4 md:px-6">
      <SearchInput v-model="search" placeholder="Search by name, phone, reg..." class="flex-1 max-w-xs" />
      <select v-model="statusFilter"
        class="border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">
        <option value="">All Statuses</option>
        <option v-for="s in statuses" :key="s.slug" :value="s.slug">{{ s.name }}</option>
      </select>
      <select v-model="serviceFilter"
        class="border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">
        <option value="">All Services</option>
        <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>
      <input type="date" v-model="dateFilter"
        class="border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 bg-white focus:outline-none focus:border-red-400">
      <button v-if="statusFilter || serviceFilter || dateFilter || search" @click="clearFilters"
        class="flex items-center gap-1 text-xs font-semibold text-gray-500 hover:text-gray-900 border border-gray-200 rounded-xl px-3 py-2 hover:bg-gray-50 transition-colors">
        <XMarkIcon class="w-3.5 h-3.5" /> Clear
      </button>
    </div>

    <!-- ── TABLE ── -->
    <div class="mx-4 md:mx-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden table-wrap">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th v-for="h in headers" :key="h"
              class="text-left px-4 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">{{ h }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading">
            <td :colspan="headers.length" class="px-4 py-12 text-center text-gray-400 text-sm">
              <div class="flex items-center justify-center gap-2">
                <div class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin" />
                Loading…
              </div>
            </td>
          </tr>
          <tr v-for="b in bookings.data" :key="b.id"
            class="hover:bg-gray-50/60 transition-colors cursor-pointer" @click="openView(b)">
            <td class="px-4 py-3.5">
              <div class="font-semibold text-gray-900 text-xs">{{ b.customer.name }}</div>
              <div class="text-gray-400 text-[10px]">{{ b.customer.phone }}</div>
            </td>
            <td class="px-4 py-3.5">
              <div class="font-mono font-bold text-gray-900 text-xs">{{ b.vehicle.registration }}</div>
              <div v-if="b.vehicle.make && b.vehicle.make !== 'Unknown'" class="text-gray-400 text-[10px]">
                {{ b.vehicle.make }} {{ b.vehicle.model }}
              </div>
            </td>
            <td class="px-4 py-3.5 text-xs text-gray-700">{{ b.service.name }}</td>
            <td class="px-4 py-3.5">
              <div class="text-xs font-medium text-gray-700">{{ b.scheduled_date }}</div>
              <div class="text-[10px] text-gray-400">{{ b.scheduled_time }}</div>
            </td>
            <td class="px-4 py-3.5">
              <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full"
                :style="`background:${statusColor(b.status.slug)}20; color:${statusColor(b.status.slug)}`">
                <span class="w-1.5 h-1.5 rounded-full" :style="`background:${statusColor(b.status.slug)}`" />
                {{ b.status.name }}
              </span>
            </td>
            <td class="px-4 py-3.5">
              <div class="flex flex-wrap items-center gap-1.5">
                <!-- Checklist badge -->
                <span v-if="b.checklist" :class="['inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full',
                  b.checklist.status === 'check_out' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700']">
                  <ClipboardDocumentCheckIcon class="w-3 h-3" />
                  {{ b.checklist.status === 'check_out' ? 'Out' : 'In' }}
                </span>
                <span v-else class="text-[10px] text-gray-300">No checklist</span>
                <!-- Invoice badge — uses is_paid flag -->
                <span v-if="b.is_paid"
                  class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                  <ReceiptPercentIcon class="w-3 h-3" />
                  {{ b.invoices?.length > 1 ? `${b.invoices.length} invoices` : 'Paid' }}
                </span>
                <span :class="paymentStatusBadge(b.payment_summary?.status)">
                  {{ paymentStatusLabel(b.payment_summary?.status) }}
                </span>
                <span v-if="b.deposit?.required" :class="['inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full',
                  b.deposit?.is_paid ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700']">
                  Deposit {{ b.deposit?.is_paid ? 'Paid' : 'Pending' }}
                </span>
              </div>
            </td>
            <td class="px-4 py-3.5" @click.stop>
              <div class="flex items-center gap-2">
                <button @click="openEdit(b)" class="text-xs font-semibold text-red-600 hover:underline">Edit</button>
                <span class="text-gray-200">·</span>
                <button @click="handleDelete(b)" class="text-xs font-semibold text-gray-400 hover:text-red-500">Delete</button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && !bookings.data?.length">
            <td :colspan="headers.length" class="px-4 py-16 text-center">
              <div class="flex flex-col items-center gap-2 text-gray-400">
                <CalendarDaysIcon class="w-8 h-8 text-gray-200" />
                <p class="text-sm">No bookings found.</p>
                <button @click="openNew" class="text-xs font-semibold text-red-600 hover:underline">Create first booking →</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="bookings.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
        <span class="text-xs text-gray-500">Showing {{ bookings.from }}–{{ bookings.to }} of {{ bookings.total }} entries</span>
        <div class="flex items-center gap-1">
          <button @click="page--" :disabled="page === 1" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">Prev</button>
          <button v-for="p in pageNumbers" :key="p" @click="page = p"
            :class="['px-3 py-1.5 text-xs border rounded-lg', p === page ? 'bg-red-600 text-white border-red-600' : 'border-gray-200 hover:bg-gray-50']">{{ p }}</button>
          <button @click="page++" :disabled="page === bookings.last_page" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         MODAL 1: VIEW BOOKING DETAILS
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showView" title="Booking Details" size="lg">
      <div v-if="selected" class="space-y-4">

        <!-- Reference -->
        <div class="flex items-center justify-between bg-red-50 rounded-xl px-4 py-3">
          <span class="text-xs text-gray-500">Reference</span>
          <span class="font-mono font-extrabold text-red-600">{{ selected.reference }}</span>
        </div>

        <!-- Details grid -->
        <div class="border border-gray-100 rounded-xl overflow-hidden">
          <div v-for="(row, i) in viewRows" :key="row.label"
            :class="['flex items-start justify-between px-4 py-3',
              i % 2 === 1 ? 'bg-gray-50/50' : '',
              i < viewRows.length - 1 ? 'border-b border-gray-100' : '']">
            <span class="text-xs text-gray-500 shrink-0">{{ row.label }}</span>
            <span class="text-xs font-semibold text-gray-900 text-right ml-4">{{ row.value }}</span>
          </div>
        </div>

        <!-- ── Invoices section ── -->
        <div class="border border-gray-100 rounded-xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center gap-2">
              <ReceiptPercentIcon class="w-4 h-4 text-gray-400" />
              <span class="text-xs font-bold text-gray-700">Payment / Invoices</span>
            </div>
            <!-- Always allow adding another invoice -->
            <button @click="goToCheckout(selected)"
              class="text-[10px] font-bold text-green-600 hover:underline flex items-center gap-1">
              <PlusIcon class="w-3 h-3" /> Add Invoice
            </button>
          </div>

          <div class="divide-y divide-gray-100">
            <div class="px-4 py-3 bg-gray-50/60 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">Overall Payment Status</span>
                <span :class="paymentStatusBadge(selected.payment_summary?.status)">{{ paymentStatusLabel(selected.payment_summary?.status) }}</span>
              </div>
              <div v-if="selected.deposit?.required" class="space-y-2">
                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-500">Deposit Progress</span>
                  <span class="font-semibold text-gray-900">
                    KES {{ selected.deposit.paid_amount?.toLocaleString() }} / {{ selected.deposit.required_amount?.toLocaleString() }}
                  </span>
                </div>
                <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full bg-amber-500 rounded-full transition-all" :style="`width:${depositProgress(selected)}%`" />
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-[10px] text-gray-500">
                    {{ selected.deposit.is_paid ? 'Down payment fully collected.' : `Outstanding: KES ${selected.deposit.outstanding_amount?.toLocaleString()}` }}
                  </span>
                  <button v-if="!selected.deposit.is_paid" @click="openDepositModal(selected)"
                    class="text-[10px] font-bold text-amber-700 border border-amber-300 rounded-lg px-2.5 py-1 hover:bg-amber-50">
                    Collect Down Payment
                  </button>
                </div>
              </div>
            </div>
            <!-- Existing invoices -->
            <div v-if="selected.invoices?.length" class="divide-y divide-gray-50">
              <div v-for="inv in selected.invoices" :key="inv.id"
                class="flex items-center justify-between px-4 py-3">
                <div>
                  <div class="text-xs font-bold text-gray-900">{{ inv.invoice_number }}</div>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs font-semibold text-green-700">KES {{ inv.total?.toLocaleString() }}</span>
                    <span class="text-[10px] text-gray-400 capitalize">· {{ inv.payment_method }}</span>
                    <span v-if="inv.mpesa_reference" class="text-[10px] text-gray-400">· {{ inv.mpesa_reference }}</span>
                  </div>
                  <div class="text-[10px] text-gray-400 mt-0.5">{{ fmtDate(inv.paid_at) }}</div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full',
                    inv.status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700']">
                    {{ inv.status }}
                  </span>
                  <button @click="printInvoice(inv.id)"
                    class="text-[10px] font-bold text-gray-400 hover:text-gray-700 flex items-center gap-0.5">
                    <PrinterIcon class="w-3 h-3" /> Print
                  </button>
                </div>
              </div>

              <!-- Total across all invoices if multiple -->
              <div v-if="selected.invoices.length > 1"
                class="flex justify-between items-center px-4 py-2.5 bg-gray-50">
                <span class="text-xs font-semibold text-gray-600">Total Paid</span>
                <span class="text-xs font-extrabold text-gray-900">
                  KES {{ paidTotal(selected.invoices).toLocaleString() }}
                </span>
              </div>
            </div>

            <!-- No invoices yet -->
            <div v-else class="flex items-center justify-between px-4 py-3">
              <span class="text-xs text-gray-400 italic">No payments recorded yet</span>
              <button @click="goToCheckout(selected)"
                class="text-xs font-bold text-green-600 border border-green-200 rounded-lg px-3 py-1.5 hover:bg-green-50 flex items-center gap-1">
                <ShoppingCartIcon class="w-3.5 h-3.5" /> Checkout
              </button>
            </div>
          </div>
        </div>

        <!-- ── Checklists section ── -->
        <div class="border border-gray-100 rounded-xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center gap-2">
              <ClipboardDocumentCheckIcon class="w-4 h-4 text-gray-400" />
              <span class="text-xs font-bold text-gray-700">Checklists</span>
            </div>
          </div>

          <!-- Check-in -->
          <div class="px-4 py-3 border-b border-gray-100">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-xs font-semibold text-gray-700">Check-in</div>
                <div v-if="selected.checklist" class="text-[10px] text-gray-500 mt-0.5">
                  {{ selected.checklist.sn }}
                  <span v-if="selected.checklist.checked_in_at"> · {{ fmtDate(selected.checklist.checked_in_at) }}</span>
                </div>
                <div v-else class="text-[10px] text-gray-400 italic">Not created</div>
              </div>
              <div class="flex items-center gap-2">
                <span v-if="selected.checklist" :class="['text-[10px] font-bold px-2 py-0.5 rounded-full',
                  selected.checklist.status === 'check_out' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700']">
                  {{ selected.checklist.status === 'check_out' ? 'Complete' : 'In Progress' }}
                </span>
                <button v-if="selected.checklist"
                  @click="openChecklistModal(selected.checklist.id, 'check_in')"
                  class="text-[10px] font-bold text-blue-600 hover:underline">View / Edit →</button>
                <button v-if="selected.checklist"
                  @click="printChecklist(selected.checklist.id)"
                  class="text-[10px] font-bold text-gray-500 hover:text-gray-800 flex items-center gap-0.5">
                  <PrinterIcon class="w-3 h-3" /> Print
                </button>
                <button v-else @click="openChecklistModal(null, 'check_in')"
                  class="text-[10px] font-bold text-red-600 border border-red-200 rounded-lg px-2 py-1 hover:bg-red-50">
                  + Create
                </button>
              </div>
            </div>
          </div>

          <!-- Check-out -->
          <div class="px-4 py-3">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-xs font-semibold text-gray-700">Check-out</div>
                <div v-if="selected.checklist?.status === 'check_out'" class="text-[10px] text-gray-500 mt-0.5">
                  {{ selected.checklist.sn }}
                  <span v-if="selected.checklist.checked_out_at"> · {{ fmtDate(selected.checklist.checked_out_at) }}</span>
                </div>
                <div v-else class="text-[10px] text-gray-400 italic">
                  {{ selected.checklist ? 'Not checked out yet' : 'Requires check-in first' }}
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button v-if="selected.checklist && selected.checklist.status !== 'check_out'"
                  @click="openChecklistModal(selected.checklist.id, 'check_out')"
                  class="text-[10px] font-bold text-green-600 border border-green-200 rounded-lg px-2 py-1 hover:bg-green-50">
                  Complete Check-out →
                </button>
                <button v-else-if="selected.checklist?.status === 'check_out'"
                  @click="openChecklistModal(selected.checklist.id, 'check_out')"
                  class="text-[10px] font-bold text-blue-600 hover:underline">View →</button>
                <button v-if="selected.checklist?.status === 'check_out'"
                  @click="printChecklist(selected.checklist.id)"
                  class="text-[10px] font-bold text-gray-500 hover:text-gray-800 flex items-center gap-0.5">
                  <PrinterIcon class="w-3 h-3" /> Print
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Status update -->
        <div class="flex flex-col gap-2">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Update Status</span>
          <div class="flex flex-wrap gap-1.5">
            <button v-for="s in statuses" :key="s.slug"
              @click="quickUpdateStatus(selected, s.slug)"
              :disabled="selected.status.slug === s.slug || updatingStatus"
              class="px-3 py-1.5 text-[10px] font-bold rounded-xl border-2 transition-all disabled:opacity-40"
              :style="selected.status.slug === s.slug
                ? `background:${statusColor(s.slug)}; border-color:${statusColor(s.slug)}; color:white`
                : `border-color:#e5e7eb; color:#374151`">
              {{ s.name }}
            </button>
          </div>
        </div>

      </div>

      <template #footer>
        <div class="flex items-center justify-between w-full">
          <button @click="openEdit(selected); showView = false"
            class="text-xs font-semibold text-gray-600 border border-gray-200 rounded-xl px-3 py-1.5 hover:bg-gray-50">
            Edit Details
          </button>
          <!-- Always show checkout — allows adding another invoice -->
          <button @click="goToCheckout(selected)"
            class="flex items-center gap-1.5 text-xs font-bold bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl transition-colors">
            <ShoppingCartIcon class="w-3.5 h-3.5" />
            {{ selected?.is_paid ? 'New Invoice' : 'Checkout' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: CHECKLIST (check-in OR check-out)
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showChecklistModal" :title="checklistModalTitle" size="2xl">
      <div v-if="checklistLoading" class="py-12 text-center text-gray-400">
        <div class="w-6 h-6 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading checklist…
      </div>
      <div v-else class="space-y-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50 rounded-xl p-4">
          <div class="flex flex-col gap-1.5 col-span-2 sm:col-span-1">
            <label class="text-[10px] font-semibold text-gray-500 uppercase">Fuel Level</label>
            <div class="flex gap-1.5 flex-wrap">
              <button v-for="level in ['F','3/4','1/2','1/4','E']" :key="level" type="button"
                @click="checklistForm.fuel_level = level"
                :class="['px-2 py-1 text-[10px] font-bold rounded-lg border-2 transition-all',
                  checklistForm.fuel_level === level
                    ? 'bg-red-600 border-red-600 text-white'
                    : 'border-gray-200 text-gray-600 hover:border-red-400']">
                {{ level }}
              </button>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-semibold text-gray-500 uppercase">Odometer (km)</label>
            <input v-model.number="checklistForm.odometer" type="number" class="input-base" placeholder="e.g. 45000">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-semibold text-gray-500 uppercase">Vehicle Colour</label>
            <input v-model="checklistForm.colour" class="input-base" placeholder="e.g. White">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-semibold text-gray-500 uppercase">Payment Option</label>
            <select v-model="checklistForm.payment_option" class="input-base">
              <option value="">Select…</option>
              <option value="mpesa">M-Pesa</option>
              <option value="cash">Cash</option>
              <option value="insurance">Insurance</option>
              <option value="cheque">Cheque</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <ChecklistSection title="Exterior"           :items="exteriorLabels" v-model="checklistForm.exterior" />
          <ChecklistSection title="Interior"           :items="interiorLabels" v-model="checklistForm.interior" />
          <div class="space-y-3">
            <ChecklistSection title="Engine Compartment" :items="engineLabels"   v-model="checklistForm.engine_compartment" />
            <ChecklistSection title="Extras"             :items="extrasLabels"   v-model="checklistForm.extras" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Exterior Remarks</label>
            <textarea v-model="checklistForm.exterior_remarks" rows="2" class="input-base resize-none" placeholder="Any exterior damage or notes…" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Interior Remarks</label>
            <textarea v-model="checklistForm.interior_remarks" rows="2" class="input-base resize-none" placeholder="Any interior damage or notes…" />
          </div>
        </div>

        <!-- Check-out release fields -->
        <div v-if="checklistMode === 'check_out'" class="border-t border-gray-100 pt-4 space-y-3">
          <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Release Information</h4>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Released By</label>
              <input v-model="checklistForm.released_by" class="input-base" placeholder="Staff name">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Released By Tel</label>
              <input v-model="checklistForm.released_by_tel" class="input-base" placeholder="+254 700 000000">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">From → To</label>
              <div class="flex gap-2">
                <input v-model="checklistForm.released_from" class="input-base" placeholder="From">
                <input v-model="checklistForm.released_to"   class="input-base" placeholder="To">
              </div>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Received By</label>
              <input v-model="checklistForm.received_by" class="input-base" placeholder="Customer name">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Received By Tel</label>
              <input v-model="checklistForm.received_by_tel" class="input-base" placeholder="+254 700 000000">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">National ID</label>
              <input v-model="checklistForm.received_by_id" class="input-base" placeholder="ID number">
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-between w-full">
          <button @click="printChecklist(activeChecklistId)"
            class="flex items-center gap-1.5 text-xs font-semibold border border-gray-200 rounded-xl px-3 py-2 hover:bg-gray-50">
            <PrinterIcon class="w-3.5 h-3.5" /> Print
          </button>
          <div class="flex gap-2">
            <button @click="showChecklistModal = false"
              class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
            <button @click="saveChecklist" :disabled="savingChecklist"
              class="px-5 py-2 text-xs font-bold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60 flex items-center gap-2">
              <span v-if="savingChecklist" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              {{ savingChecklist ? 'Saving…' : checklistMode === 'check_out' ? 'Complete Check-out' : 'Save Check-in' }}
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL 2: NEW / EDIT BOOKING FORM
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showForm" :title="editing ? 'Edit Booking' : 'New Booking'" size="2xl">
      <form  class="space-y-5">

        <section>
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Customer Information</h4>
            <label class="flex items-center gap-2 text-xs font-semibold text-gray-600">
              <input v-model="form.is_anonymous" type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
              Save anonymously
            </label>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Phone Number <span v-if="!form.is_anonymous" class="text-red-500">*</span></label>
              <input v-model="form.customer_phone" :required="!form.is_anonymous" :disabled="form.is_anonymous" class="input-base disabled:bg-gray-50 disabled:text-gray-400" placeholder="+254 700 000000" @blur="lookupCustomer">
              <p v-if="customerFound" class="text-[10px] text-green-600 flex items-center gap-1">
                <CheckCircleIcon class="w-3 h-3" /> Existing customer found & filled
              </p>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Client Name <span v-if="!form.is_anonymous" class="text-red-500">*</span></label>
              <input v-model="form.customer_name" :required="!form.is_anonymous" class="input-base" :placeholder="form.is_anonymous ? 'Anonymous Client' : 'John Doe'">
            </div>
            <div class="flex flex-col gap-1.5 sm:col-span-2">
              <label class="text-xs font-semibold text-gray-600">Email <span class="text-gray-400 font-normal">(Optional)</span></label>
              <input v-model="form.customer_email" type="email" :disabled="form.is_anonymous" class="input-base disabled:bg-gray-50 disabled:text-gray-400" placeholder="john@example.com">
            </div>
          </div>
          <p v-if="form.is_anonymous" class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
            Personal details will not be stored. The booking will still count toward daily service activity.
          </p>
        </section>

        <div class="h-px bg-gray-100" />

        <section>
          <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Vehicle</h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Registration <span class="text-red-500">*</span></label>
              <input v-model="form.vehicle_reg" required class="input-base font-mono uppercase" placeholder="KCA 123A" @blur="lookupVehicle">
              <p v-if="vehicleFound" class="text-[10px] text-green-600 flex items-center gap-1">
                <CheckCircleIcon class="w-3 h-3" /> {{ vehicleFound }}
              </p>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Make</label>
              <input v-model="form.vehicle_make" class="input-base" placeholder="Toyota">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Model</label>
              <input v-model="form.vehicle_model" class="input-base" placeholder="Hilux">
            </div>
          </div>
        </section>

        <div class="h-px bg-gray-100" />

        <section>
          <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Service & Schedule</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Service <span class="text-red-500">*</span></label>
              <select v-model="form.service_id" required class="input-base">
                <option value="">Select service…</option>
                <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Source</label>
              <select v-model="form.source" class="input-base">
                <option v-for="src in sources" :key="src.slug" :value="src.slug">{{ src.name }}</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Date <span class="text-red-500">*</span></label>
              <input v-model="form.date" type="date" required :min="today" class="input-base">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Time <span class="text-red-500">*</span></label>
              <select v-model="form.time" required class="input-base">
                <option value="">Select time…</option>
                <option v-for="slot in timeSlots" :key="slot" :value="slot">{{ slot }}</option>
              </select>
            </div>
          </div>
        </section>

        <section v-if="editing">
          <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Status</h4>
          <div class="flex flex-wrap gap-2">
            <button v-for="s in statuses" :key="s.slug" type="button" @click="form.status = s.slug"
              class="px-3 py-1.5 text-xs font-semibold rounded-xl border-2 transition-all"
              :style="form.status === s.slug
                ? `background:${statusColor(s.slug)}; border-color:${statusColor(s.slug)}; color:white`
                : `border-color:#e5e7eb; color:#374151`">
              {{ s.name }}
            </button>
          </div>
        </section>

        <section v-if="!editing && selectedService?.requires_deposit" class="space-y-4">
          <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <div class="text-xs font-bold text-amber-800">Down Payment Required</div>
            <p class="text-xs text-amber-700 mt-1">
              {{ selectedService.name }} requires a {{ selectedService.deposit_percent }}% down payment.
              Collect KES {{ requiredDepositAmount.toLocaleString() }} before saving this booking.
            </p>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <button v-for="method in ['cash','mpesa','card']" :key="method" type="button" @click="form.deposit_payment.payment_method = method"
              :class="['px-3 py-2 rounded-xl text-xs font-bold border transition-colors capitalize',
                form.deposit_payment.payment_method === method ? 'bg-red-600 border-red-600 text-white' : 'border-gray-200 text-gray-600 hover:border-red-300']">
              {{ method }}
            </button>
          </div>
          <div v-if="form.deposit_payment.payment_method === 'cash'" class="text-xs text-gray-500 bg-gray-50 rounded-xl px-3 py-2">
            Cash down payment will be marked as collected immediately when the booking is saved.
          </div>
          <div v-if="form.deposit_payment.payment_method === 'mpesa'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Customer Phone</label>
              <input v-model="bookingDepositPhone" class="input-base font-mono" placeholder="+254 700 000000">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">M-Pesa Reference</label>
              <input v-model="form.deposit_payment.mpesa_reference" class="input-base font-mono uppercase" placeholder="e.g. QHK9XXXXX">
            </div>
            <div class="sm:col-span-2">
              <button type="button" @click="sendBookingDepositPrompt" :disabled="!bookingDepositPhone || bookingDepositSending"
                class="px-4 py-2 text-xs font-bold bg-green-600 text-white rounded-xl hover:bg-green-700 disabled:opacity-60">
                {{ bookingDepositSending ? 'Sending…' : 'Send Payment Prompt' }}
              </button>
            </div>
          </div>
          <div v-if="form.deposit_payment.payment_method === 'card'" class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Card Reference</label>
            <input v-model="form.deposit_payment.card_reference" class="input-base font-mono uppercase" placeholder="Approval code">
          </div>
          <div v-if="['cash','mpesa','card'].includes(form.deposit_payment.payment_method)" class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Notes</label>
            <input v-model="form.deposit_payment.notes" class="input-base" placeholder="Optional payment note">
          </div>
        </section>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
          <textarea v-model="form.notes" rows="2" class="input-base resize-none"
            placeholder="Special requests, vehicle details, or anything the team should know…" />
        </div>

        <div class="h-px bg-gray-100" />

        <!-- Checklist toggle (new bookings only) -->
        <section v-if="!editing">
          <div class="flex items-center justify-between mb-3">
            <div>
              <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Vehicle Check-in</h4>
              <p class="text-[10px] text-gray-400 mt-0.5">Record vehicle condition at time of booking</p>
            </div>
            <button type="button" @click="form.include_checklist = !form.include_checklist"
              :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                form.include_checklist ? 'bg-red-600' : 'bg-gray-200']">
              <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow',
                form.include_checklist ? 'translate-x-4' : 'translate-x-1']" />
            </button>
          </div>
          <div v-if="form.include_checklist" class="space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50 rounded-xl p-4">
              <div class="flex flex-col gap-1.5 col-span-2 sm:col-span-1">
                <label class="text-[10px] font-semibold text-gray-500 uppercase">Fuel Level</label>
                <div class="flex gap-1.5 flex-wrap">
                  <button v-for="level in ['F','3/4','1/2','1/4','E']" :key="level" type="button"
                    @click="form.checklist.fuel_level = level"
                    :class="['px-2 py-1 text-[10px] font-bold rounded-lg border-2 transition-all',
                      form.checklist.fuel_level === level ? 'bg-red-600 border-red-600 text-white' : 'border-gray-200 text-gray-600 hover:border-red-400']">
                    {{ level }}
                  </button>
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-semibold text-gray-500 uppercase">Odometer (km)</label>
                <input v-model.number="form.checklist.odometer" type="number" class="input-base" placeholder="e.g. 45000">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-semibold text-gray-500 uppercase">Vehicle Colour</label>
                <input v-model="form.checklist.colour" class="input-base" placeholder="e.g. White">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-semibold text-gray-500 uppercase">Payment Option</label>
                <select v-model="form.checklist.payment_option" class="input-base">
                  <option value="">Select…</option>
                  <option value="mpesa">M-Pesa</option>
                  <option value="cash">Cash</option>
                  <option value="insurance">Insurance</option>
                  <option value="cheque">Cheque</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <ChecklistSection title="Exterior"           :items="exteriorLabels" v-model="form.checklist.exterior" />
              <ChecklistSection title="Interior"           :items="interiorLabels" v-model="form.checklist.interior" />
              <div class="space-y-3">
                <ChecklistSection title="Engine Compartment" :items="engineLabels"   v-model="form.checklist.engine_compartment" />
                <ChecklistSection title="Extras"             :items="extrasLabels"   v-model="form.checklist.extras" />
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-600">Exterior Remarks</label>
                <textarea v-model="form.checklist.exterior_remarks" rows="2" class="input-base resize-none" placeholder="Any exterior damage…" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-600">Interior Remarks</label>
                <textarea v-model="form.checklist.interior_remarks" rows="2" class="input-base resize-none" placeholder="Any interior damage…" />
              </div>
            </div>
          </div>
          <div v-else class="bg-gray-50 rounded-xl px-4 py-3 flex items-center gap-2 text-gray-400">
            <ClipboardDocumentCheckIcon class="w-4 h-4" />
            <span class="text-xs">Toggle on to fill vehicle check-in details</span>
          </div>
        </section>

        <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3">
          {{ formError }}
        </div>
      </form>

      <template #footer>
        <div class="flex items-center justify-between w-full">
          <button v-if="editing" @click="handleDelete(selected)"
            class="flex items-center gap-1 text-xs font-semibold text-red-500 hover:text-red-700">
            <TrashIcon class="w-3.5 h-3.5" /> Delete Booking
          </button>
          <div v-else />
          <div class="flex gap-2">
            <button @click="showForm = false"
              class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
            <button @click="saveBooking" :disabled="saving"
              class="px-5 py-2 text-xs font-bold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60 flex items-center gap-2">
              <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              {{ saving ? 'Saving…' : editing ? 'Update Booking' : 'Create Booking' }}
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <Modal v-model="showDepositModal" title="Collect Down Payment" size="sm">
      <div v-if="depositBooking" class="space-y-4">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
          <div class="text-2xl font-extrabold text-gray-900">KES {{ depositAmount.toLocaleString() }}</div>
          <div class="text-xs text-gray-500 mt-1">Outstanding deposit to collect</div>
        </div>

        <div class="grid grid-cols-3 gap-2">
          <button v-for="method in ['cash','mpesa','card']" :key="method" @click="depositForm.payment_method = method"
            :class="['px-3 py-2 rounded-xl text-xs font-bold border transition-colors capitalize',
              depositForm.payment_method === method ? 'bg-red-600 border-red-600 text-white' : 'border-gray-200 text-gray-600 hover:border-red-300']">
            {{ method }}
          </button>
        </div>

        <div v-if="depositForm.payment_method === 'cash'" class="text-xs text-gray-500 bg-gray-50 rounded-xl px-3 py-2">
          Cash payment will be recorded immediately.
        </div>

        <div v-if="depositForm.payment_method === 'mpesa'" class="space-y-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Customer Phone</label>
            <input v-model="depositPhone" class="input-base font-mono" placeholder="+254 700 000000">
          </div>
          <button @click="sendDepositStkPush" :disabled="!depositPhone || depositSending"
            class="w-full py-2.5 text-xs font-bold bg-green-600 text-white rounded-xl hover:bg-green-700 disabled:opacity-60">
            {{ depositSending ? 'Sending…' : 'Send Payment Prompt' }}
          </button>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">M-Pesa Reference</label>
            <input v-model="depositForm.mpesa_reference" class="input-base font-mono uppercase" placeholder="e.g. QHK9XXXXX">
          </div>
        </div>

        <div v-if="depositForm.payment_method === 'card'" class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Card Reference</label>
          <input v-model="depositForm.card_reference" class="input-base font-mono uppercase" placeholder="Approval code">
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes</label>
          <input v-model="depositForm.notes" class="input-base" placeholder="Optional payment note">
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2 w-full">
          <button @click="showDepositModal = false"
            class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="collectDeposit" :disabled="savingDeposit || !depositForm.payment_method || (depositForm.payment_method === 'mpesa' && !depositForm.mpesa_reference && !depositManualOverride)"
            class="px-5 py-2 text-xs font-bold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ savingDeposit ? 'Saving…' : 'Record Deposit' }}
          </button>
        </div>
      </template>
    </Modal>

  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  PlusIcon, CalendarDaysIcon, CheckCircleIcon, TrashIcon,
  XMarkIcon, ShoppingCartIcon, ClipboardDocumentCheckIcon,
  PrinterIcon, ReceiptPercentIcon,
} from '@heroicons/vue/24/outline'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import PageHeader        from '@/components/PageHeader.vue'
import SearchInput       from '@/components/SearchInput.vue'
import Modal             from '@/components/Modal.vue'
import ChecklistSection  from '@/components/ChecklistSection.vue'

const { get, post, patch, put, del, loading } = useApi()
const toast  = useToastStore()
const router = useRouter()

// ── Helpers ────────────────────────────────────────────────────────────────────
const statusColors = { pending:'#EAB308',confirmed:'#3B82F6',in_progress:'#F97316',completed:'#22C55E',cancelled:'#EF4444',no_show:'#6B7280' }
const statusColor  = slug => statusColors[slug] ?? '#6B7280'
const fmtDate      = d => d ? new Date(d).toLocaleDateString('en-KE',{day:'2-digit',month:'short',year:'numeric'}) : '—'
const paidTotal    = invoices => invoices.filter(i => i.status === 'paid').reduce((s, i) => s + (i.total ?? 0), 0)

// ── Checklist labels & defaults ────────────────────────────────────────────────
const defaultItems = keys => Object.fromEntries(keys.map(k => [k, { status:'ok', note:'' }]))
const exteriorLabels = { front_windscreen:'Front Wind Screen',rear_windscreen:'Rear Wind Screen',insurance_sticker:'Insurance Sticker',front_number_plate:'Front Number Plate',headlights:'Headlights',tail_lights:'Tail Lights',front_bumper:'Front Bumper',rear_bumper:'Rear Bumper',grille:'Grille',grille_badge:'Grille Badge',front_wiper:'Front Wiper',rear_wiper:'Rear Wiper',side_mirror:'Side Mirror',door_glasses:'Door Glasses',fuel_tank_cap:'Fuel Tank Cap',front_tyres:'Front Tyres',rear_tyres:'Rear Tyres',front_rims:'Front Rims',rear_rims:'Rear Rims',hub_wheel_caps:'Hub/Wheel Caps',roof_rails:'Roof Rails',body_moulding:'Body Moulding',emblems:'Emblems',weather_stripes:'Weather Stripes',mud_guard:'Mud Guard' }
const interiorLabels = { rear_view_mirror:'Rear View Mirror',radio:'Radio',radio_face:'Radio Face',equalizer:'Equalizer',amplifier:'Amplifier',tuner:'Tuner',speaker:'Speaker',cigar_lighter:'Cigar Lighter',door_switches:'Door Switches',rubber_mats:'Rubber Mats',carpets:'Carpets',seat_covers:'Seat Covers',boot_mat:'Boot Mat',boot_board:'Boot Board',aircon_knobs:'Air Con Knobs',keys_remotes:'No. of Keys/Remotes',seat_belts:'Seat Belts' }
const engineLabels   = { battery:'Battery',computer_control_box:'Computer/Control Box',ignition_coils:'Ignition Coils',wiper_panel_finisher_covers:'Wiper Panel Covers',horn:'Horn',engine_caps:'Engine Caps',dip_sticks:'Dip Sticks',starter:'Starter',alternator:'Alternator',fog_lights:'Fog Lights',reverse_camera:'Reverse Camera',relays:'Relays',radiator:'Radiator' }
const extrasLabels   = { jack_handle:'Jack & Handle',wheel_spanner:'Wheel Spanner',towing_pin:'Towing Pin',towing_cable_rope:'Towing Cable/Rope',first_aid_kit:'First Aid Kit',fire_extinguisher:'Fire Extinguisher',spare_wheel:'Spare Wheel',life_savers:'Life Savers' }

const defaultChecklist = () => ({
  fuel_level:'', odometer:null, colour:'', payment_option:'',
  exterior:           defaultItems(Object.keys(exteriorLabels)),
  interior:           defaultItems(Object.keys(interiorLabels)),
  engine_compartment: defaultItems(Object.keys(engineLabels)),
  extras:             defaultItems(Object.keys(extrasLabels)),
  exterior_remarks:'', interior_remarks:'',
  released_by:'', released_by_tel:'', released_from:'', released_to:'',
  received_by:'', received_by_tel:'', received_by_id:'',
})

// ── State ──────────────────────────────────────────────────────────────────────
const bookings      = ref({ data:[], last_page:1, total:0, from:1, to:0 })
const statuses      = ref([])
const services      = ref([])
const sources       = ref([])
const search        = ref(''); const statusFilter = ref(''); const serviceFilter = ref(''); const dateFilter = ref('')
const page          = ref(1)
const showView      = ref(false); const showForm = ref(false)
const editing       = ref(false); const selected = ref(null)
const saving        = ref(false); const updatingStatus = ref(false); const formError = ref(null)
const customerFound = ref(false); const vehicleFound = ref('')
const showDepositModal = ref(false)
const depositBooking = ref(null)
const savingDeposit = ref(false)
const depositSending = ref(false)
const depositManualOverride = ref(false)
const bookingDepositSending = ref(false)
const depositForm = ref({ payment_method:'cash', mpesa_reference:'', card_reference:'', gateway_reference:'', notes:'' })

// Checklist modal
const showChecklistModal  = ref(false)
const checklistLoading    = ref(false)
const savingChecklist     = ref(false)
const activeChecklistId   = ref(null)
const checklistMode       = ref('check_in')
const checklistForm       = ref(defaultChecklist())
const checklistModalTitle = computed(() =>
  checklistMode.value === 'check_out' ? 'Vehicle Check-out' : 'Vehicle Check-in'
)

const today       = new Date().toISOString().split('T')[0]
const defaultForm = () => ({
  is_anonymous:false,
  customer_name:'', customer_phone:'', customer_email:'',
  vehicle_reg:'', vehicle_make:'', vehicle_model:'',
  service_id:'', source:'walk_in', date:'', time:'', notes:'', status:'pending',
  include_checklist: true,
  deposit_payment: { payment_method:'', mpesa_reference:'', card_reference:'', gateway_reference:'', notes:'' },
  checklist: defaultChecklist(),
})
const form = ref(defaultForm())
const selectedService = computed(() => services.value.find(s => String(s.id) === String(form.value.service_id)))
const requiredDepositAmount = computed(() => {
  if (!selectedService.value?.requires_deposit) return 0
  return Math.round(((selectedService.value.price_from ?? 0) * (selectedService.value.deposit_percent ?? 0)) / 100)
})
const depositReference = computed({
  get: () => form.value.deposit_payment.mpesa_reference || form.value.deposit_payment.card_reference || form.value.deposit_payment.gateway_reference || '',
  set: value => {
    const method = form.value.deposit_payment.payment_method
    form.value.deposit_payment.mpesa_reference = method === 'mpesa' ? value : ''
    form.value.deposit_payment.card_reference = method === 'card' ? value : ''
    form.value.deposit_payment.gateway_reference = !['mpesa', 'card'].includes(method) ? value : ''
  },
})

const timeSlots = ['08:00 AM','09:00 AM','10:00 AM','11:00 AM','12:00 PM','01:00 PM','02:00 PM','03:00 PM','04:00 PM','05:00 PM']
const timeMap   = { '08:00 AM':'08:00','09:00 AM':'09:00','10:00 AM':'10:00','11:00 AM':'11:00','12:00 PM':'12:00','01:00 PM':'13:00','02:00 PM':'14:00','03:00 PM':'15:00','04:00 PM':'16:00','05:00 PM':'17:00' }

const headers     = ['Customer','Vehicle','Service','Date & Time','Status','Checklist / Invoice','Actions']
const pageNumbers = computed(() => Array.from({length:Math.min(bookings.value.last_page,5)},(_,i)=>i+1))
const depositAmount = computed(() => depositBooking.value?.deposit?.outstanding_amount ?? 0)
const depositPhone = computed({
  get: () => depositBooking.value?.customer?.phone ?? '',
  set: value => {
    if (depositBooking.value?.customer) depositBooking.value.customer.phone = value
  },
})
const bookingDepositPhone = computed({
  get: () => form.value.customer_phone ?? '',
  set: value => {
    form.value.customer_phone = value
  },
})

const viewRows = computed(() => {
  if (!selected.value) return []
  const b = selected.value
  return [
    { label:'Customer',  value:`${b.customer.name} · ${b.customer.phone}` },
    { label:'Vehicle',   value:`${b.vehicle.registration}${b.vehicle.make&&b.vehicle.make!=='Unknown'?' · '+b.vehicle.make+' '+b.vehicle.model:''}` },
    { label:'Service',   value: b.service.name },
    { label:'Scheduled', value:`${b.scheduled_date} at ${b.scheduled_time}` },
    { label:'Source',    value: b.source },
    { label:'Notes',     value: b.notes||'—' },
  ]
})
const paymentStatusLabel = status => ({ unpaid:'Unpaid', partial:'Partial', paid:'Paid' }[status] ?? 'Unpaid')
const paymentStatusBadge = status => ['inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full',
  status === 'paid' ? 'bg-green-100 text-green-700' : status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600']
const depositProgress = booking => {
  const required = booking?.deposit?.required_amount ?? 0
  const paid = booking?.deposit?.paid_amount ?? 0
  if (!required) return 0
  return Math.min(100, Math.round((paid / required) * 100))
}

// ── API ────────────────────────────────────────────────────────────────────────
async function load() {
  const data = await get('/admin/bookings', {
    page:page.value, search:search.value,
    status:statusFilter.value, service_id:serviceFilter.value, date:dateFilter.value,
  })
  bookings.value = data
}

async function loadMeta() {
  const [s,svc,src] = await Promise.all([
    get('/admin/bookings/statuses'),
    get('/admin/services',{per_page:100}),
    get('/admin/booking-sources'),
  ])
  statuses.value = s??[]; services.value = svc?.data??svc??[]; sources.value = src??[]
}

function clearFilters() {
  search.value=''; statusFilter.value=''; serviceFilter.value=''; dateFilter.value=''
  page.value=1; load()
}

async function lookupCustomer() {
  if (form.value.is_anonymous) return
  if (form.value.customer_phone.length<9) return
  try {
    const data = await get('/admin/customers',{search:form.value.customer_phone})
    const c = data?.data?.[0]
    if (c&&c.phone===form.value.customer_phone) {
      customerFound.value=true; form.value.customer_name=c.name; form.value.customer_email=c.email??''
    } else { customerFound.value=false }
  } catch {}
}

async function lookupVehicle() {
  if (form.value.vehicle_reg.length<4) return
  try {
    const data = await get('/admin/vehicles',{search:form.value.vehicle_reg.toUpperCase()})
    const v = data?.data?.[0]
    if (v&&v.registration===form.value.vehicle_reg.toUpperCase()) {
      vehicleFound.value=`${v.make} ${v.model} — ${v.customer?.name}`
      form.value.vehicle_make  = v.make !=='Unknown'?v.make :form.value.vehicle_make
      form.value.vehicle_model = v.model!=='Unknown'?v.model:form.value.vehicle_model
      if (v.color) form.value.checklist.colour = v.color
    } else { vehicleFound.value='' }
  } catch {}
}

// ── Checklist modal ────────────────────────────────────────────────────────────
async function openChecklistModal(checklistId, mode) {
  checklistMode.value     = mode
  activeChecklistId.value = checklistId
  checklistForm.value     = defaultChecklist()
  showChecklistModal.value = true

  if (checklistId) {
    checklistLoading.value = true
    try {
      const data = await get(`/admin/checklists/${checklistId}`)
      checklistForm.value = {
        fuel_level:          data.fuel_level         ?? '',
        odometer:            data.odometer           ?? null,
        colour:              data.colour             ?? '',
        payment_option:      data.payment_option     ?? '',
        exterior:            data.exterior           ?? defaultItems(Object.keys(exteriorLabels)),
        interior:            data.interior           ?? defaultItems(Object.keys(interiorLabels)),
        engine_compartment:  data.engine_compartment ?? defaultItems(Object.keys(engineLabels)),
        extras:              data.extras             ?? defaultItems(Object.keys(extrasLabels)),
        exterior_remarks:    data.exterior_remarks   ?? '',
        interior_remarks:    data.interior_remarks   ?? '',
        released_by:         data.released_by        ?? '',
        released_by_tel:     data.released_by_tel    ?? '',
        released_from:       data.released_from      ?? '',
        released_to:         data.released_to        ?? '',
        received_by:         data.received_by        ?? '',
        received_by_tel:     data.received_by_tel    ?? '',
        received_by_id:      data.received_by_id     ?? '',
      }
    } catch { toast.error('Failed to load checklist.') }
    finally { checklistLoading.value = false }
  }
}

async function saveChecklist() {
  savingChecklist.value = true
  try {
    if (activeChecklistId.value) {
      if (checklistMode.value === 'check_out') {
        await post(`/admin/checklists/${activeChecklistId.value}/checkout`, checklistForm.value)
      } else {
        await put(`/admin/checklists/${activeChecklistId.value}`, checklistForm.value)
      }
      toast.success(checklistMode.value === 'check_out' ? 'Check-out completed.' : 'Checklist saved.')
    } else {
      const b  = selected.value
      const cl = await post('/admin/checklists', {
        vehicle_id:  b.vehicle.id,
        customer_id: b.customer.id,
        ...checklistForm.value,
      })
      await patch(`/admin/bookings/${b.id}`, { checklist_id: cl.id })
      toast.success('Checklist created and linked.')
    }
    showChecklistModal.value = false
    showView.value = false
    load()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to save checklist.')
  } finally { savingChecklist.value = false }
}

function printChecklist(id) { if (id) window.open(`/print/checklist/${id}`, '_blank') }
function printInvoice(id)   { if (id) window.open(`/print/invoice/${id}`,   '_blank') }

// ── Booking actions ────────────────────────────────────────────────────────────
function openNew() {
  editing.value=false; selected.value=null; form.value=defaultForm(); formError.value=null
  customerFound.value=false; vehicleFound.value=''; showForm.value=true
}

function openEdit(b) {
  editing.value=true; selected.value=b
  const dt = b.scheduled_at ? new Date(b.scheduled_at) : null
  form.value = {
    is_anonymous:   !!b.customer.is_anonymous,
    customer_name:  b.customer.is_anonymous ? (b.customer.name === 'Anonymous' ? '' : b.customer.name) : b.customer.name,
    customer_phone: b.customer.is_anonymous ? '' : b.customer.phone,
    customer_email: b.customer.email ?? '',
    vehicle_reg:    b.vehicle.registration,
    vehicle_make:   b.vehicle.make  !== 'Unknown' ? b.vehicle.make  : '',
    vehicle_model:  b.vehicle.model !== 'Unknown' ? b.vehicle.model : '',
    service_id:     b.service.id ?? '', source: 'walk_in',
    date:           dt ? dt.toISOString().split('T')[0] : '',
    time:           b.scheduled_time ?? '',
    notes:          b.notes ?? '', status: b.status.slug,
    deposit_payment: { payment_method:'', mpesa_reference:'', card_reference:'', gateway_reference:'', notes:'' },
    include_checklist: false, checklist: defaultChecklist(),
  }
  formError.value=null; showView.value=false; showForm.value=true
}

function openView(b) { selected.value=b; showView.value=true }

function openDepositModal(booking) {
  depositBooking.value = booking
  depositForm.value = { payment_method:'cash', mpesa_reference:'', card_reference:'', gateway_reference:'', notes:'' }
  depositManualOverride.value = false
  showDepositModal.value = true
}

async function saveBooking() {
  saving.value=true; formError.value=null
  const time24      = timeMap[form.value.time] ?? '09:00'
  const scheduledAt = `${form.value.date}T${time24}:00`
  const payload = {
    is_anonymous:   form.value.is_anonymous,
    customer_name:  form.value.customer_name,
    customer_phone: form.value.customer_phone,
    customer_email: form.value.customer_email || undefined,
    vehicle_reg:    form.value.vehicle_reg.toUpperCase(),
    vehicle_make:   form.value.vehicle_make  || undefined,
    vehicle_model:  form.value.vehicle_model || undefined,
    service_id:     form.value.service_id    || undefined,
    source:         form.value.source,
    scheduled_at:   scheduledAt,
    notes:          form.value.notes         || undefined,
    ...(editing.value ? { status: form.value.status } : {}),
    ...(!editing.value && selectedService.value?.requires_deposit ? { deposit_payment: {
      payment_method: form.value.deposit_payment.payment_method || undefined,
      mpesa_reference: form.value.deposit_payment.mpesa_reference || undefined,
      card_reference: form.value.deposit_payment.card_reference || undefined,
      gateway_reference: form.value.deposit_payment.gateway_reference || undefined,
      notes: form.value.deposit_payment.notes || undefined,
    }} : {}),
    ...(!editing.value && form.value.include_checklist ? { checklist: {
      fuel_level:          form.value.checklist.fuel_level    || null,
      odometer:            form.value.checklist.odometer      || null,
      colour:              form.value.checklist.colour        || null,
      payment_option:      form.value.checklist.payment_option || null,
      exterior:            form.value.checklist.exterior,
      interior:            form.value.checklist.interior,
      engine_compartment:  form.value.checklist.engine_compartment,
      extras:              form.value.checklist.extras,
      exterior_remarks:    form.value.checklist.exterior_remarks || null,
      interior_remarks:    form.value.checklist.interior_remarks || null,
    }} : {}),
  }
  try {
    editing.value
      ? await patch(`/admin/bookings/${selected.value.id}`, payload)
      : await post('/admin/bookings', payload)
    toast.success(editing.value ? 'Booking updated.' : 'Booking created.')
    showForm.value=false; load()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save booking.'
  } finally { saving.value=false }
}

async function sendDepositStkPush() {
  if (!depositBooking.value) return
  depositSending.value = true
  try {
    const res = await post('/admin/mpesa/stk-push', {
      phone: depositPhone.value,
      amount: depositAmount.value,
      booking_id: depositBooking.value.id,
      reference: `${depositBooking.value.reference}-DEPOSIT`,
      customer_name: depositBooking.value.customer?.name,
    })
    depositForm.value.gateway_reference = res.checkout_request
    depositManualOverride.value = true
    toast.success('Deposit payment prompt sent.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to send payment prompt.')
  } finally {
    depositSending.value = false
  }
}

async function sendBookingDepositPrompt() {
  bookingDepositSending.value = true
  try {
    const res = await post('/admin/mpesa/stk-push', {
      phone: bookingDepositPhone.value,
      amount: requiredDepositAmount.value,
      reference: `BOOKING-DEPOSIT-${form.value.vehicle_reg || 'NEW'}`,
      customer_name: form.value.customer_name || 'Anonymous Client',
    })
    form.value.deposit_payment.gateway_reference = res.checkout_request
    toast.success('Deposit payment prompt sent.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to send payment prompt.')
  } finally {
    bookingDepositSending.value = false
  }
}

async function collectDeposit() {
  if (!depositBooking.value) return
  savingDeposit.value = true
  try {
    const booking = await post(`/admin/bookings/${depositBooking.value.id}/collect-deposit`, {
      payment_method: depositForm.value.payment_method,
      mpesa_reference: depositForm.value.mpesa_reference || undefined,
      card_reference: depositForm.value.card_reference || undefined,
      gateway_reference: depositForm.value.gateway_reference || undefined,
      notes: depositForm.value.notes || undefined,
    })
    selected.value = booking
    showDepositModal.value = false
    await load()
    toast.success('Down payment recorded.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to record down payment.')
  } finally {
    savingDeposit.value = false
  }
}

async function handleDelete(b) {
  if (!confirm(`Delete booking ${b.reference}?`)) return
  await del(`/admin/bookings/${b.id}`)
  toast.success('Booking deleted.'); showForm.value=false; showView.value=false; load()
}

async function quickUpdateStatus(b, slug) {
  updatingStatus.value=true
  try {
    await patch(`/admin/bookings/${b.id}`, {status:slug})
    selected.value.status = { slug, name:statuses.value.find(s=>s.slug===slug)?.name??slug }
    toast.success('Status updated.'); load()
  } catch { toast.error('Failed to update status.') }
  finally { updatingStatus.value=false }
}

function goToCheckout(b) {
  showView.value=false
  router.push({ path:'/pos', query:{
    booking_id:   b.id,
    vehicle_reg:  b.vehicle.registration,
    customer_id:  b.customer.id,
    service_id:   b.service.id,
    service_name: b.service.name,
    reference:    b.reference,
    checklist_id: b.checklist?.id ?? '',
    open_checkout: '1',
  }})
}

watch([page,statusFilter,serviceFilter,dateFilter], load)
watch(search, () => { page.value=1; load() })
onMounted(() => { load(); loadMeta() })
</script>

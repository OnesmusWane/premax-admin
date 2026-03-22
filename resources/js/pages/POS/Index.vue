<template>
  <div class="flex h-[calc(100vh-4rem)] overflow-hidden">

    <!-- ══════════════════════════════════════════════════
         STEP INDICATOR (top bar)
    ══════════════════════════════════════════════════ -->
    <div v-if="activeBooking"
      class="fixed top-16 left-0 right-0 z-10 bg-white border-b border-gray-100 shadow-sm px-6 py-2 flex items-center gap-0">
      <div v-for="(step, i) in steps" :key="step.key"
        class="flex items-center gap-0 flex-1">
        <button @click="goToStep(step.key)"
          :disabled="!canGoToStep(step.key)"
          :class="['flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all',
            currentStep === step.key
              ? 'bg-red-600 text-white'
              : completedSteps.includes(step.key)
                ? 'text-green-600 hover:bg-green-50'
                : 'text-gray-400 cursor-not-allowed']">
          <span :class="['w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0',
            currentStep === step.key ? 'bg-white text-red-600'
              : completedSteps.includes(step.key) ? 'bg-green-100 text-green-600'
              : 'bg-gray-100 text-gray-400']">
            <CheckIcon v-if="completedSteps.includes(step.key) && currentStep !== step.key" class="w-3 h-3" />
            <span v-else>{{ i + 1 }}</span>
          </span>
          {{ step.label }}
        </button>
        <ChevronRightIcon v-if="i < steps.length - 1" class="w-4 h-4 text-gray-300 shrink-0" />
      </div>

      <!-- Booking ref -->
      <div class="flex items-center gap-2 ml-auto shrink-0">
        <span class="text-[10px] text-gray-400">Booking:</span>
        <span class="font-mono text-xs font-bold text-red-600">{{ activeBooking.reference }}</span>
        <button @click="resetPOS"
          class="text-[10px] text-gray-400 hover:text-red-500 border border-gray-200 rounded-lg px-2 py-1 hover:border-red-300 transition-all">
          ✕ Cancel
        </button>
      </div>
    </div>

    <!-- Main content area (pushed down when step bar is showing) -->
    <div :class="['flex flex-1 overflow-hidden', activeBooking ? 'mt-10' : '']">

      <!-- ══════════════════════════════════════════════════
           STEP 1: SELECT OR CREATE BOOKING
      ══════════════════════════════════════════════════ -->
      <div v-if="!activeBooking" class="flex-1 flex items-center justify-center bg-gray-50 p-6">
        <div class="w-full max-w-lg space-y-4">

          <div class="text-center mb-2">
            <h2 class="text-lg font-extrabold text-gray-900">Start a Service</h2>
            <p class="text-sm text-gray-500 mt-1">Link to a booking or create a walk-in</p>
          </div>

          <!-- Search existing booking -->
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-3">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wide">Find Existing Booking</h3>
            <div class="flex gap-2">
              <input v-model="bookingSearch" @keyup.enter="searchBookings"
                class="input-base flex-1" placeholder="Name, phone, reg or reference…">
              <button @click="searchBookings" :disabled="searchingBookings"
                class="px-4 py-2 bg-gray-900 text-white text-xs font-bold rounded-xl hover:bg-gray-700 disabled:opacity-60 shrink-0">
                {{ searchingBookings ? '…' : 'Search' }}
              </button>
            </div>
            <div v-if="bookingResults.length" class="space-y-2 max-h-64 overflow-y-auto">
              <button v-for="b in bookingResults" :key="b.id"
                @click="linkBooking(b)"
                class="w-full flex items-center justify-between bg-gray-50 hover:bg-red-50 border border-gray-100 hover:border-red-300 rounded-xl px-4 py-3 transition-all text-left">
                <div>
                  <div class="text-xs font-bold text-gray-900">{{ b.customer.name }}</div>
                  <div class="text-[10px] text-gray-500">{{ b.vehicle.registration }} · {{ b.service.name }}</div>
                  <div class="text-[10px] text-gray-400 mt-0.5">{{ b.scheduled_date }} {{ b.scheduled_time }}</div>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                  <span class="font-mono text-[10px] text-red-600 font-bold">{{ b.reference }}</span>
                  <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                    :style="`background:${statusColor(b.status.slug)}20; color:${statusColor(b.status.slug)}`">
                    {{ b.status.name }}
                  </span>
                </div>
              </button>
            </div>
            <p v-else-if="hasSearched && !bookingResults.length"
              class="text-xs text-gray-400 text-center py-3">
              No bookings found for "{{ bookingSearch }}"
            </p>
          </div>

          <div class="flex items-center gap-3">
            <div class="flex-1 h-px bg-gray-200" />
            <span class="text-xs text-gray-400 font-medium">or</span>
            <div class="flex-1 h-px bg-gray-200" />
          </div>

          <!-- Walk-in -->
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wide">Walk-in / New Booking</h3>
            <div class="grid grid-cols-2 gap-3">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-semibold text-gray-500 uppercase">Phone *</label>
                <input v-model="walkIn.phone" class="input-base" placeholder="+254 700 000000"
                  @blur="lookupWalkInCustomer">
                <p v-if="walkInCustomerFound" class="text-[10px] text-green-600">✓ Customer found</p>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-semibold text-gray-500 uppercase">Full Name *</label>
                <input v-model="walkIn.name" class="input-base" placeholder="John Doe">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-semibold text-gray-500 uppercase">Registration *</label>
                <input v-model="walkIn.reg" class="input-base font-mono uppercase"
                  placeholder="KCA 123A" @blur="lookupWalkInVehicle">
                <p v-if="walkInVehicleFound" class="text-[10px] text-green-600">✓ {{ walkInVehicleFound }}</p>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-semibold text-gray-500 uppercase">Make & Model</label>
                <input v-model="walkIn.make_model" class="input-base" placeholder="Toyota Hilux">
              </div>
              <div class="flex flex-col gap-1.5 col-span-2">
                <label class="text-[10px] font-semibold text-gray-500 uppercase">Service *</label>
                <select v-model="walkIn.service_id" class="input-base">
                  <option value="">Select service…</option>
                  <option v-for="s in allServices" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
            </div>
            <div v-if="walkInError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">
              {{ walkInError }}
            </div>
            <button @click="createWalkIn"
              :disabled="creatingWalkIn || !walkIn.phone || !walkIn.name || !walkIn.reg || !walkIn.service_id"
              class="w-full py-3 text-xs font-bold bg-red-600 hover:bg-red-700 text-white rounded-xl
                     disabled:opacity-50 flex items-center justify-center gap-2 transition-colors">
              <span v-if="creatingWalkIn" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              {{ creatingWalkIn ? 'Creating…' : 'Start Walk-in Service' }}
            </button>
          </div>

        </div>
      </div>

      <!-- ══════════════════════════════════════════════════
           STEP 2: CHECKLIST
      ══════════════════════════════════════════════════ -->
      <div v-else-if="currentStep === 'checklist'"
        class="flex-1 overflow-y-auto bg-gray-50 p-5 space-y-4">

        <!-- Vehicle + Fuel row -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
          <h3 class="text-sm font-bold text-gray-900">Vehicle Details</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex flex-col gap-1.5 col-span-2">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Registration</label>
              <div class="font-mono font-bold text-gray-900 text-sm">
                {{ activeBooking.vehicle.registration }}
              </div>
              <div class="text-xs text-gray-500">
                {{ activeBooking.vehicle.make }} {{ activeBooking.vehicle.model }}
              </div>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Fuel Level</label>
              <div class="flex gap-1.5">
                <button v-for="level in ['F','3/4','1/2','1/4','E']" :key="level"
                  @click="checklist.fuel_level = level"
                  :class="['px-2.5 py-1.5 text-[10px] font-bold rounded-lg border-2 transition-all',
                    checklist.fuel_level === level
                      ? 'bg-red-600 border-red-600 text-white'
                      : 'border-gray-200 text-gray-600 hover:border-red-400']">
                  {{ level }}
                </button>
              </div>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-semibold text-gray-500 uppercase">Odometer (km)</label>
              <input v-model.number="checklist.odometer" type="number" class="input-base"
                placeholder="e.g. 45000">
            </div>
          </div>
        </div>

        <!-- Checklist sections -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <ChecklistSection title="Exterior" :items="exteriorLabels" v-model="checklist.exterior" />
          <ChecklistSection title="Interior" :items="interiorLabels" v-model="checklist.interior" />
          <div class="space-y-4">
            <ChecklistSection title="Engine Compartment" :items="engineLabels" v-model="checklist.engine_compartment" />
            <ChecklistSection title="Extras" :items="extrasLabels" v-model="checklist.extras" />
          </div>
        </div>

        <!-- Remarks -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Exterior Remarks</label>
            <textarea v-model="checklist.exterior_remarks" rows="2" class="input-base resize-none"
              placeholder="Any exterior damage or notes…" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Interior Remarks</label>
            <textarea v-model="checklist.interior_remarks" rows="2" class="input-base resize-none"
              placeholder="Any interior damage or notes…" />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4">
          <button @click="printChecklist"
            class="flex items-center gap-2 border border-gray-200 text-gray-600 text-xs font-semibold px-4 py-2.5 rounded-xl hover:bg-gray-50">
            <PrinterIcon class="w-4 h-4" /> Print Checklist
          </button>
          <div class="flex gap-2">
            <button @click="saveChecklistAndProceed" :disabled="savingChecklist"
              class="flex items-center gap-2 px-5 py-2.5 text-xs font-bold bg-red-600 hover:bg-red-700
                     text-white rounded-xl disabled:opacity-60 transition-colors">
              <span v-if="savingChecklist" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              {{ savingChecklist ? 'Saving…' : 'Save & Proceed to Services →' }}
            </button>
          </div>
        </div>

      </div>

      <!-- ══════════════════════════════════════════════════
           STEP 3: SERVICES + CHECKOUT
      ══════════════════════════════════════════════════ -->
      <template v-else-if="currentStep === 'checkout'">

        <!-- LEFT: Services -->
        <div class="flex-1 flex flex-col overflow-hidden border-r border-gray-200">
          <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 bg-white shrink-0">
            <h3 class="text-sm font-bold text-gray-900">Services</h3>
            <SearchInput v-model="serviceSearch" placeholder="Search services..." class="flex-1 max-w-xs" />
          </div>
          <div class="flex-1 overflow-y-auto p-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
              <button v-for="svc in filteredServices" :key="svc.id"
                @click="addToCart(svc)"
                :class="['flex flex-col items-center gap-2 bg-white border rounded-2xl p-4 transition-all text-center group',
                  isInCart(svc.id) ? 'border-red-400 bg-red-50/30' : 'border-gray-200 hover:border-red-400 hover:bg-red-50/30']">
                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center',
                  isInCart(svc.id) ? 'bg-red-600' : 'bg-red-50 group-hover:bg-red-100']">
                  <span :class="isInCart(svc.id) ? 'text-white' : 'text-red-500'" class="text-lg">⚙</span>
                </div>
                <div>
                  <div class="text-xs font-bold text-gray-900 leading-tight">{{ svc.name }}</div>
                  <div class="text-[10px] text-gray-500 mt-0.5">KES {{ svc.price_from?.toLocaleString() }}</div>
                  <div v-if="svc.duration_minutes" class="text-[9px] text-gray-400">
                    ~{{ formatDuration(svc.duration_minutes) }}
                  </div>
                </div>
              </button>
              <div v-if="!filteredServices.length" class="col-span-3 py-12 text-center text-gray-400 text-xs">
                No services found.
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Cart -->
        <div class="w-[300px] md:w-[340px] flex flex-col bg-white shrink-0">

          <!-- Booking badge -->
          <div class="px-4 py-2.5 bg-gray-900 flex items-center justify-between shrink-0">
            <div>
              <div class="text-[9px] text-gray-400 uppercase tracking-widest">Booking</div>
              <div class="text-xs font-mono font-bold text-white">{{ activeBooking.reference }}</div>
            </div>
            <div class="text-right">
              <div class="text-[9px] text-gray-400">{{ activeBooking.vehicle.registration }}</div>
              <div class="text-[9px] text-gray-400">{{ activeBooking.customer.name }}</div>
            </div>
          </div>

          <!-- Cart items -->
          <div class="flex-1 overflow-y-auto px-4 py-3">
            <div v-if="!cart.length"
              class="flex flex-col items-center justify-center h-full gap-3 text-gray-300">
              <ShoppingCartIcon class="w-10 h-10" />
              <p class="text-xs">Select services from the left</p>
            </div>
            <div v-else class="space-y-2">
              <div v-for="(item, i) in cart" :key="i"
                class="flex items-center justify-between bg-gray-50 rounded-xl px-3 py-2.5 gap-2">
                <div class="flex-1 min-w-0">
                  <div class="text-xs font-semibold text-gray-900 truncate">{{ item.name }}</div>
                  <div class="flex items-center gap-1 mt-0.5">
                    <span class="text-[10px] text-gray-500">KES</span>
                    <input v-model.number="item.unit_price" type="number" min="0"
                      class="text-[10px] font-semibold text-gray-700 bg-transparent border-b border-dashed border-gray-300 focus:border-red-400 outline-none w-16">
                  </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  <button @click="item.quantity > 1 ? item.quantity-- : removeFromCart(i)"
                    class="w-6 h-6 rounded-full bg-white border border-gray-200 text-gray-600 font-bold flex items-center justify-center hover:bg-gray-100">−</button>
                  <span class="text-xs font-bold w-4 text-center">{{ item.quantity }}</span>
                  <button @click="item.quantity++"
                    class="w-6 h-6 rounded-full bg-gray-900 text-white font-bold flex items-center justify-center hover:bg-gray-700">+</button>
                </div>
              </div>
              <button @click="addCustomItem"
                class="w-full flex items-center gap-2 text-xs text-gray-400 hover:text-red-500 py-1.5 transition-colors">
                <PlusCircleIcon class="w-4 h-4" /> Add custom item
              </button>
            </div>
          </div>

          <!-- Discount -->
          <div v-if="cart.length" class="px-4 py-2 border-t border-gray-100 shrink-0">
            <div class="flex items-center gap-2">
              <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide shrink-0">Discount (KES)</label>
              <input v-model.number="discount" type="number" min="0" :max="subtotal"
                class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 text-right focus:outline-none focus:border-red-400">
            </div>
          </div>

          <!-- Totals -->
          <div class="px-4 py-3 border-t border-gray-100 space-y-1.5 shrink-0">
            <div class="flex justify-between text-xs text-gray-500">
              <span>Subtotal</span><span>KES {{ subtotal.toLocaleString() }}</span>
            </div>
            <div v-if="discount > 0" class="flex justify-between text-xs text-green-600">
              <span>Discount</span><span>− KES {{ discount.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
              <span>VAT (16%)</span><span>KES {{ vatAmount.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-sm font-extrabold text-gray-900 pt-1.5 border-t border-gray-100">
              <span>Total</span><span>KES {{ total.toLocaleString() }}</span>
            </div>
          </div>

          <!-- Payment buttons -->
          <div class="px-4 pb-4 grid grid-cols-3 gap-2 shrink-0">
            <button v-for="method in paymentMethods" :key="method.key"
              @click="initiateCheckout(method.key)"
              :class="['flex flex-col items-center gap-1 py-3 rounded-xl font-bold text-[10px] text-white transition-all',
                method.bg, 'hover:opacity-90', !cart.length ? 'opacity-40 pointer-events-none' : '']">
              <component :is="method.icon" class="w-5 h-5" />
              {{ method.label }}
            </button>
          </div>
        </div>

      </template>

    </div><!-- end main content -->

    <!-- ══════════════════════════════════════════════════
         MODAL: CASH / CARD
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showPaymentModal" :title="paymentModalTitle" size="sm">
      <div class="space-y-4">
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center">
          <div class="text-2xl font-extrabold text-gray-900">KES {{ total.toLocaleString() }}</div>
          <div class="text-xs text-gray-500 mt-1">Amount to collect</div>
        </div>
        <div v-if="pendingMethod === 'cash'" class="space-y-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Amount Tendered (KES)</label>
            <input v-model.number="amountTendered" type="number" :min="total"
              class="input-base text-right font-mono text-lg font-bold" placeholder="0">
          </div>
          <div v-if="amountTendered >= total"
            class="flex justify-between items-center bg-green-50 border border-green-200 rounded-xl px-4 py-3">
            <span class="text-xs font-semibold text-green-700">Change</span>
            <span class="text-lg font-extrabold text-green-700">KES {{ (amountTendered - total).toLocaleString() }}</span>
          </div>
        </div>
        <div v-if="pendingMethod === 'card'" class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Card Reference <span class="text-gray-400 font-normal">(Optional)</span></label>
          <input v-model="cardRef" class="input-base font-mono uppercase" placeholder="Approval code">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
          <input v-model="paymentNotes" class="input-base" placeholder="Any payment notes…">
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showPaymentModal = false"
            class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="confirmCheckout()"
            :disabled="(pendingMethod === 'cash' && amountTendered < total) || saving"
            class="px-5 py-2 text-xs font-bold bg-green-600 text-white rounded-xl hover:bg-green-700 disabled:opacity-60 flex items-center gap-2">
            <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
            {{ saving ? 'Processing…' : 'Mark as Paid' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: M-PESA
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showMpesaModal" title="M-Pesa Payment" size="sm">
      <div class="space-y-4">
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
          <div class="text-2xl font-extrabold text-gray-900">KES {{ total.toLocaleString() }}</div>
          <div class="text-xs text-gray-500 mt-1">Amount to collect</div>
        </div>
        <div class="flex bg-gray-100 rounded-xl p-1 gap-1">
          <button v-for="tab in ['stk','manual']" :key="tab" @click="mpesaTab = tab"
            :class="['flex-1 py-2 text-xs font-semibold rounded-lg transition-colors',
              mpesaTab === tab ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500']">
            {{ tab === 'stk' ? '📱 STK Push' : '✍ Enter Code' }}
          </button>
        </div>
        <div v-if="mpesaTab === 'stk'" class="space-y-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Customer Phone</label>
            <input v-model="stkPhone" class="input-base font-mono" placeholder="+254 700 000000">
          </div>
          <button @click="sendStkPush" :disabled="!stkPhone || stkSent || saving"
            class="w-full py-2.5 text-xs font-bold bg-green-600 text-white rounded-xl hover:bg-green-700 disabled:opacity-60 flex items-center justify-center gap-2">
            <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
            {{ stkSent ? '📨 Prompt Sent — Awaiting Payment' : 'Send Payment Prompt' }}
          </button>
          <div v-if="stkSent" class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-700">
            Prompt sent to <strong>{{ stkPhone }}</strong>. Ask customer to enter their PIN.
          </div>
          <div v-if="stkSent" class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Confirmation Code</label>
            <input v-model="mpesaCode" class="input-base font-mono uppercase" placeholder="e.g. QHK9XXXXX">
          </div>
        </div>
        <div v-if="mpesaTab === 'manual'" class="space-y-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">M-Pesa Transaction Code</label>
            <input v-model="mpesaCode" class="input-base font-mono uppercase" placeholder="e.g. QHK9XXXXX">
            <p class="text-[10px] text-gray-400">Ask customer to share their M-Pesa confirmation SMS code.</p>
          </div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
          <input v-model="paymentNotes" class="input-base" placeholder="Any payment notes…">
        </div>
      </div>
      <template #footer>
        <div class="flex items-center justify-between w-full">
          <button @click="showMpesaModal = false"
            class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <div class="flex gap-2">
            <button @click="confirmCheckout(true)" :disabled="saving"
              class="px-4 py-2 text-xs font-semibold border border-green-300 text-green-700 rounded-xl hover:bg-green-50 disabled:opacity-60">
              Mark as Paid
            </button>
            <button @click="confirmCheckout(false)" :disabled="!mpesaCode || saving"
              class="px-5 py-2 text-xs font-bold bg-green-600 text-white rounded-xl hover:bg-green-700 disabled:opacity-60 flex items-center gap-2">
              <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              {{ saving ? 'Processing…' : 'Confirm with Code' }}
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: RECEIPT
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showReceipt" title="Payment Successful" size="sm">
      <div class="text-center space-y-4">
        <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto">
          <CheckCircleIcon class="w-9 h-9 text-green-500" />
        </div>
        <div>
          <div class="text-2xl font-extrabold text-gray-900">KES {{ lastInvoice?.total?.toLocaleString() }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ lastInvoice?.invoice_number }}</div>
          <div v-if="lastInvoice?.mpesa_reference"
            class="text-xs font-mono font-bold text-green-700 mt-1">Ref: {{ lastInvoice.mpesa_reference }}</div>
        </div>
        <div class="bg-gray-50 rounded-xl p-4 text-xs text-left space-y-2">
          <div v-for="item in lastInvoice?.items" :key="item.id" class="flex justify-between">
            <span class="text-gray-600">{{ item.description }} × {{ item.quantity }}</span>
            <span class="font-semibold">KES {{ item.total?.toLocaleString() }}</span>
          </div>
          <div v-if="lastInvoice?.discount > 0" class="flex justify-between text-green-600 border-t border-gray-200 pt-1.5">
            <span>Discount</span><span>− KES {{ lastInvoice.discount?.toLocaleString() }}</span>
          </div>
          <div class="flex justify-between border-t border-gray-200 pt-1.5 font-bold text-gray-800">
            <span>VAT ({{ lastInvoice?.vat_percent }}%)</span>
            <span>KES {{ lastInvoice?.vat_amount?.toLocaleString() }}</span>
          </div>
        </div>
        <div v-if="pendingMethod === 'cash' && changeDue > 0"
          class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 flex justify-between items-center">
          <span class="text-xs font-semibold text-yellow-700">Change Due</span>
          <span class="text-lg font-extrabold text-yellow-700">KES {{ changeDue.toLocaleString() }}</span>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-between items-center w-full">
          <button @click="printReceipt"
            class="flex items-center gap-1.5 px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">
            <PrinterIcon class="w-3.5 h-3.5" /> Print Receipt
          </button>
          <button @click="resetPOS"
            class="px-4 py-2 text-xs font-semibold bg-gray-900 text-white rounded-xl hover:bg-gray-700">
            New Sale
          </button>
        </div>
      </template>
    </Modal>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  ShoppingCartIcon, CheckCircleIcon, BanknotesIcon,
  DevicePhoneMobileIcon, CreditCardIcon, PlusCircleIcon,
  PrinterIcon, CheckIcon, ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import SearchInput       from '@/components/SearchInput.vue'
import Modal             from '@/components/Modal.vue'
import ChecklistSection  from '@/components/ChecklistSection.vue'

const { get, post, put } = useApi()
const toast  = useToastStore()
const route  = useRoute()

// ── Steps ──────────────────────────────────────────────────────────────────────
const steps = [
  { key: 'checklist', label: 'Vehicle Checklist' },
  { key: 'checkout',  label: 'Services & Payment' },
]
const currentStep    = ref('checklist')
const completedSteps = ref([])

function goToStep(key) {
  if (canGoToStep(key)) currentStep.value = key
}
function canGoToStep(key) {
  if (key === 'checklist') return true
  if (key === 'checkout')  return completedSteps.value.includes('checklist')
  return false
}

// ── Status colors ──────────────────────────────────────────────────────────────
const statusColors = {
  pending:'#EAB308', confirmed:'#3B82F6', in_progress:'#F97316',
  completed:'#22C55E', cancelled:'#EF4444', no_show:'#6B7280',
}
const statusColor = slug => statusColors[slug] ?? '#6B7280'

// ── Services ───────────────────────────────────────────────────────────────────
const allServices  = ref([])
const serviceSearch = ref('')
const filteredServices = computed(() => {
  if (!serviceSearch.value) return allServices.value
  const q = serviceSearch.value.toLowerCase()
  return allServices.value.filter(s => s.name.toLowerCase().includes(q))
})
const isInCart = id => cart.value.some(i => i.id === id)
const formatDuration = m => m >= 60 ? `${Math.floor(m/60)}h${m%60?' '+m%60+'m':''}` : `${m}m`

// ── Booking selection ──────────────────────────────────────────────────────────
const activeBooking     = ref(null)
const bookingSearch     = ref('')
const bookingResults    = ref([])
const searchingBookings = ref(false)
const hasSearched       = ref(false)

async function searchBookings() {
  if (!bookingSearch.value.trim()) return
  searchingBookings.value = true
  hasSearched.value = true
  try {
    const data = await get('/admin/bookings', { search: bookingSearch.value, per_page: 10 })
    bookingResults.value = data?.data ?? []
  } catch { toast.error('Failed to search bookings.') }
  finally { searchingBookings.value = false }
}

function linkBooking(b) {
  activeBooking.value  = b
  bookingResults.value = []
  bookingSearch.value  = ''
  currentStep.value    = 'checklist'
  completedSteps.value = []
  // Pre-fill STK phone
  stkPhone.value = b.customer?.phone ?? ''
  // Pre-add booked service to cart
  if (b.service?.id) {
    const match = allServices.value.find(s => s.id === b.service.id)
    if (match && !isInCart(match.id)) addToCart(match)
    else if (!match && b.service?.name) {
      cart.value.push({ id: b.service.id, name: b.service.name, unit_price: 0, quantity: 1 })
    }
  }
  // Init checklist defaults
  initChecklist()
}

// ── Walk-in ────────────────────────────────────────────────────────────────────
const walkIn = ref({ phone:'', name:'', reg:'', make_model:'', service_id:'' })
const walkInError = ref(null)
const walkInCustomerFound = ref(false)
const walkInVehicleFound  = ref('')
const creatingWalkIn = ref(false)

async function lookupWalkInCustomer() {
  if (walkIn.value.phone.length < 9) return
  try {
    const data = await get('/admin/customers', { search: walkIn.value.phone })
    const c = data?.data?.[0]
    if (c && c.phone === walkIn.value.phone) {
      walkIn.value.name = c.name
      walkInCustomerFound.value = true
    } else { walkInCustomerFound.value = false }
  } catch {}
}

async function lookupWalkInVehicle() {
  if (walkIn.value.reg.length < 4) return
  try {
    const data = await get('/admin/vehicles', { search: walkIn.value.reg.toUpperCase() })
    const v = data?.data?.[0]
    if (v && v.registration === walkIn.value.reg.toUpperCase()) {
      walkInVehicleFound.value = `${v.make} ${v.model}`
      if (!walkIn.value.name && v.customer?.name) walkIn.value.name  = v.customer.name
      if (!walkIn.value.phone && v.customer?.phone) walkIn.value.phone = v.customer.phone
    } else { walkInVehicleFound.value = '' }
  } catch {}
}

async function createWalkIn() {
  walkInError.value    = null
  creatingWalkIn.value = true
  try {
    const now     = new Date()
    const pad     = n => String(n).padStart(2, '0')
    const scheduled = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}:00`
    const booking = await post('/admin/bookings', {
      customer_name:  walkIn.value.name,
      customer_phone: walkIn.value.phone,
      vehicle_reg:    walkIn.value.reg.toUpperCase(),
      vehicle_make:   walkIn.value.make_model.split(' ')[0] || undefined,
      vehicle_model:  walkIn.value.make_model.split(' ').slice(1).join(' ') || undefined,
      service_id:     walkIn.value.service_id || undefined,
      source:         'walk_in',
      scheduled_at:   scheduled,
    })
    linkBooking(booking)
    walkIn.value = { phone:'', name:'', reg:'', make_model:'', service_id:'' }
    walkInCustomerFound.value = false; walkInVehicleFound.value = ''
    toast.success('Walk-in booking created.')
  } catch (e) {
    walkInError.value = e.response?.data?.message ?? 'Failed to create booking.'
  } finally { creatingWalkIn.value = false }
}

// ── Checklist ──────────────────────────────────────────────────────────────────
const activeChecklistId = ref(null)
const savingChecklist   = ref(false)

const checklist = ref({
  fuel_level: '', odometer: null,
  exterior: {}, interior: {}, engine_compartment: {}, extras: {},
  exterior_remarks: '', interior_remarks: '',
})

const defaultItems = keys => Object.fromEntries(keys.map(k => [k, { status:'ok', note:'' }]))

function initChecklist() {
  checklist.value = {
    fuel_level: '', odometer: null,
    exterior:            defaultItems(['front_windscreen','rear_windscreen','insurance_sticker','front_number_plate','headlights','tail_lights','front_bumper','rear_bumper','grille','grille_badge','front_wiper','rear_wiper','side_mirror','door_glasses','fuel_tank_cap','front_tyres','rear_tyres','front_rims','rear_rims','hub_wheel_caps','roof_rails','body_moulding','emblems','weather_stripes','mud_guard']),
    interior:            defaultItems(['rear_view_mirror','radio','radio_face','equalizer','amplifier','tuner','speaker','cigar_lighter','door_switches','rubber_mats','carpets','seat_covers','boot_mat','boot_board','aircon_knobs','keys_remotes','seat_belts']),
    engine_compartment:  defaultItems(['battery','computer_control_box','ignition_coils','wiper_panel_finisher_covers','horn','engine_caps','dip_sticks','starter','alternator','fog_lights','reverse_camera','relays','radiator']),
    extras:              defaultItems(['jack_handle','wheel_spanner','towing_pin','towing_cable_rope','first_aid_kit','fire_extinguisher','spare_wheel','life_savers']),
    exterior_remarks: '', interior_remarks: '',
  }
}

const exteriorLabels = { front_windscreen:'Front Wind Screen',rear_windscreen:'Rear Wind Screen',insurance_sticker:'Insurance Sticker',front_number_plate:'Front Number Plate',headlights:'Headlights',tail_lights:'Tail Lights',front_bumper:'Front Bumper',rear_bumper:'Rear Bumper',grille:'Grille',grille_badge:'Grille Badge',front_wiper:'Front Wiper',rear_wiper:'Rear Wiper',side_mirror:'Side Mirror',door_glasses:'Door Glasses',fuel_tank_cap:'Fuel Tank Cap',front_tyres:'Front Tyres',rear_tyres:'Rear Tyres',front_rims:'Front Rims',rear_rims:'Rear Rims',hub_wheel_caps:'Hub/Wheel Caps',roof_rails:'Roof Rails',body_moulding:'Body Moulding',emblems:'Emblems',weather_stripes:'Weather Stripes',mud_guard:'Mud Guard' }
const interiorLabels = { rear_view_mirror:'Rear View Mirror',radio:'Radio',radio_face:'Radio Face',equalizer:'Equalizer',amplifier:'Amplifier',tuner:'Tuner',speaker:'Speaker',cigar_lighter:'Cigar Lighter',door_switches:'Door Switches',rubber_mats:'Rubber Mats',carpets:'Carpets',seat_covers:'Seat Covers',boot_mat:'Boot Mat',boot_board:'Boot Board',aircon_knobs:'Air Con Knobs',keys_remotes:'No. of Keys/Remotes',seat_belts:'Seat Belts' }
const engineLabels   = { battery:'Battery',computer_control_box:'Computer/Control Box',ignition_coils:'Ignition Coils',wiper_panel_finisher_covers:'Wiper Panel Covers',horn:'Horn',engine_caps:'Engine Caps',dip_sticks:'Dip Sticks',starter:'Starter',alternator:'Alternator',fog_lights:'Fog Lights',reverse_camera:'Reverse Camera',relays:'Relays',radiator:'Radiator' }
const extrasLabels   = { jack_handle:'Jack & Handle',wheel_spanner:'Wheel Spanner',towing_pin:'Towing Pin',towing_cable_rope:'Towing Cable/Rope',first_aid_kit:'First Aid Kit',fire_extinguisher:'Fire Extinguisher',spare_wheel:'Spare Wheel',life_savers:'Life Savers' }

async function saveChecklistAndProceed() {
  savingChecklist.value = true
  try {
    const payload = {
      vehicle_id:          activeBooking.value.vehicle.id,
      customer_id:         activeBooking.value.customer.id,
      job_card_id:         null,
      fuel_level:          checklist.value.fuel_level   || null,
      odometer:            checklist.value.odometer     || null,
      exterior:            checklist.value.exterior,
      interior:            checklist.value.interior,
      engine_compartment:  checklist.value.engine_compartment,
      extras:              checklist.value.extras,
      exterior_remarks:    checklist.value.exterior_remarks || null,
      interior_remarks:    checklist.value.interior_remarks || null,
    }

    let result
    if (activeChecklistId.value) {
      result = await put(`/admin/checklists/${activeChecklistId.value}`, payload)
    } else {
      result = await post('/admin/checklists', payload)
      activeChecklistId.value = result.id
    }

    completedSteps.value = ['checklist']
    currentStep.value    = 'checkout'
    toast.success('Checklist saved.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to save checklist.')
  } finally { savingChecklist.value = false }
}

function printChecklist() {
  if (activeChecklistId.value) {
    window.open(`/print/checklist/${activeChecklistId.value}`, '_blank')
  } else {
    toast.error('Save the checklist first to print it.')
  }
}

// ── Cart ───────────────────────────────────────────────────────────────────────
const cart     = ref([])
const discount = ref(0)
const subtotal  = computed(() => cart.value.reduce((s,i) => s + i.unit_price * i.quantity, 0))
const vatAmount = computed(() => Math.round((subtotal.value - discount.value) * 0.16))
const total     = computed(() => subtotal.value - discount.value + vatAmount.value)

function addToCart(svc) {
  const ex = cart.value.find(i => i.id === svc.id)
  if (ex) { ex.quantity++; return }
  cart.value.push({ id: svc.id, name: svc.name, unit_price: svc.price_from ?? 0, quantity: 1 })
}
function removeFromCart(i) { cart.value.splice(i, 1) }
function addCustomItem() {
  cart.value.push({ id: Date.now(), name: 'Custom Item', unit_price: 0, quantity: 1 })
}

// ── Payment ────────────────────────────────────────────────────────────────────
const showPaymentModal = ref(false)
const showMpesaModal   = ref(false)
const showReceipt      = ref(false)
const pendingMethod    = ref('')
const saving           = ref(false)
const lastInvoice      = ref(null)
const amountTendered   = ref(0)
const changeDue        = ref(0)
const cardRef          = ref('')
const mpesaTab         = ref('stk')
const mpesaCode        = ref('')
const stkPhone         = ref('')
const stkSent          = ref(false)
const paymentNotes     = ref('')

const paymentModalTitle = computed(() => ({ cash:'Cash Payment', card:'Card Payment' }[pendingMethod.value] ?? 'Payment'))
const paymentMethods = [
  { key:'cash',  label:'Cash',   bg:'bg-green-600', icon:BanknotesIcon },
  { key:'mpesa', label:'M-Pesa', bg:'bg-green-500', icon:DevicePhoneMobileIcon },
  { key:'card',  label:'Card',   bg:'bg-blue-600',  icon:CreditCardIcon },
]

function initiateCheckout(method) {
  if (!cart.value.length) return
  pendingMethod.value = method
  if (method === 'mpesa') {
    stkPhone.value = activeBooking.value?.customer?.phone ?? ''
    mpesaCode.value = ''; stkSent.value = false
    showMpesaModal.value = true
  } else {
    amountTendered.value = method === 'cash' ? total.value : 0
    showPaymentModal.value = true
  }
}

async function sendStkPush() {
  saving.value = true
  try {
    await post('/admin/mpesa/stk-push', { phone: stkPhone.value, amount: total.value, booking_id: activeBooking.value?.id })
    stkSent.value = true
    toast.success('Payment prompt sent to ' + stkPhone.value)
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to send STK push.')
  } finally { saving.value = false }
}

async function confirmCheckout(skipCode = false) {
  saving.value = true
  try {
    const inv = await post('/admin/pos/checkout', {
      customer_id:     activeBooking.value?.customer?.id ?? undefined,
      booking_id:      activeBooking.value?.id           ?? undefined,
      checklist_id:    activeChecklistId.value           ?? undefined,
      vehicle_reg:     activeBooking.value?.vehicle?.registration ?? undefined,
      items:           cart.value.map(i => ({ description:i.name, quantity:i.quantity, unit_price:i.unit_price })),
      payment_method:  pendingMethod.value,
      mpesa_reference: pendingMethod.value === 'mpesa' && !skipCode ? mpesaCode.value : undefined,
      card_reference:  pendingMethod.value === 'card' ? cardRef.value : undefined,
      discount:        discount.value > 0 ? discount.value : undefined,
      notes:           paymentNotes.value || undefined,
    })
    lastInvoice.value      = inv
    changeDue.value        = pendingMethod.value === 'cash' ? amountTendered.value - total.value : 0
    showPaymentModal.value = false
    showMpesaModal.value   = false
    showReceipt.value      = true
    toast.success('Payment recorded — ' + inv.invoice_number)
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Checkout failed.')
  } finally { saving.value = false }
}

function printReceipt() {
  if (lastInvoice.value?.id) window.open(`/print/invoice/${lastInvoice.value.id}`, '_blank')
}

function resetPOS() {
  activeBooking.value     = null
  activeChecklistId.value = null
  cart.value              = []; discount.value = 0
  currentStep.value       = 'checklist'
  completedSteps.value    = []
  showReceipt.value       = false
  mpesaCode.value = ''; cardRef.value = ''
  paymentNotes.value = ''; amountTendered.value = 0
  stkSent.value = false; bookingSearch.value = ''
  bookingResults.value = []; hasSearched.value = false
  walkIn.value = { phone:'', name:'', reg:'', make_model:'', service_id:'' }
  initChecklist()
}

// ── Boot ───────────────────────────────────────────────────────────────────────
onMounted(async () => {
  allServices.value = await get('/admin/services') ?? []
  initChecklist()
  // Prefill from booking redirect
  const q = route.query
  if (q.booking_id) {
    try {
      const b = await get(`/admin/bookings/${q.booking_id}`)
      if (b) linkBooking(b)
    } catch {}
  }
})
</script>
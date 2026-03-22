<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">

    <!-- ── SIDEBAR (desktop) ── -->
    <aside
      :class="['hidden md:flex flex-col bg-gray-900 text-white shrink-0 transition-all duration-300',
        sidebarCollapsed ? 'md:w-16' : 'md:w-[200px]']">
      <div class="flex items-center gap-3 px-4 py-4 border-b border-white/10 h-16 overflow-hidden">
        <!-- <img src="./assets/images/logos/logo.png" alt="Premax"
          class="w-8 h-8 rounded-lg object-contain bg-white p-0.5 shrink-0"> -->
        <span v-if="!sidebarCollapsed" class="font-extrabold text-sm tracking-tight whitespace-nowrap">
          Premax <span class="text-red-500">Autocare</span>
        </span>
      </div>

      <nav class="flex-1 py-4 overflow-y-auto space-y-0.5">
        <NavItem v-for="item in navItems" :key="item.to"
          :item="item" :collapsed="sidebarCollapsed" />
      </nav>

      <button @click="sidebarCollapsed = !sidebarCollapsed"
        class="flex items-center justify-center h-10 border-t border-white/10 text-gray-400 hover:text-white transition-colors">
        <ChevronLeftIcon v-if="!sidebarCollapsed" class="w-4 h-4" />
        <ChevronRightIcon v-else class="w-4 h-4" />
      </button>
    </aside>

    <!-- ── MAIN ── -->
    <div class="flex-1 flex flex-col overflow-hidden">

      <!-- Topbar -->
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:px-6 shrink-0 z-10">
        <h1 class="text-base font-bold text-gray-900">{{ pageTitle }}</h1>

        <div class="flex items-center gap-3">

          <!-- Add Booking button -->
          <button @click="showBookingModal = true"
            class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-3 py-2 rounded-xl transition-colors">
            <PlusIcon class="w-4 h-4" />
            <span class="hidden sm:inline">Add Booking</span>
          </button>

          <!-- Notifications -->
          <button class="relative text-gray-500 hover:text-gray-900">
            <BellIcon class="w-5 h-5" />
            <span v-if="notifCount"
              class="absolute -top-1 -right-1 w-4 h-4 bg-red-600 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
              {{ notifCount }}
            </span>
          </button>

          <!-- User avatar + dropdown -->
          <div class="relative" ref="userMenuRef">
            <button @click="showUserMenu = !showUserMenu"
              class="flex items-center gap-2 hover:opacity-80 transition-opacity">
              <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 font-bold text-sm flex items-center justify-center select-none">
                {{ userInitials }}
              </div>
              <div class="hidden md:block text-left">
                <div class="text-xs font-semibold text-gray-900 leading-tight">{{ auth.user?.name }}</div>
                <div class="text-[10px] text-gray-400 leading-tight capitalize">{{ auth.user?.role ?? 'Staff' }}</div>
              </div>
              <ChevronDownIcon class="hidden md:block w-3.5 h-3.5 text-gray-400" />
            </button>

            <!-- Dropdown -->
            <Transition name="dropdown">
              <div v-if="showUserMenu"
                class="absolute right-0 top-full mt-2 w-52 bg-white rounded-2xl border border-gray-100 shadow-xl overflow-hidden z-50">
                <!-- User info header -->
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-red-100 text-red-600 font-bold text-sm flex items-center justify-center shrink-0">
                      {{ userInitials }}
                    </div>
                    <div class="min-w-0">
                      <div class="text-xs font-bold text-gray-900 truncate">{{ auth.user?.name }}</div>
                      <div class="text-[10px] text-gray-500 truncate">{{ auth.user?.email }}</div>
                    </div>
                  </div>
                </div>

                <!-- Menu items -->
                <div class="py-1.5">
                  <button @click="goToProfile"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    <UserCircleIcon class="w-4 h-4 text-gray-400" />
                    My Profile
                  </button>
                  <button @click="goToUpdateDetails"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    <PencilSquareIcon class="w-4 h-4 text-gray-400" />
                    Update Details
                  </button>
                  <div class="h-px bg-gray-100 mx-2 my-1" />
                  <button @click="logout"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">
                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                    Sign Out
                  </button>
                </div>
              </div>
            </Transition>
          </div>

        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto pb-16 md:pb-0">
        <RouterView />
      </main>
    </div>

    <!-- ══════════════════════════════════════════════════
         MOBILE BOTTOM NAV
    ══════════════════════════════════════════════════ -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-100 flex z-40 shadow-lg">
      <RouterLink v-for="item in mobileNavItems" :key="item.to" :to="item.to"
        class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 text-[10px] font-semibold transition-colors"
        :class="isActive(item.to) ? 'text-red-600' : 'text-gray-400'">
        <component :is="item.icon" :class="['w-5 h-5', isActive(item.to) ? 'text-red-600' : 'text-gray-400']" />
        {{ item.label }}
      </RouterLink>
      <button @click="showMoreDrawer = true"
        class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 text-[10px] font-semibold transition-colors"
        :class="isMoreActive ? 'text-red-600' : 'text-gray-400'">
        <Bars3Icon :class="['w-5 h-5', isMoreActive ? 'text-red-600' : 'text-gray-400']" />
        More
      </button>
    </nav>

    <!-- ══════════════════════════════════════════════════
         MORE DRAWER (mobile)
    ══════════════════════════════════════════════════ -->
    <Transition name="fade">
      <div v-if="showMoreDrawer" class="md:hidden fixed inset-0 bg-black/30 z-50 backdrop-blur-sm"
        @click="showMoreDrawer = false" />
    </Transition>

    <Transition name="slide-up">
      <div v-if="showMoreDrawer"
        class="md:hidden fixed bottom-0 inset-x-0 z-50 bg-white rounded-t-3xl shadow-2xl pb-8">
        <div class="flex justify-center pt-3 pb-1">
          <div class="w-10 h-1 bg-gray-200 rounded-full" />
        </div>
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
          <span class="text-sm font-bold text-gray-900">More Options</span>
          <button @click="showMoreDrawer = false"
            class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-100 text-gray-500">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
        <div class="grid grid-cols-3 gap-3 px-5 pt-4">
          <RouterLink v-for="item in moreNavItems" :key="item.to" :to="item.to"
            @click="showMoreDrawer = false"
            :class="['flex flex-col items-center gap-2 py-4 rounded-2xl border-2 transition-all',
              isActive(item.to)
                ? 'border-red-400 bg-red-50 text-red-600'
                : 'border-gray-100 bg-gray-50 text-gray-600']">
            <div :class="['w-11 h-11 rounded-xl flex items-center justify-center',
              isActive(item.to) ? 'bg-red-600' : 'bg-white shadow-sm']">
              <component :is="item.icon" :class="['w-5 h-5', isActive(item.to) ? 'text-white' : 'text-gray-600']" />
            </div>
            <span class="text-[11px] font-semibold">{{ item.label }}</span>
          </RouterLink>
        </div>

        <!-- Sign out at bottom of drawer -->
        <div class="px-5 mt-4 pt-4 border-t border-gray-100">
          <button @click="logout"
            class="w-full flex items-center justify-center gap-2 py-3 text-xs font-bold text-red-600 bg-red-50 rounded-2xl hover:bg-red-100 transition-colors">
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
            Sign Out
          </button>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════════
         ADD BOOKING MODAL (Quick Booking)
    ══════════════════════════════════════════════════ -->
    <QuickBookingModal v-model="showBookingModal" :customer="null" @saved="onBookingSaved" />

    <!-- ══════════════════════════════════════════════════
         UPDATE DETAILS MODAL
    ══════════════════════════════════════════════════ -->
    <Transition name="fade">
      <div v-if="showUpdateModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 backdrop-blur-sm"
        @click.self="showUpdateModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Update My Details</h2>
            <button @click="showUpdateModal = false" class="text-gray-400 hover:text-gray-700">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <div class="p-6 space-y-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Full Name</label>
              <input v-model="updateForm.name" class="input-base" placeholder="Your name">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-600">Email</label>
              <input v-model="updateForm.email" type="email" class="input-base" placeholder="your@email.com">
            </div>
            <div class="border-t border-gray-100 pt-4 space-y-3">
              <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Change Password</div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-600">Current Password</label>
                <input v-model="updateForm.current_password" type="password" class="input-base" placeholder="Current password">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-600">New Password</label>
                <input v-model="updateForm.new_password" type="password" class="input-base" placeholder="Leave blank to keep current">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-600">Confirm New Password</label>
                <input v-model="updateForm.new_password_confirmation" type="password" class="input-base" placeholder="Repeat new password">
              </div>
            </div>
            <div v-if="updateError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ updateError }}</div>
            <div v-if="updateSuccess" class="text-xs text-green-600 bg-green-50 rounded-xl px-3 py-2">Details updated successfully.</div>
          </div>
          <div class="flex justify-end gap-2 px-6 pb-5">
            <button @click="showUpdateModal = false"
              class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
            <button @click="saveUpdateDetails" :disabled="savingUpdate"
              class="px-5 py-2 text-xs font-bold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60 flex items-center gap-2">
              <span v-if="savingUpdate" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              {{ savingUpdate ? 'Saving…' : 'Save Changes' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useApi }        from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import {
  ChevronLeftIcon, ChevronRightIcon, PlusIcon, BellIcon,
  XMarkIcon, Bars3Icon, ChevronDownIcon,
  UserCircleIcon, PencilSquareIcon, ArrowRightOnRectangleIcon,
  Squares2X2Icon, CalendarDaysIcon, UserGroupIcon,
  TruckIcon, ShoppingCartIcon, CubeIcon, CreditCardIcon, CogIcon,
} from '@heroicons/vue/24/outline'
import NavItem           from '@/components/NavItem.vue'
import QuickBookingModal from '@/components/QuickBookingModal.vue'

const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()
const { put } = useApi()
const toast = useToastStore()

// ── UI state ───────────────────────────────────────────────────────────────────
const sidebarCollapsed = ref(false)
const showUserMenu     = ref(false)
const showMoreDrawer   = ref(false)
const showBookingModal = ref(false)
const showUpdateModal  = ref(false)
const notifCount       = ref(0)
const userMenuRef      = ref(null)

// ── Update details form ────────────────────────────────────────────────────────
const updateForm = ref({ name:'', email:'', current_password:'', new_password:'', new_password_confirmation:'' })
const savingUpdate  = ref(false)
const updateError   = ref(null)
const updateSuccess = ref(false)

// ── Computed ───────────────────────────────────────────────────────────────────
const userInitials = computed(() => {
  const name = auth.user?.name ?? ''
  return name.split(' ').slice(0,2).map(w => w[0]?.toUpperCase()).join('') || '?'
})

const pageTitle = computed(() => {
  const map = { dashboard:'Dashboard', bookings:'Bookings', customers:'Customers', vehicles:'Vehicles', pos:'Point of Sale', inventory:'Inventory', payments:'Payments', reports:'Reports', settings:'Settings' }
  return map[route.path.split('/')[1]] ?? 'Premax Admin'
})

// ── Nav items ──────────────────────────────────────────────────────────────────
const navItems = [
  { label:'Dashboard', to:'/dashboard', icon:Squares2X2Icon },
  { label:'Bookings',  to:'/bookings',  icon:CalendarDaysIcon },
  { label:'Customers', to:'/customers', icon:UserGroupIcon },
  { label:'Vehicles',  to:'/vehicles',  icon:TruckIcon },
  { label:'POS',       to:'/pos',       icon:ShoppingCartIcon },
  { label:'Inventory', to:'/inventory', icon:CubeIcon },
  { label:'Payments',  to:'/payments',  icon:CreditCardIcon },
  { label:'Settings',  to:'/settings',  icon:CogIcon },
]

const mobileNavItems = [
  { label:'Dashboard', to:'/dashboard', icon:Squares2X2Icon },
  { label:'Bookings',  to:'/bookings',  icon:CalendarDaysIcon },
  { label:'Customers', to:'/customers', icon:UserGroupIcon },
  { label:'POS',       to:'/pos',       icon:ShoppingCartIcon },
]

const moreNavItems = [
  { label:'Vehicles',  to:'/vehicles',  icon:TruckIcon },
  { label:'Inventory', to:'/inventory', icon:CubeIcon },
  { label:'Payments',  to:'/payments',  icon:CreditCardIcon },
  { label:'Settings',  to:'/settings',  icon:CogIcon },
]

const isActive     = to => route.path.startsWith(to)
const moreRoutes   = moreNavItems.map(i => i.to)
const isMoreActive = computed(() => moreRoutes.some(r => route.path.startsWith(r)))

// ── Actions ────────────────────────────────────────────────────────────────────
function goToProfile() {
  showUserMenu.value = false
  // If you have an employee profile page, route there
  router.push('/settings')
}

function goToUpdateDetails() {
  showUserMenu.value = false
  updateForm.value = {
    name:  auth.user?.name  ?? '',
    email: auth.user?.email ?? '',
    current_password: '', new_password: '', new_password_confirmation: '',
  }
  updateError.value   = null
  updateSuccess.value = false
  showUpdateModal.value = true
}

async function saveUpdateDetails() {
  savingUpdate.value = true; updateError.value = null; updateSuccess.value = false
  try {
    const payload = { name: updateForm.value.name, email: updateForm.value.email }
    if (updateForm.value.new_password) {
      if (updateForm.value.new_password !== updateForm.value.new_password_confirmation) {
        updateError.value = 'New passwords do not match.'; savingUpdate.value = false; return
      }
      payload.current_password          = updateForm.value.current_password
      payload.password                  = updateForm.value.new_password
      payload.password_confirmation     = updateForm.value.new_password_confirmation
    }
    await put('/admin/profile', payload)
    auth.user.name  = updateForm.value.name
    auth.user.email = updateForm.value.email
    updateSuccess.value = true
    toast.success('Details updated.')
    setTimeout(() => { showUpdateModal.value = false }, 1200)
  } catch (e) {
    updateError.value = e.response?.data?.message ?? 'Failed to update details.'
  } finally { savingUpdate.value = false }
}

async function logout() {
  showUserMenu.value  = false
  showMoreDrawer.value = false
  await auth.logout()
  router.push('/login')
}

function onBookingSaved() {
  showBookingModal.value = false
  toast.success('Booking created.')
}

// ── Close user menu on outside click ──────────────────────────────────────────
function onClickOutside(e) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    showUserMenu.value = false
  }
}
onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.25s cubic-bezier(0.32,0.72,0,1); }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.dropdown-enter-active, .dropdown-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
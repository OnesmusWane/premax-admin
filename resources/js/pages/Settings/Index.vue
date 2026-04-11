<template>
  <div class="p-4 md:p-6 space-y-4">

    <PageHeader title="Settings" subtitle="Manage business information and services">
      <button v-if="!['services','users','staff','reviews'].includes(activeTab)" @click="saveAll" :disabled="saving"
        class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl disabled:opacity-60 transition-colors">
        <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
        {{ saving ? 'Saving…' : 'Save Changes' }}
      </button>
    </PageHeader>

    <!-- Tabs -->
    <div class="px-4 md:px-6">
      <div class="flex items-center gap-0 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
          :class="['px-4 py-3 text-xs font-semibold border-b-2 transition-colors whitespace-nowrap',
            activeTab === tab.key
              ? 'border-red-600 text-red-600'
              : 'border-transparent text-gray-500 hover:text-gray-800']">
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         MY PROFILE
    ══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'profile'" class="mx-4 md:mx-6 space-y-4">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center gap-4 mb-6">
          <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 font-extrabold text-xl flex items-center justify-center shrink-0">
            {{ profileInitials }}
          </div>
          <div>
            <div class="text-sm font-bold text-gray-900">{{ auth.user?.name }}</div>
            <div class="text-xs text-gray-500">{{ auth.user?.email }}</div>
            <div class="text-[10px] text-gray-400 capitalize mt-0.5">{{ auth.user?.role ?? 'Staff' }}</div>
          </div>
        </div>
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-widest mb-4">Personal Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Full Name <span class="text-red-500">*</span></label>
            <input v-model="profileForm.name" class="input-base" placeholder="Your name">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Email <span class="text-red-500">*</span></label>
            <input v-model="profileForm.email" type="email" class="input-base" placeholder="your@email.com">
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-widest">Change Password</h3>
        <p class="text-xs text-gray-400">Leave blank to keep your current password.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Current Password</label>
            <input v-model="profileForm.current_password" type="password" class="input-base" placeholder="Current password">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">New Password</label>
            <input v-model="profileForm.new_password" type="password" class="input-base" placeholder="New password">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Confirm New Password</label>
            <input v-model="profileForm.new_password_confirmation" type="password" class="input-base" placeholder="Repeat new password">
          </div>
        </div>
        <div v-if="profileError"   class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ profileError }}</div>
        <div v-if="profileSuccess" class="text-xs text-green-600 bg-green-50 rounded-xl px-3 py-2">Profile updated successfully.</div>
        <div class="flex justify-end">
          <button @click="saveProfile" :disabled="savingProfile"
            class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl disabled:opacity-60">
            <span v-if="savingProfile" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
            {{ savingProfile ? 'Saving…' : 'Save Profile' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         USERS
    ══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'users'" class="mx-4 md:mx-6 space-y-4">
      <div class="flex items-center justify-between">
        <div class="text-xs text-gray-500">{{ users.length }} user{{ users.length !== 1 ? 's' : '' }}</div>
        <button @click="openAddUser"
          class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-xl">
          <PlusIcon class="w-3.5 h-3.5" /> Add User
        </button>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm table-wrap">
        <div v-if="loadingUsers" class="p-8 text-center text-gray-400 text-xs">
          <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
          Loading users…
        </div>
        <div v-else-if="!users.length" class="p-8 text-center text-gray-400 text-xs">No users found.</div>
        <div v-else class="divide-y divide-gray-50">
          <div v-for="user in users" :key="user.id"
            class="flex items-center justify-between px-5 py-4 hover:bg-gray-50/60 transition-colors">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                :style="`background:${avatarColor(user.name)}`">
                {{ initials(user.name) }}
              </div>
              <div>
                <div class="text-xs font-bold text-gray-900 flex items-center gap-2">
                  {{ user.name }}
                  <span v-if="user.id === auth.user?.id"
                    class="text-[9px] font-bold bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded-full">You</span>
                </div>
                <div class="text-[10px] text-gray-500">{{ user.email }}</div>
                <div class="flex items-center gap-1.5 flex-wrap mt-1">
                  <span v-for="role in user.roles" :key="role.id"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                    {{ role.name }}
                  </span>
                </div>
                <div class="text-[10px] text-gray-400 mt-1">
                  {{ user.direct_permissions?.length ?? 0 }} direct permission{{ (user.direct_permissions?.length ?? 0) !== 1 ? 's' : '' }}
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full',
                user.two_factor_enabled ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700']">
                {{ user.two_factor_enabled ? '2FA Ready' : '2FA Pending' }}
              </span>
              <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full',
                user.is_active !== false ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                {{ user.is_active !== false ? 'Active' : 'Inactive' }}
              </span>
              <button @click="openEditUser(user)" class="text-xs font-semibold text-red-600 hover:underline">Edit</button>
              <button v-if="user.id !== auth.user?.id" @click="toggleUserActive(user)"
                :class="['text-xs font-semibold', user.is_active !== false ? 'text-gray-400 hover:text-red-500' : 'text-green-600 hover:text-green-700']">
                {{ user.is_active !== false ? 'Deactivate' : 'Activate' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         CONTACT INFO
    ══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'contact'" class="mx-4 md:mx-6 space-y-4 table-wrap">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-bold text-gray-900">Phone & Email</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Primary Phone</label>
            <input v-model="contact.phone_primary" class="input-base" placeholder="+254 742 091 794">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Secondary Phone</label>
            <input v-model="contact.phone_secondary" class="input-base" placeholder="+254 722 219 396">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">WhatsApp Number</label>
            <input v-model="contact.phone_whatsapp" class="input-base" placeholder="+254 742 091 794">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Primary Email</label>
            <input v-model="contact.email_primary" type="email" class="input-base" placeholder="premaxautocare@gmail.com">
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Support / Bookings Email</label>
            <input v-model="contact.email_support" type="email" class="input-base" placeholder="bookings@premaxautocare.co.ke">
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-bold text-gray-900">Physical Address</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Street Address</label>
            <input v-model="contact.street_address" class="input-base" placeholder="Kiambu Road / Northern Bypass Junction">
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Landmark</label>
            <input v-model="contact.landmark" class="input-base" placeholder="Next to Glee Hotel">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Building / Premises</label>
            <input v-model="contact.building" class="input-base" placeholder="Optional">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Area / Estate</label>
            <input v-model="contact.area" class="input-base" placeholder="Ruaka">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">City</label>
            <input v-model="contact.city" class="input-base" placeholder="Nairobi">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">County</label>
            <input v-model="contact.county" class="input-base" placeholder="Kiambu">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">P.O. Box</label>
            <input v-model="contact.po_box" class="input-base" placeholder="58230">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Postal Code</label>
            <input v-model="contact.postal_code" class="input-base" placeholder="00200">
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-bold text-gray-900">GPS & Maps</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Latitude</label>
            <input v-model.number="contact.latitude" type="number" step="0.0000001" class="input-base font-mono" placeholder="-1.2181690">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Longitude</label>
            <input v-model.number="contact.longitude" type="number" step="0.0000001" class="input-base font-mono" placeholder="36.7972270">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Map Zoom (1–20)</label>
            <input v-model.number="contact.map_zoom" type="number" min="1" max="20" class="input-base" placeholder="16">
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Google Maps URL</label>
            <input v-model="contact.google_maps_url" class="input-base" placeholder="https://maps.google.com/…">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">what3words</label>
            <input v-model="contact.what3words" class="input-base" placeholder="///filled.count.soap">
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         SOCIAL MEDIA
    ══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'social'" class="mx-4 md:mx-6 ">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4 table-wrap">
        <h3 class="text-sm font-bold text-gray-900">Social Media & Website</h3>
        <p class="text-xs text-gray-500">These links appear on the website and in customer-facing communications.</p>
        <div class="space-y-3">
          <div v-for="field in socialFields" :key="field.key" class="flex items-center gap-3">
            <div :class="['w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-white text-sm font-bold select-none', field.bg]">
              {{ field.icon }}
            </div>
            <div class="flex-1 flex flex-col gap-1">
              <label class="text-xs font-semibold text-gray-600">{{ field.label }}</label>
              <input v-model="contact[field.key]" class="input-base" :placeholder="field.placeholder">
            </div>
            <div :class="['w-2 h-2 rounded-full shrink-0 transition-colors', contact[field.key] ? 'bg-green-500' : 'bg-gray-200']" />
          </div>
        </div>
        <div class="pt-2 border-t border-gray-100">
          <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Active</div>
          <div class="flex flex-wrap gap-2">
            <span v-for="field in socialFields.filter(f => contact[f.key])" :key="field.key"
              :class="['text-[10px] font-bold px-2.5 py-1 rounded-full text-white', field.bg]">
              {{ field.label }}
            </span>
            <span v-if="!socialFields.some(f => contact[f.key])" class="text-xs text-gray-400 italic">No social links added yet</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         BUSINESS HOURS
    ══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'hours'" class="mx-4 md:mx-6">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-3 table-wrap">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Business Hours</h3>
        <div v-for="day in days" :key="day.key"
          class="flex items-center gap-4 py-2.5 border-b border-gray-50 last:border-0">
          <span class="text-xs font-semibold text-gray-700 w-24 shrink-0">{{ day.label }}</span>
          <button type="button" @click="toggleDayClosed(day.key)"
            :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors shrink-0',
              businessHours[day.key]?.closed ? 'bg-gray-200' : 'bg-red-600']">
            <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow',
              businessHours[day.key]?.closed ? 'translate-x-1' : 'translate-x-4']" />
          </button>
          <span class="text-[10px] w-10 shrink-0" :class="businessHours[day.key]?.closed ? 'text-gray-400' : 'text-red-600 font-semibold'">
            {{ businessHours[day.key]?.closed ? 'Closed' : 'Open' }}
          </span>
          <template v-if="!businessHours[day.key]?.closed">
            <div class="flex items-center gap-2">
              <input v-model="businessHours[day.key].open" type="time" class="input-base text-xs w-28" />
              <span class="text-xs text-gray-400">to</span>
              <input v-model="businessHours[day.key].close" type="time" class="input-base text-xs w-28" />
            </div>
          </template>
          <span v-else class="text-xs text-gray-400 italic">Not open this day</span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         SERVICES
    ══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'services'" class="mx-4 md:mx-6 space-y-4">
      <div class="flex flex-col md:flex-row items-start md:items-center md:justify-between gap-2 ">
        <div class="text-xs text-gray-500">
          {{ allServices.length }} service{{ allServices.length !== 1 ? 's' : '' }} across {{ categories.length }} categories
        </div>
        <div class="flex gap-2">
          <button @click="openAddCategory"
            class="flex items-center gap-1.5 border border-gray-200 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-gray-50">
            <PlusIcon class="w-3.5 h-3.5" /> Add Category
          </button>
          <button @click="openAddService(null)"
            class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-xl">
            <PlusIcon class="w-3.5 h-3.5" /> Add Service
          </button>
        </div>
      </div>
      <div v-for="cat in categories" :key="cat.id"
        class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row items-start md:items-center md:justify-between px-5 py-3.5 bg-gray-900">
          <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full shrink-0" :style="`background:${cat.color ?? '#DC2626'}`" />
            <div>
              <div class="font-bold text-white text-xs">{{ cat.name }}</div>
              <div v-if="cat.description" class="text-[10px] text-gray-400 mt-0.5">{{ cat.description }}</div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-[10px] text-gray-400">{{ servicesInCategory(cat.id).length }} services</span>
            <button @click="openAddService(cat)"
              class="text-[10px] font-bold text-red-400 hover:text-white border border-gray-700 rounded-lg px-2 py-1 transition-colors">
              + Service
            </button>
            <button @click="openEditCategory(cat)"
              class="text-[10px] font-semibold text-gray-400 hover:text-white transition-colors">Edit</button>
          </div>
        </div>
        <div class="divide-y divide-gray-50">
          <div v-for="svc in servicesInCategory(cat.id)" :key="svc.id"
            class="flex items-start justify-between px-5 py-3 hover:bg-gray-50/60 transition-colors">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-semibold text-gray-900">{{ svc.name }}</span>
                <span v-if="svc.is_popular" class="text-[9px] font-bold bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">Popular</span>
                <span v-if="!svc.is_active" class="text-[9px] font-bold bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">Inactive</span>
              </div>
              <div v-if="svc.description" class="text-[10px] text-gray-400 mt-0.5 truncate max-w-sm">{{ svc.description }}</div>
              <div class="flex items-center gap-3 mt-1 text-[10px] text-gray-500">
                <span v-if="svc.price_from">KES {{ svc.price_from?.toLocaleString() }}{{ svc.price_to ? ' – ' + svc.price_to?.toLocaleString() : '+' }}</span>
                <span v-if="svc.duration_minutes">⏱ {{ formatDuration(svc.duration_minutes) }}</span>
              </div>
            </div>
            <div class="flex items-center gap-2 ml-4 shrink-0">
              <button @click="toggleServiceActive(svc)"
                :class="['text-[10px] font-bold px-2 py-1 rounded-lg border transition-colors',
                  svc.is_active ? 'border-gray-200 text-gray-500 hover:border-red-200 hover:text-red-600' : 'border-green-200 text-green-600 hover:bg-green-50']">
                {{ svc.is_active ? 'Deactivate' : 'Activate' }}
              </button>
              <button @click="openEditService(svc)" class="text-xs font-semibold text-red-600 hover:underline">Edit</button>
            </div>
          </div>
          <div v-if="!servicesInCategory(cat.id).length" class="px-5 py-4 text-xs text-gray-400 italic">No services in this category yet.</div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         SYSTEM
    ══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'system'" class="mx-4 md:mx-6 space-y-4">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-bold text-gray-900">KopoKopo Mobile Money</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Till Number</label>
            <input v-model="settings.kopokopo_till_number" class="input-base font-mono" placeholder="K000000">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Environment</label>
            <select v-model="settings.kopokopo_environment" class="input-base">
              <option value="sandbox">Sandbox (Testing)</option>
              <option value="production">Production (Live)</option>
            </select>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Client ID</label>
            <input v-model="settings.kopokopo_client_id" class="input-base font-mono" placeholder="KopoKopo client id">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Client Secret</label>
            <input v-model="settings.kopokopo_client_secret" type="password" class="input-base font-mono" placeholder="KopoKopo client secret">
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-bold text-gray-900">Invoice & Tax</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Default VAT %</label>
            <input v-model.number="settings.default_vat" type="number" min="0" max="100" class="input-base" placeholder="16">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Invoice Prefix</label>
            <input v-model="settings.invoice_prefix" class="input-base font-mono" placeholder="INV">
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         STAFF MEMBERS
    ══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'staff'" class="mx-4 md:mx-6 space-y-4">
      <div class="flex items-center justify-between">
        <div class="text-xs text-gray-500">{{ staffMembers.length }} staff member{{ staffMembers.length !== 1 ? 's' : '' }}</div>
        <button @click="openAddStaff"
          class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-xl">
          <PlusIcon class="w-3.5 h-3.5" /> Add Member
        </button>
      </div>

      <div v-if="loadingStaff" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center text-gray-400 text-xs">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading staff…
      </div>
      <div v-else-if="!staffMembers.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center text-gray-400 text-xs">
        No staff members yet.
      </div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="member in staffMembers" :key="member.id"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <div v-if="member.avatar_url"
                class="w-11 h-11 rounded-full object-cover shrink-0 overflow-hidden">
                <img :src="member.avatar_url" :alt="member.name" class="w-full h-full object-cover">
              </div>
              <div v-else
                class="w-11 h-11 rounded-full flex items-center justify-center text-white text-sm font-extrabold shrink-0"
                :style="`background:${member.avatar_color ?? '#DC2626'}`">
                {{ staffInitials(member.name) }}
              </div>
              <div>
                <div class="text-xs font-bold text-gray-900">{{ member.name }}</div>
                <div class="text-[10px] text-red-600 font-semibold mt-0.5">{{ member.role }}</div>
              </div>
            </div>
            <span :class="['text-[9px] font-bold px-2 py-0.5 rounded-full shrink-0',
              member.show_on_website ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
              {{ member.show_on_website ? 'Visible' : 'Hidden' }}
            </span>
          </div>
          <p v-if="member.bio" class="text-[10px] text-gray-500 leading-relaxed line-clamp-3">{{ member.bio }}</p>
          <div class="flex items-center gap-3 mt-auto pt-2 border-t border-gray-50">
            <div class="text-[10px] text-gray-400 flex-1">
              <span v-if="member.email">{{ member.email }}</span>
              <span v-if="member.phone" :class="member.email ? 'ml-2' : ''">{{ member.phone }}</span>
            </div>
            <button @click="openEditStaff(member)" class="text-xs font-semibold text-red-600 hover:underline shrink-0">Edit</button>
            <button @click="toggleStaffVisibility(member)"
              :class="['text-xs font-semibold shrink-0', member.show_on_website ? 'text-gray-400 hover:text-red-500' : 'text-green-600 hover:text-green-700']">
              {{ member.show_on_website ? 'Hide' : 'Show' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         REVIEWS
    ══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'reviews'" class="mx-4 md:mx-6 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="text-xs text-gray-500">{{ reviews.length }} review{{ reviews.length !== 1 ? 's' : '' }}</div>
          <!-- Filter -->
          <div class="flex gap-1">
            <button v-for="f in reviewFilters" :key="f.key" @click="reviewFilter = f.key"
              :class="['px-2.5 py-1 rounded-lg text-[10px] font-bold transition-colors',
                reviewFilter === f.key ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200']">
              {{ f.label }}
            </button>
          </div>
        </div>
        <button @click="openAddReview"
          class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-xl shrink-0">
          <PlusIcon class="w-3.5 h-3.5" /> Add Review
        </button>
      </div>

      <div v-if="loadingReviews" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center text-gray-400 text-xs">
        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
        Loading reviews…
      </div>
      <div v-else-if="!filteredReviews.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center text-gray-400 text-xs">
        No reviews found.
      </div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="review in filteredReviews" :key="review.id"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-col gap-3">
          <!-- Header -->
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                :style="`background:${review.reviewer_avatar_color ?? '#DC2626'}`">
                {{ review.reviewer_initials ?? staffInitials(review.reviewer_name) }}
              </div>
              <div>
                <div class="text-xs font-bold text-gray-900">{{ review.reviewer_name }}</div>
                <div class="flex items-center gap-1 mt-0.5">
                  <span v-for="n in 5" :key="n"
                    :class="['text-[10px]', n <= review.rating ? 'text-yellow-400' : 'text-gray-200']">★</span>
                  <span class="text-[9px] text-gray-400 ml-1">{{ review.rating }}/5</span>
                </div>
              </div>
            </div>
            <div class="flex flex-col items-end gap-1 shrink-0">
              <span :class="['text-[9px] font-bold px-2 py-0.5 rounded-full',
                review.status === 'approved' ? 'bg-green-100 text-green-700' :
                review.status === 'pending'  ? 'bg-yellow-100 text-yellow-700' :
                'bg-red-100 text-red-600']">
                {{ review.status }}
              </span>
              <span v-if="review.is_featured" class="text-[9px] font-bold bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">Featured</span>
            </div>
          </div>

          <!-- Body -->
          <p class="text-[11px] text-gray-600 leading-relaxed line-clamp-4 flex-1">{{ review.body }}</p>

          <!-- Meta -->
          <div class="flex items-center gap-2 text-[9px] text-gray-400 flex-wrap">
            <span class="capitalize px-1.5 py-0.5 bg-gray-100 rounded-md font-medium">{{ review.source }}</span>
            <span v-if="review.is_verified_customer" class="text-green-600 font-semibold">✓ Verified</span>
            <span>{{ formatReviewDate(review.reviewed_at) }}</span>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-2 border-t border-gray-50 gap-2">
            <div class="flex gap-2">
              <button v-if="review.status !== 'approved'" @click="updateReviewStatus(review, 'approved')"
                class="text-[10px] font-bold text-green-600 hover:underline">Approve</button>
              <button v-if="review.status !== 'rejected'" @click="updateReviewStatus(review, 'rejected')"
                class="text-[10px] font-bold text-red-500 hover:underline">Reject</button>
            </div>
            <div class="flex gap-2 shrink-0">
              <button @click="toggleReviewFeatured(review)"
                :class="['text-[10px] font-semibold', review.is_featured ? 'text-blue-600 hover:text-gray-500' : 'text-gray-400 hover:text-blue-600']">
                {{ review.is_featured ? 'Unfeature' : 'Feature' }}
              </button>
              <button @click="openEditReview(review)" class="text-[10px] font-semibold text-red-600 hover:underline">Edit</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         MODAL: ADD / EDIT USER
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showUserForm" :title="editingUser ? 'Edit User' : 'Add User'" size="md">
      <form class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Full Name <span class="text-red-500">*</span></label>
            <input v-model="userForm.name" required class="input-base" placeholder="Jane Wanjiku">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Email <span class="text-red-500">*</span></label>
            <input v-model="userForm.email" type="email" required class="input-base" placeholder="jane@premaxautocare.co.ke">
          </div>
          <div class="flex items-center gap-3 sm:col-span-2">
            <button type="button" @click="userForm.is_active = !userForm.is_active"
              :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors', userForm.is_active ? 'bg-red-600' : 'bg-gray-200']">
              <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow', userForm.is_active ? 'translate-x-4' : 'translate-x-1']" />
            </button>
            <label class="text-xs font-semibold text-gray-600">Account Active</label>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">
              Password <span v-if="!editingUser" class="text-red-500">*</span>
              <span v-else class="text-gray-400 font-normal">(leave blank to keep current)</span>
            </label>
            <input v-model="userForm.password" type="password" class="input-base"
              :placeholder="editingUser ? 'Leave blank to keep current' : 'Minimum 8 characters'">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Confirm Password</label>
            <input v-model="userForm.password_confirmation" type="password" class="input-base" placeholder="Repeat password">
          </div>
        </div>

        <div class="space-y-3">
          <div class="text-xs font-semibold text-gray-600">Assigned Roles <span class="text-red-500">*</span></div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label v-for="role in rolesCatalog" :key="role.id"
              class="border rounded-2xl p-3 cursor-pointer transition-colors"
              :class="userForm.role_ids.includes(role.id) ? 'border-red-300 bg-red-50' : 'border-gray-200 hover:border-gray-300'">
              <div class="flex items-start gap-3">
                <input type="checkbox" :checked="userForm.role_ids.includes(role.id)"
                  @change="toggleRole(role.id)" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                <div>
                  <div class="text-xs font-bold text-gray-900">{{ role.name }}</div>
                  <div class="text-[11px] text-gray-500 mt-1">{{ role.description }}</div>
                </div>
              </div>
            </label>
          </div>
        </div>

        <div class="space-y-3">
          <div>
            <div class="text-xs font-semibold text-gray-600">Direct Permissions</div>
            <div class="text-[11px] text-gray-400 mt-1">Use direct permissions to grant exceptions across roles for this specific user.</div>
          </div>
          <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
            <div v-for="group in permissionGroups" :key="group.name" class="border border-gray-100 rounded-2xl p-3">
              <div class="text-xs font-bold text-gray-900 mb-2">{{ group.name }}</div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label v-for="permission in group.items" :key="permission.id"
                  class="flex items-start gap-2 rounded-xl px-2 py-2 hover:bg-gray-50">
                  <input type="checkbox" :checked="userForm.permission_ids.includes(permission.id)"
                    @change="togglePermission(permission.id)" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                  <div>
                    <div class="text-xs font-semibold text-gray-700">{{ permission.name }}</div>
                    <div class="text-[10px] text-gray-400">{{ permission.description }}</div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div v-if="editingUser" class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 text-xs text-gray-600">
          <div><span class="font-semibold text-gray-800">2FA status:</span> {{ editingUser.two_factor_enabled ? 'Configured' : 'Pending setup on next sign-in' }}</div>
          <div class="mt-1"><span class="font-semibold text-gray-800">Effective permissions:</span> {{ editingUser.permission_slugs?.length ?? 0 }}</div>
        </div>
        <div v-if="userFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ userFormError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-between items-center w-full">
          <div class="flex items-center gap-3">
            <button v-if="editingUser" @click="sendUserPasswordReset(editingUser)"
              class="text-xs font-semibold text-blue-700 hover:underline">
              Send Password Reset Link
            </button>
            <button v-if="editingUser && editingUser.two_factor_enabled" @click="resetUserTwoFactor(editingUser)"
              class="text-xs font-semibold text-amber-700 hover:underline">
              Reset 2FA
            </button>
          </div>
          <div class="flex justify-end gap-2">
          <button @click="showUserForm = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="saveUser" :disabled="savingUser"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ savingUser ? 'Saving…' : editingUser ? 'Save Changes' : 'Add User' }}
          </button>
          </div>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: ADD / EDIT SERVICE
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showServiceForm" :title="editingService ? 'Edit Service' : 'Add Service'" size="lg">
      <form class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Service Name <span class="text-red-500">*</span></label>
            <input v-model="serviceForm.name" required class="input-base" placeholder="e.g. Engine Oil & Filter Change">
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Description</label>
            <textarea v-model="serviceForm.description" rows="2" class="input-base resize-none" placeholder="Brief description shown to customers…" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Category <span class="text-red-500">*</span></label>
            <select v-model="serviceForm.service_category_id" class="input-base">
              <option value="">Select category…</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Duration (minutes)</label>
            <input v-model.number="serviceForm.duration_minutes" type="number" min="0" class="input-base" placeholder="30">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Price From (KES)</label>
            <input v-model.number="serviceForm.price_from" type="number" min="0" class="input-base" placeholder="1500">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Price To (KES) <span class="text-gray-400 font-normal">(leave blank for fixed)</span></label>
            <input v-model.number="serviceForm.price_to" type="number" min="0" class="input-base" placeholder="Optional">
          </div>
          <div class="flex items-center gap-3 sm:col-span-2">
            <button type="button" @click="serviceForm.requires_deposit = !serviceForm.requires_deposit"
              :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors', serviceForm.requires_deposit ? 'bg-amber-500' : 'bg-gray-200']">
              <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow', serviceForm.requires_deposit ? 'translate-x-4' : 'translate-x-1']" />
            </button>
            <label class="text-xs font-semibold text-gray-600">Require Booking Deposit</label>
          </div>
          <div v-if="serviceForm.requires_deposit" class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Deposit Percentage</label>
            <input v-model.number="serviceForm.deposit_percent" type="number" min="1" max="100" class="input-base" placeholder="30">
          </div>
          <div class="flex items-center gap-3 sm:col-span-2">
            <button type="button" @click="serviceForm.is_popular = !serviceForm.is_popular"
              :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors', serviceForm.is_popular ? 'bg-red-600' : 'bg-gray-200']">
              <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow', serviceForm.is_popular ? 'translate-x-4' : 'translate-x-1']" />
            </button>
            <label class="text-xs font-semibold text-gray-600">Mark as Popular</label>
          </div>
        </div>
        <div v-if="serviceFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ serviceFormError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showServiceForm = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="saveService" :disabled="savingService"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ savingService ? 'Saving…' : editingService ? 'Save Changes' : 'Add Service' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: ADD / EDIT CATEGORY
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showCategoryForm" :title="editingCategory ? 'Edit Category' : 'Add Category'" size="sm">
      <form class="space-y-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Category Name <span class="text-red-500">*</span></label>
          <input v-model="categoryForm.name" required class="input-base" placeholder="e.g. Tyre Services">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Description</label>
          <textarea v-model="categoryForm.description" rows="2" class="input-base resize-none" placeholder="Brief description…" />
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-gray-600">Colour</label>
          <div class="flex items-center gap-3">
            <input v-model="categoryForm.color" type="color" class="w-10 h-9 rounded-lg border border-gray-200 cursor-pointer">
            <input v-model="categoryForm.color" class="input-base font-mono flex-1" placeholder="#DC2626">
          </div>
        </div>
        <div v-if="categoryFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ categoryFormError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showCategoryForm = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
          <button @click="saveCategory" :disabled="savingCategory"
            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ savingCategory ? 'Saving…' : editingCategory ? 'Save Changes' : 'Add Category' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: ADD / EDIT STAFF MEMBER
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showStaffForm" :title="editingStaff ? 'Edit Staff Member' : 'Add Staff Member'" size="md">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Full Name <span class="text-red-500">*</span></label>
            <input v-model="staffForm.name" class="input-base" placeholder="e.g. James Mwangi">
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Role / Title <span class="text-red-500">*</span></label>
            <input v-model="staffForm.role" class="input-base" placeholder="e.g. Lead Technician">
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Bio</label>
            <textarea v-model="staffForm.bio" rows="3" class="input-base resize-none"
              placeholder="Brief bio shown on the website…" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Email</label>
            <input v-model="staffForm.email" type="email" class="input-base" placeholder="james@premaxautocare.co.ke">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Phone</label>
            <input v-model="staffForm.phone" class="input-base" placeholder="+254 700 000000">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Avatar Colour</label>
            <div class="flex items-center gap-2">
              <input v-model="staffForm.avatar_color" type="color" class="w-10 h-9 rounded-lg border border-gray-200 cursor-pointer">
              <input v-model="staffForm.avatar_color" class="input-base font-mono flex-1" placeholder="#DC2626">
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Sort Order</label>
            <input v-model.number="staffForm.sort_order" type="number" min="1" class="input-base" placeholder="1">
          </div>
          <div class="flex items-center gap-3 sm:col-span-2">
            <button type="button" @click="staffForm.show_on_website = !staffForm.show_on_website"
              :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors', staffForm.show_on_website ? 'bg-red-600' : 'bg-gray-200']">
              <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow', staffForm.show_on_website ? 'translate-x-4' : 'translate-x-1']" />
            </button>
            <label class="text-xs font-semibold text-gray-600">Show on Website</label>
          </div>
        </div>
        <div v-if="staffFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ staffFormError }}</div>
      </div>
      <template #footer>
        <div class="flex justify-between items-center w-full">
          <button v-if="editingStaff" @click="deleteStaff(editingStaff)"
            class="text-xs font-semibold text-red-500 hover:underline">Delete</button>
          <div v-else />
          <div class="flex gap-2">
            <button @click="showStaffForm = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
            <button @click="saveStaff" :disabled="savingStaff"
              class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
              {{ savingStaff ? 'Saving…' : editingStaff ? 'Save Changes' : 'Add Member' }}
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <!-- ══════════════════════════════════════════════════
         MODAL: ADD / EDIT REVIEW
    ══════════════════════════════════════════════════ -->
    <Modal v-model="showReviewForm" :title="editingReview ? 'Edit Review' : 'Add Review'" size="md">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Reviewer Name <span class="text-red-500">*</span></label>
            <input v-model="reviewForm.reviewer_name" class="input-base" placeholder="e.g. David Ochieng">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Source</label>
            <select v-model="reviewForm.source" class="input-base">
              <option value="website">Website</option>
              <option value="google">Google</option>
              <option value="facebook">Facebook</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="walk_in">Walk-in</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-xs font-semibold text-gray-600">Review Body <span class="text-red-500">*</span></label>
            <textarea v-model="reviewForm.body" rows="4" class="input-base resize-none"
              placeholder="Customer's feedback…" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Rating</label>
            <div class="flex gap-2 pt-1">
              <button v-for="n in 5" :key="n" type="button" @click="reviewForm.rating = n"
                :class="['text-xl transition-transform hover:scale-110', n <= reviewForm.rating ? 'text-yellow-400' : 'text-gray-200']">★</button>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Status</label>
            <select v-model="reviewForm.status" class="input-base">
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Avatar Colour</label>
            <div class="flex items-center gap-2">
              <input v-model="reviewForm.reviewer_avatar_color" type="color" class="w-10 h-9 rounded-lg border border-gray-200 cursor-pointer">
              <input v-model="reviewForm.reviewer_avatar_color" class="input-base font-mono flex-1" placeholder="#DC2626">
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Reviewed At</label>
            <input v-model="reviewForm.reviewed_at" type="date" class="input-base">
          </div>
          <div class="flex items-center gap-3">
            <button type="button" @click="reviewForm.is_featured = !reviewForm.is_featured"
              :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors', reviewForm.is_featured ? 'bg-red-600' : 'bg-gray-200']">
              <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow', reviewForm.is_featured ? 'translate-x-4' : 'translate-x-1']" />
            </button>
            <label class="text-xs font-semibold text-gray-600">Featured</label>
          </div>
          <div class="flex items-center gap-3">
            <button type="button" @click="reviewForm.is_verified_customer = !reviewForm.is_verified_customer"
              :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors', reviewForm.is_verified_customer ? 'bg-red-600' : 'bg-gray-200']">
              <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow', reviewForm.is_verified_customer ? 'translate-x-4' : 'translate-x-1']" />
            </button>
            <label class="text-xs font-semibold text-gray-600">Verified Customer</label>
          </div>
        </div>
        <div v-if="reviewFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ reviewFormError }}</div>
      </div>
      <template #footer>
        <div class="flex justify-between items-center w-full">
          <button v-if="editingReview" @click="deleteReview(editingReview)"
            class="text-xs font-semibold text-red-500 hover:underline">Delete</button>
          <div v-else />
          <div class="flex gap-2">
            <button @click="showReviewForm = false" class="px-4 py-2 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
            <button @click="saveReview" :disabled="savingReview"
              class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-60">
              {{ savingReview ? 'Saving…' : editingReview ? 'Save Changes' : 'Add Review' }}
            </button>
          </div>
        </div>
      </template>
    </Modal>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { useApi }        from '@/composables/useApi'
import { useAuthStore }  from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import PageHeader        from '@/components/PageHeader.vue'
import Modal             from '@/components/Modal.vue'

const { get, post, put, patch, destroy } = useApi()
const auth  = useAuthStore()
const toast = useToastStore()

const tabs = [
  { key: 'profile',  label: 'My Profile' },
  { key: 'users',    label: 'Users' },
  { key: 'contact',  label: 'Contact Info' },
  { key: 'social',   label: 'Social Media' },
  { key: 'hours',    label: 'Business Hours' },
  { key: 'services', label: 'Services' },
  { key: 'staff',    label: 'Staff Members' },
  { key: 'reviews',  label: 'Reviews' },
  { key: 'system',   label: 'System' },
]
const activeTab = ref('profile')

// ── My Profile ─────────────────────────────────────────────────────────────────
const profileForm    = ref({ name:'', email:'', current_password:'', new_password:'', new_password_confirmation:'' })
const savingProfile  = ref(false)
const profileError   = ref(null)
const profileSuccess = ref(false)

const profileInitials = computed(() =>
  auth.user?.name?.split(' ').slice(0,2).map(w => w[0]?.toUpperCase()).join('') ?? '?'
)

watch(activeTab, tab => {
  if (tab === 'profile') {
    profileForm.value = { name: auth.user?.name ?? '', email: auth.user?.email ?? '', current_password:'', new_password:'', new_password_confirmation:'' }
    profileError.value = null; profileSuccess.value = false
  }
  if (tab === 'users') {
    if (!users.value.length) loadUsers()
    if (!rolesCatalog.value.length || !permissionsCatalog.value.length) loadUserMeta()
  }
  if (tab === 'staff'   && !staffMembers.value.length) loadStaff()
  if (tab === 'reviews' && !reviews.value.length)      loadReviews()
})

async function saveProfile() {
  savingProfile.value = true; profileError.value = null; profileSuccess.value = false
  try {
    if (profileForm.value.new_password && profileForm.value.new_password !== profileForm.value.new_password_confirmation) {
      profileError.value = 'New passwords do not match.'; savingProfile.value = false; return
    }
    const payload = { name: profileForm.value.name, email: profileForm.value.email }
    if (profileForm.value.new_password) {
      payload.current_password      = profileForm.value.current_password
      payload.password              = profileForm.value.new_password
      payload.password_confirmation = profileForm.value.new_password_confirmation
    }
    await put('/admin/profile', payload)
    auth.user.name = profileForm.value.name; auth.user.email = profileForm.value.email
    profileSuccess.value = true; toast.success('Profile updated.')
    profileForm.value.current_password = ''; profileForm.value.new_password = ''; profileForm.value.new_password_confirmation = ''
  } catch (e) { profileError.value = e.response?.data?.message ?? 'Failed to update profile.' }
  finally { savingProfile.value = false }
}

// ── Users ──────────────────────────────────────────────────────────────────────
const users         = ref([])
const loadingUsers  = ref(false)
const showUserForm  = ref(false)
const savingUser    = ref(false)
const editingUser   = ref(null)
const userFormError = ref(null)
const rolesCatalog  = ref([])
const permissionsCatalog = ref([])
const userForm      = ref({ name:'', email:'', is_active:true, role_ids:[], permission_ids:[], password:'', password_confirmation:'' })

const initials    = name => name?.split(' ').slice(0,2).map(w => w[0]?.toUpperCase()).join('') ?? '?'
const colors      = ['#EF4444','#3B82F6','#22C55E','#A855F7','#F97316','#EC4899','#14B8A6','#EAB308']
const avatarColor = name => colors[(name?.charCodeAt(0) ?? 0) % colors.length]
const permissionGroups = computed(() => {
  const groups = new Map()
  for (const permission of permissionsCatalog.value) {
    const group = permission.group_name || 'Other'
    if (!groups.has(group)) groups.set(group, [])
    groups.get(group).push(permission)
  }
  return Array.from(groups.entries()).map(([name, items]) => ({ name, items }))
})

async function loadUsers() {
  loadingUsers.value = true
  try { users.value = await get('/admin/users') ?? [] } catch {}
  finally { loadingUsers.value = false }
}
async function loadUserMeta() {
  try {
    const meta = await get('/admin/users/meta')
    rolesCatalog.value = meta?.roles ?? []
    permissionsCatalog.value = meta?.permissions ?? []
  } catch {}
}
function openAddUser() {
  editingUser.value = null
  userForm.value = { name:'', email:'', is_active:true, role_ids:[], permission_ids:[], password:'', password_confirmation:'' }
  userFormError.value = null
  showUserForm.value = true
}
function openEditUser(u) {
  editingUser.value = u
  userForm.value = {
    name: u.name,
    email: u.email,
    is_active: u.is_active !== false,
    role_ids: u.roles?.map(role => role.id) ?? [],
    permission_ids: u.direct_permissions?.map(permission => permission.id) ?? [],
    password: '',
    password_confirmation: '',
  }
  userFormError.value = null
  showUserForm.value = true
}
function toggleRole(roleId) {
  userForm.value.role_ids = userForm.value.role_ids.includes(roleId)
    ? userForm.value.role_ids.filter(id => id !== roleId)
    : [...userForm.value.role_ids, roleId]
}
function togglePermission(permissionId) {
  userForm.value.permission_ids = userForm.value.permission_ids.includes(permissionId)
    ? userForm.value.permission_ids.filter(id => id !== permissionId)
    : [...userForm.value.permission_ids, permissionId]
}
async function saveUser() {
  savingUser.value=true; userFormError.value=null
  try {
    if (!userForm.value.role_ids.length) {
      userFormError.value = 'Please assign at least one role.'
      savingUser.value = false
      return
    }
    const p={
      name:userForm.value.name,
      email:userForm.value.email,
      is_active:userForm.value.is_active,
      role_ids:userForm.value.role_ids,
      permission_ids:userForm.value.permission_ids,
    }
    if (userForm.value.password) {
      if (userForm.value.password!==userForm.value.password_confirmation) { userFormError.value='Passwords do not match.'; savingUser.value=false; return }
      p.password=userForm.value.password; p.password_confirmation=userForm.value.password_confirmation
    } else if (!editingUser.value) { userFormError.value='Password is required for new users.'; savingUser.value=false; return }
    if (editingUser.value) { const u=await put(`/admin/users/${editingUser.value.id}`,p); const i=users.value.findIndex(x=>x.id===editingUser.value.id); if(i>-1) users.value[i]={...users.value[i],...u}; toast.success('User updated.') }
    else { const u=await post('/admin/users',p); users.value.push(u); toast.success('User added.') }
    showUserForm.value=false
  } catch(e) { userFormError.value=e.response?.data?.message??'Failed to save user.' }
  finally { savingUser.value=false }
}
async function toggleUserActive(user) {
  try { await patch(`/admin/users/${user.id}`,{is_active:!user.is_active}); user.is_active=!user.is_active; toast.success(user.is_active?'User activated.':'User deactivated.') }
  catch { toast.error('Failed to update user.') }
}
async function resetUserTwoFactor(user) {
  try {
    await post(`/admin/users/${user.id}/reset-2fa`)
    user.two_factor_enabled = false
    if (editingUser.value?.id === user.id) editingUser.value.two_factor_enabled = false
    toast.success('2FA reset. The user must set it up again on next login.')
  } catch (e) {
    userFormError.value = e.response?.data?.message ?? 'Failed to reset 2FA.'
  }
}
async function sendUserPasswordReset(user) {
  try {
    await post(`/admin/users/${user.id}/send-password-reset`)
    toast.success('Password reset link sent to the user email.')
  } catch (e) {
    userFormError.value = e.response?.data?.message ?? 'Failed to send password reset link.'
  }
}

// ── Contact ────────────────────────────────────────────────────────────────────
const contact = ref({ phone_primary:'',phone_secondary:'',phone_whatsapp:'',email_primary:'',email_support:'',facebook_url:'',instagram_url:'',twitter_url:'',tiktok_url:'',youtube_url:'',linkedin_url:'',website_url:'',street_address:'',landmark:'',building:'',area:'',city:'',county:'',po_box:'',postal_code:'',latitude:null,longitude:null,map_zoom:16,google_maps_url:'',what3words:'' })
const businessHours = ref({ monday:{open:'07:30',close:'18:00',closed:false},tuesday:{open:'07:30',close:'18:00',closed:false},wednesday:{open:'07:30',close:'18:00',closed:false},thursday:{open:'07:30',close:'18:00',closed:false},friday:{open:'07:30',close:'18:00',closed:false},saturday:{open:'08:00',close:'17:00',closed:false},sunday:{open:null,close:null,closed:true} })
const days = [{key:'monday',label:'Monday'},{key:'tuesday',label:'Tuesday'},{key:'wednesday',label:'Wednesday'},{key:'thursday',label:'Thursday'},{key:'friday',label:'Friday'},{key:'saturday',label:'Saturday'},{key:'sunday',label:'Sunday'}]
const socialFields = [
  {key:'facebook_url', label:'Facebook',   icon:'f', bg:'bg-blue-600', placeholder:'https://facebook.com/premaxautocare'},
  {key:'instagram_url',label:'Instagram',  icon:'📷',bg:'bg-pink-600', placeholder:'https://instagram.com/premaxautocare'},
  {key:'twitter_url',  label:'X (Twitter)',icon:'𝕏', bg:'bg-gray-900', placeholder:'https://x.com/premaxautocare'},
  {key:'tiktok_url',   label:'TikTok',     icon:'♪', bg:'bg-gray-800', placeholder:'https://tiktok.com/@premaxautocare'},
  {key:'youtube_url',  label:'YouTube',    icon:'▶', bg:'bg-red-600',  placeholder:'https://youtube.com/@premaxautocare'},
  {key:'linkedin_url', label:'LinkedIn',   icon:'in',bg:'bg-blue-700', placeholder:'https://linkedin.com/company/premaxautocare'},
  {key:'website_url',  label:'Website',    icon:'🌐',bg:'bg-gray-600', placeholder:'https://premaxautocare.co.ke'},
]
function toggleDayClosed(day) { businessHours.value[day].closed = !businessHours.value[day].closed }

// ── System settings ────────────────────────────────────────────────────────────
const settings = ref({ kopokopo_till_number:'',kopokopo_environment:'sandbox',kopokopo_client_id:'',kopokopo_client_secret:'',default_vat:16,invoice_prefix:'INV' })

// ── Services ───────────────────────────────────────────────────────────────────
const categories  = ref([])
const allServices = ref([])
const saving      = ref(false)
const showServiceForm   = ref(false); const showCategoryForm  = ref(false)
const savingService     = ref(false); const savingCategory    = ref(false)
const editingService    = ref(null);  const editingCategory   = ref(null)
const serviceFormError  = ref(null);  const categoryFormError = ref(null)
const serviceForm  = ref({name:'',description:'',service_category_id:'',price_from:null,price_to:null,duration_minutes:null,requires_deposit:false,deposit_percent:null,is_popular:false,is_active:true})
const categoryForm = ref({name:'',description:'',color:'#DC2626'})
const servicesInCategory = id => allServices.value.filter(s => s.service_category_id === id)
const formatDuration     = m => m >= 60 ? `${Math.floor(m/60)}h${m%60?' '+m%60+'m':''}` : `${m}m`

// ── Staff Members ──────────────────────────────────────────────────────────────
const staffMembers   = ref([])
const loadingStaff   = ref(false)
const showStaffForm  = ref(false)
const savingStaff    = ref(false)
const editingStaff   = ref(null)
const staffFormError = ref(null)
const staffForm      = ref({ name:'', role:'', bio:'', email:'', phone:'', avatar_color:'#DC2626', sort_order:1, show_on_website:true })

const staffInitials = name => name?.split(' ').slice(0,2).map(w => w[0]?.toUpperCase()).join('') ?? '?'

async function loadStaff() {
  loadingStaff.value = true
  try { staffMembers.value = await get('/admin/staff-members') ?? [] } catch {}
  finally { loadingStaff.value = false }
}

function openAddStaff() {
  editingStaff.value = null
  staffForm.value = { name:'', role:'', bio:'', email:'', phone:'', avatar_color:'#DC2626', sort_order: staffMembers.value.length + 1, show_on_website:true }
  staffFormError.value = null; showStaffForm.value = true
}

function openEditStaff(member) {
  editingStaff.value = member
  staffForm.value = { name:member.name, role:member.role, bio:member.bio??'', email:member.email??'', phone:member.phone??'', avatar_color:member.avatar_color??'#DC2626', sort_order:member.sort_order??1, show_on_website:member.show_on_website }
  staffFormError.value = null; showStaffForm.value = true
}

async function saveStaff() {
  if (!staffForm.value.name || !staffForm.value.role) { staffFormError.value = 'Name and role are required.'; return }
  savingStaff.value = true; staffFormError.value = null
  try {
    if (editingStaff.value) {
      const u = await put(`/admin/staff-members/${editingStaff.value.id}`, staffForm.value)
      const i = staffMembers.value.findIndex(s => s.id === editingStaff.value.id)
      if (i > -1) staffMembers.value[i] = { ...staffMembers.value[i], ...u }
      toast.success('Staff member updated.')
    } else {
      const s = await post('/admin/staff-members', staffForm.value)
      staffMembers.value.push(s); toast.success('Staff member added.')
    }
    showStaffForm.value = false
  } catch (e) { staffFormError.value = e.response?.data?.message ?? 'Failed to save.' }
  finally { savingStaff.value = false }
}

async function toggleStaffVisibility(member) {
  try {
    await patch(`/admin/staff-members/${member.id}`, { show_on_website: !member.show_on_website })
    member.show_on_website = !member.show_on_website
    toast.success(member.show_on_website ? 'Now visible on website.' : 'Hidden from website.')
  } catch { toast.error('Failed to update.') }
}

async function deleteStaff(member) {
  if (!confirm(`Remove ${member.name} from staff?`)) return
  try {
    await destroy(`/admin/staff-members/${member.id}`)
    staffMembers.value = staffMembers.value.filter(s => s.id !== member.id)
    showStaffForm.value = false; toast.success('Staff member removed.')
  } catch { toast.error('Failed to delete.') }
}

// ── Reviews ────────────────────────────────────────────────────────────────────
const reviews        = ref([])
const loadingReviews = ref(false)
const showReviewForm = ref(false)
const savingReview   = ref(false)
const editingReview  = ref(null)
const reviewFormError = ref(null)
const reviewFilter   = ref('all')

const reviewFilters = [
  { key: 'all',      label: 'All' },
  { key: 'approved', label: 'Approved' },
  { key: 'pending',  label: 'Pending' },
  { key: 'featured', label: 'Featured' },
]

const reviewForm = ref({
  reviewer_name: '', reviewer_avatar_color: '#DC2626',
  rating: 5, body: '', source: 'website',
  status: 'approved', is_featured: false,
  is_verified_customer: true, reviewed_at: new Date().toISOString().split('T')[0],
})

const filteredReviews = computed(() => {
  if (reviewFilter.value === 'all')      return reviews.value
  if (reviewFilter.value === 'featured') return reviews.value.filter(r => r.is_featured)
  return reviews.value.filter(r => r.status === reviewFilter.value)
})

const formatReviewDate = d => d ? new Date(d).toLocaleDateString('en-KE', { day:'numeric', month:'short', year:'numeric' }) : ''

async function loadReviews() {
  loadingReviews.value = true
  try { reviews.value = await get('/admin/reviews') ?? [] } catch {}
  finally { loadingReviews.value = false }
}

function openAddReview() {
  editingReview.value = null
  reviewForm.value = { reviewer_name:'', reviewer_avatar_color:'#DC2626', rating:5, body:'', source:'website', status:'approved', is_featured:false, is_verified_customer:true, reviewed_at: new Date().toISOString().split('T')[0] }
  reviewFormError.value = null; showReviewForm.value = true
}

function openEditReview(review) {
  editingReview.value = review
  reviewForm.value = {
    reviewer_name:        review.reviewer_name,
    reviewer_avatar_color:review.reviewer_avatar_color ?? '#DC2626',
    rating:               review.rating,
    body:                 review.body,
    source:               review.source ?? 'website',
    status:               review.status ?? 'approved',
    is_featured:          review.is_featured,
    is_verified_customer: review.is_verified_customer,
    reviewed_at:          review.reviewed_at ? review.reviewed_at.split('T')[0] : new Date().toISOString().split('T')[0],
  }
  reviewFormError.value = null; showReviewForm.value = true
}

async function saveReview() {
  if (!reviewForm.value.reviewer_name || !reviewForm.value.body) { reviewFormError.value = 'Name and review body are required.'; return }
  savingReview.value = true; reviewFormError.value = null
  try {
    if (editingReview.value) {
      const u = await put(`/admin/reviews/${editingReview.value.id}`, reviewForm.value)
      const i = reviews.value.findIndex(r => r.id === editingReview.value.id)
      if (i > -1) reviews.value[i] = { ...reviews.value[i], ...u }
      toast.success('Review updated.')
    } else {
      const r = await post('/admin/reviews', reviewForm.value)
      reviews.value.unshift(r); toast.success('Review added.')
    }
    showReviewForm.value = false
  } catch (e) { reviewFormError.value = e.response?.data?.message ?? 'Failed to save.' }
  finally { savingReview.value = false }
}

async function updateReviewStatus(review, status) {
  try {
    await patch(`/admin/reviews/${review.id}`, { status })
    review.status = status
    toast.success(status === 'approved' ? 'Review approved.' : 'Review rejected.')
  } catch { toast.error('Failed to update.') }
}

async function toggleReviewFeatured(review) {
  try {
    await patch(`/admin/reviews/${review.id}`, { is_featured: !review.is_featured })
    review.is_featured = !review.is_featured
    toast.success(review.is_featured ? 'Review featured.' : 'Review unfeatured.')
  } catch { toast.error('Failed to update.') }
}

async function deleteReview(review) {
  if (!confirm('Delete this review?')) return
  try {
    await destroy(`/admin/reviews/${review.id}`)
    reviews.value = reviews.value.filter(r => r.id !== review.id)
    showReviewForm.value = false; toast.success('Review deleted.')
  } catch { toast.error('Failed to delete.') }
}

// ── Load ───────────────────────────────────────────────────────────────────────
async function loadContact() {
  try {
    const data = await get('/admin/settings/contact')
    if (!data) return
    Object.keys(contact.value).forEach(k => { if (data[k] !== undefined) contact.value[k] = data[k] })
    if (data.business_hours) businessHours.value = typeof data.business_hours === 'string' ? JSON.parse(data.business_hours) : data.business_hours
  } catch {}
}

async function loadSettings() {
  try { const d = await get('/admin/settings'); if (d) Object.assign(settings.value, d) } catch {}
}

async function loadServices() {
  try {
    const [cats, svcs] = await Promise.all([get('/admin/service-categories'), get('/admin/services', { per_page:200 })])
    categories.value  = cats ?? []
    allServices.value = svcs?.data ?? svcs ?? []
  } catch {}
}

async function saveAll() {
  saving.value = true
  try {
    if (['contact','social','hours'].includes(activeTab.value)) {
      await put('/admin/settings/contact', { ...contact.value, business_hours: businessHours.value })
      toast.success('Settings saved.')
    }
    if (activeTab.value === 'system') { await post('/admin/settings', settings.value); toast.success('System settings saved.') }
  } catch (e) { toast.error(e.response?.data?.message ?? 'Failed to save.') }
  finally { saving.value = false }
}

// ── Service CRUD ───────────────────────────────────────────────────────────────
function openAddService(cat) { editingService.value=null; serviceForm.value={name:'',description:'',service_category_id:cat?.id??'',price_from:null,price_to:null,duration_minutes:null,requires_deposit:false,deposit_percent:null,is_popular:false,is_active:true}; serviceFormError.value=null; showServiceForm.value=true }
function openEditService(svc) { editingService.value=svc; serviceForm.value={name:svc.name,description:svc.description??'',service_category_id:svc.service_category_id,price_from:svc.price_from,price_to:svc.price_to,duration_minutes:svc.duration_minutes,requires_deposit:svc.requires_deposit,deposit_percent:svc.deposit_percent,is_popular:svc.is_popular,is_active:svc.is_active}; serviceFormError.value=null; showServiceForm.value=true }
async function saveService() {
  savingService.value=true; serviceFormError.value=null
  try {
    if (editingService.value) { const u=await put(`/admin/services/${editingService.value.id}`,serviceForm.value); const i=allServices.value.findIndex(s=>s.id===editingService.value.id); if(i>-1) allServices.value[i]=u; toast.success('Service updated.') }
    else { const c=await post('/admin/services',serviceForm.value); allServices.value.push(c); toast.success('Service added.') }
    showServiceForm.value=false
  } catch(e) { serviceFormError.value=e.response?.data?.message??'Failed.' }
  finally { savingService.value=false }
}
async function toggleServiceActive(svc) {
  try { const u=await patch(`/admin/services/${svc.id}`,{is_active:!svc.is_active}); svc.is_active=u.is_active; toast.success(svc.is_active?'Service activated.':'Service deactivated.') }
  catch { toast.error('Failed to update.') }
}
function openAddCategory() { editingCategory.value=null; categoryForm.value={name:'',description:'',color:'#DC2626'}; categoryFormError.value=null; showCategoryForm.value=true }
function openEditCategory(cat) { editingCategory.value=cat; categoryForm.value={name:cat.name,description:cat.description??'',color:cat.color??'#DC2626'}; categoryFormError.value=null; showCategoryForm.value=true }
async function saveCategory() {
  savingCategory.value=true; categoryFormError.value=null
  try {
    if (editingCategory.value) { const u=await put(`/admin/service-categories/${editingCategory.value.id}`,categoryForm.value); const i=categories.value.findIndex(c=>c.id===editingCategory.value.id); if(i>-1) categories.value[i]={...categories.value[i],...u}; toast.success('Category updated.') }
    else { const c=await post('/admin/service-categories',categoryForm.value); categories.value.push(c); toast.success('Category added.') }
    showCategoryForm.value=false
  } catch(e) { categoryFormError.value=e.response?.data?.message??'Failed.' }
  finally { savingCategory.value=false }
}

onMounted(() => { loadContact(); loadSettings(); loadServices(); loadUserMeta() })
</script>

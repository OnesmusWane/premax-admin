<template>
  <div class="min-h-screen bg-gray-950 flex items-center justify-center px-4">
    <div class="w-full max-w-sm">

      <!-- Logo -->
      <div class="flex flex-col items-center mb-8">
        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-lg mb-4"></div>
        <h1 class="text-white font-extrabold text-xl tracking-tight">Premax <span class="text-red-500">Admin</span></h1>
        <p class="text-gray-500 text-xs mt-1">Reset your password</p>
      </div>

      <!-- Card -->
      <div class="bg-gray-900 border border-white/10 rounded-2xl p-7 shadow-2xl">

        <div v-if="!validLink" class="space-y-4 text-center">
          <div class="w-12 h-12 bg-red-950 rounded-xl flex items-center justify-center mx-auto">
            <ExclamationTriangleIcon class="w-6 h-6 text-red-400" />
          </div>
          <div>
            <h2 class="text-white font-bold text-sm">Invalid or Expired Link</h2>
            <p class="text-xs text-gray-400 mt-2 leading-relaxed">This password reset link is invalid or has expired. Please request a new one.</p>
          </div>
          <router-link to="/login"
            class="block w-full text-center bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 rounded-xl transition-all">
            Back to Sign In
          </router-link>
        </div>

        <div v-else-if="succeeded" class="space-y-4 text-center">
          <div class="w-12 h-12 bg-emerald-950 rounded-xl flex items-center justify-center mx-auto">
            <CheckCircleIcon class="w-6 h-6 text-emerald-400" />
          </div>
          <div>
            <h2 class="text-white font-bold text-sm">Password Reset</h2>
            <p class="text-xs text-gray-400 mt-2 leading-relaxed">Your password has been updated. You can now sign in with your new password.</p>
          </div>
          <router-link to="/login"
            class="block w-full text-center bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 rounded-xl transition-all">
            Sign In
          </router-link>
        </div>

        <div v-else class="space-y-4">
          <div>
            <h2 class="text-white font-bold text-sm">Choose a New Password</h2>
            <p class="text-xs text-gray-400 mt-1">Setting a new password for <span class="text-white">{{ email }}</span>.</p>
          </div>

          <div v-if="error" class="bg-red-950 border border-red-800 text-red-400 text-xs rounded-xl px-4 py-3">
            {{ error }}
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">New Password</label>
            <div class="relative">
              <input v-model="form.password" :type="showPwd ? 'text' : 'password'" placeholder="At least 8 characters"
                class="w-full bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600
                       focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all pr-10">
              <button type="button" @click="showPwd = !showPwd"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                <EyeIcon v-if="!showPwd" class="w-4 h-4" />
                <EyeSlashIcon v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">Confirm New Password</label>
            <input v-model="form.password_confirmation" type="password" placeholder="Repeat new password"
              class="bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600
                     focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
          </div>

          <button @click="submit" :disabled="loading"
            class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold text-sm
                   py-3 rounded-xl transition-all flex items-center justify-center gap-2">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            {{ loading ? 'Resetting…' : 'Reset Password' }}
          </button>

          <router-link to="/login"
            class="block text-center text-xs text-gray-500 hover:text-gray-300 transition-colors pt-1">
            Back to Sign In
          </router-link>
        </div>

      </div>

      <p class="text-center text-gray-700 text-xs mt-6">
        Premax Autocare & Diagnostic Services Admin Panel
      </p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { EyeIcon, EyeSlashIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'

const auth  = useAuthStore()
const route = useRoute()

const email    = ref('')
const token    = ref('')
const validLink = ref(false)
const succeeded = ref(false)
const loading   = ref(false)
const error     = ref(null)
const showPwd   = ref(false)

const form = ref({ password: '', password_confirmation: '' })

onMounted(() => {
  email.value = String(route.query.email ?? '')
  token.value = String(route.query.reset_token ?? '')
  validLink.value = !!(email.value && token.value)
})

async function submit() {
  error.value = null
  if (!form.value.password || form.value.password.length < 8) {
    error.value = 'Password must be at least 8 characters.'
    return
  }
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match.'
    return
  }

  loading.value = true
  try {
    await auth.resetPassword({
      email: email.value,
      token: token.value,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    })
    succeeded.value = true
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to reset password. The link may have expired.'
  } finally {
    loading.value = false
  }
}
</script>

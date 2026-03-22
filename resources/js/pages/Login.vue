<template>
  <div class="min-h-screen bg-gray-950 flex items-center justify-center px-4">
    <div class="w-full max-w-sm">

      <!-- Logo -->
      <div class="flex flex-col items-center mb-8">
        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-lg mb-4">
          <!-- <img src="/assets/images/logos/logo.png" alt="Premax" class="w-12 h-12 object-contain"> -->
        </div>
        <h1 class="text-white font-extrabold text-xl tracking-tight">Premax <span class="text-red-500">Admin</span></h1>
        <p class="text-gray-500 text-xs mt-1">Sign in to your dashboard</p>
      </div>

      <!-- Card -->
      <div class="bg-gray-900 border border-white/10 rounded-2xl p-7 shadow-2xl">

        <!-- Error -->
        <div v-if="error" class="mb-4 bg-red-950 border border-red-800 text-red-400 text-xs rounded-xl px-4 py-3">
          {{ error }}
        </div>

        <form @submit.prevent="handleLogin" class="flex flex-col gap-4">

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">Email Address</label>
            <input v-model="form.email" type="email" required placeholder="admin@premaxautocare.co.ke"
              class="bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600
                     focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">Password</label>
            <div class="relative">
              <input v-model="form.password" :type="showPwd ? 'text' : 'password'" required placeholder="••••••••"
                class="w-full bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600
                       focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all pr-10">
              <button type="button" @click="showPwd = !showPwd"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                <EyeIcon v-if="!showPwd" class="w-4 h-4" />
                <EyeSlashIcon v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <button type="submit" :disabled="loading"
            class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold text-sm
                   py-3 rounded-xl transition-all duration-200 mt-1 flex items-center justify-center gap-2">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            {{ loading ? 'Signing in…' : 'Sign In' }}
          </button>

        </form>
      </div>

      <p class="text-center text-gray-700 text-xs mt-6">
        Premax Autocare & Diagnostic Services Admin Panel
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()

const form    = ref({ email: '', password: '' })
const loading = ref(false)
const error   = ref(null)
const showPwd = ref(false)

async function handleLogin() {
  loading.value = true
  error.value   = null
  try {
    await auth.login(form.value.email, form.value.password)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Login failed. Check your credentials.'
  } finally {
    loading.value = false
  }
}
</script>
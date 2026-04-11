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
        <div v-if="status" class="mb-4 bg-emerald-950 border border-emerald-800 text-emerald-300 text-xs rounded-xl px-4 py-3">
          {{ status }}
        </div>

        <div v-if="error" class="mb-4 bg-red-950 border border-red-800 text-red-400 text-xs rounded-xl px-4 py-3">
          {{ error }}
        </div>

        <form v-if="step === 'credentials'" @submit.prevent="handleLogin" class="flex flex-col gap-4">
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

          <button type="button" @click="openForgotPassword"
            class="text-xs text-gray-400 hover:text-white transition-colors">
            Forgot password?
          </button>
        </form>

        <div v-else-if="step === 'forgot'" class="space-y-4">
          <div>
            <h2 class="text-white font-bold text-sm">Reset Password</h2>
            <p class="text-xs text-gray-400 mt-1">Enter your email and we will send a reset link.</p>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">Email Address</label>
            <input v-model="resetEmail" type="email" required placeholder="admin@premaxautocare.co.ke"
              class="bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
          </div>
          <div class="flex gap-2">
            <button @click="resetLoginFlow"
              class="flex-1 border border-white/10 text-gray-300 text-sm font-semibold py-3 rounded-xl hover:bg-white/5 transition-all">
              Back
            </button>
            <button @click="sendPasswordResetLink" :disabled="loading"
              class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold text-sm py-3 rounded-xl transition-all">
              {{ loading ? 'Sending…' : 'Send Reset Link' }}
            </button>
          </div>
        </div>

        <div v-else-if="step === 'reset'" class="space-y-4">
          <div>
            <h2 class="text-white font-bold text-sm">Choose a New Password</h2>
            <p class="text-xs text-gray-400 mt-1">Set a new password for {{ resetEmail }}.</p>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">New Password</label>
            <input v-model="resetForm.password" type="password"
              class="bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">Confirm Password</label>
            <input v-model="resetForm.password_confirmation" type="password"
              class="bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
          </div>
          <button @click="submitPasswordReset" :disabled="loading"
            class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold text-sm py-3 rounded-xl transition-all">
            {{ loading ? 'Resetting…' : 'Reset Password' }}
          </button>
        </div>

        <div v-else-if="step === 'setup'" class="space-y-4">
          <div>
            <h2 class="text-white font-bold text-sm">Set Up Two-Factor Authentication</h2>
            <p class="text-xs text-gray-400 mt-1">Two-factor authentication is mandatory. You can either scan the QR code with an authenticator app or use the manual setup key below.</p>
          </div>

          <div class="bg-gray-800 border border-white/10 rounded-xl p-4 space-y-3">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center">
                <QrCodeIcon class="w-4 h-4 text-gray-200" />
              </div>
              <div>
                <div class="text-xs font-bold text-white">Scan With Authenticator App</div>
                <div class="text-[11px] text-gray-400">Google Authenticator, Microsoft Authenticator, Authy, 1Password, and similar apps will work.</div>
              </div>
            </div>

            <div class="bg-white rounded-2xl p-3 flex items-center justify-center">
              <img :src="setupQrUrl" alt="Two-factor setup QR code" class="w-52 h-52 object-contain rounded-xl">
            </div>

            <div class="flex gap-2">
              <a :href="setupUrl"
                class="px-3 py-2 text-xs font-semibold rounded-xl bg-white/5 text-gray-200 hover:bg-white/10">
                Open Setup Link
              </a>
              <button @click="copyText(setupUrl)"
                class="px-3 py-2 text-xs font-semibold rounded-xl bg-white/5 text-gray-200 hover:bg-white/10">
                Copy Setup URL
              </button>
            </div>
          </div>

          <div class="bg-gray-800 border border-white/10 rounded-xl p-4 space-y-3">
            <div>
              <div class="text-xs font-bold text-white">Manual Setup</div>
              <div class="text-[11px] text-gray-400 mt-1">If scanning is not available, enter this secret key manually in your authenticator app.</div>
            </div>
            <div>
              <div class="text-[10px] uppercase tracking-wide text-gray-500 font-bold">Manual Setup Key</div>
              <div class="mt-1 text-sm font-mono text-white break-all">{{ setupSecret }}</div>
            </div>
            <div class="flex gap-2">
              <button @click="copyText(setupSecret)"
                class="px-3 py-2 text-xs font-semibold rounded-xl bg-white/5 text-gray-200 hover:bg-white/10">Copy Key</button>
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">Authenticator Code</label>
            <input v-model="twoFactorCode" inputmode="numeric" maxlength="6" placeholder="123456"
              class="bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600
                     focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
          </div>

          <div class="flex gap-2">
            <button @click="resetLoginFlow"
              class="flex-1 border border-white/10 text-gray-300 text-sm font-semibold py-3 rounded-xl hover:bg-white/5 transition-all">
              Back
            </button>
            <button @click="completeTwoFactor" :disabled="loading"
              class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold text-sm py-3 rounded-xl transition-all flex items-center justify-center gap-2">
              <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              {{ loading ? 'Verifying…' : 'Verify & Continue' }}
            </button>
          </div>
        </div>

        <div v-else-if="step === 'verify'" class="space-y-4">
          <div>
            <h2 class="text-white font-bold text-sm">Enter Two-Factor Code</h2>
            <p class="text-xs text-gray-400 mt-1">Open your authenticator app for {{ challengeUserEmail || form.email }} and enter the current 6-digit code.</p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">Authenticator Code</label>
            <input v-model="twoFactorCode" inputmode="numeric" maxlength="6" placeholder="123456"
              class="bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600
                     focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
          </div>

          <div class="flex gap-2">
            <button @click="resetLoginFlow"
              class="flex-1 border border-white/10 text-gray-300 text-sm font-semibold py-3 rounded-xl hover:bg-white/5 transition-all">
              Back
            </button>
            <button @click="completeTwoFactor" :disabled="loading"
              class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold text-sm py-3 rounded-xl transition-all flex items-center justify-center gap-2">
              <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              {{ loading ? 'Verifying…' : 'Verify Code' }}
            </button>
          </div>
          <button @click="openTwoFactorRecovery"
            class="text-xs text-gray-400 hover:text-white transition-colors">
            Lost your authenticator?
          </button>
        </div>

        <div v-else-if="step === 'recovery-request'" class="space-y-4">
          <div>
            <h2 class="text-white font-bold text-sm">Recover Two-Factor Authentication</h2>
            <p class="text-xs text-gray-400 mt-1">Enter your email to receive a 6-digit recovery code.</p>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">Email Address</label>
            <input v-model="recoveryEmail" type="email"
              class="bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
          </div>
          <div class="flex gap-2">
            <button @click="step = 'verify'"
              class="flex-1 border border-white/10 text-gray-300 text-sm font-semibold py-3 rounded-xl hover:bg-white/5 transition-all">
              Back
            </button>
            <button @click="requestRecoveryCode" :disabled="loading"
              class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold text-sm py-3 rounded-xl transition-all">
              {{ loading ? 'Sending…' : 'Send Code' }}
            </button>
          </div>
        </div>

        <div v-else-if="step === 'recovery-verify'" class="space-y-4">
          <div>
            <h2 class="text-white font-bold text-sm">Enter Recovery Code</h2>
            <p class="text-xs text-gray-400 mt-1">We sent a 6-digit code to {{ recoveryEmail }}.</p>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">Recovery Code</label>
            <input v-model="recoveryCode" inputmode="numeric" maxlength="6" placeholder="123456"
              class="bg-gray-800 border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 placeholder-gray-600 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
          </div>
          <div class="flex gap-2">
            <button @click="step = 'recovery-request'"
              class="flex-1 border border-white/10 text-gray-300 text-sm font-semibold py-3 rounded-xl hover:bg-white/5 transition-all">
              Back
            </button>
            <button @click="verifyRecoveryCode" :disabled="loading"
              class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold text-sm py-3 rounded-xl transition-all">
              {{ loading ? 'Verifying…' : 'Verify & Continue' }}
            </button>
          </div>
        </div>

        <div v-else class="space-y-4">
          <div>
            <h2 class="text-white font-bold text-sm">Sign In</h2>
            <p class="text-xs text-gray-400 mt-1">This session step is no longer available. Return to sign in to continue.</p>
          </div>
          <button @click="resetLoginFlow"
            class="w-full border border-white/10 text-gray-300 text-sm font-semibold py-3 rounded-xl hover:bg-white/5 transition-all">
            Back to Sign In
          </button>
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
import { useRouter } from 'vue-router'
import { EyeIcon, EyeSlashIcon, QrCodeIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()
const route = useRoute()

const form    = ref({ email: '', password: '' })
const loading = ref(false)
const error   = ref(null)
const status = ref(null)
const showPwd = ref(false)
const step = ref('credentials')
const challengeToken = ref(null)
const twoFactorCode = ref('')
const setupSecret = ref('')
const setupUrl = ref('')
const setupQrUrl = ref('')
const challengeUserEmail = ref('')
const resetEmail = ref('')
const resetToken = ref('')
const resetForm = ref({ password:'', password_confirmation:'' })
const recoveryEmail = ref('')
const recoveryChallengeToken = ref('')
const recoveryCode = ref('')

async function handleLogin() {
  loading.value = true
  error.value   = null
  status.value = null
  try {
    const data = await auth.login(form.value.email, form.value.password)

    if (data.requires_two_factor_setup) {
      challengeToken.value = data.challenge_token
      setupSecret.value = data.setup?.secret ?? ''
      setupUrl.value = data.setup?.otpauth_url ?? ''
      setupQrUrl.value = buildQrUrl(setupUrl.value)
      challengeUserEmail.value = data.user?.email ?? form.value.email
      twoFactorCode.value = ''
      step.value = 'setup'
      return
    }

    if (data.requires_two_factor) {
      challengeToken.value = data.challenge_token
      challengeUserEmail.value = data.user?.email ?? form.value.email
      twoFactorCode.value = ''
      step.value = 'verify'
      return
    }

    router.push(nextRoute())
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Login failed. Check your credentials.'
  } finally {
    loading.value = false
  }
}

async function completeTwoFactor() {
  loading.value = true
  error.value = null
  status.value = null
  try {
    await auth.verifyTwoFactor(challengeToken.value, twoFactorCode.value)
    router.push(nextRoute())
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to verify authentication code.'
  } finally {
    loading.value = false
  }
}

function resetLoginFlow() {
  step.value = 'credentials'
  challengeToken.value = null
  twoFactorCode.value = ''
  setupSecret.value = ''
  setupUrl.value = ''
  setupQrUrl.value = ''
  challengeUserEmail.value = ''
  recoveryEmail.value = ''
  recoveryChallengeToken.value = ''
  recoveryCode.value = ''
  error.value = null
  status.value = null
}

function openForgotPassword() {
  resetEmail.value = form.value.email
  error.value = null
  status.value = null
  step.value = 'forgot'
}

function openTwoFactorRecovery() {
  recoveryEmail.value = challengeUserEmail.value || form.value.email
  error.value = null
  status.value = null
  step.value = 'recovery-request'
}

async function sendPasswordResetLink() {
  loading.value = true
  error.value = null
  status.value = null
  try {
    await auth.requestPasswordReset(resetEmail.value)
    status.value = 'Reset link sent. Check your email.'
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to send reset link.'
  } finally {
    loading.value = false
  }
}

async function submitPasswordReset() {
  loading.value = true
  error.value = null
  status.value = null
  try {
    await auth.resetPassword({
      email: resetEmail.value,
      token: resetToken.value,
      password: resetForm.value.password,
      password_confirmation: resetForm.value.password_confirmation,
    })
    resetLoginFlow()
    status.value = 'Password reset successful. Sign in with your new password.'
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to reset password.'
  } finally {
    loading.value = false
  }
}

async function requestRecoveryCode() {
  loading.value = true
  error.value = null
  status.value = null
  try {
    const data = await auth.requestTwoFactorRecovery(recoveryEmail.value)
    recoveryChallengeToken.value = data.challenge_token
    step.value = 'recovery-verify'
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to send recovery code.'
  } finally {
    loading.value = false
  }
}

async function verifyRecoveryCode() {
  loading.value = true
  error.value = null
  status.value = null
  try {
    const data = await auth.verifyTwoFactorRecovery(recoveryChallengeToken.value, recoveryCode.value)
    challengeToken.value = data.challenge_token
    setupSecret.value = data.setup?.secret ?? ''
    setupUrl.value = data.setup?.otpauth_url ?? ''
    setupQrUrl.value = buildQrUrl(setupUrl.value)
    challengeUserEmail.value = data.user?.email ?? recoveryEmail.value
    twoFactorCode.value = ''
    step.value = 'setup'
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to verify recovery code.'
  } finally {
    loading.value = false
  }
}

async function copyText(value) {
  try {
    await navigator.clipboard.writeText(value)
  } catch {}
}

function buildQrUrl(value) {
  if (!value) return ''
  return `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(value)}`
}

function nextRoute() {
  const priority = [
    { permission: 'dashboard.view', path: '/dashboard' },
    { permission: 'bookings.manage', path: '/bookings' },
    { permission: 'customers.manage', path: '/customers' },
    { permission: 'vehicles.manage', path: '/vehicles' },
    { permission: 'pos.manage', path: '/pos' },
    { permission: 'inventory.manage', path: '/inventory' },
    { permission: 'payments.manage', path: '/payments' },
    { permission: 'gallery.manage', path: '/gallery' },
    { permission: 'feedback.manage', path: '/feedback' },
    { permission: 'reports.view', path: '/reports' },
    { permission: 'settings.manage', path: '/settings' },
  ]

  return priority.find(item => auth.hasPermission(item.permission))?.path ?? '/dashboard'
}

onMounted(() => {
  if (route.query.reset_token && route.query.email) {
    resetToken.value = String(route.query.reset_token)
    resetEmail.value = String(route.query.email)
    step.value = 'reset'
  }
})
</script>

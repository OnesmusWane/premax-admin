<template>
  <div class="min-h-screen bg-gray-950 px-4 py-12">
    <div class="mx-auto max-w-2xl">

      <div class="mb-8 flex items-center gap-4">
        <router-link to="/login"
          class="text-gray-500 hover:text-white text-xs transition-colors">
          ← Back to Sign In
        </router-link>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-24">
        <span class="w-6 h-6 border-2 border-white/20 border-t-white rounded-full animate-spin" />
      </div>

      <div v-else-if="error" class="bg-red-950 border border-red-800 text-red-400 text-sm rounded-2xl px-6 py-4">
        {{ error }}
      </div>

      <div v-else-if="page" class="bg-gray-900 border border-white/10 rounded-2xl p-8 shadow-2xl">
        <h1 class="text-white font-extrabold text-2xl tracking-tight mb-1">{{ page.title }}</h1>
        <p v-if="page.effective_date" class="text-gray-500 text-xs mb-6">
          Effective {{ formatDate(page.effective_date) }}
          <span v-if="page.version" class="ml-2">· Version {{ page.version }}</span>
        </p>
        <div class="prose prose-invert prose-sm max-w-none text-gray-300 leading-relaxed" v-html="page.content" />
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const page    = ref(null)
const loading = ref(true)
const error   = ref(null)

const TYPE_MAP = {
  'privacy-policy':   'privacy_policy',
  'terms-of-service': 'terms_of_service',
}

async function load() {
  const type = TYPE_MAP[route.name] ?? route.meta?.legalType
  if (!type) {
    error.value = 'Unknown legal page.'
    loading.value = false
    return
  }
  try {
    const { data } = await axios.get(`/legal/${type}`)
    page.value = data
  } catch {
    error.value = 'This page could not be loaded. Please try again later.'
  } finally {
    loading.value = false
  }
}

function formatDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleDateString('en-KE', { year: 'numeric', month: 'long', day: 'numeric' })
}

onMounted(load)
</script>

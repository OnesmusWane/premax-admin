<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="text-3xl font-black tracking-tight text-slate-950">Comment Templates</h2>
        <p class="mt-1 text-sm text-slate-500">
          Quick-reply templates for comment responses. Type a shortcut (e.g.
          <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700">/price</code>) in any reply box to find it instantly.
        </p>
      </div>
      <button
        @click="openCreate"
        class="inline-flex items-center rounded-xl bg-[var(--color-custom-primary)] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#b71a1f]"
      >
        <PlusIcon class="mr-2 h-4 w-4" />
        New Template
      </button>
    </div>

    <!-- Filters row -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <div class="relative flex-1">
        <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          v-model="search"
          class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-[var(--color-custom-primary)] focus:bg-white"
          placeholder="Search by name, shortcut, or text..."
        />
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="p in platformOptions"
          :key="p.value"
          @click="platformFilter = p.value"
          class="rounded-xl px-4 py-2.5 text-sm font-bold transition"
          :class="platformFilter === p.value
            ? 'bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]'
            : 'border border-slate-200 text-slate-600 hover:bg-slate-50'"
        >
          {{ p.label }}
        </button>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="i in 5" :key="i" class="h-44 animate-pulse rounded-2xl bg-slate-100" />
    </div>

    <!-- Empty state -->
    <div
      v-else-if="!filtered.length"
      class="rounded-2xl border border-dashed border-slate-200 px-6 py-16 text-center"
    >
      <ChatBubbleOvalLeftEllipsisIcon class="mx-auto h-10 w-10 text-slate-300" />
      <p class="mt-3 text-sm font-semibold text-slate-500">
        {{ templates.length ? 'No templates match your search.' : 'No templates yet.' }}
      </p>
      <p class="mt-1 text-xs text-slate-400">
        {{ templates.length ? 'Try a different search or platform.' : 'Create your first template to speed up replies.' }}
      </p>
    </div>

    <!-- Templates grid -->
    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="tpl in filtered"
        :key="tpl.id"
        class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-slate-300 hover:shadow-sm"
      >
        <!-- Top row: platform + shortcut -->
        <div class="mb-3 flex items-center justify-between">
          <span
            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold capitalize"
            :class="platformBadge(tpl.platform)"
          >
            {{ tpl.platform || 'all' }}
          </span>
          <span
            v-if="tpl.shortcut"
            class="rounded-xl bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-700"
          >
            {{ tpl.shortcut }}
          </span>
        </div>

        <!-- Name -->
        <div class="mb-2 text-sm font-black text-slate-950">{{ tpl.name }}</div>

        <!-- Body preview -->
        <p class="mb-4 flex-1 text-sm leading-relaxed text-slate-600 line-clamp-3">{{ tpl.body }}</p>

        <!-- Footer -->
        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
          <span class="text-xs text-slate-400">Used {{ tpl.usage_count }}×</span>
          <div class="flex gap-1.5">
            <button
              @click="useTemplate(tpl)"
              class="rounded-lg bg-[rgba(211,30,36,0.08)] px-3 py-1.5 text-xs font-bold text-[var(--color-custom-primary)] transition hover:bg-[rgba(211,30,36,0.15)]"
              title="Copy to clipboard"
            >
              Use
            </button>
            <button
              @click="openEdit(tpl)"
              class="rounded-lg px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100"
            >
              Edit
            </button>
            <button
              @click="deleteTemplate(tpl)"
              class="rounded-lg px-3 py-1.5 text-xs font-bold text-red-500 transition hover:bg-red-50"
            >
              Delete
            </button>
          </div>
        </div>
      </article>
    </div>

    <!-- Create / Edit Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          @click.self="closeModal"
        >
          <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
              <h3 class="text-lg font-black text-slate-950">
                {{ editing ? 'Edit Template' : 'New Template' }}
              </h3>
              <button @click="closeModal" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <XMarkIcon class="h-5 w-5" />
              </button>
            </div>

            <form @submit.prevent="saveTemplate" class="space-y-4 px-6 py-5">
              <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">
                  Name <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.name"
                  class="input-base"
                  placeholder="e.g. Price Inquiry"
                  required
                  maxlength="120"
                />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">
                    Shortcut
                  </label>
                  <input
                    v-model="form.shortcut"
                    class="input-base font-mono"
                    placeholder="/price"
                    maxlength="30"
                  />
                  <p class="mt-1 text-xs text-slate-400">Start with "/"</p>
                </div>
                <div>
                  <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">
                    Platform
                  </label>
                  <select v-model="form.platform" class="input-base">
                    <option value="all">All platforms</option>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="tiktok">TikTok</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">
                  Body <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="form.body"
                  class="input-base resize-none"
                  rows="5"
                  placeholder="Template reply text..."
                  required
                  maxlength="2000"
                />
                <p class="mt-1 text-right text-xs text-slate-400">{{ (form.body || '').length }}/2000</p>
              </div>

              <p v-if="formError" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600">
                {{ formError }}
              </p>

              <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button
                  type="button"
                  @click="closeModal"
                  class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="rounded-xl bg-[var(--color-custom-primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#b71a1f] disabled:opacity-60"
                >
                  {{ saving ? 'Saving...' : (editing ? 'Update' : 'Create') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  ChatBubbleOvalLeftEllipsisIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const { get, post, put, del } = useApi()

const templates    = ref([])
const loading      = ref(false)
const saving       = ref(false)
const showModal    = ref(false)
const editing      = ref(null)
const formError    = ref(null)
const search       = ref('')
const platformFilter = ref('all')

const platformOptions = [
  { value: 'all',       label: 'All' },
  { value: 'facebook',  label: 'Facebook' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'tiktok',    label: 'TikTok' },
]

const defaultForm = () => ({ name: '', body: '', platform: 'all', shortcut: '' })
const form = ref(defaultForm())

const filtered = computed(() => {
  let list = templates.value
  if (platformFilter.value !== 'all') {
    list = list.filter(t => t.platform === platformFilter.value || t.platform === 'all')
  }
  const q = search.value.trim().toLowerCase()
  if (q) {
    list = list.filter(t =>
      t.name.toLowerCase().includes(q) ||
      (t.body || '').toLowerCase().includes(q) ||
      (t.shortcut || '').toLowerCase().includes(q),
    )
  }
  return list
})

async function loadTemplates() {
  loading.value = true
  try {
    const data = await get('/comment-templates')
    templates.value = data.templates || []
  } catch {
    toast.error('Failed to load templates.')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value  = null
  form.value     = defaultForm()
  formError.value = null
  showModal.value = true
}

function openEdit(tpl) {
  editing.value  = tpl.id
  form.value     = { name: tpl.name, body: tpl.body, platform: tpl.platform || 'all', shortcut: tpl.shortcut || '' }
  formError.value = null
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editing.value   = null
  form.value      = defaultForm()
  formError.value = null
}

async function saveTemplate() {
  formError.value = null
  saving.value    = true
  const payload = {
    name:     form.value.name.trim(),
    body:     form.value.body.trim(),
    platform: form.value.platform || null,
    shortcut: form.value.shortcut.trim() || null,
  }
  try {
    if (editing.value) {
      const updated = await put(`/comment-templates/${editing.value}`, payload)
      templates.value = templates.value.map(t => t.id === updated.id ? updated : t)
      toast.success('Template updated.')
    } else {
      const created = await post('/comment-templates', payload)
      templates.value = [created, ...templates.value]
      toast.success('Template created.')
    }
    closeModal()
  } catch (err) {
    const errors = err.response?.data?.errors
    formError.value = errors
      ? Object.values(errors).flat()[0]
      : (err.response?.data?.message || 'Failed to save template.')
  } finally {
    saving.value = false
  }
}

async function useTemplate(tpl) {
  try {
    await post(`/comment-templates/${tpl.id}/use`)
    tpl.usage_count = (tpl.usage_count || 0) + 1
    await navigator.clipboard.writeText(tpl.body)
    toast.success('Reply copied to clipboard.')
  } catch {
    toast.error('Could not copy to clipboard.')
  }
}

async function deleteTemplate(tpl) {
  if (!confirm(`Delete "${tpl.name}"?`)) return
  try {
    await del(`/comment-templates/${tpl.id}`)
    templates.value = templates.value.filter(t => t.id !== tpl.id)
    toast.success('Template deleted.')
  } catch {
    toast.error('Failed to delete template.')
  }
}

function platformBadge(platform) {
  return {
    facebook:  'bg-blue-50 text-blue-700',
    instagram: 'bg-pink-50 text-pink-700',
    tiktok:    'bg-slate-900 text-white',
    all:       'bg-slate-100 text-slate-600',
  }[platform] ?? 'bg-slate-100 text-slate-600'
}

onMounted(loadTemplates)
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>

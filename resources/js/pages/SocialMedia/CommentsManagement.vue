<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-3xl font-black tracking-tight text-slate-950">Comments</h2>
      <p class="mt-1 text-sm text-slate-500">All comments from your published posts across every platform.</p>
    </div>

    <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm">
      <div class="grid min-h-[760px] xl:grid-cols-[380px,1fr]">

        <!-- ── LEFT: Comment list ── -->
        <aside class="flex flex-col border-b border-slate-200 xl:border-b-0 xl:border-r">

          <!-- Search + filters -->
          <div class="border-b border-slate-200 px-4 py-3 space-y-3">
            <div class="relative">
              <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="search"
                class="w-full rounded-xl bg-slate-50 py-2.5 pl-10 pr-4 text-sm outline-none placeholder:text-slate-400 focus:ring-2 focus:ring-[rgba(211,30,36,0.12)]"
                placeholder="Search by author or text…"
              />
            </div>

            <!-- Status tabs -->
            <div class="flex gap-1 flex-wrap">
              <button
                v-for="s in statusOptions"
                :key="s.value"
                @click="statusFilter = s.value"
                class="rounded-xl px-3 py-1.5 text-xs font-bold transition"
                :class="statusFilter === s.value
                  ? 'bg-[var(--color-custom-primary)] text-white'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              >
                {{ s.label }}
                <span
                  v-if="s.value !== 'all' && counts[s.value]"
                  class="ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-black"
                  :class="statusFilter === s.value ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'"
                >
                  {{ counts[s.value] }}
                </span>
              </button>
            </div>

            <!-- Platform filter -->
            <div class="flex gap-1">
              <button
                v-for="p in platformOptions"
                :key="p.value"
                @click="platformFilter = p.value"
                class="flex-1 rounded-xl py-1.5 text-xs font-bold transition"
                :class="platformFilter === p.value
                  ? platformActiveClass(p.value)
                  : 'bg-slate-50 text-slate-500 hover:bg-slate-100'"
              >
                {{ p.label }}
              </button>
            </div>
          </div>

          <!-- List -->
          <div class="flex-1 overflow-y-auto">
            <div v-if="loading && !comments.length" class="space-y-2 p-3">
              <div v-for="i in 6" :key="i" class="h-24 animate-pulse rounded-2xl bg-slate-100" />
            </div>

            <div
              v-else-if="!comments.length"
              class="flex flex-col items-center justify-center px-6 py-16 text-center"
            >
              <ChatBubbleOvalLeftEllipsisIcon class="h-10 w-10 text-slate-300" />
              <p class="mt-3 text-sm font-semibold text-slate-500">No comments found</p>
              <p class="mt-1 text-xs text-slate-400">Try changing the filters above.</p>
            </div>

            <button
              v-for="comment in comments"
              :key="comment.id"
              @click="selectComment(comment)"
              class="flex w-full items-start gap-3 border-b border-slate-100 px-4 py-4 text-left transition last:border-b-0"
              :class="selected?.id === comment.id ? 'bg-[rgba(211,30,36,0.05)]' : 'hover:bg-slate-50'"
            >
              <!-- Avatar -->
              <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-black"
                :class="platformAvatarClass(comment.account?.platform)"
              >
                {{ initials(comment.author_name) }}
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-sm font-black text-slate-950 leading-tight">{{ comment.author_name }}</span>
                    <span v-if="comment.author_handle" class="text-xs text-slate-400">{{ comment.author_handle }}</span>
                  </div>
                  <span class="shrink-0 text-[11px] text-slate-400 leading-tight">{{ relativeTime(comment.received_at) }}</span>
                </div>

                <!-- Post + platform row -->
                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                  <span
                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                    :class="platformPillClass(comment.account?.platform)"
                  >
                    {{ platformLabel(comment.account?.platform) }}
                  </span>
                  <span v-if="comment.post?.title" class="truncate text-[11px] text-slate-400">
                    {{ comment.post.title }}
                  </span>
                </div>

                <!-- Comment preview -->
                <p class="mt-1.5 line-clamp-2 text-sm leading-snug text-slate-600">{{ comment.comment_text }}</p>

                <!-- Status + reaction row -->
                <div class="mt-2 flex items-center gap-2">
                  <span class="rounded-md px-2 py-0.5 text-[10px] font-bold" :class="statusChipClass(comment.status)">
                    {{ prettyStatus(comment.status) }}
                  </span>
                  <span v-if="adminReaction(comment) === 'like'" class="text-xs">👍</span>
                  <span v-if="adminReaction(comment) === 'dislike'" class="text-xs">👎</span>
                  <span v-if="comment.reply_text" class="ml-auto rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                    Replied
                  </span>
                </div>
              </div>
            </button>
          </div>

          <!-- Pagination -->
          <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-3">
            <button
              :disabled="meta.current_page === 1 || loading"
              @click="loadComments(meta.current_page - 1)"
              class="rounded-lg px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100 disabled:opacity-40"
            >
              ← Prev
            </button>
            <span class="text-xs text-slate-400">{{ meta.current_page }} / {{ meta.last_page }}</span>
            <button
              :disabled="meta.current_page === meta.last_page || loading"
              @click="loadComments(meta.current_page + 1)"
              class="rounded-lg px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100 disabled:opacity-40"
            >
              Next →
            </button>
          </div>
        </aside>

        <!-- ── RIGHT: Detail + Reply panel ── -->
        <div v-if="selected" class="flex min-h-0 flex-col">

          <!-- Header: post + platform info -->
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
            <div class="flex items-start gap-3">
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-base font-black"
                :class="platformAvatarClass(selected.account?.platform)"
              >
                {{ initials(selected.author_name) }}
              </div>
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-lg font-black text-slate-950">{{ selected.author_name }}</span>
                  <span v-if="selected.author_handle" class="text-sm text-slate-400">{{ selected.author_handle }}</span>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                  <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" :class="platformPillClass(selected.account?.platform)">
                    {{ platformLabel(selected.account?.platform) }}
                  </span>
                  <span class="text-sm text-slate-500">·</span>
                  <span class="text-sm text-slate-500">{{ selected.account?.name }}</span>
                </div>
              </div>
            </div>
            <div class="shrink-0 text-right">
              <div class="text-xs text-slate-400">{{ formatDateTime(selected.received_at) }}</div>
              <span class="mt-1 inline-block rounded-md px-2 py-0.5 text-xs font-bold" :class="statusChipClass(selected.status)">
                {{ prettyStatus(selected.status) }}
              </span>
            </div>
          </div>

          <!-- Post context bar -->
          <div v-if="selected.post" class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-6 py-2.5">
            <DocumentTextIcon class="h-3.5 w-3.5 shrink-0 text-slate-400" />
            <span class="text-xs font-semibold text-slate-600">Post:</span>
            <span class="truncate text-xs text-slate-500">{{ selected.post.title || 'Untitled post' }}</span>
            <span class="rounded px-1.5 py-0.5 text-[10px] font-bold" :class="statusChipClass(selected.post.status)">
              {{ prettyStatus(selected.post.status) }}
            </span>
          </div>

          <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">

            <!-- Comment bubble -->
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <p class="whitespace-pre-line text-sm leading-7 text-slate-800">{{ selected.comment_text }}</p>
            </div>

            <!-- Like / Dislike reaction -->
            <div>
              <div class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">React to this comment</div>
              <div class="flex gap-3">
                <button
                  @click="react('like')"
                  :disabled="reacting"
                  class="flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold transition disabled:opacity-50"
                  :class="adminReaction(selected) === 'like'
                    ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                    : 'border-slate-200 text-slate-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700'"
                >
                  <HandThumbUpIcon class="h-4 w-4" />
                  Like
                </button>
                <button
                  @click="react('dislike')"
                  :disabled="reacting"
                  class="flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold transition disabled:opacity-50"
                  :class="adminReaction(selected) === 'dislike'
                    ? 'border-red-300 bg-red-50 text-red-600'
                    : 'border-slate-200 text-slate-600 hover:border-red-200 hover:bg-red-50 hover:text-red-600'"
                >
                  <HandThumbDownIcon class="h-4 w-4" />
                  Dislike
                </button>
                <button
                  v-if="adminReaction(selected)"
                  @click="react('none')"
                  :disabled="reacting"
                  class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500 transition hover:bg-slate-50 disabled:opacity-50"
                >
                  Clear
                </button>
              </div>
            </div>

            <!-- Status controls -->
            <div>
              <div class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Status</div>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="s in ['needs_reply','replied','ignored']"
                  :key="s"
                  @click="setStatus(s)"
                  :disabled="updatingStatus"
                  class="rounded-xl border px-4 py-2 text-sm font-bold transition disabled:opacity-50"
                  :class="selected.status === s
                    ? statusActiveClass(s)
                    : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                >
                  {{ prettyStatus(s) }}
                </button>
              </div>
            </div>

            <!-- Existing reply (read-only) -->
            <div v-if="selected.reply_text" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
              <div class="mb-1.5 flex items-center gap-2">
                <CheckCircleIcon class="h-4 w-4 text-emerald-600" />
                <span class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Reply Sent</span>
                <span v-if="selected.replied_at" class="ml-auto text-xs text-emerald-500">{{ formatDateTime(selected.replied_at) }}</span>
              </div>
              <p class="whitespace-pre-line text-sm leading-6 text-emerald-800">{{ selected.reply_text }}</p>
            </div>

            <!-- Reply form -->
            <div>
              <div class="mb-2 flex items-center justify-between gap-2">
                <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">
                  {{ selected.reply_text ? 'Send another reply' : 'Reply' }}
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-[11px] text-slate-400">Type <kbd class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-600">/</kbd> for quick insert</span>
                  <button
                    @click="openTemplateBrowser"
                    class="inline-flex items-center gap-1 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-custom-primary hover:text-custom-primary"
                  >
                    <DocumentTextIcon class="h-3.5 w-3.5" />
                    Templates
                  </button>
                </div>
              </div>

              <!-- Template picker dropdown -->
              <div class="relative">
                <textarea
                  ref="replyRef"
                  v-model="replyDraft"
                  @input="onReplyInput"
                  @keydown.escape="closePicker"
                  @keydown.down.prevent="pickerIndex = Math.min(pickerIndex + 1, pickerTemplates.length - 1)"
                  @keydown.up.prevent="pickerIndex = Math.max(pickerIndex - 1, 0)"
                  @keydown.enter.prevent="pickerOpen && pickerTemplates.length ? insertTemplate(pickerTemplates[pickerIndex]) : null"
                  rows="4"
                  class="input-base resize-none"
                  :placeholder="selected.reply_text ? 'Write a follow-up reply…' : 'Write your reply…'"
                />

                <!-- Template dropdown -->
                <div
                  v-if="pickerOpen && pickerTemplates.length"
                  class="absolute bottom-[calc(100%+6px)] left-0 z-20 w-full rounded-2xl border border-slate-200 bg-white shadow-xl"
                >
                  <div class="border-b border-slate-100 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">
                    Templates
                  </div>
                  <div class="max-h-48 overflow-y-auto py-1">
                    <button
                      v-for="(tpl, i) in pickerTemplates"
                      :key="tpl.id"
                      @mousedown.prevent="insertTemplate(tpl)"
                      class="flex w-full flex-col gap-0.5 px-4 py-2.5 text-left transition"
                      :class="i === pickerIndex ? 'bg-[rgba(211,30,36,0.06)]' : 'hover:bg-slate-50'"
                    >
                      <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-slate-900">{{ tpl.name }}</span>
                        <span v-if="tpl.shortcut" class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-600">{{ tpl.shortcut }}</span>
                      </div>
                      <p class="line-clamp-1 text-xs text-slate-500">{{ tpl.body }}</p>
                    </button>
                  </div>
                </div>
              </div>

              <p v-if="replyError" class="mt-2 text-sm font-semibold text-red-600">{{ replyError }}</p>

              <div class="mt-3 flex justify-end">
                <button
                  @click="sendReply"
                  :disabled="!replyDraft.trim() || sendingReply"
                  class="inline-flex items-center rounded-xl bg-[var(--color-custom-primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#b71a1f] disabled:opacity-50"
                >
                  <PaperAirplaneIcon class="mr-2 h-4 w-4" />
                  {{ sendingReply ? 'Sending…' : 'Send Reply' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- No selection placeholder -->
        <div v-else class="hidden xl:flex flex-col items-center justify-center text-center p-12">
          <ChatBubbleOvalLeftEllipsisIcon class="h-14 w-14 text-slate-200" />
          <p class="mt-4 text-base font-black text-slate-400">Select a comment</p>
          <p class="mt-1 text-sm text-slate-400">Pick a comment from the list to reply, react, and manage its status.</p>
        </div>

      </div>
    </div>
  </div>

  <!-- ── Template browser modal ── -->
  <Teleport to="body">
    <div v-if="templateBrowserOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm" @click="templateBrowserOpen = false" />
      <div class="relative flex w-full max-w-md flex-col rounded-2xl bg-white shadow-2xl" style="max-height: 72vh">

        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h3 class="text-base font-black text-slate-950">Comment Templates</h3>
          <button
            @click="templateBrowserOpen = false"
            class="flex h-7 w-7 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700"
          >✕</button>
        </div>

        <div class="border-b border-slate-100 px-4 py-3">
          <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              ref="templateBrowserInputRef"
              v-model="templateBrowserSearch"
              @input="debounceBrowserSearch"
              class="w-full rounded-xl bg-slate-50 py-2.5 pl-10 pr-4 text-sm outline-none placeholder:text-slate-400 focus:ring-2 focus:ring-[rgba(211,30,36,0.12)]"
              placeholder="Search by name, shortcut, or content…"
            />
          </div>
        </div>

        <div class="flex-1 overflow-y-auto py-1">
          <div v-if="browserLoading" class="space-y-1.5 p-3">
            <div v-for="i in 4" :key="i" class="h-14 animate-pulse rounded-xl bg-slate-100" />
          </div>

          <div
            v-else-if="!browserTemplates.length"
            class="flex flex-col items-center justify-center px-6 py-10 text-center"
          >
            <DocumentTextIcon class="h-8 w-8 text-slate-300" />
            <p class="mt-2 text-sm font-semibold text-slate-500">No templates found</p>
            <p class="mt-1 text-xs text-slate-400">Try a different search or create one in the Templates section.</p>
          </div>

          <button
            v-for="tpl in browserTemplates"
            :key="tpl.id"
            @click="pickBrowserTemplate(tpl)"
            class="flex w-full flex-col gap-0.5 px-4 py-3 text-left transition hover:bg-[rgba(211,30,36,0.04)]"
          >
            <div class="flex items-center gap-2">
              <span class="text-sm font-bold text-slate-900">{{ tpl.name }}</span>
              <span v-if="tpl.shortcut" class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-600">{{ tpl.shortcut }}</span>
              <span v-if="tpl.usage_count" class="ml-auto text-[10px] text-slate-400">Used {{ tpl.usage_count }}×</span>
            </div>
            <p class="line-clamp-2 text-xs leading-4 text-slate-500">{{ tpl.body }}</p>
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue'
import {
  ChatBubbleOvalLeftEllipsisIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  HandThumbDownIcon,
  HandThumbUpIcon,
  MagnifyingGlassIcon,
  PaperAirplaneIcon,
} from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'

const toast    = useToastStore()
const { get, post, patch } = useApi()

// ─── State ────────────────────────────────────────────────────────────────────
const comments       = ref([])
const meta           = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const loading        = ref(false)
const selected       = ref(null)
const reacting       = ref(false)
const updatingStatus = ref(false)
const sendingReply   = ref(false)
const replyDraft     = ref('')
const replyError     = ref(null)
const replyRef       = ref(null)

const search         = ref('')
const statusFilter   = ref('all')
const platformFilter = ref('all')

const pickerOpen      = ref(false)
const pickerTemplates = ref([])
const pickerQuery     = ref('')
const pickerIndex     = ref(0)

const counts = ref({ needs_reply: 0, replied: 0, ignored: 0 })

const templateBrowserOpen      = ref(false)
const templateBrowserSearch    = ref('')
const templateBrowserInputRef  = ref(null)
const browserTemplates         = ref([])
const browserLoading           = ref(false)
let   browserSearchTimer       = null

const statusOptions = [
  { value: 'all',         label: 'All' },
  { value: 'needs_reply', label: 'Needs Reply' },
  { value: 'replied',     label: 'Replied' },
  { value: 'ignored',     label: 'Ignored' },
]

const platformOptions = [
  { value: 'all',       label: 'All' },
  { value: 'facebook',  label: 'Facebook' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'tiktok',    label: 'TikTok' },
]

// ─── Data loading ─────────────────────────────────────────────────────────────
let debounceTimer = null
watch([search, statusFilter, platformFilter], () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadComments(1), 280)
})

async function loadComments(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 20 }
    if (search.value.trim())              params.search   = search.value.trim()
    if (statusFilter.value !== 'all')     params.status   = statusFilter.value
    if (platformFilter.value !== 'all')   params.platform = platformFilter.value

    const data    = await get('/admin/social-media/comments', params)
    comments.value = data.data || []
    meta.value     = {
      current_page: data.current_page,
      last_page:    data.last_page,
      per_page:     data.per_page,
      total:        data.total,
    }
    loadCounts()
  } catch {
    toast.error('Failed to load comments.')
  } finally {
    loading.value = false
  }
}

async function loadCounts() {
  try {
    const [nr, rp, ig] = await Promise.all([
      get('/admin/social-media/comments', { per_page: 1, status: 'needs_reply', ...(platformFilter.value !== 'all' ? { platform: platformFilter.value } : {}) }),
      get('/admin/social-media/comments', { per_page: 1, status: 'replied',     ...(platformFilter.value !== 'all' ? { platform: platformFilter.value } : {}) }),
      get('/admin/social-media/comments', { per_page: 1, status: 'ignored',     ...(platformFilter.value !== 'all' ? { platform: platformFilter.value } : {}) }),
    ])
    counts.value = { needs_reply: nr.total, replied: rp.total, ignored: ig.total }
  } catch { /* non-critical */ }
}

// ─── Comment selection ────────────────────────────────────────────────────────
function selectComment(comment) {
  selected.value = comment
  replyDraft.value = ''
  replyError.value = null
  closePicker()
}

// ─── Reactions ────────────────────────────────────────────────────────────────
function adminReaction(comment) {
  return comment?.metadata?.admin_reaction ?? null
}

async function react(reaction) {
  if (!selected.value) return
  const current = adminReaction(selected.value)
  const send    = current === reaction ? 'none' : reaction  // toggle off if same
  reacting.value = true
  try {
    const updated = await post(`/admin/social-media/comments/${selected.value.id}/react`, { reaction: send })
    patchComment(updated)
  } catch {
    toast.error('Failed to save reaction.')
  } finally {
    reacting.value = false
  }
}

// ─── Status ───────────────────────────────────────────────────────────────────
async function setStatus(status) {
  if (!selected.value || selected.value.status === status) return
  updatingStatus.value = true
  try {
    const updated = await patch(`/admin/social-media/comments/${selected.value.id}`, { status })
    patchComment(updated)
    toast.success(`Marked as ${prettyStatus(status)}.`)
    loadCounts()
  } catch {
    toast.error('Failed to update status.')
  } finally {
    updatingStatus.value = false
  }
}

// ─── Reply ────────────────────────────────────────────────────────────────────
async function sendReply() {
  if (!selected.value || !replyDraft.value.trim()) return
  replyError.value  = null
  sendingReply.value = true
  try {
    const updated = await post(`/admin/social-media/comments/${selected.value.id}/reply`, {
      reply_text: replyDraft.value.trim(),
    })
    patchComment(updated)
    replyDraft.value = ''
    toast.success('Reply sent.')
    loadCounts()
  } catch (err) {
    const errors = err.response?.data?.errors
    replyError.value = errors
      ? Object.values(errors).flat()[0]
      : (err.response?.data?.message || 'Failed to send reply.')
  } finally {
    sendingReply.value = false
  }
}

// ─── Template picker ──────────────────────────────────────────────────────────
async function onReplyInput() {
  const val   = replyDraft.value
  const slash = val.lastIndexOf('/')
  if (slash === -1) { closePicker(); return }
  const query = val.slice(slash + 1)
  pickerQuery.value = query
  pickerIndex.value = 0

  try {
    const data = await get('/comment-templates', {
      search:   query,
      platform: selected.value?.account?.platform || 'all',
    })
    pickerTemplates.value = (data.templates || []).slice(0, 6)
    pickerOpen.value      = pickerTemplates.value.length > 0
  } catch {
    closePicker()
  }
}

function insertTemplate(tpl) {
  const val   = replyDraft.value
  const slash = val.lastIndexOf('/')
  replyDraft.value = val.slice(0, slash) + tpl.body
  closePicker()
  post(`/comment-templates/${tpl.id}/use`).catch(() => {})
  replyRef.value?.focus()
}

function closePicker() {
  pickerOpen.value      = false
  pickerTemplates.value = []
  pickerIndex.value     = 0
}

// ─── Template browser modal ───────────────────────────────────────────────────
async function openTemplateBrowser() {
  templateBrowserSearch.value = ''
  templateBrowserOpen.value   = true
  await loadBrowserTemplates('')
  await nextTick()
  templateBrowserInputRef.value?.focus()
}

function debounceBrowserSearch() {
  clearTimeout(browserSearchTimer)
  browserSearchTimer = setTimeout(() => loadBrowserTemplates(templateBrowserSearch.value), 250)
}

async function loadBrowserTemplates(query) {
  browserLoading.value = true
  try {
    const data = await get('/comment-templates', {
      search:   query || '',
      platform: selected.value?.account?.platform || 'all',
    })
    browserTemplates.value = data.templates || []
  } catch {
    browserTemplates.value = []
  } finally {
    browserLoading.value = false
  }
}

function pickBrowserTemplate(tpl) {
  replyDraft.value        = tpl.body
  templateBrowserOpen.value = false
  post(`/comment-templates/${tpl.id}/use`).catch(() => {})
  nextTick(() => replyRef.value?.focus())
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function patchComment(updated) {
  comments.value = comments.value.map(c => c.id === updated.id ? updated : c)
  if (selected.value?.id === updated.id) selected.value = updated
}

function initials(name) {
  return String(name || '?').split(' ').slice(0, 2).map(p => p[0]?.toUpperCase() || '').join('')
}

function relativeTime(value) {
  if (!value) return '—'
  const diff    = Math.max(0, Date.now() - new Date(value).getTime())
  const minutes = Math.round(diff / 60000)
  if (minutes < 1)  return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.round(minutes / 60)
  if (hours < 24)   return `${hours}h ago`
  return `${Math.round(hours / 24)}d ago`
}

function formatDateTime(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
}

function prettyStatus(value) {
  return String(value || '').replaceAll('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function platformLabel(platform) {
  return { facebook: 'Facebook', instagram: 'Instagram', tiktok: 'TikTok' }[platform] ?? 'Social'
}

function platformPillClass(platform) {
  return {
    facebook:  'border border-slate-200 bg-white text-slate-700',
    instagram: 'border border-[rgba(211,30,36,0.12)] bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]',
    tiktok:    'border border-slate-800 bg-slate-900 text-white',
  }[platform] ?? 'border border-slate-200 bg-slate-100 text-slate-600'
}

function platformAvatarClass(platform) {
  return {
    facebook:  'bg-blue-100 text-blue-700',
    instagram: 'bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]',
    tiktok:    'bg-slate-900 text-white',
  }[platform] ?? 'bg-slate-100 text-slate-600'
}

function platformActiveClass(platform) {
  return {
    facebook:  'bg-blue-100 text-blue-700',
    instagram: 'bg-[rgba(211,30,36,0.1)] text-[var(--color-custom-primary)]',
    tiktok:    'bg-slate-900 text-white',
    all:       'bg-[var(--color-custom-primary)] text-white',
  }[platform] ?? 'bg-slate-200 text-slate-700'
}

function statusChipClass(status) {
  if (status === 'replied')     return 'bg-emerald-50 text-emerald-700'
  if (status === 'needs_reply') return 'bg-amber-50 text-amber-700'
  if (status === 'ignored')     return 'bg-slate-100 text-slate-500'
  return 'bg-slate-100 text-slate-600'
}

function statusActiveClass(status) {
  if (status === 'needs_reply') return 'border-amber-300 bg-amber-50 text-amber-700'
  if (status === 'replied')     return 'border-emerald-300 bg-emerald-50 text-emerald-700'
  if (status === 'ignored')     return 'border-slate-300 bg-slate-100 text-slate-600'
  return 'border-slate-300 bg-slate-100 text-slate-600'
}

onMounted(() => loadComments())
</script>

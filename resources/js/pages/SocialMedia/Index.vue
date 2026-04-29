<template>
  <div class="min-h-full bg-slate-50">
    <div class="flex min-h-[calc(100vh-4rem)] flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm lg:flex-row">
      <aside class="w-full border-b border-slate-200 bg-white lg:w-[240px] lg:border-b-0 lg:border-r">
          <div class="flex items-center gap-3 border-b border-slate-200 px-6 py-5">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]">
            <ShareIcon class="h-5 w-5" />
          </div>
          <div>
            <div class="text-xl font-black tracking-tight text-slate-950">SocialHub</div>
            <div class="text-xs text-slate-400">Social media control center</div>
          </div>
        </div>

        <nav class="space-y-1 px-3 py-5">
          <button
            v-for="item in menuItems"
            :key="item.key"
            @click="setActiveSection(item.key)"
            class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-bold transition"
            :class="activeSection === item.key ? 'bg-[var(--color-custom-primary)] text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
          >
            <component :is="item.icon" class="h-4 w-4 shrink-0" />
            <span>{{ item.label }}</span>
          </button>
        </nav>

        <!-- <div class="mt-auto hidden border-t border-slate-200 px-4 py-4 lg:block">
          <button class="flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-500 transition hover:bg-slate-50">
            <ChevronLeftIcon class="mr-2 h-4 w-4" />
            Collapse
          </button>
        </div> -->
      </aside>

      <div class="flex min-h-0 flex-1 flex-col">
     

        <main class="min-h-0 flex-1 overflow-y-auto px-5 py-6 lg:px-8">
          <section v-if="activeSection === 'posts'" class="space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <h2 class="text-3xl font-black tracking-tight text-slate-950">Posts</h2>
                <p class="mt-1 text-sm text-slate-500">Manage and schedule your social media content.</p>
              </div>

              <button
                @click="openComposer()"
                class="inline-flex items-center rounded-xl bg-[var(--color-custom-primary)] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#b71a1f]"
              >
                <PlusIcon class="mr-2 h-4 w-4" />
                Create Post
              </button>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-3">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="filter in postFilters"
                    :key="filter"
                    @click="postStatusFilter = filter"
                    class="rounded-xl px-4 py-2 text-sm font-bold transition"
                    :class="postStatusFilter === filter ? 'bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]' : 'text-slate-600 hover:bg-slate-100'"
                  >
                    {{ prettyFilter(filter) }}
                  </button>
                </div>

                <div class="relative flex items-center gap-3">
                  <div class="relative w-full min-w-[250px]">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      v-model="postSearch"
                      class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-[var(--color-custom-primary)] focus:bg-white"
                      placeholder="Search posts..."
                    />
                  </div>

                  <button
                    @click="showPostFilters = !showPostFilters"
                    class="inline-flex items-center rounded-xl border px-4 py-2.5 text-sm font-semibold transition"
                    :class="showPostFilters || hasActivePostFilters ? 'border-[var(--color-custom-primary)] bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                  >
                    <FunnelIcon class="mr-2 h-4 w-4" />
                    Filter
                    <span v-if="activePostFilterCount" class="ml-2 rounded-full bg-[var(--color-custom-primary)] px-2 py-0.5 text-[11px] font-bold text-white">
                      {{ activePostFilterCount }}
                    </span>
                  </button>

                  <div
                    v-if="showPostFilters"
                    class="absolute right-0 top-[calc(100%+0.75rem)] z-10 w-[320px] rounded-2xl border border-slate-200 bg-white p-4 shadow-xl"
                  >
                    <div class="flex items-center justify-between">
                      <div class="text-sm font-black text-slate-950">Post Filters</div>
                      <button @click="resetPostFilters" class="text-xs font-bold text-[var(--color-custom-primary)] hover:text-[#b71a1f]">Reset</button>
                    </div>

                    <div class="mt-4">
                      <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Platform</div>
                      <div class="mt-2 flex flex-wrap gap-2">
                        <button
                          v-for="option in platformFilterOptions"
                          :key="option.value"
                          @click="postPlatformFilter = option.value"
                          class="rounded-full px-3 py-2 text-xs font-bold transition"
                          :class="postPlatformFilter === option.value ? 'bg-[var(--color-custom-primary)] text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'"
                        >
                          {{ option.label }}
                        </button>
                      </div>
                    </div>

                    <div class="mt-4">
                      <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Group By</div>
                      <div class="mt-2 flex flex-wrap gap-2">
                        <button
                          v-for="option in groupByOptions"
                          :key="option.value"
                          @click="postGroupBy = option.value"
                          class="rounded-full px-3 py-2 text-xs font-bold transition"
                          :class="postGroupBy === option.value ? 'bg-[var(--color-custom-primary)] text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'"
                        >
                          {{ option.label }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-8">
              <section
                v-for="group in postGroups"
                :key="group.key"
                class="space-y-4"
              >
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <div class="flex items-center gap-3">
                      <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-sm font-black" :class="platformIconWrapClass(group.platform)">
                        {{ platformShort(group.platform) }}
                      </div>
                      <div>
                        <h3 class="text-lg font-black text-slate-950">{{ group.label }}</h3>
                        <p class="text-sm text-slate-500">{{ group.posts.length }} post{{ group.posts.length === 1 ? '' : 's' }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-3">
                  <article
                    v-for="post in group.posts"
                    :key="post.id"
                    :class="postCardShellClass(group.platform)"
                  >
                    <div class="flex items-start justify-between px-4 py-4" :class="postCardHeaderClass(group.platform)">
                      <div class="min-w-0">
                        <div class="flex items-center gap-2">
                          <div
                            v-for="badge in postAccountBadges(post)"
                            :key="`${post.id}-${badge.id}`"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-[11px] font-black"
                            :class="platformBadgeClass(badge.platform)"
                          >
                            {{ platformShort(badge.platform) }}
                          </div>
                          <span class="rounded-md px-2 py-1 text-[11px] font-bold" :class="statusChipClass(post.status)">
                            {{ prettyStatus(post.status) }}
                          </span>
                        </div>
                        <div class="mt-2 text-xs" :class="postCardMutedTextClass(group.platform)">{{ postScheduleText(post) }}</div>
                      </div>

                      <EllipsisVerticalIcon class="h-5 w-5" :class="postCardMutedTextClass(group.platform)" />
                    </div>

                    <div class="px-4">
                      <p class="line-clamp-4 text-sm leading-6" :class="postCardBodyTextClass(group.platform)">{{ post.content }}</p>
                      <div v-if="post.status === 'failed' && post.failure_reason" class="mt-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        {{ post.failure_reason }}
                      </div>
                    </div>

                    <div v-if="post.media?.length" class="mt-4">
                      <div class="h-[190px] overflow-hidden" :class="postCardMediaClass(group.platform)">
                        <video
                          v-if="inferIsVideo(post.media[0])"
                          :src="post.media[0]"
                          class="h-full w-full object-cover"
                          controls
                          muted
                        />
                        <img
                          v-else
                          :src="post.media[0]"
                          :alt="post.title || 'Post image'"
                          class="h-full w-full object-cover"
                        />
                      </div>
                    </div>

                    <div class="flex items-center justify-between border-t px-4 py-3 text-sm" :class="postCardFooterClass(group.platform)">
                      <div class="flex items-center gap-4">
                        <span class="inline-flex items-center gap-1">
                          <HeartIcon class="h-4 w-4" />
                          {{ postLikeCount(post, group.platform) }}
                        </span>
                        <button
                          @click="openPostComments(post, group.platform)"
                          class="inline-flex items-center gap-1 rounded-md px-2 py-1 transition hover:bg-black/5"
                          :class="group.platform === 'tiktok' ? 'hover:bg-white/5' : ''"
                        >
                          <ChatBubbleOvalLeftIcon class="h-4 w-4" />
                          {{ postCommentCount(post, group.platform) }}
                        </button>
                        <span class="inline-flex items-center gap-1">
                          <ShareIcon class="h-4 w-4" />
                          {{ postShareCount(post, group.platform) }}
                        </span>
                      </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t px-4 py-3" :class="postCardActionBarClass(group.platform)">
                      <button
                        v-if="post.status === 'failed'"
                        @click="retryPost(post)"
                        :disabled="retryingPostId === post.id"
                        class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100 disabled:opacity-50"
                      >
                        {{ retryingPostId === post.id ? 'Retrying…' : 'Retry' }}
                      </button>
                      <button
                        @click="openPostEditor(post)"
                        class="rounded-lg border px-3 py-2 text-xs font-bold transition"
                        :class="postCardSecondaryActionClass(group.platform)"
                      >
                        Edit Content
                      </button>
                      <button
                        @click="deletePost(post)"
                        class="rounded-lg border px-3 py-2 text-xs font-bold transition"
                        :class="postCardDangerActionClass(group.platform)"
                      >
                        Delete
                      </button>
                    </div>

                  </article>
                </div>
              </section>

              <div v-if="!postGroups.length" class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-400">
                No posts match the current filters.
              </div>
            </div>
          </section>

          <section v-else-if="activeSection === 'inbox'" class="space-y-6">
            <div>
              <h2 class="text-3xl font-black tracking-tight text-slate-950">Inbox</h2>
            </div>

            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm">
              <div class="grid min-h-[720px] xl:grid-cols-[320px,1fr]">
                <aside class="border-r border-slate-200 bg-white">
                  <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex items-center justify-between">
                      <h3 class="text-2xl font-black text-slate-950">Inbox</h3>
                      <EllipsisVerticalIcon class="h-5 w-5 text-slate-400" />
                    </div>
                  </div>

                  <div class="p-4">
                    <div class="relative">
                      <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                      <input
                        v-model="conversationSearch"
                        class="w-full rounded-xl bg-slate-50 py-3 pl-11 pr-4 text-sm outline-none placeholder:text-slate-400 focus:ring-2 focus:ring-[rgba(211,30,36,0.12)]"
                        placeholder="Search messages..."
                      />
                    </div>
                  </div>

                  <div class="max-h-[620px] space-y-1 overflow-y-auto px-2 pb-3">
                    <button
                      v-for="conversation in filteredConversations"
                      :key="conversation.id"
                      @click="activeConversationId = conversation.id"
                      class="flex w-full items-start gap-3 rounded-2xl px-4 py-3 text-left transition"
                      :class="activeConversationId === conversation.id ? 'bg-[rgba(211,30,36,0.08)]' : 'hover:bg-slate-50'"
                    >
                      <div class="relative">
                        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-sm font-black text-slate-600">
                          <img
                            v-if="conversation.customer_avatar_url"
                            :src="conversation.customer_avatar_url"
                            :alt="conversation.customer_name"
                            class="h-full w-full object-cover"
                          />
                          <span v-else>{{ initials(conversation.customer_name) }}</span>
                        </div>
                        <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-400" />
                      </div>

                      <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                          <div class="truncate text-sm font-black text-slate-950">{{ conversation.customer_name }}</div>
                          <div class="text-xs text-slate-400">{{ formatConversationTime(conversation) }}</div>
                        </div>
                        <div class="mt-1 flex items-center gap-2">
                          <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="platformPillClass(conversation.account?.platform)">
                            {{ platformLabel(conversation.account?.platform) }}
                          </span>
                          <span v-if="conversation.unread_count" class="rounded-full bg-[var(--color-custom-primary)] px-2 py-0.5 text-[10px] font-bold text-white">
                            {{ conversation.unread_count }}
                          </span>
                        </div>
                        <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ conversation.last_message_preview }}</p>
                      </div>
                    </button>
                  </div>
                </aside>

                <section v-if="activeConversation" class="flex min-h-0 flex-col">
                  <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div class="flex items-center gap-3">
                      <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-sm font-black text-slate-600">
                        <img
                          v-if="activeConversation.customer_avatar_url"
                          :src="activeConversation.customer_avatar_url"
                          :alt="activeConversation.customer_name"
                          class="h-full w-full object-cover"
                        />
                        <span v-else>{{ initials(activeConversation.customer_name) }}</span>
                      </div>
                      <div>
                        <div class="text-xl font-black text-slate-950">{{ activeConversation.customer_name }}</div>
                        <div class="text-sm text-slate-500">{{ platformLabel(activeConversation.account?.platform) }} · online</div>
                      </div>
                    </div>

                    <div class="flex items-center gap-3 text-slate-500">
                      <PhoneIcon class="h-5 w-5" />
                      <VideoCameraIcon class="h-5 w-5" />
                      <MagnifyingGlassIcon class="h-5 w-5" />
                      <EllipsisVerticalIcon class="h-5 w-5" />
                    </div>
                  </div>

                  <div ref="messageAreaRef" class="flex-1 overflow-y-auto bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.8),_rgba(244,247,251,1))] px-5 py-6">
                    <div v-if="messagesLoading" class="flex h-full items-center justify-center">
                      <svg class="h-6 w-6 animate-spin text-slate-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                      </svg>
                    </div>

                    <div v-else class="mx-auto max-w-4xl space-y-4">
                      <div class="flex justify-center">
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-400 shadow-sm">Today</span>
                      </div>

                      <article
                        v-for="message in orderedMessages(activeConversation.messages || [])"
                        :key="message.id"
                        class="flex"
                        :class="message.direction === 'outbound' ? 'justify-end' : 'justify-start'"
                      >
                        <div
                          class="max-w-[70%] rounded-[18px] px-4 py-3 text-sm shadow-sm"
                          :class="message.direction === 'outbound' ? 'bg-[var(--color-custom-primary)] text-white' : 'bg-white text-slate-700'"
                        >
                          <div class="whitespace-pre-line leading-6">{{ message.body }}</div>
                          <div class="mt-2 text-[11px]" :class="message.direction === 'outbound' ? 'text-white/70' : 'text-slate-400'">
                            {{ formatMessageTime(message.sent_at || message.created_at) }}
                          </div>
                        </div>
                      </article>
                    </div>
                  </div>

                  <div class="border-t border-slate-200 bg-white px-5 py-4">
                    <div class="flex items-center gap-3">
                      <FaceSmileIcon class="h-5 w-5 text-slate-400" />
                      <PaperClipIcon class="h-5 w-5 text-slate-400" />
                      <input
                        v-model="messageDraft"
                        class="flex-1 rounded-full border border-slate-200 px-5 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-[var(--color-custom-primary)]"
                        placeholder="Type a message..."
                        @keyup.enter="sendMessage"
                      />
                      <button
                        @click="sendMessage"
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-custom-primary)] text-white transition hover:bg-[#b71a1f]"
                      >
                        <PaperAirplaneIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </section>

                <section v-else class="flex items-center justify-center bg-slate-50 text-slate-400">
                  Choose a conversation
                </section>
              </div>
            </div>
          </section>

          <section v-else-if="activeSection === 'accounts'" class="space-y-6">
            <div class="flex items-center justify-between gap-4">
              <div>
                <h2 class="text-3xl font-black tracking-tight text-slate-950">Connected Accounts</h2>
                <p class="mt-1 text-sm text-slate-500">Manage your social media profiles and connections.</p>
              </div>

              <button
                @click="openAccountPanel()"
                class="inline-flex items-center rounded-xl bg-[var(--color-custom-primary)] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#b71a1f]"
              >
                <PlusIcon class="mr-2 h-4 w-4" />
                Connect Account
              </button>
            </div>

            <div class="grid gap-5 xl:grid-cols-4">
              <article
                v-for="account in accounts"
                :key="account.id"
                class="overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm"
              >
                <div class="px-4 py-4">
                  <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl text-base font-black" :class="platformTileClass(account.platform)">
                      {{ platformShort(account.platform).toLowerCase() }}
                    </div>
                    <EllipsisVerticalIcon class="h-5 w-5 text-slate-400" />
                  </div>

                  <div class="mt-4 text-xl font-black text-slate-950">{{ account.name }}</div>
                  <div class="mt-1 text-sm text-slate-400">{{ account.username || '@unassigned' }}</div>

                  <div class="mt-4">
                    <span class="rounded-md px-3 py-1 text-xs font-bold" :class="account.auth_status === 'connected' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'">
                      {{ prettyStatus(account.auth_status) }}
                    </span>
                  </div>

                  <div v-if="account.token_expires_at" class="mt-3 text-xs text-slate-500">
                    Token expires {{ formatDateTime(account.token_expires_at) }}
                  </div>
                  <div v-if="account.sync_error" class="mt-2 rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs leading-5 text-red-700">
                    {{ account.sync_error }}
                  </div>
                </div>

                <div class="border-t border-slate-100 px-4 py-4">
                  <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <div class="text-xs text-slate-400">Followers</div>
                      <div class="mt-1 font-black text-slate-900">{{ formatCompact(account.followers_count) }}</div>
                    </div>
                    <div>
                      <div class="text-xs text-slate-400">Last Synced</div>
                      <div class="mt-1 font-black text-slate-900">{{ relativeTime(account.last_synced_at) }}</div>
                    </div>
                  </div>
                </div>

                <div class="border-t border-slate-100 p-3 grid gap-2" :class="account.auth_status === 'connected' ? 'grid-cols-3' : 'grid-cols-2'">
                  <button
                    @click="syncAccount(account)"
                    class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"
                  >
                    Sync
                  </button>
                  <button
                    v-if="account.auth_status === 'connected'"
                    @click="openAccountPanel(account)"
                    class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"
                  >
                    Edit
                  </button>
                  <button
                    @click="toggleAccountConnection(account)"
                    class="rounded-xl px-3 py-2.5 text-sm font-bold"
                    :class="account.auth_status === 'connected' ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-[var(--color-custom-primary)] text-white hover:bg-[#b71a1f]'"
                  >
                    {{ account.auth_status === 'connected' ? 'Disconnect' : 'Reconnect' }}
                  </button>
                </div>

                <!-- TikTok OAuth + Token refresh buttons -->
                <div v-if="account.platform === 'tiktok'" class="border-t border-slate-100 p-3 space-y-2">
                  <button
                    @click="connectWithTikTok(account)"
                    :disabled="connectingTikTokId === account.id"
                    class="w-full rounded-xl bg-slate-950 px-3 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 disabled:opacity-50"
                  >
                    {{ connectingTikTokId === account.id ? 'Redirecting…' : '🎵 Authorize with TikTok' }}
                  </button>
                  <button
                    v-if="account.credentials?.refresh_token || account.auth_status === 'connected'"
                    @click="refreshTikTokToken(account)"
                    :disabled="refreshingTikTokTokenId === account.id"
                    class="w-full rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100 disabled:opacity-50"
                    title="Use the stored refresh_token to get a new 24-hour access token"
                  >
                    {{ refreshingTikTokTokenId === account.id ? 'Refreshing…' : 'Refresh Access Token' }}
                  </button>
                </div>

                <!-- Sync Posts — FB, IG, and TikTok when connected -->
                <div
                  v-if="account.auth_status === 'connected' && ['facebook', 'instagram', 'tiktok'].includes(account.platform)"
                  class="border-t border-slate-100 px-3 py-2 space-y-2"
                >
                  <button
                    @click="syncAccountPosts(account)"
                    :disabled="syncingPostsId === account.id"
                    class="w-full rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100 disabled:opacity-50"
                    title="Import all posts from this account into the portal"
                  >
                    {{ syncingPostsId === account.id ? 'Importing Posts…' : '↓ Import Posts from Platform' }}
                  </button>
                  <button
                    v-if="['facebook', 'instagram'].includes(account.platform)"
                    @click="syncAccountMessages(account)"
                    :disabled="syncingMessagesId === account.id"
                    class="w-full rounded-xl border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-bold text-teal-700 transition hover:bg-teal-100 disabled:opacity-50"
                    title="Pull latest DMs and conversations from this account"
                  >
                    {{ syncingMessagesId === account.id ? 'Syncing Messages…' : '↓ Sync Messages & DMs' }}
                  </button>
                </div>

                <div v-if="account.platform === 'facebook'" class="border-t border-slate-100 p-3 space-y-2">
                  <!-- Primary: Authorize with Facebook — runs full OAuth, auto-populates all tokens -->
                  <button
                    @click="connectWithFacebook(account)"
                    :disabled="connectingOAuthId === account.id"
                    class="w-full rounded-xl bg-[#1877F2] px-3 py-2.5 text-sm font-bold text-white transition hover:bg-[#1463cc] disabled:opacity-50"
                  >
                    {{ connectingOAuthId === account.id ? 'Redirecting…' : '🔗 Authorize with Facebook' }}
                  </button>

                  <!-- Secondary row: extend the stored user token OR re-fetch the page token -->
                  <div class="grid grid-cols-2 gap-2">
                    <button
                      @click="refreshToken(account)"
                      :disabled="refreshingTokenId === account.id"
                      class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100 disabled:opacity-50"
                      title="Extend user_access_token by 60 days using the stored token"
                    >
                      {{ refreshingTokenId === account.id ? 'Extending…' : 'Extend User Token' }}
                    </button>
                    <button
                      @click="regeneratePageToken(account)"
                      :disabled="regeneratingPageTokenId === account.id"
                      class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50"
                      title="Re-fetch page_access_token using the stored user_access_token"
                    >
                      {{ regeneratingPageTokenId === account.id ? 'Fetching…' : 'Refresh Page Token' }}
                    </button>
                  </div>
                </div>
              </article>
            </div>
          </section>

          <section v-else-if="activeSection === 'youtube-videos'" class="space-y-6">
            <div class="flex items-center justify-between gap-4">
              <div>
                <h2 class="text-3xl font-black tracking-tight text-slate-950">YouTube Videos</h2>
                <p class="mt-1 text-sm text-slate-500">Manage your YouTube channel content and uploads.</p>
              </div>

              <button class="inline-flex items-center rounded-xl bg-[var(--color-custom-primary)] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#b71a1f]">
                <PlusIcon class="mr-2 h-4 w-4" />
                Upload Video
              </button>
            </div>

            <div class="grid gap-5 xl:grid-cols-3">
              <article
                v-for="video in youtubeVideos"
                :key="video.id"
                class="overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm"
              >
                <div class="relative h-[172px] overflow-hidden bg-slate-100">
                  <img :src="video.thumbnail" :alt="video.title" class="h-full w-full object-cover" />
                  <span class="absolute left-3 top-3 rounded-md px-2 py-1 text-[11px] font-bold text-white" :class="video.status === 'processing' ? 'bg-amber-500' : (video.status === 'draft' ? 'bg-slate-600' : 'bg-emerald-500')">
                    {{ prettyStatus(video.status) }}
                  </span>
                  <span class="absolute bottom-3 right-3 rounded-md bg-black/80 px-2 py-1 text-[11px] font-bold text-white">
                    {{ video.duration }}
                  </span>
                </div>

                <div class="px-4 py-4">
                  <div class="flex items-start justify-between gap-3">
                    <div class="text-xl font-black leading-6 text-slate-950">{{ video.title }}</div>
                    <EllipsisVerticalIcon class="h-5 w-5 text-slate-400" />
                  </div>
                  <div class="mt-2 text-sm text-slate-400">{{ video.subtitle }}</div>
                </div>

                <div class="flex items-center gap-5 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                  <span class="inline-flex items-center gap-1"><EyeIcon class="h-4 w-4" /> {{ video.views }}</span>
                  <span class="inline-flex items-center gap-1"><HandThumbUpIcon class="h-4 w-4" /> {{ video.likes }}</span>
                  <span class="inline-flex items-center gap-1"><ChatBubbleOvalLeftIcon class="h-4 w-4" /> {{ video.comments }}</span>
                </div>
              </article>
            </div>
          </section>

          <section v-else class="space-y-6">
            <div class="flex items-center justify-between gap-4">
              <div>
                <h2 class="text-3xl font-black tracking-tight text-slate-950">Comments Management</h2>
                <p class="mt-1 text-sm text-slate-500">Review and moderate comments on your YouTube videos.</p>
              </div>

              <div class="relative min-w-[260px]">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                  v-model="youtubeCommentSearch"
                  class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm outline-none placeholder:text-slate-400 focus:border-[var(--color-custom-primary)]"
                  placeholder="Search comments..."
                />
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-3">
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="filter in youtubeCommentFilters"
                  :key="filter"
                  @click="youtubeCommentStatus = filter"
                  class="rounded-xl px-4 py-2 text-sm font-bold transition"
                  :class="youtubeCommentStatus === filter ? 'bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]' : 'text-slate-600 hover:bg-slate-100'"
                >
                  {{ prettyFilter(filter) }}
                </button>
              </div>
            </div>

            <div class="overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-white">
                  <tr class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-400">
                    <th class="px-4 py-4">Comment</th>
                    <th class="px-4 py-4">Video</th>
                    <th class="px-4 py-4">Date</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="comment in filteredYoutubeComments" :key="comment.id">
                    <td class="px-4 py-4">
                      <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-sm font-black text-slate-600">
                          <img v-if="comment.avatar" :src="comment.avatar" :alt="comment.author" class="h-full w-full object-cover" />
                          <span v-else>{{ initials(comment.author) }}</span>
                        </div>
                        <div>
                          <div class="font-black text-slate-950">{{ comment.author }}</div>
                          <div class="mt-1 text-sm text-slate-600">{{ comment.text }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-4 text-sm text-slate-600">{{ comment.video }}</td>
                    <td class="px-4 py-4 text-sm text-slate-500">{{ comment.date }}</td>
                    <td class="px-4 py-4">
                      <span class="rounded-md px-2 py-1 text-xs font-bold" :class="statusChipClass(comment.status)">
                        {{ prettyStatus(comment.status) }}
                      </span>
                    </td>
                    <td class="px-4 py-4">
                      <div class="flex items-center justify-end gap-3 text-slate-400">
                        <CheckCircleIcon v-if="comment.status === 'pending'" class="h-4 w-4 text-emerald-500" />
                        <XCircleIcon v-if="comment.status === 'pending'" class="h-4 w-4 text-red-500" />
                        <ChatBubbleOvalLeftIcon class="h-4 w-4 text-[var(--color-custom-primary)]" />
                        <EllipsisVerticalIcon class="h-4 w-4" />
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </main>
      </div>
    </div>

    <Transition name="fade">
      <div
        v-if="showComposer"
        class="fixed inset-0 z-50 bg-slate-900/30 backdrop-blur-sm"
        @click.self="closeComposer"
      >
        <div class="mx-auto my-8 w-[min(1180px,calc(100vw-2rem))] rounded-[28px] bg-white shadow-2xl flex flex-col max-h-[calc(100vh-4rem)]">
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
              <div class="text-2xl font-black text-slate-950">{{ editingPostId ? 'Update Post' : 'Create Post' }}</div>
              <div class="mt-1 text-sm text-slate-500">Draft once and preview it before scheduling or publishing.</div>
            </div>
            <button @click="closeComposer" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">
              Close
            </button>
          </div>

          <div class="grid gap-6 p-6 xl:grid-cols-[1.05fr,0.95fr] overflow-y-auto flex-1 min-h-0">
            <div class="space-y-5">
              <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Campaign Title</label>
                <input
                  v-model="postForm.title"
                  class="input-base"
                  :disabled="Boolean(editingPostId)"
                  placeholder="Wheel alignment weekend offer"
                />
                <p v-if="editingPostId" class="mt-2 text-xs text-slate-400">Title is locked while editing. Only the post content can change.</p>
              </div>

              <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Caption</label>
                <textarea
                  v-model="postForm.content"
                  rows="6"
                  class="input-base resize-none"
                  placeholder="Write your post copy, CTA, and hashtags."
                />
              </div>

              <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Photos / Videos</label>
                <div
                  v-if="!editingPostId"
                  class="relative rounded-2xl border-2 border-dashed px-6 py-8 text-center transition"
                  :class="dragOver ? 'border-[var(--color-custom-primary)] bg-[rgba(211,30,36,0.06)]' : 'border-slate-200 bg-slate-50 hover:border-slate-300'"
                  @dragover.prevent="dragOver = true"
                  @dragleave="dragOver = false"
                  @drop.prevent="onFileDrop($event)"
                >
                  <input
                    ref="fileInputRef"
                    type="file"
                    accept="image/jpeg,image/png,video/mp4,video/quicktime,video/avi,video/webm"
                    class="absolute inset-0 cursor-pointer opacity-0"
                    @change="onFileSelect($event)"
                  />
                  <PhotoIcon class="mx-auto h-8 w-8 text-slate-300" />
                  <div class="mt-3 text-sm font-bold text-slate-600">Drop a file here or click to browse</div>
                  <div class="mt-1 text-xs text-slate-400">One image (JPG, PNG) or video per post. Instagram requires an image or video.</div>
                  <div v-if="uploadingMedia" class="mt-3 text-xs font-bold text-[var(--color-custom-primary)]">Uploading…</div>
                </div>

                <div
                  v-else
                  class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800"
                >
                  Image updates are locked in edit mode. You can update the text only, as requested.
                </div>

                <div v-if="mediaFiles.length" class="mt-4 flex flex-wrap gap-3">
                  <div
                    v-for="(file, index) in mediaFiles"
                    :key="`${file.url}-${index}`"
                    class="relative h-24 w-24 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100"
                  >
                    <video v-if="file.isVideo" :src="file.previewUrl || file.url" class="h-full w-full object-cover" muted />
                    <img v-else :src="file.previewUrl || file.url" :alt="file.name" class="h-full w-full object-cover" />
                    <button
                      v-if="!editingPostId"
                      @click.prevent="removeMedia(index)"
                      class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-slate-900/70 text-[10px] font-black text-white hover:bg-[var(--color-custom-primary)]"
                    >
                      ✕
                    </button>
                  </div>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Publish To</label>
                  <div v-if="!editingPostId" class="space-y-2">
                    <label
                      v-for="account in connectedAccounts"
                      :key="account.id"
                      class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3"
                    >
                      <input
                        :checked="postForm.account_ids.includes(account.id)"
                        :disabled="editingPostId && postHasExternalTargets"
                        type="checkbox"
                        class="rounded border-slate-300 text-[var(--color-custom-primary)] focus:ring-[var(--color-custom-primary)]"
                        @change="togglePostAccount(account.id)"
                      />
                      <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" :class="platformPillClass(account.platform)">
                        {{ platformLabel(account.platform) }}
                      </span>
                      <span class="truncate text-sm font-semibold text-slate-700">{{ account.username || account.name }}</span>
                    </label>
                  </div>
                  <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-for="account in connectedAccounts.filter((account) => postForm.account_ids.includes(account.id))"
                        :key="`locked-${account.id}`"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700"
                      >
                        <span class="rounded-full px-2 py-0.5 text-[10px]" :class="platformPillClass(account.platform)">{{ platformLabel(account.platform) }}</span>
                        {{ account.username || account.name }}
                      </span>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Target accounts are locked in content-only edit mode.</p>
                  </div>
                </div>

                <div v-if="!editingPostId" class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                  <label class="flex items-center gap-3 text-sm font-bold text-slate-700">
                    <input
                      v-model="postForm.publish_now"
                      type="checkbox"
                      class="rounded border-slate-300 text-[var(--color-custom-primary)] focus:ring-[var(--color-custom-primary)]"
                    />
                    Publish immediately
                  </label>

                  <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Scheduled Time</label>
                    <input
                      v-model="postForm.scheduled_for"
                      type="datetime-local"
                      class="input-base"
                      :disabled="postForm.publish_now"
                    />
                  </div>
                </div>
                <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                  <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Locked Publish Settings</div>
                  <div class="mt-3 text-sm font-semibold text-slate-700">{{ composerStatusText }}</div>
                  <p class="mt-2 text-xs text-slate-400">Scheduling and publish options stay unchanged while editing existing content.</p>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Post Preview</div>
                <div class="mt-4 grid gap-4">
                  <article
                    v-for="preview in composerPreviewPlatforms"
                    :key="preview.key"
                    :class="previewCardShellClass(preview.key)"
                  >
                    <div class="flex items-center justify-between px-4 py-4" :class="previewCardHeaderClass(preview.key)">
                      <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full text-xs font-black" :class="platformBadgeClass(preview.key)">
                          {{ platformShort(preview.key) }}
                        </div>
                        <div>
                          <div class="text-sm font-black" :class="previewCardTitleClass(preview.key)">{{ preview.name }}</div>
                          <div class="text-xs" :class="previewCardMutedTextClass(preview.key)">{{ composerStatusText }}</div>
                        </div>
                      </div>
                      <span class="rounded-md px-2 py-1 text-[11px] font-bold" :class="statusChipClass(postPreviewStatus)">
                        {{ prettyStatus(postPreviewStatus) }}
                      </span>
                    </div>

                    <div class="px-4">
                      <p class="whitespace-pre-line text-sm leading-6" :class="previewCardBodyClass(preview.key)">{{ postForm.content || 'Your caption preview will appear here.' }}</p>
                    </div>

                    <div v-if="mediaFiles.length" class="mt-4">
                      <div class="h-[220px] overflow-hidden" :class="previewCardMediaClass(preview.key)">
                        <video
                          v-if="mediaFiles[0]?.isVideo"
                          :src="mediaFiles[0]?.previewUrl || mediaFiles[0]?.url"
                          class="h-full w-full object-cover"
                          controls
                          muted
                        />
                        <img
                          v-else
                          :src="mediaFiles[0]?.previewUrl || mediaFiles[0]?.url"
                          :alt="mediaFiles[0]?.name || 'Preview image'"
                          class="h-full w-full object-cover"
                        />
                      </div>
                    </div>

                    <div class="flex items-center justify-between px-4 py-3 text-sm" :class="previewCardFooterClass(preview.key)">
                      <div class="flex items-center gap-4">
                        <span class="inline-flex items-center gap-1"><HeartIcon class="h-4 w-4" /> 245</span>
                        <span class="inline-flex items-center gap-1"><ChatBubbleOvalLeftIcon class="h-4 w-4" /> 12</span>
                        <span class="inline-flex items-center gap-1"><ShareIcon class="h-4 w-4" /> 34</span>
                      </div>
                    </div>
                  </article>
                </div>
              </div>
            </div>
          </div>

          <div v-if="postError" class="border-t border-slate-200 px-6 py-4 text-sm text-red-600">
            {{ postError }}
          </div>

          <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-5">
            <button @click="closeComposer" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">
              Cancel
            </button>
            <button
              :disabled="savingPost || uploadingMedia"
              @click="savePost"
              class="rounded-xl bg-[var(--color-custom-primary)] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#b71a1f] disabled:opacity-60"
            >
              {{ savingPost ? 'Saving...' : (editingPostId ? 'Update Text' : 'Save Post') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div
        v-if="showAccountPanel"
        class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm"
        @click.self="closeAccountPanel"
      >
        <div class="mx-auto mt-10 w-[min(900px,calc(100vw-2rem))] rounded-[28px] bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
              <div class="text-2xl font-black text-slate-950">{{ editingAccountId ? 'Update Account' : 'Connect Account' }}</div>
              <div class="mt-1 text-sm text-slate-500">Enter the platform credentials and profile details.</div>
            </div>
            <button @click="closeAccountPanel" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">
              Close
            </button>
          </div>

          <div class="grid gap-4 p-6 md:grid-cols-2">
            <div>
              <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Platform</label>
              <select v-model="accountForm.platform" class="input-base" :disabled="Boolean(editingAccountId)">
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="tiktok">TikTok</option>
              </select>
            </div>
            <div>
              <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Display Name</label>
              <input v-model="accountForm.name" class="input-base" placeholder="Premax Autocare" />
            </div>
            <div>
              <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Username</label>
              <input v-model="accountForm.username" class="input-base" placeholder="@premaxautocare" />
            </div>
            <div>
              <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Followers</label>
              <input v-model.number="accountForm.followers_count" type="number" min="0" class="input-base" />
            </div>
          </div>

          <div class="border-t border-slate-200 px-6 py-6">
            <div class="mb-4 text-sm font-bold text-slate-700">Credentials</div>

            <!-- Instagram auto-sync banner: shown when a connected FB account exists -->
            <div
              v-if="accountForm.platform === 'instagram' && linkedFacebookAccount"
              class="mb-4 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800"
            >
              <span class="mt-0.5 shrink-0 text-base">🔗</span>
              <div>
                <span class="font-bold">Auto-synced from Facebook ({{ linkedFacebookAccount.name }}).</span>
                App credentials and access token are shared with your Facebook account and kept in sync automatically.
                Only the <span class="font-bold">Instagram Business Account ID</span> is needed here.
              </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <div v-for="field in visibleCredentialFields" :key="field.key">
                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">
                  {{ field.label }}
                </label>
                <input
                  v-model="accountForm.credentials[field.key]"
                  :type="field.secret ? 'password' : 'text'"
                  class="input-base"
                  :placeholder="(editingAccountId && field.secret) ? 'Leave blank to keep existing' : credentialPlaceholder(field)"
                />
              </div>
            </div>
          </div>

          <div v-if="accountError" class="border-t border-slate-200 px-6 py-4 text-sm text-red-600">
            {{ accountError }}
          </div>

          <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-5">
            <button @click="closeAccountPanel" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">
              Cancel
            </button>
            <button
              :disabled="savingAccount"
              @click="saveAccount"
              class="rounded-xl bg-[var(--color-custom-primary)] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#b71a1f] disabled:opacity-60"
            >
              {{ savingAccount ? 'Saving...' : (editingAccountId ? 'Update Account' : 'Connect Account') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div
        v-if="showPostCommentsPanel"
        class="fixed inset-0 z-50 bg-slate-900/30 backdrop-blur-sm"
        @click.self="closePostComments"
      >
        <div class="mx-auto mt-8 w-[min(980px,calc(100vw-2rem))] rounded-[28px] bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
              <div class="text-2xl font-black text-slate-950">Post Comments</div>
              <div class="mt-1 text-sm text-slate-500">
                {{ selectedPostCommentsTitle }}
              </div>
            </div>
            <button @click="closePostComments" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">
              Close
            </button>
          </div>

          <div class="grid min-h-[560px] gap-0 lg:grid-cols-[0.95fr,1.05fr]">
            <aside class="border-b border-slate-200 bg-slate-50 p-5 lg:border-b-0 lg:border-r">
              <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Post Preview</div>
                <div class="mt-3 text-sm font-semibold text-slate-900">
                  {{ selectedPostForComments?.title || 'Untitled Post' }}
                </div>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">
                  {{ selectedPostForComments?.content || 'No caption available.' }}
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                  <span class="rounded-full px-3 py-1 text-xs font-bold" :class="platformPillClass(selectedCommentsPlatform)">
                    {{ platformLabel(selectedCommentsPlatform) }}
                  </span>
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                    {{ selectedPostComments.length }} comment{{ selectedPostComments.length === 1 ? '' : 's' }}
                  </span>
                </div>
              </div>
            </aside>

            <section class="flex min-h-0 flex-col">
              <div class="border-b border-slate-200 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                  <div class="text-sm font-bold text-slate-700">Customer Interactions</div>
                  <button
                    @click="refreshCommentsForPost"
                    class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                  >
                    Refresh
                  </button>
                </div>
              </div>

              <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5">
                <article
                  v-for="comment in selectedPostComments"
                  :key="comment.id"
                  class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                  <div class="flex items-start justify-between gap-4">
                    <div>
                      <div class="flex items-center gap-2">
                        <div class="font-black text-slate-950">{{ comment.author_name }}</div>
                        <span v-if="comment.author_handle" class="text-xs text-slate-400">{{ comment.author_handle }}</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="platformPillClass(comment.account?.platform || selectedCommentsPlatform)">
                          {{ platformLabel(comment.account?.platform || selectedCommentsPlatform) }}
                        </span>
                      </div>
                      <div class="mt-1 text-xs text-slate-400">{{ formatCommentTime(comment.received_at || comment.created_at) }}</div>
                    </div>
                    <span class="rounded-md px-2 py-1 text-[11px] font-bold" :class="statusChipClass(comment.status)">
                      {{ prettyStatus(comment.status) }}
                    </span>
                  </div>

                  <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ comment.comment_text }}</p>

                  <div v-if="comment.reply_text" class="mt-4 rounded-2xl border border-[rgba(211,30,36,0.12)] bg-[rgba(211,30,36,0.05)] px-4 py-3">
                    <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-[var(--color-custom-primary)]">Reply Sent</div>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ comment.reply_text }}</p>
                  </div>

                  <div class="mt-4 flex flex-wrap gap-2">
                    <button
                      @click="setCommentStatus(comment, 'needs_reply')"
                      class="rounded-lg border px-3 py-2 text-xs font-bold transition"
                      :class="comment.status === 'needs_reply' ? 'border-[var(--color-custom-primary)] bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                    >
                      Needs Reply
                    </button>
                    <button
                      @click="setCommentStatus(comment, 'ignored')"
                      class="rounded-lg border px-3 py-2 text-xs font-bold transition"
                      :class="comment.status === 'ignored' ? 'border-slate-400 bg-slate-100 text-slate-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                    >
                      Ignore
                    </button>
                  </div>

                  <div class="mt-4">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Reply</label>
                    <textarea
                      v-model="commentReplyDrafts[comment.id]"
                      rows="3"
                      class="input-base resize-none"
                      placeholder="Write a helpful reply..."
                    />
                    <div class="mt-3 flex justify-end">
                      <button
                        @click="replyToComment(comment)"
                        :disabled="replyingCommentId === comment.id || !String(commentReplyDrafts[comment.id] || '').trim()"
                        class="inline-flex items-center rounded-xl bg-[var(--color-custom-primary)] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#b71a1f] disabled:opacity-50"
                      >
                        <PaperAirplaneIcon class="mr-2 h-4 w-4" />
                        {{ replyingCommentId === comment.id ? 'Sending...' : 'Reply to Comment' }}
                      </button>
                    </div>
                  </div>
                </article>

                <div v-if="!selectedPostComments.length" class="rounded-2xl border border-dashed border-slate-200 px-6 py-12 text-center text-sm text-slate-400">
                  No synced comments for this post yet.
                </div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import {
  BellIcon,
  ChatBubbleOvalLeftIcon,
  CheckCircleIcon,
  ChevronLeftIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  FaceSmileIcon,
  FunnelIcon,
  HandThumbUpIcon,
  HeartIcon,
  MagnifyingGlassIcon,
  PaperAirplaneIcon,
  PaperClipIcon,
  PhoneIcon,
  PhotoIcon,
  PlusIcon,
  ShareIcon,
  UserGroupIcon,
  VideoCameraIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const { get, post, patch, del } = useApi()

const menuItems = [
  { key: 'posts', label: 'Posts', icon: PhotoIcon },
  { key: 'inbox', label: 'Inbox', icon: ChatBubbleOvalLeftIcon },
  { key: 'accounts', label: 'Accounts', icon: UserGroupIcon },
  //{ key: 'youtube-videos', label: 'YouTube Videos', icon: VideoCameraIcon },
  //{ key: 'youtube-comments', label: 'YT Comments', icon: ChatBubbleOvalLeftIcon },
]

const postFilters = ['all', 'published', 'scheduled', 'draft']
const platformFilterOptions = [
  { value: 'all', label: 'All Platforms' },
  { value: 'facebook', label: 'Facebook' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'tiktok', label: 'TikTok' },
]
const groupByOptions = [
  { value: 'platform', label: 'Platform' },
  { value: 'none', label: 'None' },
]
const youtubeCommentFilters = ['all', 'pending', 'approved', 'spam']

const activeSection = ref('posts')
const globalSearch = ref('')
const postSearch = ref('')
const postStatusFilter = ref('all')
const postPlatformFilter = ref('all')
const postGroupBy = ref('platform')
const showPostFilters = ref(false)
const conversationSearch = ref('')
const youtubeCommentSearch = ref('')
const youtubeCommentStatus = ref('all')

const loading = ref(false)
const savingPost = ref(false)
const savingAccount = ref(false)
const uploadingMedia = ref(false)
const refreshingTokenId = ref(null)
const connectingOAuthId = ref(null)
const connectingTikTokId = ref(null)
const refreshingTikTokTokenId = ref(null)
const regeneratingPageTokenId = ref(null)
const syncingPostsId = ref(null)
const syncingMessagesId = ref(null)
const retryingPostId = ref(null)
const dragOver = ref(false)
const fileInputRef = ref(null)

const showComposer = ref(false)
const showAccountPanel = ref(false)
const showPostCommentsPanel = ref(false)
const editingPostId = ref(null)
const editingAccountId = ref(null)
const activeConversationId = ref(null)
const messagesLoading = ref(false)
const messageAreaRef = ref(null)
const selectedPostCommentsId = ref(null)
const selectedCommentsPlatform = ref('all')
const replyingCommentId = ref(null)

const postError = ref(null)
const accountError = ref(null)
const messageDraft = ref('')
const commentReplyDrafts = ref({})
const postComments = ref([])

const summary = ref({})
const platforms = ref({})
const accounts = ref([])
const posts = ref([])
const comments = ref([])
const conversations = ref([])
const connectionBlueprints = ref({})
const mediaFiles = ref([])

const postForm = ref(defaultPostForm())
const accountForm = ref(defaultAccountForm())

const youtubeVideos = ref([
  {
    id: 1,
    title: 'How to Change Your Oil in 10 Minutes | Premax Autocare',
    thumbnail: 'https://images.unsplash.com/photo-1487754180451-c456f719a1fc?auto=format&fit=crop&w=1200&q=80',
    status: 'published',
    duration: '10:24',
    subtitle: 'Oct 20, 2023',
    views: '12.4K',
    likes: '845',
    comments: '124',
  },
  {
    id: 2,
    title: 'Top 5 Warning Signs Your Brakes Need Replacing',
    thumbnail: 'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?auto=format&fit=crop&w=1200&q=80',
    status: 'published',
    duration: '08:15',
    subtitle: 'Oct 15, 2023',
    views: '8.2K',
    likes: '512',
    comments: '89',
  },
  {
    id: 3,
    title: 'Understanding Your Dashboard Warning Lights',
    thumbnail: 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1200&q=80',
    status: 'processing',
    duration: '12:45',
    subtitle: 'Uploading...',
    views: '-',
    likes: '-',
    comments: '-',
  },
  {
    id: 4,
    title: 'Winter Car Maintenance Tips - Prepare for the Cold',
    thumbnail: 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=80',
    status: 'draft',
    duration: '15:30',
    subtitle: 'Last edited today',
    views: '-',
    likes: '-',
    comments: '-',
  },
])

const youtubeComments = ref([
  {
    id: 1,
    author: 'CarEnthusiast99',
    text: 'Great tutorial! Very easy to follow. Can you do one on changing brake pads next?',
    video: 'How to Change Your Oil in 10 Minutes',
    date: '2 hours ago',
    status: 'approved',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=80',
  },
  {
    id: 2,
    author: 'NewDriver2023',
    text: 'I started hearing a squeaking noise yesterday. This video confirmed my suspicions. Booking an appointment now!',
    video: 'Top 5 Warning Signs Your Brakes Need Replacing',
    date: '5 hours ago',
    status: 'pending',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80',
  },
  {
    id: 3,
    author: 'SpamBot5000',
    text: 'CLICK HERE TO WIN A FREE CAR!!! www.totallynotascam.com',
    video: 'How to Change Your Oil in 10 Minutes',
    date: '1 day ago',
    status: 'spam',
    avatar: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=300&q=80',
  },
  {
    id: 4,
    author: 'SarahJenkins',
    text: 'The check engine light section was super helpful. Turns out it was just a loose gas cap!',
    video: 'Understanding Your Dashboard Warning Lights',
    date: '2 days ago',
    status: 'approved',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80',
  },
])

const authInitials = computed(() => {
  const name = auth.user?.name || 'SH'
  return name.split(' ').slice(0, 2).map((part) => part[0]?.toUpperCase() || '').join('')
})

const currentSectionLabel = computed(() => (
  menuItems.find((item) => item.key === activeSection.value)?.label || 'Social Media'
))

const connectedAccounts = computed(() => accounts.value.filter((account) => account.auth_status === 'connected'))

const selectedPostForComments = computed(() => (
  posts.value.find((post) => post.id === selectedPostCommentsId.value) || null
))

const selectedPostComments = computed(() => {
  return postComments.value
})

const selectedPostCommentsTitle = computed(() => {
  if (!selectedPostForComments.value) return 'Review incoming comments and reply in one place.'
  const platformText = selectedCommentsPlatform.value === 'all' ? 'all platforms' : platformLabel(selectedCommentsPlatform.value)
  return `${selectedPostForComments.value.title || 'Untitled post'} · ${platformText}`
})

const hasActivePostFilters = computed(() => (
  postPlatformFilter.value !== 'all' || postGroupBy.value !== 'platform'
))

const activePostFilterCount = computed(() => (
  Number(postPlatformFilter.value !== 'all') + Number(postGroupBy.value !== 'platform')
))

const searchedPosts = computed(() => {
  const term = `${globalSearch.value} ${postSearch.value}`.trim().toLowerCase()

  return posts.value.filter((post) => {
    const statusMatch = postStatusFilter.value === 'all' || post.status === postStatusFilter.value
    const platformMatch = postPlatformFilter.value === 'all'
      || (post.targets || []).some((target) => target.account?.platform === postPlatformFilter.value)

    if (!term) return statusMatch && platformMatch

    const haystack = [
      post.title,
      post.content,
      ...(post.targets || []).map((target) => target.account?.name || ''),
      ...(post.targets || []).map((target) => target.account?.username || ''),
      ...(post.targets || []).map((target) => target.account?.platform || ''),
    ].join(' ').toLowerCase()

    return statusMatch && platformMatch && haystack.includes(term)
  })
})

const postGroups = computed(() => {
  if (postGroupBy.value === 'none') {
    return [{
      key: 'all-posts',
      platform: postPlatformFilter.value === 'all' ? 'unassigned' : postPlatformFilter.value,
      label: postPlatformFilter.value === 'all' ? 'All Posts' : platformLabel(postPlatformFilter.value),
      posts: searchedPosts.value,
    }]
  }

  const grouped = new Map()

  for (const post of searchedPosts.value) {
    const targets = post.targets?.length ? post.targets : [{ account: { platform: 'unassigned', name: 'Unassigned' }, id: `u-${post.id}` }]

    for (const target of targets) {
      const platform = target.account?.platform || 'unassigned'
      const key = `${platform}-${target.account?.id || 'none'}`

      if (!grouped.has(key)) {
        grouped.set(key, {
          key,
          platform,
          label: target.account?.name || platformLabel(platform),
          posts: [],
        })
      }

      grouped.get(key).posts.push(post)
    }
  }

  return Array.from(grouped.values()).map((group) => ({
    ...group,
    posts: uniqueById(group.posts),
  }))
})

const filteredConversations = computed(() => {
  const term = `${globalSearch.value} ${conversationSearch.value}`.trim().toLowerCase()

  return conversations.value.filter((conversation) => {
    if (!term) return true

    return [
      conversation.customer_name,
      conversation.customer_handle,
      conversation.last_message_preview,
      conversation.account?.platform,
    ]
      .filter(Boolean)
      .some((value) => value.toLowerCase().includes(term))
  })
})

const activeConversation = computed(() => (
  filteredConversations.value.find((conversation) => conversation.id === activeConversationId.value)
  || conversations.value.find((conversation) => conversation.id === activeConversationId.value)
  || null
))

const currentAccountBlueprint = computed(() => (
  connectionBlueprints.value[accountForm.value.platform] || { credentials: [] }
))

const editingPost = computed(() => posts.value.find((post) => post.id === editingPostId.value) || null)

// The connected Facebook account, used to auto-fill shared Instagram credentials
const linkedFacebookAccount = computed(() =>
  accounts.value.find((a) => a.platform === 'facebook' && a.auth_status === 'connected') || null
)

// Fields that are auto-filled from Facebook — hide them in the IG form when FB is linked
const igAutoFilledKeys = ['app_id', 'app_secret', 'access_token', 'page_id']

const visibleCredentialFields = computed(() => {
  const fields = currentAccountBlueprint.value?.credentials || []
  if (accountForm.value.platform !== 'instagram' || !linkedFacebookAccount.value) return fields
  return fields.filter((f) => !igAutoFilledKeys.includes(f.key))
})

const postHasExternalTargets = computed(() => (
  Boolean(editingPost.value?.targets?.some((target) => target.external_post_id))
))

const composerStatusText = computed(() => {
  if (editingPost.value) {
    if (editingPost.value.status === 'published') {
      return `Published ${formatDateTime(editingPost.value.published_at)}`
    }
    if (editingPost.value.status === 'scheduled') {
      return `Scheduled for ${formatDateTime(editingPost.value.scheduled_for)}`
    }
    return 'Saved as draft'
  }

  if (postForm.value.publish_now) return 'Publishing immediately'
  if (postForm.value.scheduled_for) return `Scheduled for ${formatDateTime(postForm.value.scheduled_for)}`
  return 'Saved as draft'
})

const postPreviewStatus = computed(() => (
  editingPost.value?.status || (postForm.value.publish_now ? 'published' : (postForm.value.scheduled_for ? 'scheduled' : 'draft'))
))

const composerPreviewPlatforms = computed(() => {
  const selected = connectedAccounts.value.filter((account) => postForm.value.account_ids.includes(account.id))
  return selected.length
    ? selected.map((account) => ({ key: account.platform, name: account.name }))
    : [{ key: 'facebook', name: 'Social preview' }]
})

const filteredYoutubeComments = computed(() => {
  const term = `${globalSearch.value} ${youtubeCommentSearch.value}`.trim().toLowerCase()

  return youtubeComments.value.filter((comment) => {
    const statusMatch = youtubeCommentStatus.value === 'all' || comment.status === youtubeCommentStatus.value
    if (!term) return statusMatch
    return statusMatch && [comment.author, comment.text, comment.video].join(' ').toLowerCase().includes(term)
  })
})

watch(
  () => route.query.section,
  (section) => {
    if (typeof section === 'string' && menuItems.some((item) => item.key === section)) {
      activeSection.value = section
    }
  },
  { immediate: true },
)

watch(filteredConversations, (list) => {
  if (!list.length) {
    activeConversationId.value = null
    return
  }

  if (!list.some((conversation) => conversation.id === activeConversationId.value)) {
    activeConversationId.value = list[0].id
  }
})

onMounted(async () => {
  // Handle redirect back from OAuth flows (Facebook or TikTok)
  const oauthResult = route.query.oauth
  const oauthPlatform = route.query.platform || 'facebook'

  if (oauthResult === 'success') {
    const label = oauthPlatform === 'tiktok' ? 'TikTok' : 'Facebook'
    toast.success(`${label} connected — tokens stored automatically.`)
    router.replace({ query: { ...route.query, oauth: undefined, platform: undefined } })
  } else if (oauthResult === 'denied') {
    const label = oauthPlatform === 'tiktok' ? 'TikTok' : 'Facebook'
    toast.warning(`${label} authorization was cancelled.`)
    router.replace({ query: { ...route.query, oauth: undefined, platform: undefined } })
  } else if (oauthResult === 'error') {
    const label = oauthPlatform === 'tiktok' ? 'TikTok' : 'Facebook'
    toast.error(`${label} OAuth failed. Check logs for details.`)
    router.replace({ query: { ...route.query, oauth: undefined, platform: undefined } })
  }

  await loadData()
})

watch(activeConversationId, async (id) => {
  if (!id) return
  messagesLoading.value = true
  try {
    const data = await get(`/admin/social-media/conversations/${id}/messages`)
    conversations.value = conversations.value.map((c) =>
      c.id === data.conversation_id ? { ...c, messages: data.messages, unread_count: 0 } : c,
    )
    scrollMessagesToBottom()
  } catch {
    // silently fall back to preloaded messages
  } finally {
    messagesLoading.value = false
  }
})

function scrollMessagesToBottom() {
  nextTick(() => {
    if (messageAreaRef.value) {
      messageAreaRef.value.scrollTop = messageAreaRef.value.scrollHeight
    }
  })
}

function defaultPostForm() {
  return {
    title: '',
    content: '',
    account_ids: [],
    publish_now: false,
    scheduled_for: '',
  }
}

function defaultAccountForm() {
  return {
    platform: 'facebook',
    name: '',
    username: '',
    followers_count: 0,
    credentials: {},
  }
}

function setActiveSection(section) {
  activeSection.value = section
  router.replace({ query: { ...route.query, section } })
}

function resetPostFilters() {
  postPlatformFilter.value = 'all'
  postGroupBy.value = 'platform'
}

function openPostComments(post, platform = 'all') {
  selectedPostCommentsId.value = post.id
  selectedCommentsPlatform.value = platform
  showPostCommentsPanel.value = true
  void loadPostComments(post.id, platform)
}

function closePostComments() {
  showPostCommentsPanel.value = false
  selectedPostCommentsId.value = null
  selectedCommentsPlatform.value = 'all'
  commentReplyDrafts.value = {}
  postComments.value = []
  replyingCommentId.value = null
}

async function refreshCommentsForPost() {
  if (!selectedPostForComments.value) return

  try {
    const response = await post(`/admin/social-media/posts/${selectedPostForComments.value.id}/sync-interactions`)
    const refreshedPost = response.post

    if (refreshedPost) {
      posts.value = posts.value.map((item) => item.id === refreshedPost.id ? refreshedPost : item)
    }

    const syncedPlatforms = (response.results || [])
      .filter((item) => item.status === 'synced')
      .map((item) => platformLabel(item.platform))

    if (syncedPlatforms.length) {
      toast.success(`Interactions synced from ${syncedPlatforms.join(', ')}.`)
    } else {
      const firstMessage = response.results?.find((item) => item.message)?.message
      toast.warning(firstMessage || 'No live platform interactions were synced for this post.')
    }

    await loadData()
    await loadPostComments(selectedPostForComments.value.id, selectedCommentsPlatform.value)
  } catch (error) {
    toast.error(error.response?.data?.message || error.response?.data?.errors?.post?.[0] || 'Unable to sync live interactions for this post.')
  }
}

async function loadPostComments(postId, platform = 'all') {
  try {
    const response = await get(`/admin/social-media/posts/${postId}/comments${platform && platform !== 'all' ? `?platform=${platform}` : ''}`)
    postComments.value = response.comments || []
    commentReplyDrafts.value = Object.fromEntries(
      postComments.value.map((comment) => [comment.id, comment.reply_text || '']),
    )
  } catch (error) {
    postComments.value = []
    commentReplyDrafts.value = {}
    toast.error(error.response?.data?.message || 'Unable to load post comments.')
  }
}

function openComposer(post = null) {
  postError.value = null
  showComposer.value = true

  if (!post) {
    resetPostForm()
    return
  }

  editingPostId.value = post.id
  postForm.value = {
    title: post.title || '',
    content: post.content || '',
    account_ids: uniqueById((post.targets || []).map((target) => ({ id: target.social_account_id }))).map((item) => item.id),
    publish_now: false,
    scheduled_for: post.scheduled_for ? toDateTimeLocal(post.scheduled_for) : '',
  }
  releaseMediaPreviewUrls()
  mediaFiles.value = hydrateMediaFiles(post.media || [])
}

function openPostEditor(post) {
  openComposer(post)
}

function closeComposer() {
  showComposer.value = false
  resetPostForm()
}

function resetPostForm() {
  editingPostId.value = null
  postForm.value = defaultPostForm()
  releaseMediaPreviewUrls()
  mediaFiles.value = []
  dragOver.value = false
}

function openAccountPanel(account = null) {
  accountError.value = null
  showAccountPanel.value = true

  if (!account) {
    editingAccountId.value = null
    accountForm.value = defaultAccountForm()
    return
  }

  editingAccountId.value = account.id

  // Secret fields come back masked (e.g. "****1234") — clear them so a save without
  // re-entering a secret keeps the existing value instead of storing the mask string.
  const sanitized = account.connection_setup?.credentials || {}
  const blueprint = connectionBlueprints.value[account.platform] || { credentials: [] }
  const secretKeys = new Set(blueprint.credentials.filter((f) => f.secret).map((f) => f.key))
  const safeCredentials = Object.fromEntries(
    Object.entries(sanitized).map(([k, v]) => [k, secretKeys.has(k) ? '' : v]),
  )

  accountForm.value = {
    platform: account.platform,
    name: account.name,
    username: account.username || '',
    followers_count: account.followers_count || 0,
    credentials: safeCredentials,
  }
}

function closeAccountPanel() {
  showAccountPanel.value = false
  editingAccountId.value = null
  accountForm.value = defaultAccountForm()
}

function togglePostAccount(accountId) {
  const next = new Set(postForm.value.account_ids)
  if (next.has(accountId)) next.delete(accountId)
  else next.add(accountId)
  postForm.value.account_ids = Array.from(next)
}

async function loadData() {
  loading.value = true

  try {
    const data = await get('/admin/social-media')
    summary.value = data.summary || {}
    platforms.value = data.platforms || {}
    accounts.value = data.accounts || []
    posts.value = data.posts || []
    comments.value = data.comments || []
    conversations.value = data.conversations || []
    connectionBlueprints.value = data.connection_blueprints || {}

    if (!activeConversationId.value && conversations.value.length) {
      activeConversationId.value = conversations.value[0].id
    }

    // Fire background interaction syncs so counts look live — no await, no spinner
    backgroundSyncInteractions()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to load social media data.')
  } finally {
    loading.value = false
  }
}

function backgroundSyncInteractions() {
  const toSyncIds = posts.value
    .filter((p) => p.status === 'published' && p.targets.some((t) => t.external_post_id))
    .map((p) => p.id)

  toSyncIds.forEach((id) => {
    post(`/admin/social-media/posts/${id}/sync-interactions`)
      .then((data) => {
        if (data?.post) {
          posts.value = posts.value.map((p) => (p.id === data.post.id ? data.post : p))
        }
      })
      .catch(() => {})
  })
}

function validatePostForm() {
  if (!editingPostId.value) {
    if (!postForm.value.title?.trim()) return 'Campaign title is required.'
    if (!postForm.value.content?.trim()) return 'Caption is required.'
    if (postForm.value.account_ids.length === 0) return 'Select at least one platform to post to.'

    const hasInstagram = connectedAccounts.value
      .filter((a) => postForm.value.account_ids.includes(a.id))
      .some((a) => a.platform === 'instagram')

    if (hasInstagram && mediaFiles.value.length === 0) {
      return 'Instagram requires a photo or video — please attach a file.'
    }

    if (!postForm.value.publish_now && !postForm.value.scheduled_for) {
      return 'Select a publish date or check "Publish immediately".'
    }
  } else {
    if (!postForm.value.content?.trim()) return 'Caption cannot be empty.'
  }
  return null
}

async function savePost() {
  postError.value = null

  const validationError = validatePostForm()
  if (validationError) {
    postError.value = validationError
    return
  }

  savingPost.value = true

  const payload = editingPostId.value
    ? { content: postForm.value.content }
    : {
        title: postForm.value.title.trim(),
        content: postForm.value.content,
        scheduled_for: postForm.value.publish_now ? null : (postForm.value.scheduled_for || null),
        account_ids: postForm.value.account_ids,
      }

  if (!editingPostId.value) {
    payload.publish_now = postForm.value.publish_now
    payload.media = mediaFiles.value.map((file) => file.url)
  }

  try {
    if (editingPostId.value) {
      const updated = await patch(`/admin/social-media/posts/${editingPostId.value}`, payload)
      posts.value = posts.value.map((p) => p.id === updated.id ? updated : p)
      toast.success('Post text updated.')
    } else {
      const { posts: saved } = await post('/admin/social-media/posts', payload)
      posts.value = [...saved, ...posts.value]
      const first = saved[0]
      toast.success(first?.status === 'published'
        ? `${saved.length} post(s) published.`
        : `${saved.length} post(s) scheduled.`)
    }

    closeComposer()
    await loadData()
  } catch (error) {
    const errors = error.response?.data?.errors
    if (errors) {
      const first = Object.values(errors).flat()[0]
      postError.value = first || 'Unable to save the post.'
    } else {
      postError.value = error.response?.data?.message || 'Unable to save the post.'
    }
  } finally {
    savingPost.value = false
  }
}

async function retryPost(postItem) {
  retryingPostId.value = postItem.id
  try {
    const updated = await post(`/admin/social-media/posts/${postItem.id}/publish`)
    posts.value = posts.value.map((p) => p.id === updated.id ? updated : p)
    toast.success('Post queued for publishing.')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Retry failed. Check account credentials.')
  } finally {
    retryingPostId.value = null
  }
}

async function deletePost(postItem) {
  const confirmed = window.confirm(`Delete "${postItem.title || 'this post'}"?`)
  if (!confirmed) return

  try {
    await del(`/admin/social-media/posts/${postItem.id}`)
    posts.value = posts.value.filter((post) => post.id !== postItem.id)
    toast.success('Post deleted.')
    await loadData()
  } catch (error) {
    toast.error(error.response?.data?.message || error.response?.data?.errors?.post?.[0] || 'Unable to delete the post.')
  }
}

async function sendMessage() {
  if (!activeConversation.value || !messageDraft.value.trim()) return

  try {
    const data = await post(`/admin/social-media/conversations/${activeConversation.value.id}/messages`, {
      body: messageDraft.value.trim(),
    })
    conversations.value = conversations.value.map((c) =>
      c.id === data.conversation.id ? data.conversation : c,
    )
    messageDraft.value = ''
    scrollMessagesToBottom()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Unable to send the message.')
  }
}

async function replyToComment(comment) {
  const replyText = String(commentReplyDrafts.value[comment.id] || '').trim()
  if (!replyText) return

  replyingCommentId.value = comment.id

  try {
    const updated = await post(`/admin/social-media/comments/${comment.id}/reply`, {
      reply_text: replyText,
      status: 'replied',
    })

    comments.value = comments.value.map((item) => item.id === updated.id ? updated : item)
    postComments.value = postComments.value.map((item) => item.id === updated.id ? updated : item)
    commentReplyDrafts.value = {
      ...commentReplyDrafts.value,
      [comment.id]: updated.reply_text || '',
    }
    toast.success('Reply sent.')
    await loadData()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Unable to reply to the comment.')
  } finally {
    replyingCommentId.value = null
  }
}

async function setCommentStatus(comment, status) {
  try {
    const updated = await patch(`/admin/social-media/comments/${comment.id}`, { status })
    comments.value = comments.value.map((item) => item.id === updated.id ? updated : item)
    postComments.value = postComments.value.map((item) => item.id === updated.id ? updated : item)
    toast.success(`Comment marked as ${prettyStatus(status).toLowerCase()}.`)
  } catch (error) {
    toast.error(error.response?.data?.message || 'Unable to update comment status.')
  }
}

async function saveAccount() {
  savingAccount.value = true
  accountError.value = null

  try {
    const payload = {
      platform: accountForm.value.platform,
      name: accountForm.value.name,
      username: accountForm.value.username || null,
      followers_count: Number(accountForm.value.followers_count || 0),
      credentials: compactCredentials(accountForm.value.credentials),
      auth_status: 'connected',
      status: 'active',
      capabilities: defaultCapabilitiesForPlatform(accountForm.value.platform),
    }

    if (editingAccountId.value) {
      await patch(`/admin/social-media/accounts/${editingAccountId.value}`, payload)
      toast.success('Account updated.')
    } else {
      await post('/admin/social-media/accounts', payload)
      toast.success('Account connected.')
    }

    closeAccountPanel()
    await loadData()
  } catch (error) {
    accountError.value = error.response?.data?.message || 'Unable to save account.'
  } finally {
    savingAccount.value = false
  }
}

async function syncAccount(account) {
  try {
    const synced = await post(`/admin/social-media/accounts/${account.id}/sync`)
    accounts.value = accounts.value.map((item) => item.id === synced.id ? synced : item)
    if (synced.auth_status === 'expired') {
      toast.warning(`${account.name} needs reconnecting. The token is expired or invalid.`)
    } else {
      toast.success(`${account.name} synced.`)
    }
    await loadData()
  } catch (error) {
    toast.error(error.response?.data?.message || error.response?.data?.errors?.account?.[0] || 'Unable to sync account.')
  }
}

async function refreshToken(account) {
  refreshingTokenId.value = account.id
  try {
    const updated = await post(`/admin/social-media/accounts/${account.id}/refresh-token`)
    accounts.value = accounts.value.map((item) => item.id === updated.id ? updated : item)
    toast.success(`${account.name} access token refreshed — valid for 60 days.`)
  } catch (error) {
    const msg = error.response?.data?.errors?.token?.[0]
      || error.response?.data?.errors?.credentials?.[0]
      || error.response?.data?.message
      || 'Token refresh failed. The token may be fully expired — update the credentials manually.'
    toast.error(msg)
  } finally {
    refreshingTokenId.value = null
  }
}

async function syncAccountPosts(account) {
  syncingPostsId.value = account.id
  try {
    const result = await post(`/admin/social-media/accounts/${account.id}/sync-posts`)
    toast.success(`Synced ${result.total} posts from ${account.name} (${result.created} new, ${result.synced} updated).`)
    await loadData()
  } catch (error) {
    const msg = error.response?.data?.errors?.sync?.[0]
      || error.response?.data?.message
      || 'Post sync failed. Check account credentials.'
    toast.error(msg)
  } finally {
    syncingPostsId.value = null
  }
}

async function syncAccountMessages(account) {
  syncingMessagesId.value = account.id
  try {
    const result = await post(`/admin/social-media/accounts/${account.id}/sync-messages`)
    toast.success(`${account.name}: ${result.synced_conversations} conversation(s), ${result.new_messages} new message(s) synced.`)
    await loadData()
  } catch (error) {
    const msg = error.response?.data?.errors?.sync?.[0]
      || error.response?.data?.message
      || 'Message sync failed. Check account credentials and permissions.'
    toast.error(msg)
  } finally {
    syncingMessagesId.value = null
  }
}

async function connectWithFacebook(account) {
  connectingOAuthId.value = account.id
  try {
    const data = await get(`/admin/social-media/accounts/${account.id}/oauth-url`)
    window.location.href = data.oauth_url
  } catch (error) {
    const msg = error.response?.data?.errors?.credentials?.[0]
      || error.response?.data?.message
      || 'Could not start Facebook login. Check that app_id and app_secret are saved.'
    toast.error(msg)
    connectingOAuthId.value = null
  }
}

async function connectWithTikTok(account) {
  connectingTikTokId.value = account.id
  try {
    const data = await get(`/admin/social-media/accounts/${account.id}/tiktok-oauth-url`)
    window.location.href = data.oauth_url
  } catch (error) {
    const msg = error.response?.data?.errors?.credentials?.[0]
      || error.response?.data?.message
      || 'Could not start TikTok login. Check that client_key and client_secret are saved.'
    toast.error(msg)
    connectingTikTokId.value = null
  }
}

async function refreshTikTokToken(account) {
  refreshingTikTokTokenId.value = account.id
  try {
    const updated = await post(`/admin/social-media/accounts/${account.id}/refresh-tiktok-token`)
    accounts.value = accounts.value.map((item) => item.id === updated.id ? updated : item)
    toast.success(`${account.name} TikTok access token refreshed.`)
  } catch (error) {
    const msg = error.response?.data?.errors?.token?.[0]
      || error.response?.data?.errors?.credentials?.[0]
      || error.response?.data?.message
      || 'Token refresh failed. The refresh_token may be expired — re-authorize with TikTok.'
    toast.error(msg)
  } finally {
    refreshingTikTokTokenId.value = null
  }
}

async function regeneratePageToken(account) {
  regeneratingPageTokenId.value = account.id
  try {
    const updated = await post(`/admin/social-media/accounts/${account.id}/regenerate-page-token`)
    accounts.value = accounts.value.map((item) => item.id === updated.id ? updated : item)
    toast.success(`${account.name} page token regenerated.`)
  } catch (error) {
    const msg = error.response?.data?.errors?.token?.[0]
      || error.response?.data?.message
      || 'Page token regeneration failed. Re-authorize with Facebook if the user token is also expired.'
    toast.error(msg)
  } finally {
    regeneratingPageTokenId.value = null
  }
}

async function toggleAccountConnection(account) {
  const disconnecting = account.auth_status === 'connected'

  if (!disconnecting && ['expired', 'error'].includes(account.auth_status)) {
    openAccountPanel(account)
    toast.warning('Update the Facebook credentials to reconnect this account.')
    return
  }

  try {
    await patch(`/admin/social-media/accounts/${account.id}`, {
      auth_status: disconnecting ? 'disconnected' : 'connected',
      status: disconnecting ? 'paused' : 'active',
      is_active: !disconnecting,
    })
    toast.success(disconnecting ? `${account.name} disconnected.` : `${account.name} reconnected.`)
    await loadData()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Unable to update account.')
  }
}

async function onFileSelect(event) {
  const files = Array.from(event.target.files || [])
  await uploadFiles(files)
  event.target.value = ''
}

async function onFileDrop(event) {
  dragOver.value = false
  const files = Array.from(event.dataTransfer?.files || [])
  if (files.length > 1) {
    postError.value = 'Only one file per post. Drop a single image or video.'
    return
  }
  await uploadFiles(files)
}

async function uploadFiles(files) {
  if (!files.length) return

  // Only one file per post — take the first, replace any existing
  const file = files[0]

  if (mediaFiles.value.length > 0) {
    releaseMediaPreviewUrls()
    mediaFiles.value = []
  }

  uploadingMedia.value = true

  try {
    const formData = new FormData()
    formData.append('file', file)

    const { data } = await axios.post('/admin/social-media/media', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    mediaFiles.value = [{
      url: data.url,
      name: data.name || file.name,
      isVideo: file.type.startsWith('video/'),
      previewUrl: URL.createObjectURL(file),
    }]
  } catch (error) {
    postError.value = error.response?.data?.message || 'File upload failed.'
  } finally {
    uploadingMedia.value = false
  }
}

function removeMedia(index) {
  const [removed] = mediaFiles.value.splice(index, 1)
  if (removed?.previewUrl?.startsWith('blob:')) {
    URL.revokeObjectURL(removed.previewUrl)
  }
}

function releaseMediaPreviewUrls() {
  for (const file of mediaFiles.value) {
    if (file?.previewUrl?.startsWith('blob:')) {
      URL.revokeObjectURL(file.previewUrl)
    }
  }
}

function hydrateMediaFiles(urls) {
  return (urls || []).map((url) => ({
    url,
    name: url.split('/').pop() || 'media',
    isVideo: inferIsVideo(url),
    previewUrl: url,
  }))
}

function inferIsVideo(url) {
  const extension = (url.split('?')[0].split('.').pop() || '').toLowerCase()
  return ['mp4', 'mov', 'avi', 'webm', 'wmv', 'mkv'].includes(extension)
}

function defaultCapabilitiesForPlatform(platform) {
  if (platform === 'tiktok') return ['publish', 'schedule', 'messages']
  return ['publish', 'schedule', 'comments', 'messages']
}

function compactCredentials(credentials) {
  return Object.fromEntries(
    Object.entries(credentials || {}).filter(([, value]) => String(value ?? '').trim() !== ''),
  )
}

function credentialPlaceholder(field) {
  return field.secret ? 'Saved value stays masked unless replaced' : `Enter ${field.label.toLowerCase()}`
}

function prettyFilter(value) {
  return value === 'all' ? 'All' : prettyStatus(value)
}

function prettyStatus(value) {
  return String(value || '').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function platformLabel(platform) {
  return {
    facebook: 'Facebook',
    instagram: 'Instagram',
    tiktok: 'TikTok',
    unassigned: 'Unassigned',
  }[platform] || 'Social'
}

function platformShort(platform) {
  return {
    facebook: 'F',
    instagram: 'I',
    tiktok: 'T',
    unassigned: 'U',
  }[platform] || 'S'
}

function platformPillClass(platform) {
  return {
    facebook: 'border border-slate-200 bg-white text-slate-700',
    instagram: 'border border-[rgba(211,30,36,0.12)] bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]',
    tiktok: 'border border-slate-800 bg-slate-900 text-white',
    unassigned: 'border border-slate-200 bg-slate-100 text-slate-600',
  }[platform] || 'border border-slate-200 bg-slate-100 text-slate-600'
}

function platformIconWrapClass(platform) {
  return {
    facebook: 'border border-slate-200 bg-white text-slate-700',
    instagram: 'border border-[rgba(211,30,36,0.12)] bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]',
    tiktok: 'border border-slate-800 bg-slate-900 text-white',
    unassigned: 'border border-slate-200 bg-slate-100 text-slate-600',
  }[platform] || 'border border-slate-200 bg-slate-100 text-slate-600'
}

function platformTileClass(platform) {
  return {
    facebook: 'bg-[var(--color-custom-primary)] text-white',
    instagram: 'border border-[rgba(211,30,36,0.2)] bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]',
    tiktok: 'bg-[var(--color-custom-secondary)] text-white',
  }[platform] || 'bg-slate-500 text-white'
}

function platformBadgeClass(platform) {
  return {
    facebook: 'border border-slate-200 bg-white text-slate-700',
    instagram: 'border border-[rgba(211,30,36,0.12)] bg-[rgba(211,30,36,0.08)] text-[var(--color-custom-primary)]',
    tiktok: 'border border-white/10 bg-slate-900 text-white',
    unassigned: 'border border-slate-200 bg-slate-100 text-slate-600',
  }[platform] || 'border border-slate-200 bg-slate-100 text-slate-600'
}

function postCardShellClass(platform) {
  return {
    facebook: 'overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm',
    instagram: 'overflow-hidden rounded-[28px] border border-[rgba(211,30,36,0.14)] bg-[linear-gradient(180deg,rgba(211,30,36,0.05),#ffffff_42%)] shadow-sm',
    tiktok: 'overflow-hidden rounded-[20px] border border-slate-800 bg-slate-950 shadow-sm',
    unassigned: 'overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm',
  }[platform] || 'overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm'
}

function postCardHeaderClass(platform) {
  return {
    facebook: 'border-b border-slate-100 bg-white',
    instagram: 'border-b border-[rgba(211,30,36,0.08)] bg-transparent',
    tiktok: 'border-b border-white/10 bg-slate-950',
    unassigned: 'border-b border-slate-100 bg-white',
  }[platform] || 'border-b border-slate-100 bg-white'
}

function postCardMutedTextClass(platform) {
  return platform === 'tiktok' ? 'text-slate-400' : 'text-slate-400'
}

function postCardBodyTextClass(platform) {
  return platform === 'tiktok' ? 'text-slate-100' : 'text-slate-700'
}

function postCardMediaClass(platform) {
  return {
    facebook: 'mx-4 rounded-2xl bg-slate-100',
    instagram: 'mx-4 rounded-[24px] border border-[rgba(211,30,36,0.08)] bg-white',
    tiktok: 'mx-4 rounded-xl border border-white/10 bg-black',
    unassigned: 'mx-4 rounded-2xl bg-slate-100',
  }[platform] || 'mx-4 rounded-2xl bg-slate-100'
}

function postCardFooterClass(platform) {
  return {
    facebook: 'border-slate-100 text-slate-500',
    instagram: 'border-[rgba(211,30,36,0.08)] text-slate-500',
    tiktok: 'border-white/10 text-slate-300',
    unassigned: 'border-slate-100 text-slate-500',
  }[platform] || 'border-slate-100 text-slate-500'
}

function postCardPrimaryActionClass(platform) {
  return platform === 'tiktok'
    ? 'text-white hover:text-slate-300'
    : 'text-[var(--color-custom-primary)] hover:text-[#b71a1f]'
}

function postCardActionBarClass(platform) {
  return {
    facebook: 'border-slate-100 bg-white',
    instagram: 'border-[rgba(211,30,36,0.08)] bg-[rgba(211,30,36,0.03)]',
    tiktok: 'border-white/10 bg-black/30',
    unassigned: 'border-slate-100 bg-white',
  }[platform] || 'border-slate-100 bg-white'
}

function postCardSecondaryActionClass(platform) {
  return {
    facebook: 'border-slate-200 text-slate-700 hover:bg-slate-50',
    instagram: 'border-[rgba(211,30,36,0.14)] text-[var(--color-custom-primary)] hover:bg-[rgba(211,30,36,0.08)]',
    tiktok: 'border-white/15 text-white hover:bg-white/5',
    unassigned: 'border-slate-200 text-slate-700 hover:bg-slate-50',
  }[platform] || 'border-slate-200 text-slate-700 hover:bg-slate-50'
}

function postCardDangerActionClass(platform) {
  return {
    facebook: 'border-red-200 text-red-600 hover:bg-red-50',
    instagram: 'border-red-200 text-red-600 hover:bg-red-50',
    tiktok: 'border-red-500/30 text-red-200 hover:bg-red-500/10',
    unassigned: 'border-red-200 text-red-600 hover:bg-red-50',
  }[platform] || 'border-red-200 text-red-600 hover:bg-red-50'
}

function postCardExternalMetaClass(platform) {
  return {
    facebook: 'border-slate-100 text-slate-500',
    instagram: 'border-[rgba(211,30,36,0.08)] text-slate-500',
    tiktok: 'border-white/10 text-slate-400',
    unassigned: 'border-slate-100 text-slate-500',
  }[platform] || 'border-slate-100 text-slate-500'
}

function previewCardShellClass(platform) {
  return {
    facebook: 'overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm',
    instagram: 'overflow-hidden rounded-[30px] border border-[rgba(211,30,36,0.14)] bg-[linear-gradient(180deg,rgba(211,30,36,0.05),#ffffff_48%)] shadow-sm',
    tiktok: 'overflow-hidden rounded-[18px] border border-slate-800 bg-slate-950 shadow-sm',
    unassigned: 'overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm',
  }[platform] || 'overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm'
}

function previewCardHeaderClass(platform) {
  return {
    facebook: 'border-b border-slate-100 bg-white',
    instagram: 'border-b border-[rgba(211,30,36,0.08)] bg-transparent',
    tiktok: 'border-b border-white/10 bg-slate-950',
    unassigned: 'border-b border-slate-100 bg-white',
  }[platform] || 'border-b border-slate-100 bg-white'
}

function previewCardTitleClass(platform) {
  return platform === 'tiktok' ? 'text-white' : 'text-slate-950'
}

function previewCardMutedTextClass(platform) {
  return platform === 'tiktok' ? 'text-slate-400' : 'text-slate-400'
}

function previewCardBodyClass(platform) {
  return platform === 'tiktok' ? 'text-slate-100' : 'text-slate-700'
}

function previewCardMediaClass(platform) {
  return {
    facebook: 'mx-4 rounded-2xl bg-slate-100',
    instagram: 'mx-4 rounded-[26px] border border-[rgba(211,30,36,0.08)] bg-white',
    tiktok: 'mx-4 rounded-xl border border-white/10 bg-black',
    unassigned: 'mx-4 rounded-2xl bg-slate-100',
  }[platform] || 'mx-4 rounded-2xl bg-slate-100'
}

function previewCardFooterClass(platform) {
  return {
    facebook: 'border-t border-slate-100 text-slate-500',
    instagram: 'border-t border-[rgba(211,30,36,0.08)] text-slate-500',
    tiktok: 'border-t border-white/10 text-slate-300',
    unassigned: 'border-t border-slate-100 text-slate-500',
  }[platform] || 'border-t border-slate-100 text-slate-500'
}

function statusChipClass(status) {
  if (['published', 'connected', 'approved'].includes(status)) return 'bg-emerald-50 text-emerald-700'
  if (['scheduled', 'pending', 'processing'].includes(status)) return 'bg-amber-50 text-amber-700'
  if (['draft', 'paused'].includes(status)) return 'bg-slate-100 text-slate-600'
  if (['spam', 'disconnected', 'failed', 'error'].includes(status)) return 'bg-red-50 text-red-600'
  return 'bg-slate-100 text-slate-600'
}

function postScheduleText(post) {
  if (post.published_at) return formatDateTime(post.published_at)
  if (post.scheduled_for) return formatDateTime(post.scheduled_for)
  return 'Last edited recently'
}

function formatDateTime(value) {
  if (!value) return 'Not scheduled'
  return new Date(value).toLocaleString([], {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

function formatMessageTime(value) {
  if (!value) return '--'
  return new Date(value).toLocaleTimeString([], {
    hour: 'numeric',
    minute: '2-digit',
  })
}

function formatConversationTime(conversation) {
  if (!conversation.last_message_at) return '--'
  const date = new Date(conversation.last_message_at)
  const now = new Date()

  if (date.toDateString() === now.toDateString()) {
    return formatMessageTime(conversation.last_message_at)
  }

  return date.toLocaleDateString([], { weekday: 'short' })
}

function formatCommentTime(value) {
  if (!value) return '--'
  return new Date(value).toLocaleString([], {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

function relativeTime(value) {
  if (!value) return 'Never'

  const then = new Date(value)
  const diff = Math.max(0, Date.now() - then.getTime())
  const minutes = Math.round(diff / 60000)

  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes} min ago`

  const hours = Math.round(minutes / 60)
  if (hours < 24) return `${hours} hour${hours === 1 ? '' : 's'} ago`

  const days = Math.round(hours / 24)
  return `${days} day${days === 1 ? '' : 's'} ago`
}

function formatCompact(value) {
  return new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 }).format(Number(value || 0))
}

function initials(name) {
  return String(name || '?')
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('')
}

function orderedMessages(list) {
  return [...list].sort((a, b) => new Date(a.sent_at || a.created_at) - new Date(b.sent_at || b.created_at))
}

function postAccountBadges(post) {
  return uniqueById((post.targets || []).map((target) => ({
    id: target.social_account_id || target.id,
    platform: target.account?.platform || 'unassigned',
  })))
}

function uniqueById(items) {
  const seen = new Set()
  return items.filter((item) => {
    const key = item?.id
    if (seen.has(key)) return false
    seen.add(key)
    return true
  })
}

function platformTargets(post, platform = 'all') {
  const targets = post.targets || []
  if (platform === 'all' || platform === 'unassigned') return targets
  return targets.filter((target) => target.account?.platform === platform)
}

function postLikeCount(post, platform = 'all') {
  return platformTargets(post, platform).reduce((sum, target) => sum + Number(target.likes_count || 0), 0)
}

function postCommentCount(post, platform = 'all') {
  const targetComments = platformTargets(post, platform).reduce((sum, target) => sum + Number(target.comments_count || 0), 0)
  return targetComments || Number(post.comments_count || post.engagement?.comments || 0)
}

function postShareCount(post, platform = 'all') {
  return platformTargets(post, platform).reduce((sum, target) => sum + Number(target.shares_count || 0), 0)
}

function toDateTimeLocal(value) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''

  const pad = (part) => String(part).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}
</script>

<template>
  <div class="p-4 md:p-6 space-y-4">
    <PageHeader :title="activeTab === 'media' ? 'Media' : 'Works'"
      :subtitle="activeTab === 'media' ? 'All uploaded images and videos — publish to gallery or use in products, works and services' : 'Manage work case studies and portfolio entries'">
      <button v-if="activeTab === 'works'" @click="openAddCase"
        class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors">
        <PlusIcon class="w-4 h-4" />
        Add Work Case
      </button>
    </PageHeader>

    <!-- Tabs -->
    <div class="mx-4 md:mx-6">
      <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
        <button @click="activeTab = 'media'"
          :class="['flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-lg transition-colors',
            activeTab === 'media' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          <PhotoIcon class="w-4 h-4" />
          Media
        </button>
        <button @click="activeTab = 'works'"
          :class="['flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-lg transition-colors',
            activeTab === 'works' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          <BookOpenIcon class="w-4 h-4" />
          Works
        </button>
      </div>
    </div>

    <!-- ── MEDIA TAB ─────────────────────────────────────────────────────────── -->
    <div v-show="activeTab === 'media'" class="mx-4 md:mx-6">
      <MediaLibraryView />
    </div>

    <!-- HIDDEN (kept for template parser) -->
    <div v-if="false" class="mx-4 md:mx-6 grid grid-cols-1 xl:grid-cols-[380px,1fr] gap-4">
      <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
        <div>
          <h3 class="text-sm font-bold text-gray-900">Add Gallery Images</h3>
          <p class="text-xs text-gray-500 mt-1">Upload one or more photos. Published images are immediately available from the public gallery API.</p>
        </div>

        <input ref="fileInput" type="file" accept="image/*" multiple class="hidden" @change="onFileChange">

        <button type="button" @click="triggerPicker"
          class="w-full border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-red-300 hover:bg-red-50/40 transition-colors">
          <PhotoIcon class="w-8 h-8 mx-auto text-gray-300" />
          <div class="mt-3 text-sm font-semibold text-gray-700">Choose gallery images</div>
          <div class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP or AVIF, up to 4MB each, max 2400×1600 px</div>
        </button>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
          Recommended upload limits: up to 12 images at once, each image no larger than 4MB, 2400×1600 pixels, and 3,840,000 total pixels.
        </div>

        <div v-if="selectedFiles.length" class="space-y-3">
          <div class="text-xs font-semibold text-gray-600">{{ selectedFiles.length }} image{{ selectedFiles.length !== 1 ? 's' : '' }} selected</div>
          <div class="grid grid-cols-3 gap-2">
            <div v-for="file in selectedFiles" :key="file.name + file.size" class="aspect-square rounded-xl overflow-hidden bg-gray-100">
              <img :src="file.preview" :alt="file.name" class="w-full h-full object-cover">
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Service</label>
            <select v-model="uploadForm.service_id" class="input-base">
              <option value="">All services / no specific service</option>
              <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
            </select>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Title</label>
            <input v-model="uploadForm.title" class="input-base" placeholder="Optional title for a single image">
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Alt Text</label>
            <input v-model="uploadForm.alt_text" class="input-base" placeholder="Short image description for accessibility">
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-600">Description</label>
            <textarea v-model="uploadForm.description" rows="4" class="input-base resize-none" placeholder="Optional caption or details"></textarea>
          </div>

          <label class="flex items-center gap-2 text-xs font-semibold text-gray-700">
            <input v-model="uploadForm.is_published" type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
            Publish on website immediately
          </label>
        </div>

        <div v-if="uploadError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ uploadError }}</div>
        <div v-if="selectionErrors.length" class="text-xs text-amber-700 bg-amber-50 rounded-xl px-3 py-2 space-y-1">
          <div v-for="message in selectionErrors" :key="message">{{ message }}</div>
        </div>

        <button @click="uploadImages" :disabled="uploading || !selectedFiles.length"
          class="w-full py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl disabled:opacity-60 flex items-center justify-center gap-2 transition-colors">
          <span v-if="uploading" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
          {{ uploading ? 'Uploading…' : 'Save Gallery Images' }}
        </button>
      </section>

      <section class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Gallery Library</h3>
              <p class="text-xs text-gray-500 mt-1">{{ galleryItems.length }} item{{ galleryItems.length !== 1 ? 's' : '' }} in your gallery</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="filter = 'all'" :class="filterClass('all')">All</button>
              <button @click="filter = 'published'" :class="filterClass('published')">Published</button>
              <button @click="filter = 'draft'" :class="filterClass('draft')">Drafts</button>
            </div>
          </div>
        </div>

        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4">
          <div v-for="i in 6" :key="i" class="h-80 bg-white rounded-2xl border border-gray-100 animate-pulse"></div>
        </div>

        <div v-else-if="!filteredItems.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
          <PhotoIcon class="w-10 h-10 mx-auto text-gray-200" />
          <div class="mt-3 text-sm font-semibold text-gray-700">No gallery images yet</div>
          <div class="text-xs text-gray-400 mt-1">Upload photos here and they'll be ready for website visualization.</div>
        </div>

        <div v-else class="space-y-4">
          <article v-for="group in groupedGallery" :key="group.key"
            class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <button @click="toggleGroup(group.key)"
              class="w-full flex items-center justify-between gap-4 px-5 py-4 bg-gray-50 hover:bg-gray-100/80 transition-colors">
              <div class="min-w-0 text-left">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="text-sm font-bold text-gray-900">{{ group.label }}</h4>
                  <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-600">
                    {{ group.items.length }} image{{ group.items.length !== 1 ? 's' : '' }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Compact admin view grouped by service for easier website organization.</p>
              </div>
              <span class="text-xs font-semibold text-gray-500">{{ expandedGroups[group.key] ? 'Hide' : 'Show' }}</span>
            </button>

            <div v-if="expandedGroups[group.key]" class="p-4 md:p-5">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <article v-for="item in group.items" :key="item.id"
                  class="border border-gray-100 rounded-2xl bg-white p-3">
                  <div class="flex gap-3">
                    <button @click="openPreview(item)"
                      class="w-28 h-24 shrink-0 rounded-xl overflow-hidden bg-gray-100 border border-gray-100 hover:border-red-200 transition-colors">
                      <img :src="item.image_url" :alt="item.alt_text || item.title || 'Gallery image'" class="w-full h-full object-cover">
                    </button>

                    <div class="min-w-0 flex-1 space-y-2">
                      <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                          <input :value="item.title ?? ''" @change="updateItem(item, 'title', $event.target.value)"
                            class="w-full text-xs font-bold text-gray-900 bg-transparent border-0 border-b border-transparent focus:border-red-200 px-0 py-0.5 focus:ring-0"
                            placeholder="Image title">
                          <div class="text-[10px] text-gray-400 mt-1">Sort order: {{ item.sort_order }}</div>
                        </div>
                        <span :class="['text-[10px] font-bold px-2 py-1 rounded-full shrink-0',
                          item.is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                          {{ item.is_published ? 'Published' : 'Draft' }}
                        </span>
                      </div>

                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <select :value="item.service_id ?? ''" @change="updateItem(item, 'service_id', normalizeServiceId($event.target.value))"
                          class="input-base text-xs min-h-0 py-2">
                          <option value="">No linked service</option>
                          <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
                        </select>

                        <input :value="item.alt_text ?? ''" @change="updateItem(item, 'alt_text', $event.target.value)"
                          class="input-base text-xs min-h-0 py-2" placeholder="Alt text">
                      </div>

                      <textarea :value="item.description ?? ''" rows="2"
                        @change="updateItem(item, 'description', $event.target.value)"
                        class="input-base resize-none text-xs min-h-0"
                        placeholder="Caption or short description"></textarea>

                      <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        <button @click="openPreview(item)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors">
                          View
                        </button>
                        <button @click="togglePublished(item)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border transition-colors"
                          :class="item.is_published ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-700'">
                          {{ item.is_published ? 'Unpublish' : 'Publish' }}
                        </button>
                        <button @click="moveItem(item, -1)" :disabled="isMoving(item, -1)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border border-gray-200 text-gray-700 disabled:opacity-50">
                          Up
                        </button>
                        <button @click="moveItem(item, 1)" :disabled="isMoving(item, 1)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border border-gray-200 text-gray-700 disabled:opacity-50">
                          Down
                        </button>
                        <button @click="removeItem(item)"
                          class="text-xs font-semibold rounded-xl px-3 py-2 border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                          Delete
                        </button>
                      </div>
                    </div>
                  </div>
                </article>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>

    <!-- ── WORKS TAB ────────────────────────────────────────────────────────── -->
    <div v-show="activeTab === 'works'" class="mx-4 md:mx-6 space-y-4">
      <div v-if="casesLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in 6" :key="i" class="h-64 bg-white rounded-2xl border border-gray-100 animate-pulse"></div>
      </div>

      <div v-else-if="!cases.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
        <BookOpenIcon class="w-10 h-10 mx-auto text-gray-200" />
        <div class="mt-3 text-sm font-semibold text-gray-700">No work cases yet</div>
        <div class="text-xs text-gray-400 mt-1">Add your first work case to showcase your results.</div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="wc in cases" :key="wc.id"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">

          <!-- Cover image -->
          <div class="aspect-[4/3] bg-gray-100 relative overflow-hidden">
            <img
              :src="getCaseImageUrl(wc.after_image || wc.before_image)"
              :alt="wc.title"
              class="w-full h-full object-cover"
              v-if="wc.after_image || wc.before_image"
            >
            <div v-else class="w-full h-full flex items-center justify-center">
              <PhotoIcon class="w-10 h-10 text-gray-300" />
            </div>

            <!-- Category badge -->
            <span :class="['absolute top-2 left-2 text-[10px] font-bold px-2 py-1 rounded-full', categoryBadgeClass(wc.category)]">
              {{ wc.category }}
            </span>
          </div>

          <div class="p-4 flex-1 flex flex-col gap-3">
            <div class="font-bold text-sm text-gray-900 leading-tight">{{ wc.title }}</div>

            <!-- Counts -->
            <div class="flex flex-wrap gap-1.5">
              <span class="text-[10px] font-semibold bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                {{ wc.steps_count }} step{{ wc.steps_count !== 1 ? 's' : '' }}
              </span>
              <span class="text-[10px] font-semibold bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                {{ wc.metrics_count }} metric{{ wc.metrics_count !== 1 ? 's' : '' }}
              </span>
              <span class="text-[10px] font-semibold bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                {{ wc.gallery_count }} photo{{ wc.gallery_count !== 1 ? 's' : '' }}
              </span>
            </div>

            <!-- Toggles + actions -->
            <div class="flex items-center gap-2 mt-auto flex-wrap">
              <button @click="toggleCaseFeatured(wc)"
                :title="wc.is_featured ? 'Unfeature' : 'Feature'"
                :class="['text-xs font-semibold rounded-xl px-2.5 py-1.5 border transition-colors',
                  wc.is_featured ? 'border-yellow-300 bg-yellow-50 text-yellow-700' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50']">
                ⭐
              </button>
              <button @click="toggleCaseActive(wc)"
                :class="['text-xs font-semibold rounded-xl px-2.5 py-1.5 border transition-colors',
                  wc.is_active ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-500']">
                {{ wc.is_active ? 'Active' : 'Inactive' }}
              </button>
              <div class="flex-1"></div>
              <button @click="openEditCase(wc)"
                class="text-xs font-semibold rounded-xl px-3 py-1.5 border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors">
                Edit
              </button>
              <button @click="deleteCase(wc)"
                class="text-xs font-semibold rounded-xl px-3 py-1.5 border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── GALLERY PREVIEW MODAL ────────────────────────────────────────────── -->
    <Modal v-model="showPreview" title="Gallery Preview" size="2xl">
      <div v-if="previewItem" class="space-y-4">
        <div class="rounded-2xl overflow-hidden bg-gray-100 border border-gray-100">
          <img :src="previewItem.image_url" :alt="previewItem.alt_text || previewItem.title || 'Gallery image'"
            class="w-full max-h-[70vh] object-contain bg-gray-50">
        </div>
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div>
            <div class="text-sm font-bold text-gray-900">{{ previewItem.title || 'Untitled image' }}</div>
            <div class="text-xs text-gray-500 mt-1">
              {{ previewItem.service?.name || 'No linked service' }} · {{ previewItem.is_published ? 'Published' : 'Draft' }}
            </div>
          </div>
          <div class="text-xs text-gray-500">Sort order: {{ previewItem.sort_order }}</div>
        </div>
        <p v-if="previewItem.description" class="text-sm text-gray-600">{{ previewItem.description }}</p>
      </div>
    </Modal>

    <!-- ── WORK CASE FORM MODAL ─────────────────────────────────────────────── -->
    <Modal v-model="showCaseForm" :title="editingCase ? 'Edit Work Case' : 'Add Work Case'" size="2xl">
      <form @submit.prevent="saveCase" class="space-y-6">

        <!-- Section 1: Core -->
        <div class="space-y-4">
          <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Core Details</h4>
          <div class="grid grid-cols-1 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Title <span class="text-red-500">*</span></label>
              <input v-model="caseForm.title" class="input-base" placeholder="e.g. Full Detail — Toyota Land Cruiser" required>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Category <span class="text-red-500">*</span></label>
              <select v-model="caseForm.category" class="input-base" required>
                <option value="">Select category</option>
                <option value="detailing">Detailing</option>
                <option value="performance">Performance</option>
                <option value="bodywork">Bodywork</option>
                <option value="diagnostics">Diagnostics</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Service Type</label>
              <input v-model="caseForm.service_type" class="input-base" placeholder="e.g. Paint Correction">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Client Type</label>
              <input v-model="caseForm.client_type" class="input-base" placeholder="e.g. Individual, Fleet">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Duration (days)</label>
              <input v-model.number="caseForm.duration_days" type="number" min="0" class="input-base" placeholder="e.g. 3">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Completed At</label>
              <input v-model="caseForm.completed_at" type="date" class="input-base">
            </div>
            <div class="flex flex-col gap-3 pt-1">
              <label class="flex items-center gap-2 text-xs font-semibold text-gray-700">
                <input v-model="caseForm.is_featured" type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                Featured
              </label>
              <label class="flex items-center gap-2 text-xs font-semibold text-gray-700">
                <input v-model="caseForm.is_active" type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                Active
              </label>
            </div>
          </div>
        </div>

        <!-- Section 2: Content -->
        <div class="space-y-4">
          <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Content</h4>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-700">Brief</label>
            <textarea v-model="caseForm.brief" rows="3" class="input-base resize-none" placeholder="Short summary of the work done"></textarea>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-700">Challenge</label>
            <textarea v-model="caseForm.challenge" rows="3" class="input-base resize-none" placeholder="What challenges did you face?"></textarea>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-700">Outcome</label>
            <textarea v-model="caseForm.outcome" rows="3" class="input-base resize-none" placeholder="What were the results?"></textarea>
          </div>
        </div>

        <!-- Section 3: Images -->
        <div class="space-y-4">
          <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Before / After Images</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Before Image -->
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Before Image</label>
              <div v-if="editingCase && caseForm.before_image && !beforePreview" class="relative w-full aspect-video rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                <img :src="getCaseImageUrl(caseForm.before_image)" alt="Before" class="w-full h-full object-cover">
              </div>
              <div v-if="beforePreview" class="relative w-full aspect-video rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                <img :src="beforePreview" alt="Before preview" class="w-full h-full object-cover">
              </div>
              <div class="flex gap-2">
                <input type="file" accept="image/*" class="input-base text-xs flex-1" @change="onBeforeImageChange">
                <button type="button" @click="openPicker('before')"
                  class="shrink-0 text-xs font-semibold px-3 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                  Library
                </button>
              </div>
            </div>
            <!-- After Image -->
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">After Image</label>
              <div v-if="editingCase && caseForm.after_image && !afterPreview" class="relative w-full aspect-video rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                <img :src="getCaseImageUrl(caseForm.after_image)" alt="After" class="w-full h-full object-cover">
              </div>
              <div v-if="afterPreview" class="relative w-full aspect-video rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                <img :src="afterPreview" alt="After preview" class="w-full h-full object-cover">
              </div>
              <div class="flex gap-2">
                <input type="file" accept="image/*" class="input-base text-xs flex-1" @change="onAfterImageChange">
                <button type="button" @click="openPicker('after')"
                  class="shrink-0 text-xs font-semibold px-3 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                  Library
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 4: Steps -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Our Approach (Steps)</h4>
            <button type="button" @click="addStep"
              class="text-xs font-semibold text-red-600 hover:text-red-700 flex items-center gap-1">
              <PlusIcon class="w-3.5 h-3.5" /> Add Step
            </button>
          </div>
          <div v-if="!caseForm.steps.length" class="text-xs text-gray-400 italic">No steps added yet.</div>
          <div v-for="(step, index) in caseForm.steps" :key="index" class="flex gap-3 items-start">
            <div class="w-6 h-6 rounded-full bg-gray-100 text-gray-500 text-[10px] font-bold flex items-center justify-center shrink-0 mt-1">
              {{ index + 1 }}
            </div>
            <div class="flex-1 space-y-2">
              <input v-model="step.title" class="input-base text-xs" placeholder="Step title">
              <textarea v-model="step.detail" rows="2" class="input-base resize-none text-xs" placeholder="Step detail"></textarea>
            </div>
            <button type="button" @click="removeStep(index)"
              class="text-gray-400 hover:text-red-500 mt-1 shrink-0">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Section 5: Metrics -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Metrics</h4>
            <button type="button" @click="addMetric"
              class="text-xs font-semibold text-red-600 hover:text-red-700 flex items-center gap-1">
              <PlusIcon class="w-3.5 h-3.5" /> Add Metric
            </button>
          </div>
          <div v-if="!caseForm.metrics.length" class="text-xs text-gray-400 italic">No metrics added yet.</div>
          <div v-for="(metric, index) in caseForm.metrics" :key="index" class="flex gap-2 items-center">
            <input v-model="metric.label" class="input-base text-xs flex-1" placeholder="Label (e.g. Swirl Removal)">
            <input v-model="metric.value" class="input-base text-xs flex-1" placeholder="Value (e.g. 98%)">
            <button type="button" @click="removeMetric(index)"
              class="text-gray-400 hover:text-red-500 shrink-0">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Section 6: Gallery -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Gallery Photos</h4>
            <span v-if="caseForm.gallery_keep.length || caseGalleryPreviews.length"
              class="text-[11px] text-gray-400">
              {{ caseForm.gallery_keep.length + caseGalleryPreviews.length }} photo{{ (caseForm.gallery_keep.length + caseGalleryPreviews.length) !== 1 ? 's' : '' }}
            </span>
          </div>

          <!-- Existing + new thumbnails combined -->
          <div v-if="caseForm.gallery_keep.length || caseGalleryPreviews.length" class="flex flex-wrap gap-2">
            <div v-for="path in caseForm.gallery_keep" :key="path"
              class="relative w-20 h-20 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 group cursor-pointer">
              <img :src="getCaseImageUrl(path)" alt="Gallery" class="w-full h-full object-cover">
              <button type="button" @click="removeGalleryKeep(path)"
                class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                <XMarkIcon class="w-4 h-4 text-white" />
              </button>
            </div>
            <div v-for="(preview, i) in caseGalleryPreviews" :key="`new-${i}`"
              class="relative w-20 h-20 rounded-xl overflow-hidden bg-gray-100 border-2 border-blue-300 group cursor-pointer">
              <img :src="preview" alt="New" class="w-full h-full object-cover">
              <span class="absolute top-0.5 left-0.5 text-[8px] font-bold bg-blue-500 text-white px-1 py-0.5 rounded">NEW</span>
              <button type="button" @click="removeNewGalleryImage(i)"
                class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                <XMarkIcon class="w-4 h-4 text-white" />
              </button>
            </div>
          </div>

          <!-- Upload zone + library picker -->
          <div class="flex gap-2">
            <label class="flex-1 block cursor-pointer">
              <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:border-red-300 hover:bg-red-50/30 transition-colors">
                <PhotoIcon class="w-5 h-5 mx-auto text-gray-300 mb-1.5" />
                <p class="text-xs font-semibold text-gray-600">Upload new photos</p>
                <p class="text-[11px] text-gray-400 mt-0.5">Max 10 MB each</p>
              </div>
              <input type="file" accept="image/*" multiple class="hidden" @change="onCaseGalleryChange">
            </label>
            <button type="button" @click="openPicker('gallery')"
              class="flex-1 border-2 border-dashed border-blue-200 rounded-xl p-4 text-center hover:border-blue-400 hover:bg-blue-50/30 transition-colors">
              <PhotoIcon class="w-5 h-5 mx-auto text-blue-300 mb-1.5" />
              <p class="text-xs font-semibold text-blue-600">Pick from Library</p>
              <p class="text-[11px] text-blue-400 mt-0.5">Use existing media</p>
            </button>
          </div>

          <!-- Upload error (shown inline, amber style) -->
          <div v-if="galleryUploadError" class="flex items-start gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
            <ExclamationTriangleIcon class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" />
            <span>{{ galleryUploadError }}</span>
          </div>
        </div>

        <!-- Error -->
        <div v-if="caseFormError" class="text-xs text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ caseFormError }}</div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
          <button type="button" @click="showCaseForm = false"
            class="text-xs font-semibold px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors">
            Cancel
          </button>
          <button type="submit" :disabled="caseSaving"
            class="text-xs font-bold px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white disabled:opacity-60 flex items-center gap-2 transition-colors">
            <span v-if="caseSaving" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
            {{ caseSaving ? 'Saving…' : (editingCase ? 'Update Work Case' : 'Create Work Case') }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Media picker -->
    <MediaPicker :open="pickerOpen" :multiple="pickerTarget === 'gallery'"
      @close="pickerOpen = false" @select="onPickerSelect" />
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { BookOpenIcon, ExclamationTriangleIcon, PhotoIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import MediaLibraryView from '@/pages/SocialMedia/MediaLibrary.vue'
import MediaPicker from '@/components/MediaPicker.vue'
import Modal from '@/components/Modal.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useApi } from '@/composables/useApi'
import { useToastStore } from '@/stores/toast'

const { get, post, patch, del } = useApi()
const toast = useToastStore()

const websiteBase = (import.meta.env.VITE_WEBSITE_URL ?? 'http://localhost:8006').replace(/\/$/, '')

// ── Tab state ──────────────────────────────────────────────────────────────────
const activeTab = ref('media')

// ── Works state ────────────────────────────────────────────────────────────────
const cases = ref([])
const casesLoading = ref(false)
const showCaseForm = ref(false)
const editingCase = ref(null)

const blankCaseForm = () => ({
  title: '',
  category: '',
  service_type: '',
  before_image: null,
  after_image: null,
  brief: '',
  challenge: '',
  outcome: '',
  duration_days: null,
  completed_at: '',
  client_type: '',
  is_featured: false,
  is_active: true,
  steps: [],
  metrics: [],
  gallery_keep: [],
})

const caseForm = ref(blankCaseForm())
const beforeImageFile = ref(null)
const afterImageFile = ref(null)
const beforePreview = ref(null)
const afterPreview = ref(null)
const caseGalleryFiles = ref([])
const caseGalleryPreviews = ref([])
const caseFormError = ref(null)
const galleryUploadError = ref(null)
const caseSaving = ref(false)

// ── Media picker ───────────────────────────────────────────────────────────────
const pickerOpen   = ref(false)
const pickerTarget = ref(null) // 'before' | 'after' | 'gallery'

function openPicker(target) { pickerTarget.value = target; pickerOpen.value = true }

function onPickerSelect(url) {
  if (pickerTarget.value === 'before') {
    caseForm.value.before_image = url
    if (beforePreview.value) URL.revokeObjectURL(beforePreview.value)
    beforePreview.value = null
    beforeImageFile.value = null
  } else if (pickerTarget.value === 'after') {
    caseForm.value.after_image = url
    if (afterPreview.value) URL.revokeObjectURL(afterPreview.value)
    afterPreview.value = null
    afterImageFile.value = null
  } else if (pickerTarget.value === 'gallery') {
    if (!Array.isArray(url)) url = [url]
    url.forEach(u => {
      if (!caseForm.value.gallery_keep.includes(u)) caseForm.value.gallery_keep.push(u)
    })
  }
}

// ── Works helpers ──────────────────────────────────────────────────────────────
function getCaseImageUrl(path) {
  if (!path) return ''
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  return `${websiteBase}/${path}`
}

function categoryBadgeClass(cat) {
  const map = {
    detailing: 'bg-blue-100 text-blue-700',
    performance: 'bg-red-100 text-red-700',
    bodywork: 'bg-amber-100 text-amber-700',
    diagnostics: 'bg-purple-100 text-purple-700',
  }
  return map[cat] ?? 'bg-gray-100 text-gray-600'
}

async function loadCases() {
  casesLoading.value = true
  try {
    cases.value = await get('/admin/work-cases')
  } catch {
    toast.error('Failed to load work cases.')
  } finally {
    casesLoading.value = false
  }
}

function openAddCase() {
  editingCase.value = null
  caseForm.value = blankCaseForm()
  resetCaseImageState()
  caseFormError.value = null
  showCaseForm.value = true
}

function openEditCase(wc) {
  editingCase.value = wc
  caseForm.value = {
    title: wc.title ?? '',
    category: wc.category ?? '',
    service_type: wc.service_type ?? '',
    before_image: wc.before_image ?? null,
    after_image: wc.after_image ?? null,
    brief: wc.brief ?? '',
    challenge: wc.challenge ?? '',
    outcome: wc.outcome ?? '',
    duration_days: wc.duration_days ?? null,
    completed_at: wc.completed_at ? wc.completed_at.slice(0, 10) : '',
    client_type: wc.client_type ?? '',
    is_featured: !!wc.is_featured,
    is_active: !!wc.is_active,
    steps: (wc.steps ?? []).map(s => ({ title: s.title ?? '', detail: s.detail ?? '' })),
    metrics: (wc.metrics ?? []).map(m => ({ label: m.label ?? '', value: m.value ?? '' })),
    gallery_keep: (wc.gallery ?? []).map(g => g.image_path),
  }
  resetCaseImageState()
  caseFormError.value = null
  showCaseForm.value = true
}

function resetCaseImageState() {
  if (beforePreview.value) URL.revokeObjectURL(beforePreview.value)
  if (afterPreview.value) URL.revokeObjectURL(afterPreview.value)
  caseGalleryPreviews.value.forEach(p => URL.revokeObjectURL(p))

  beforeImageFile.value = null
  afterImageFile.value = null
  beforePreview.value = null
  afterPreview.value = null
  caseGalleryFiles.value = []
  caseGalleryPreviews.value = []
  galleryUploadError.value = null
}

function onBeforeImageChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  if (file.size > MAX_FILE_SIZE) {
    caseFormError.value = `Before image too large (${(file.size/1024/1024).toFixed(1)} MB). Max 10 MB.`
    event.target.value = ''; return
  }
  if (beforePreview.value) URL.revokeObjectURL(beforePreview.value)
  beforeImageFile.value = file
  beforePreview.value = URL.createObjectURL(file)
}

function onAfterImageChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  if (file.size > MAX_FILE_SIZE) {
    caseFormError.value = `After image too large (${(file.size/1024/1024).toFixed(1)} MB). Max 10 MB.`
    event.target.value = ''; return
  }
  if (afterPreview.value) URL.revokeObjectURL(afterPreview.value)
  afterImageFile.value = file
  afterPreview.value = URL.createObjectURL(file)
}

function onCaseGalleryChange(event) {
  const files = Array.from(event.target.files ?? [])
  const oversized = []
  galleryUploadError.value = null
  for (const file of files) {
    if (file.size > MAX_FILE_SIZE) {
      oversized.push(`${file.name} (${(file.size / 1024 / 1024).toFixed(1)} MB)`)
      continue
    }
    caseGalleryFiles.value.push(file)
    caseGalleryPreviews.value.push(URL.createObjectURL(file))
  }
  if (oversized.length) {
    galleryUploadError.value = `${oversized.length} file${oversized.length > 1 ? 's' : ''} skipped — exceeds the 10 MB limit: ${oversized.join(', ')}`
  }
  event.target.value = ''
}

function removeNewGalleryImage(index) {
  URL.revokeObjectURL(caseGalleryPreviews.value[index])
  caseGalleryFiles.value.splice(index, 1)
  caseGalleryPreviews.value.splice(index, 1)
}

function removeGalleryKeep(path) {
  caseForm.value.gallery_keep = caseForm.value.gallery_keep.filter(p => p !== path)
}

function addStep() {
  caseForm.value.steps.push({ title: '', detail: '' })
}

function removeStep(index) {
  caseForm.value.steps.splice(index, 1)
}

function addMetric() {
  caseForm.value.metrics.push({ label: '', value: '' })
}

function removeMetric(index) {
  caseForm.value.metrics.splice(index, 1)
}

async function saveCase() {
  caseFormError.value = null
  caseSaving.value = true

  try {
    const fd = new FormData()

    fd.append('title', caseForm.value.title)
    fd.append('category', caseForm.value.category)
    fd.append('service_type', caseForm.value.service_type ?? '')
    fd.append('brief', caseForm.value.brief ?? '')
    fd.append('challenge', caseForm.value.challenge ?? '')
    fd.append('outcome', caseForm.value.outcome ?? '')
    fd.append('duration_days', caseForm.value.duration_days ?? '')
    fd.append('completed_at', caseForm.value.completed_at ?? '')
    fd.append('client_type', caseForm.value.client_type ?? '')
    fd.append('is_featured', caseForm.value.is_featured ? '1' : '0')
    fd.append('is_active', caseForm.value.is_active ? '1' : '0')
    fd.append('steps', JSON.stringify(caseForm.value.steps))
    fd.append('metrics', JSON.stringify(caseForm.value.metrics))

    if (beforeImageFile.value) {
      fd.append('before_image', beforeImageFile.value)
    }
    if (afterImageFile.value) {
      fd.append('after_image', afterImageFile.value)
    }

    if (editingCase.value) {
      fd.append('_method', 'PUT')
      fd.append('gallery_keep', JSON.stringify(caseForm.value.gallery_keep))
    }

    caseGalleryFiles.value.forEach(file => fd.append('gallery_images[]', file))

    let result
    if (editingCase.value) {
      result = await post(`/admin/work-cases/${editingCase.value.id}`, fd)
      const idx = cases.value.findIndex(c => c.id === editingCase.value.id)
      if (idx !== -1) cases.value[idx] = result
    } else {
      result = await post('/admin/work-cases', fd)
      cases.value.unshift(result)
    }

    toast.success(editingCase.value ? 'Work case updated.' : 'Work case created.')
    showCaseForm.value = false
    resetCaseImageState()
  } catch (err) {
    const errs = err.response?.data?.errors
    if (errs) {
      caseFormError.value = Object.values(errs).flat().join(' ')
    } else {
      caseFormError.value = err.response?.data?.message ?? 'Failed to save work case.'
    }
  } finally {
    caseSaving.value = false
  }
}

async function toggleCaseFeatured(wc) {
  try {
    const updated = await patch(`/admin/work-cases/${wc.id}/toggle-featured`)
    const idx = cases.value.findIndex(c => c.id === wc.id)
    if (idx !== -1) cases.value[idx] = { ...cases.value[idx], ...updated }
    toast.success(updated.is_featured ? 'Marked as featured.' : 'Removed from featured.')
  } catch {
    toast.error('Failed to update featured status.')
  }
}

async function toggleCaseActive(wc) {
  try {
    const updated = await patch(`/admin/work-cases/${wc.id}/toggle-active`)
    const idx = cases.value.findIndex(c => c.id === wc.id)
    if (idx !== -1) cases.value[idx] = { ...cases.value[idx], ...updated }
    toast.success(updated.is_active ? 'Work case activated.' : 'Work case deactivated.')
  } catch {
    toast.error('Failed to update active status.')
  }
}

async function deleteCase(wc) {
  if (!window.confirm(`Delete work case "${wc.title}"? This cannot be undone.`)) return

  try {
    await del(`/admin/work-cases/${wc.id}`)
    cases.value = cases.value.filter(c => c.id !== wc.id)
    toast.success('Work case deleted.')
  } catch {
    toast.error('Failed to delete work case.')
  }
}

// ── Lifecycle ──────────────────────────────────────────────────────────────────
onMounted(() => {
  loadCases()
})

onBeforeUnmount(() => {
  resetCaseImageState()
})
</script>

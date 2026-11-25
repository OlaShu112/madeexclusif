<script setup>
import {
  ref,
  onMounted,
  onBeforeUnmount
} from 'vue'

import { api } from '@/api/http'

const media = ref([])
const loading = ref(false)
const uploading = ref(false)
const saving = ref(false)

const error = ref('')
const success = ref('')

const collection = ref('')
const uploadCollection = ref('general')

const search = ref('')
const selectedFile = ref(null)
const previewUrl = ref('')
const altText = ref('')

const selectedMedia = ref(null)

const currentPage = ref(1)
const lastPage = ref(1)

const fileInput = ref(null)

let requestVersion = 0

const collections = [
  { value: '', label: 'All Media' },
  { value: 'logo', label: 'Business Logo' },
  { value: 'hero', label: 'Hero Banners' },
  { value: 'promotion', label: 'Promotional Banners' },
  { value: 'category', label: 'Category Images' },
  { value: 'general', label: 'Other Images' }
]

async function loadMedia(page = 1) {
  const version = ++requestVersion

  loading.value = true
  error.value = ''

  try {
    const response = await api(
      '/seller/website/media',
      {
        params: {
          collection: collection.value,
          q: search.value,
          page,
          per_page: 24
        }
      }
    )

    if (version !== requestVersion) return

    media.value = response.data || []

    currentPage.value =
      response.current_page || 1

    lastPage.value =
      response.last_page || 1

  } catch (err) {

    if (version !== requestVersion) return

    error.value =
      err?.message || 'Unable to load media library.'

  } finally {

    if (version === requestVersion) {
      loading.value = false
    }
  }
}

function clearPreview() {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }

  previewUrl.value = ''
  selectedFile.value = null

  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function selectFile(file) {
  if (uploading.value) return

  clearPreview()

  error.value = ''
  success.value = ''

  if (!file) return

  const supportedTypes = [
    'image/jpeg',
    'image/png',
    'image/webp'
  ]

  if (!supportedTypes.includes(file.type)) {
    error.value = 'Select a JPG, PNG or WebP image.'
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    error.value = 'Images must not exceed 5 MB.'
    return
  }

  selectedFile.value = file

  previewUrl.value =
    URL.createObjectURL(file)
}

function handleFileChange(event) {
  selectFile(event.target.files?.[0])
}

function handleDrop(event) {
  selectFile(event.dataTransfer.files?.[0])
}

async function uploadImage() {
  if (!selectedFile.value || uploading.value) {
    return
  }

  uploading.value = true
  error.value = ''
  success.value = ''

  try {
    const formData = new FormData()

    formData.append(
      'image',
      selectedFile.value
    )

    formData.append(
      'collection',
      uploadCollection.value
    )

    formData.append(
      'alt_text',
      altText.value
    )

    const response = await api(
      '/seller/website/media',
      {
        method: 'POST',
        body: formData
      }
    )

    clearPreview()

    altText.value = ''

    collection.value =
      uploadCollection.value

    search.value = ''

    await loadMedia(1)

    success.value = response.message

  } catch (err) {

    error.value =
      err?.message || 'Unable to upload image.'

  } finally {

    uploading.value = false
  }
}

function openMedia(item) {
  selectedMedia.value = {
    ...item
  }
}

async function saveMetadata() {
  if (!selectedMedia.value || saving.value) {
    return
  }

  saving.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api(
      `/seller/website/media/${selectedMedia.value.id}`,
      {
        method: 'PATCH',
        body: {
          name: selectedMedia.value.name,
          alt_text: selectedMedia.value.alt_text
        }
      }
    )

    selectedMedia.value = null

    await loadMedia(currentPage.value)

    success.value = response.message

  } catch (err) {

    error.value =
      err?.message || 'Unable to update image.'

  } finally {

    saving.value = false
  }
}

function formatSize(bytes) {
  if (bytes >= 1024 * 1024) {
    return (
      bytes / (1024 * 1024)
    ).toFixed(2) + ' MB'
  }

  return Math.ceil(bytes / 1024) + ' KB'
}

function collectionName(value) {
  return collections.find(
    item => item.value === value
  )?.label || value
}

onMounted(() => loadMedia())

onBeforeUnmount(() => {
  requestVersion++
  clearPreview()
})
</script>

<template>
  <main class="media-page">

    <div class="page-heading">
      <div>
        <RouterLink to="/seller/website/theme">
          ← Website Builder
        </RouterLink>

        <h1>Media Library</h1>

        <p>
          Upload and manage images for your business website.
        </p>
      </div>
    </div>

    <p
      v-if="error"
      class="error-message"
      role="alert"
    >
      {{ error }}
    </p>

    <p
      v-if="success"
      class="success-message"
      role="status"
    >
      {{ success }}
    </p>

    <div class="media-layout">

      <aside class="media-sidebar">
        <h3>Media Collections</h3>

        <button
          v-for="item in collections"
          :key="item.value"
          :class="{
            active: collection === item.value
          }"
          @click="
            collection = item.value;
            loadMedia(1)
          "
        >
          {{ item.label }}
        </button>
      </aside>

      <section class="media-content">

        <form
          class="upload-panel"
          @submit.prevent="uploadImage"
        >

          <div
            class="drop-zone"
            @dragover.prevent
            @drop.prevent="handleDrop"
          >

            <img
              v-if="previewUrl"
              :src="previewUrl"
              alt="Selected image preview"
              class="upload-preview"
            />

            <template v-else>
              <h2>Upload Website Images</h2>

              <p>
                Drag and drop an image or browse your files.
              </p>

              <small>
                JPG, PNG or WebP. Maximum 5 MB.
              </small>
            </template>

            <input
              ref="fileInput"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              aria-label="Choose image"
              :disabled="uploading"
              @change="handleFileChange"
            />

          </div>

          <fieldset
            class="upload-settings"
            :disabled="uploading"
          >

            <label>
              Media collection

              <select v-model="uploadCollection">
                <option
                  v-for="item in collections.filter(
                    item => item.value
                  )"
                  :key="item.value"
                  :value="item.value"
                >
                  {{ item.label }}
                </option>
              </select>
            </label>

            <label>
              Alternative text

              <input
                v-model="altText"
                maxlength="255"
                placeholder="Describe the image"
              />
            </label>

            <button
              type="submit"
              class="primary-button"
              :disabled="!selectedFile || uploading"
            >
              {{
                uploading
                  ? 'Uploading...'
                  : 'Upload Image'
              }}
            </button>

            <button
              v-if="selectedFile"
              type="button"
              @click="clearPreview"
            >
              Cancel Selection
            </button>

          </fieldset>

        </form>

        <form
          class="media-toolbar"
          @submit.prevent="loadMedia(1)"
        >
          <h2>
            {{ collectionName(collection) || 'All Media' }}
          </h2>

          <input
            v-model="search"
            placeholder="Search images"
            aria-label="Search media library"
          />

          <button type="submit">
            Search
          </button>
        </form>

        <p v-if="loading">
          Loading media library...
        </p>

        <div
          v-else-if="!media.length"
          class="empty-state"
        >
          <h3>No images available</h3>

          <p>
            Upload an image to start building your media library.
          </p>
        </div>

        <div v-else class="media-grid">

          <button
            v-for="item in media"
            :key="item.id"
            class="media-card"
            @click="openMedia(item)"
          >
            <img
              :src="item.url"
              :alt="item.alt_text || item.name"
              loading="lazy"
            />

            <div class="media-details">

              <strong>{{ item.name }}</strong>

              <small>
                {{ item.width }} × {{ item.height }}
              </small>

              <small>
                {{ formatSize(item.size) }}
              </small>

              <small>
                {{ collectionName(item.collection) }}
              </small>

            </div>
          </button>

        </div>

        <div
          v-if="lastPage > 1"
          class="pagination"
        >
          <button
            :disabled="currentPage === 1 || loading"
            @click="loadMedia(currentPage - 1)"
          >
            Previous
          </button>

          <span>
            Page {{ currentPage }} of {{ lastPage }}
          </span>

          <button
            :disabled="currentPage === lastPage || loading"
            @click="loadMedia(currentPage + 1)"
          >
            Next
          </button>
        </div>

        <section
          v-if="selectedMedia"
          class="details-panel"
        >
          <div class="details-heading">
            <h2>Image Details</h2>

            <button
              :disabled="saving"
              @click="selectedMedia = null"
            >
              Close
            </button>
          </div>

          <img
            :src="selectedMedia.url"
            :alt="selectedMedia.alt_text || selectedMedia.name"
          />

          <form @submit.prevent="saveMetadata">

            <fieldset :disabled="saving">

              <label>
                Image name

                <input
                  v-model="selectedMedia.name"
                  required
                  maxlength="255"
                />
              </label>

              <label>
                Alternative text

                <textarea
                  v-model="selectedMedia.alt_text"
                  maxlength="255"
                  rows="3"
                />
              </label>

              <p>
                Original filename:
                {{ selectedMedia.original_name }}
              </p>

              <p>
                Dimensions:
                {{ selectedMedia.width }}
                ×
                {{ selectedMedia.height }}
              </p>

              <p>
                File size:
                {{ formatSize(selectedMedia.size) }}
              </p>

              <button
                class="primary-button"
                type="submit"
                :disabled="saving"
              >
                {{
                  saving
                    ? 'Saving...'
                    : 'Save Image Details'
                }}
              </button>

            </fieldset>

          </form>

        </section>

      </section>

    </div>

  </main>
</template>

<style scoped>
.media-page {
  max-width: 1600px;
  margin: auto;
  padding: 28px;
  color: #222;
}

.page-heading {
  margin-bottom: 25px;
}

.page-heading h1 {
  margin-bottom: 8px;
}

.media-layout {
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}

.media-sidebar,
.upload-panel,
.details-panel {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 20px;
}

.media-sidebar {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

button {
  border: 1px solid #ddd;
  background: #fff;
  border-radius: 6px;
  padding: 10px 14px;
  cursor: pointer;
  font: inherit;
}

button:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.media-sidebar button {
  text-align: left;
}

.active,
.primary-button {
  background: #182333;
  color: #fff;
}

.drop-zone {
  border: 2px dashed #bbb;
  border-radius: 10px;
  text-align: center;
  padding: 30px 20px;
  background: #fafafa;
}

.drop-zone input {
  display: block;
  max-width: 100%;
  margin: 20px auto 0;
}

.upload-preview {
  max-width: 100%;
  max-height: 240px;
  object-fit: contain;
}

fieldset {
  border: none;
  padding: 0;
  margin: 0;
  min-width: 0;
}

.upload-settings {
  display: grid;
  gap: 15px;
  margin-top: 20px;
}

label {
  display: block;
  font-size: 14px;
}

input:not([type="file"]),
select,
textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 11px;
  border: 1px solid #ccc;
  border-radius: 6px;
  margin-top: 8px;
  font: inherit;
}

.media-toolbar,
.details-heading,
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin: 24px 0;
}

.media-toolbar input {
  width: 220px;
  max-width: 100%;
}

.media-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fill, minmax(170px, 1fr));
  gap: 18px;
}

.media-card {
  text-align: left;
  padding: 0;
  overflow: hidden;
  min-width: 0;
}

.media-card:hover {
  border-color: #182333;
}

.media-card img {
  width: 100%;
  height: 160px;
  object-fit: contain;
  background: #f4f4f4;
}

.media-details {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 14px;
  overflow-wrap: anywhere;
}

.media-details small {
  color: #666;
}

.empty-state {
  padding: 40px 20px;
  text-align: center;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
}

.details-panel {
  margin-top: 24px;
}

.details-panel img {
  display: block;
  max-width: 100%;
  max-height: 300px;
  object-fit: contain;
  margin: 20px auto;
}

.details-panel label {
  margin-bottom: 16px;
}

.error-message,
.success-message {
  padding: 14px;
  border-radius: 6px;
}

.error-message {
  background: #fff0f0;
  color: #a00000;
}

.success-message {
  background: #edf8ef;
  color: #176638;
}

@media (max-width: 800px) {
  .media-page {
    padding: 14px;
  }

  .media-layout {
    grid-template-columns: 1fr;
  }
}
</style>
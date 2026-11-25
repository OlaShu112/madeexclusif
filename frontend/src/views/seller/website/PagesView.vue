<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/api/http'

const pages = ref([])
const error = ref('')
const success = ref('')
const busy = ref(false)
const editing = ref(null)
const currentPage = ref(1)
const lastPage = ref(1)

const types = [
  'about', 'contact', 'faq', 'privacy',
  'terms', 'returns', 'delivery',
  'cookies', 'custom'
]

const emptyForm = () => ({
  title: '',
  slug: '',
  type: 'custom',
  content: '',
  seo_title: '',
  seo_description: '',
  show_in_header: false,
  show_in_footer: true,
  sort_order: 0
})

const form = ref(emptyForm())

async function load(page = 1) {
  try {
    const response = await api('/seller/website/pages', {
      params: { page }
    })

    pages.value = response.data
    currentPage.value = response.current_page
    lastPage.value = response.last_page
  } catch (e) {
    error.value = e.message
  }
}

function createPage() {
  editing.value = null
  form.value = emptyForm()
  error.value = ''
  success.value = ''
}

async function editPage(id) {
  error.value = ''

  try {
    const response = await api(
      `/seller/website/pages/${id}`
    )

    editing.value = id

    const source = {
      ...response.page,
      ...(response.draft || {})
    }

    form.value = Object.fromEntries(
      Object.keys(emptyForm()).map(key => [
        key,
        source[key] ?? emptyForm()[key]
      ])
    )
  } catch (e) {
    error.value = e.message
  }
}

function generateSlug() {
  if (editing.value === null) {
    form.value.slug = form.value.title
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '')
  }
}

async function save(publish = false) {
  if (busy.value) return

  busy.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api(
      editing.value
        ? `/seller/website/pages/${editing.value}`
        : '/seller/website/pages',
      {
        method: editing.value ? 'PUT' : 'POST',
        body: form.value
      }
    )

    editing.value = response.page.id

    if (publish) {
      await api(
        `/seller/website/pages/${editing.value}/publish`,
        { method: 'POST' }
      )
    }

    success.value = publish
      ? 'Page published successfully.'
      : 'Draft saved successfully.'

    await load(currentPage.value)
  } catch (e) {
    error.value = e.message
  } finally {
    busy.value = false
  }
}

async function unpublishPage(id) {
  try {
    await api(
      `/seller/website/pages/${id}/unpublish`,
      { method: 'POST' }
    )

    await load(currentPage.value)
  } catch (e) {
    error.value = e.message
  }
}

async function deletePage(id) {
  if (!window.confirm('Delete this website page?')) return

  try {
    await api(`/seller/website/pages/${id}`, {
      method: 'DELETE'
    })

    if (editing.value === id) createPage()

    await load(currentPage.value)
  } catch (e) {
    error.value = e.message
  }
}

onMounted(() => load())
</script>

<template>
  <section class="pages-management">
    <header>
      <h1>Website Pages</h1>
      <p>Manage your website content and legal policies.</p>
    </header>

    <p v-if="error" class="error">{{ error }}</p>
    <p v-if="success" class="success">{{ success }}</p>

    <div class="management-grid">
      <aside>
        <button @click="createPage">Create New Page</button>

        <article v-for="page in pages" :key="page.id">
          <strong>{{ page.title }}</strong>

          <p>
            {{ page.is_published ? 'Published' : 'Draft' }}
          </p>

          <button @click="editPage(page.id)">Edit</button>

          <button
            v-if="page.is_published"
            @click="unpublishPage(page.id)"
          >
            Unpublish
          </button>

          <button @click="deletePage(page.id)">
            Delete
          </button>
        </article>

        <button
          :disabled="currentPage <= 1"
          @click="load(currentPage - 1)"
        >
          Previous
        </button>

        <button
          :disabled="currentPage >= lastPage"
          @click="load(currentPage + 1)"
        >
          Next
        </button>
      </aside>

      <form @submit.prevent="save(false)">
        <h2>{{ editing ? 'Edit Page' : 'New Page' }}</h2>

        <label>Page Title</label>
        <input
          v-model="form.title"
          @input="generateSlug"
          required
          maxlength="255"
        />

        <label>Page URL Slug</label>
        <input v-model="form.slug" required />

        <label>Page Type</label>
        <select v-model="form.type">
          <option v-for="type in types" :key="type">
            {{ type }}
          </option>
        </select>

        <label>Page Content</label>
        <textarea v-model="form.content" rows="14" />

        <label>SEO Title</label>
        <input v-model="form.seo_title" />

        <label>SEO Description</label>
        <textarea v-model="form.seo_description" rows="3" />

        <label>Display Order</label>
        <input
          v-model.number="form.sort_order"
          type="number"
          min="0"
          max="1000"
        />

        <label class="check">
          <input
            v-model="form.show_in_header"
            type="checkbox"
          />
          Show in website header
        </label>

        <label class="check">
          <input
            v-model="form.show_in_footer"
            type="checkbox"
          />
          Show in website footer
        </label>

        <div class="actions">
          <button type="submit" :disabled="busy">
            Save Draft
          </button>

          <button
            type="button"
            :disabled="busy"
            @click="save(true)"
          >
            Publish Page
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<style scoped>
.pages-management {
  padding: 24px;
}

.management-grid {
  display: grid;
  grid-template-columns: 280px minmax(0, 1fr);
  gap: 24px;
}

aside, form {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
}

article {
  padding: 16px 0;
  border-bottom: 1px solid #eee;
}

label {
  display: block;
  margin-top: 16px;
  margin-bottom: 6px;
  font-weight: 600;
}

input:not([type="checkbox"]), select, textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  box-sizing: border-box;
}

button {
  padding: 10px 14px;
  margin: 4px;
  background: #172334;
  color: white;
  border: 0;
  border-radius: 6px;
  cursor: pointer;
}

button:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.actions {
  margin-top: 20px;
}

.error { color: #b91c1c; }
.success { color: #15803d; }

@media (max-width: 800px) {
  .management-grid {
    grid-template-columns: 1fr;
  }
}
</style>

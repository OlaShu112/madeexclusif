<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api/http'

const router = useRouter()

const loading = ref(false)
const checking = ref(true)
const loadingCategories = ref(false)
const storeCheckFailed = ref(false)

const error = ref('')
const categoryError = ref('')
const success = ref('')

const existingStore = ref(null)

// Business categories retrieved from Laravel.
const storeCategories = ref([])

const form = reactive({
  name: '',
  business_category_id: '',
  tagline: '',
})

/**
 * Retrieve all active business categories.
 */
async function loadBusinessCategories() {
  loadingCategories.value = true
  categoryError.value = ''

  try {
    const response = await api('/business-categories')

    const categories = Array.isArray(response)
      ? response
      : response?.data

    if (!Array.isArray(categories)) {
      throw new Error('Invalid business category response.')
    }

    storeCategories.value = categories

    if (!storeCategories.value.length) {
      categoryError.value =
        'No business categories are currently available.'
    }

  } catch (err) {
    storeCategories.value = []

    categoryError.value =
      'Unable to load business categories. Please try again.'

    console.error('Business category loading failed:', err)

  } finally {
    loadingCategories.value = false
  }
}

/**
 * Check whether the authenticated customer
 * already owns a store.
 */
async function checkExistingStore() {
  checking.value = true
  storeCheckFailed.value = false
  error.value = ''

  try {
    const response = await api('/my-store')

    existingStore.value = response?.store ?? null

  } catch (err) {
    storeCheckFailed.value = true

    error.value =
      'Unable to check your store details. Please try again.'

    console.error('Store check failed:', err)

  } finally {
    checking.value = false
  }
}

/**
 * Initialise the registration page.
 */
async function initialise() {
  await checkExistingStore()

  if (!existingStore.value && !storeCheckFailed.value) {
    await loadBusinessCategories()
  }
}

/**
 * Display the business category name.
 */
function categoryLabel(store) {
  if (!store) return 'Not specified'

  return store.business_category?.name
    || storeCategories.value.find(
      category =>
        Number(category.id) === Number(store.business_category_id)
    )?.name
    || store.type
    || 'Not specified'
}

/**
 * Submit a new seller application.
 */
async function submit() {
  if (
    loading.value ||
    checking.value ||
    loadingCategories.value ||
    storeCheckFailed.value ||
    existingStore.value
  ) return

  error.value = ''
  success.value = ''

  if (form.name.trim().length < 2) {
    error.value = 'Please enter a valid store name.'
    return
  }

  if (!form.business_category_id) {
    error.value = 'Please select your business category.'
    return
  }

  const selectedCategory = storeCategories.value.find(
    category =>
      Number(category.id) === Number(form.business_category_id)
  )

  if (!selectedCategory) {
    error.value = 'Please select an available business category.'
    return
  }

  loading.value = true

  try {
    const response = await api('/stores', {
      method: 'POST',

      body: {
        name: form.name.trim(),

        business_category_id: Number(
          form.business_category_id
        ),

        tagline: form.tagline.trim(),
      },
    })

    if (!response?.store) {
      throw new Error('Invalid store registration response.')
    }

    existingStore.value = response.store

    success.value =
      response.message ||
      'Your store application has been submitted successfully.'

  } catch (err) {
    console.error('Store registration failed:', err)

    const validationErrors =
      err?.response?.data?.errors || err?.errors

    error.value =
      validationErrors?.name?.[0]
      || validationErrors?.business_category_id?.[0]
      || validationErrors?.tagline?.[0]
      || err?.message
      || 'Unable to submit your application.'

  } finally {
    loading.value = false
  }
}

onMounted(initialise)
</script>

<template>
  <main class="container open-store">

    <div class="page-heading">
      <h1>Open Your Store</h1>

      <p>
        Join Made Exclusif and create your own online storefront.
        Choose your business category and start building your
        online business.
      </p>
    </div>

    <!-- Checking existing store -->
    <p v-if="checking" role="status">
      Checking your store details...
    </p>

    <!-- Store verification failed -->
    <div v-else-if="storeCheckFailed" class="notice">

      <p class="error" role="alert">
        {{ error }}
      </p>

      <button
        type="button"
        class="primary-button"
        @click="initialise"
      >
        Try Again
      </button>

    </div>

    <!-- Existing store -->
    <div v-else-if="existingStore" class="notice">

      <h2>Your Store</h2>

      <p v-if="success" class="success" role="status">
        {{ success }}
      </p>

      <p>
        <strong>Name:</strong>
        {{ existingStore.name }}
      </p>

      <p>
        <strong>Business Category:</strong>
        {{ categoryLabel(existingStore) }}
      </p>

      <p>
        <strong>Status:</strong>

        <span
          class="store-status"
          :class="`status-${existingStore.status}`"
        >
          {{ existingStore.status }}
        </span>
      </p>

      <p v-if="existingStore.status === 'pending'">
        Your application is awaiting administrator approval.
        You will be able to manage your products once your
        store has been approved.
      </p>

      <p v-else-if="existingStore.status === 'suspended'">
        Your store is currently suspended.
        Please contact the Made Exclusif support team.
      </p>

      <p v-else-if="existingStore.status === 'active'">
        Your store is active. You can now manage your
        products and customer orders.
      </p>

      <button
        v-if="existingStore.status === 'active'"
        type="button"
        class="primary-button"
        @click="router.push({ name: 'seller' })"
      >
        Go to Seller Dashboard
      </button>

    </div>

    <!-- New store application -->
    <form
      v-else
      class="store-form"
      @submit.prevent="submit"
    >

      <!-- Store name -->
      <div class="field">

        <label for="store-name">
          Store Name
        </label>

        <input
          id="store-name"
          v-model="form.name"
          type="text"
          minlength="2"
          maxlength="120"
          required
          :disabled="loading"
          placeholder="Enter your store name"
        />

        <small class="field-help">
          Choose a unique name for your business.
        </small>

      </div>

      <!-- Dynamic business category -->
      <div class="field">

        <label for="store-category">
          Business Category
        </label>

        <select
          id="store-category"
          v-model="form.business_category_id"
          :disabled="
            loading ||
            loadingCategories ||
            !storeCategories.length
          "
          required
        >

          <option value="" disabled>
            {{
              loadingCategories
                ? 'Loading business categories...'
                : 'Select your business category'
            }}
          </option>

          <option
            v-for="category in storeCategories"
            :key="category.id"
            :value="category.id"
          >
            {{ category.name }}
          </option>

        </select>

        <small class="field-help">
          Choose the category that best describes your business.
        </small>

        <p
          v-if="categoryError"
          class="error"
          role="alert"
        >
          {{ categoryError }}
        </p>

        <button
          v-if="categoryError"
          type="button"
          class="primary-button"
          :disabled="loadingCategories"
          @click="loadBusinessCategories"
        >
          Reload Categories
        </button>

        <small class="field-help">
          If your business category is not listed,
          please contact Made Exclusif support.
        </small>

      </div>

      <!-- Store tagline -->
      <div class="field">

        <label for="store-tagline">
          Store Tagline
        </label>

        <textarea
          id="store-tagline"
          v-model="form.tagline"
          maxlength="255"
          rows="3"
          :disabled="loading"
          placeholder="Tell customers about your store"
        />

        <small class="field-help">
          {{ form.tagline.length }} / 255 characters
        </small>

      </div>

      <!-- Approval information -->
      <div class="approval-notice">

        <strong>Store Approval</strong>

        <p>
          All new stores are reviewed by the Made Exclusif
          administration team before they can begin selling
          on the marketplace.
        </p>

      </div>

      <!-- Error message -->
      <p
        v-if="error"
        class="error"
        role="alert"
      >
        {{ error }}
      </p>

      <!-- Submit application -->
      <button
        type="submit"
        class="primary-button"
        :disabled="
          loading ||
          loadingCategories ||
          !storeCategories.length
        "
      >
        {{ loading ? 'Submitting...' : 'Submit Application' }}
      </button>

    </form>

  </main>
</template>

<style scoped>
.open-store {
  max-width: 760px;
  padding-top: 55px;
  padding-bottom: 80px;
}

.page-heading {
  margin-bottom: 35px;
}

.page-heading h1 {
  margin-bottom: 12px;
  font-size: clamp(28px, 4vw, 42px);
}

.page-heading p {
  color: #666;
  line-height: 1.7;
}

.store-form,
.notice {
  padding: 30px;
  border: 1px solid #e8e8e8;
  border-radius: 10px;
  background: #fff;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 22px;
}

.field label {
  font-weight: 600;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #d8d8d8;
  border-radius: 5px;
  font: inherit;
  box-sizing: border-box;
  background: #fff;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
  outline: none;
  border-color: #222;
}

.field input:disabled,
.field select:disabled,
.field textarea:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
}

.field-help {
  color: #777;
  font-size: 13px;
  line-height: 1.5;
}

.primary-button {
  padding: 13px 24px;
  border: 0;
  border-radius: 5px;
  background: #222;
  color: #fff;
  cursor: pointer;
  transition: background 0.2s ease;
}

.primary-button:hover {
  background: #444;
}

.primary-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error {
  color: #b42318;
  margin-bottom: 16px;
}

.success {
  color: #16703c;
  margin-bottom: 16px;
}

.notice h2 {
  margin-bottom: 20px;
}

.notice p {
  margin-bottom: 12px;
}

.approval-notice {
  padding: 16px;
  background: #f7f7f7;
  border: 1px solid #e8e8e8;
  border-radius: 5px;
  margin-bottom: 22px;
}

.approval-notice p {
  margin-top: 8px;
  color: #666;
  font-size: 14px;
  line-height: 1.6;
}

.store-status {
  display: inline-block;
  padding: 5px 10px;
  margin-left: 6px;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-pending {
  background: #fff4dc;
  color: #946200;
}

.status-active {
  background: #e5f5e9;
  color: #16703c;
}

.status-suspended {
  background: #fce8e8;
  color: #b42318;
}

@media (max-width: 640px) {
  .open-store {
    padding-top: 30px;
    padding-bottom: 40px;
  }

  .store-form,
  .notice {
    padding: 20px;
  }

  .primary-button {
    width: 100%;
  }
}
</style>
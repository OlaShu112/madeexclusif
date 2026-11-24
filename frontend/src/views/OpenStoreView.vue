
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
const storeCategories = ref([])

const form = reactive({
  name: '',
  business_category_id: '',
  tagline: ''
})

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

    if (!categories.length) {
      categoryError.value =
        'No business categories are currently available.'
    }

  } catch (err) {
    storeCategories.value = []

    categoryError.value =
      'Unable to load business categories. Please try again.'

  } finally {
    loadingCategories.value = false
  }
}

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

  } finally {
    checking.value = false
  }
}

async function initialise() {
  await checkExistingStore()

  if (!existingStore.value && !storeCheckFailed.value) {
    await loadBusinessCategories()
  }
}

function categoryLabel(store) {
  if (!store) return 'Not specified'

  return store.business_category?.name ||
    storeCategories.value.find(category =>
      Number(category.id) === Number(store.business_category_id)
    )?.name ||
    store.type ||
    'Not specified'
}

function formatDate(value) {
  if (!value) return 'Not recorded'

  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? 'Not recorded'
    : date.toLocaleDateString('en-GB')
}

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
        business_category_id: Number(form.business_category_id),
        tagline: form.tagline.trim()
      }
    })

    if (!response?.store) {
      throw new Error('Invalid store registration response.')
    }

    existingStore.value = response.store

    success.value = response.message ||
      'Your store application has been submitted successfully.'

  } catch (err) {
    const validationErrors =
      err?.response?.data?.errors || err?.errors

    error.value =
      validationErrors?.name?.[0] ||
      validationErrors?.business_category_id?.[0] ||
      validationErrors?.tagline?.[0] ||
      err?.message ||
      'Unable to submit your application.'

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
        Join Made Exclusif and create your own online
        storefront. Choose your business category
        and start building your online business.
      </p>
    </div>

    <p v-if="checking" role="status">
      Checking your store details...
    </p>

    <div v-else-if="storeCheckFailed" class="notice">
      <p class="error" role="alert">{{ error }}</p>

      <button
        type="button"
        class="primary-button"
        @click="initialise"
      >
        Try Again
      </button>
    </div>

    <div v-else-if="existingStore" class="notice">

      <h2>Your Business</h2>

      <p v-if="success" class="success" role="status">
        {{ success }}
      </p>

      <p>
        <strong>Business name:</strong>
        {{ existingStore.name }}
      </p>

      <p>
        <strong>Business category:</strong>
        {{ categoryLabel(existingStore) }}
      </p>

      <p>
        <strong>Application date:</strong>
        {{ formatDate(existingStore.created_at) }}
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

      <div
        v-if="existingStore.status === 'pending'"
        class="status-panel pending-panel"
      >
        <h3>Application Under Review</h3>

        <p>
          Your business application is awaiting
          administrator approval.
        </p>

        <p>
          Once your application has been approved,
          you will be able to access your seller
          dashboard and manage your products.
        </p>
      </div>

      <div
        v-else-if="existingStore.status === 'active'"
        class="status-panel active-panel"
      >
        <h3>Your Business Is Active</h3>

        <p>
          You can now manage your products,
          inventory and customer orders.
        </p>

        <p v-if="existingStore.approved_at">
          Approved on:
          {{ formatDate(existingStore.approved_at) }}
        </p>

        <button
          type="button"
          class="primary-button"
          @click="router.push({ name: 'seller' })"
        >
          Go to Seller Dashboard
        </button>
      </div>

      <div
        v-else-if="existingStore.status === 'rejected'"
        class="status-panel restricted-panel"
      >
        <h3>Application Not Approved</h3>

        <p>
          Unfortunately, your business application
          has not been approved.
        </p>

        <p v-if="existingStore.rejected_at">
          Decision date:
          {{ formatDate(existingStore.rejected_at) }}
        </p>

        <div class="reason">
          <strong>Reason for rejection</strong>

          <p>
            {{
              existingStore.rejection_reason ||
              'Please contact support for further information.'
            }}
          </p>
        </div>

        <p>
          Contact the Made Exclusif support team
          if you would like your application
          to be reconsidered.
        </p>
      </div>

      <div
        v-else-if="existingStore.status === 'suspended'"
        class="status-panel restricted-panel"
      >
        <h3>Business Suspended</h3>

        <p>
          Your storefront is currently suspended
          and seller access is restricted.
        </p>

        <p v-if="existingStore.suspended_at">
          Suspension date:
          {{ formatDate(existingStore.suspended_at) }}
        </p>

        <div class="reason">
          <strong>Reason for suspension</strong>

          <p>
            {{
              existingStore.suspension_reason ||
              'Please contact support for further information.'
            }}
          </p>
        </div>

        <p>
          Please contact Made Exclusif support
          to discuss the next steps.
        </p>
      </div>

      <p v-else>
        Please contact support to confirm your
        business application status.
      </p>

      <button
        type="button"
        class="secondary-button"
        @click="initialise"
      >
        Refresh Application Status
      </button>

    </div>

    <form
      v-else
      class="store-form"
      @submit.prevent="submit"
    >
      <div class="field">
        <label for="store-name">Store Name</label>

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
          Choose the category that best describes
          your business.
        </small>

        <p v-if="categoryError" class="error" role="alert">
          {{ categoryError }}
        </p>

        <button
          v-if="categoryError"
          type="button"
          class="secondary-button"
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

      <div class="field">
        <label for="store-tagline">Store Tagline</label>

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

      <div class="approval-notice">
        <strong>Business Approval</strong>

        <p>
          All new businesses are reviewed by the
          Made Exclusif administration team before
          they can begin selling on the marketplace.
        </p>

        <p>
          We will notify you by email when a decision
          has been made regarding your application.
        </p>
      </div>

      <p v-if="error" class="error" role="alert">
        {{ error }}
      </p>

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
  outline: 2px solid #555;
  outline-offset: 2px;
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

.primary-button,
.secondary-button {
  padding: 13px 24px;
  border: 1px solid #222;
  border-radius: 5px;
  font: inherit;
  cursor: pointer;
}

.primary-button {
  background: #222;
  color: #fff;
}

.secondary-button {
  background: #fff;
  color: #222;
}

.primary-button:disabled,
.secondary-button:disabled {
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

.approval-notice,
.status-panel {
  padding: 20px;
  background: #f7f7f7;
  border: 1px solid #e8e8e8;
  border-radius: 6px;
  margin: 22px 0;
}

.status-panel h3 {
  margin-bottom: 12px;
}

.status-panel p,
.approval-notice p {
  margin-top: 8px;
  line-height: 1.6;
}

.pending-panel {
  background: #fff8e9;
  border-color: #e8d6ac;
}

.active-panel {
  background: #f0f8f2;
  border-color: #b9dfc4;
}

.restricted-panel {
  background: #fff3f3;
  border-color: #e9bcbc;
}

.reason {
  background: #fff;
  padding: 16px;
  margin: 16px 0;
  border-radius: 5px;
  overflow-wrap: anywhere;
  white-space: pre-wrap;
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

.status-suspended,
.status-rejected {
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

  .primary-button,
  .secondary-button {
    width: 100%;
  }
}
</style>

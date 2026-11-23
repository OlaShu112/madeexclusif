<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api/http'

const router = useRouter()

const loading = ref(false)
const checking = ref(true)
const error = ref('')
const success = ref('')
const existingStore = ref(null)

const form = reactive({
  name: '',
  type: 'clothing',
  tagline: '',
})

async function checkExistingStore() {
  checking.value = true

  try {
    existingStore.value = await api('/seller/store')
  } catch (err) {
    // A 404 may mean the customer has not opened a store.
    if (err?.status !== 404 && err?.response?.status !== 404) {
      error.value = 'Unable to check your store details.'
    }
  } finally {
    checking.value = false
  }
}

async function submit() {
  if (loading.value) return

  error.value = ''
  success.value = ''

  if (!form.name.trim()) {
    error.value = 'Please enter your store name.'
    return
  }

  loading.value = true

  try {
    const response = await api('/stores', {
        method: 'POST',
        body: {
           name: form.name.trim(),
          type: form.type,
         tagline: form.tagline.trim(),
       },
    })
    existingStore.value = response?.store ?? response

    success.value =
      'Your store application has been submitted successfully.'

  } catch (err) {
    console.error('Store registration failed:', err)
      error.value =
      err?.message ||
      'Unable to submit your application.'
  } finally {
    loading.value = false
  }
}

onMounted(checkExistingStore)
</script>

<template>
  <main class="container open-store">
    <div class="page-heading">
      <h1>Open Your Store</h1>
      <p>
        Join Made Exclusif and create your own online storefront.
      </p>
    </div>

    <p v-if="checking">Checking your store details...</p>

    <div v-else-if="existingStore" class="notice">
      <h2>Your Store</h2>

      <p>
        <strong>Name:</strong>
        {{ existingStore.name }}
      </p>

      <p>
        <strong>Status:</strong>
        {{ existingStore.status }}
      </p>

      <p v-if="existingStore.status === 'pending'">
        Your application is awaiting administrator approval.
      </p>

      <button
        type="button"
        class="primary-button"
        @click="router.push({ name: 'seller' })"
      >
        Go to Seller Dashboard
      </button>
    </div>

    <form v-else class="store-form" @submit.prevent="submit">
      <div class="field">
        <label for="store-name">Store Name</label>
        <input
          id="store-name"
          v-model="form.name"
          type="text"
          maxlength="120"
          required
          placeholder="Enter your store name"
        />
      </div>

      <div class="field">
        <label for="store-type">Store Category</label>
        <select id="store-type" v-model="form.type">
          <option value="clothing">Clothing & Fashion</option>
          <option value="perfume">Perfumes & Fragrances</option>
        </select>
      </div>

      <div class="field">
        <label for="store-tagline">Store Tagline</label>
        <textarea
          id="store-tagline"
          v-model="form.tagline"
          maxlength="255"
          rows="3"
          placeholder="Tell customers about your store"
        />
      </div>

      <p v-if="error" class="error" role="alert">
        {{ error }}
      </p>

      <p v-if="success" class="success" role="status">
        {{ success }}
      </p>

      <button
        v-if="!existingStore"
        type="submit"
        class="primary-button"
        :disabled="loading"
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
}

.primary-button {
  padding: 13px 24px;
  border: 0;
  border-radius: 5px;
  background: #222;
  color: #fff;
  cursor: pointer;
}

.primary-button:disabled {
  opacity: 0.6;
  cursor: wait;
}

.error {
  color: #b42318;
}

.success {
  color: #16703c;
}
</style>


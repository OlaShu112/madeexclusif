<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useBusinessContext } from '@/composables/useBusinessContext'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const {
  businessName,
  storeSlug,
  isIndependentStore,
  loginRoute,
  safeRedirect
} = useBusinessContext()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const error = ref('')
const busy = ref(false)

async function submit() {

  if (busy.value) return

  error.value = ''

  if (form.password !== form.password_confirmation) {
    error.value = 'The passwords do not match.'
    return
  }

  busy.value = true

  try {

    await auth.register({
      ...form,
      store: isIndependentStore.value
        ? storeSlug.value
        : null
    })

    await router.push(
      safeRedirect(route.query.redirect)
    )

  } catch (e) {

    error.value =
      e.message || 'Unable to create your account.'

  } finally {

    busy.value = false

  }
}
</script>

<template>
  <main
    class="container section register-page"
    :class="{ 'business-register': isIndependentStore }"
  >

    <h1>Create account</h1>

    <p
      v-if="isIndependentStore"
      class="muted introduction"
    >
      Create your account to shop with {{ businessName }},
      manage your orders and enjoy a personalised
      shopping experience.
    </p>

    <div
      v-if="error"
      class="alert"
      role="alert"
    >
      {{ error }}
    </div>

    <form @submit.prevent="submit">

      <!-- Customer name -->

      <div class="field">

        <label for="n">
          Name
        </label>

        <input
          id="n"
          v-model="form.name"
          class="input"
          required
          autocomplete="name"
        />

      </div>

      <!-- Customer email -->

      <div class="field">

        <label for="e">
          Email
        </label>

        <input
          id="e"
          v-model="form.email"
          type="email"
          class="input"
          required
          autocomplete="email"
        />

      </div>

      <!-- Customer password -->

      <div class="field">

        <label for="p">
          Password (min. 8 characters)
        </label>

        <input
          id="p"
          v-model="form.password"
          type="password"
          minlength="8"
          class="input"
          required
          autocomplete="new-password"
        />

      </div>

      <!-- Password confirmation -->

      <div class="field">

        <label for="p2">
          Confirm password
        </label>

        <input
          id="p2"
          v-model="form.password_confirmation"
          type="password"
          minlength="8"
          class="input"
          required
          autocomplete="new-password"
        />

      </div>

      <!-- Registration button -->

      <button
        type="submit"
        class="btn btn--block"
        :disabled="busy"
      >
        {{ busy ? 'Creating…' : 'Create account' }}
      </button>

    </form>

    <p class="muted login-link">

      Already registered?

      <RouterLink
        :to="loginRoute(route.query)"
        class="linkbtn"
      >
        Sign in
      </RouterLink>

    </p>

  </main>
</template>

<style scoped>
.register-page {
  max-width: 440px;
  margin: 0 auto;
}

.introduction {
  margin-bottom: 25px;
  line-height: 1.7;
}

.login-link {
  margin-top: 20px;
}

button:disabled {
  opacity: 0.6;
  cursor: wait;
}

/* Independent business theme */

.business-register {
  color: var(--store-text);
}

.business-register h1,
.business-register label,
.business-register .linkbtn {
  color: var(--store-text);
}

.business-register .muted {
  color: var(--store-text);
  opacity: 0.8;
}

.business-register .btn {
  background: var(--store-primary);
  color: var(--store-primary-text);
  border-color: var(--store-primary);
}

.business-register .input {
  font-family: inherit;
}

.business-register .input:focus {
  outline: 2px solid var(--store-primary);
  outline-offset: 2px;
}
</style>
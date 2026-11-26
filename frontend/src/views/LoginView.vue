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
  registerRoute,
  forgotPasswordRoute,
  safeRedirect
} = useBusinessContext()

const form = reactive({
  email: '',
  password: '',
})

const error = ref('')
const busy = ref(false)
const showPassword = ref(false)

async function submit() {
  if (busy.value) return

  busy.value = true
  error.value = ''

  try {
    await auth.login({
      ...form,
      portal: 'customer',
      store: isIndependentStore.value
        ? storeSlug.value
        : null
    })

    await router.push(
      safeRedirect(route.query.redirect)
    )

  } catch (e) {
    error.value = e.message || 'Unable to sign in.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <main
    class="container section login-page"
    :class="{ 'business-login': isIndependentStore }"
  >

    <h1>Sign In</h1>

    <p class="muted introduction">
      Welcome back to {{ businessName }}.
      Sign in to access your account.
    </p>

    <div
      v-if="error"
      class="alert"
      role="alert"
    >
      {{ error }}
    </div>

    <form @submit.prevent="submit">

      <div class="field">

        <label for="e">
          Email Address
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

      <div class="field">

        <label for="p">
          Password
        </label>

        <input
          id="p"
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          class="input"
          required
          autocomplete="current-password"
        />

      </div>

      <div class="login-options">

        <label class="show-password">

          <input
            v-model="showPassword"
            type="checkbox"
          />

          Show password

        </label>

        <RouterLink
          :to="forgotPasswordRoute(route.query)"
          class="linkbtn"
        >
          Forgot Password?
        </RouterLink>

      </div>

      <button
        type="submit"
        class="btn btn--block"
        :disabled="busy"
      >
        {{ busy ? 'Signing in...' : 'Sign In' }}
      </button>

    </form>

    <p class="muted register-link">

      New here?

      <RouterLink
        :to="registerRoute(route.query)"
        class="linkbtn"
      >
        Create an Account
      </RouterLink>

    </p>

  </main>
</template>

<style scoped>
.login-page {
  max-width: 440px;
  margin: 0 auto;
}

.introduction {
  margin-bottom: 25px;
  line-height: 1.7;
}

.login-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 25px;
  font-size: 14px;
}

.show-password {
  display: flex;
  align-items: center;
  gap: 6px;
}

.register-link {
  margin-top: 25px;
  text-align: center;
}

button:disabled {
  opacity: 0.6;
  cursor: wait;
}

/* Independent business theme */

.business-login {
  color: var(--store-text);
}

.business-login h1,
.business-login label,
.business-login .linkbtn {
  color: var(--store-text);
}

.business-login .muted {
  color: var(--store-text);
  opacity: 0.8;
}

.business-login .btn {
  background: var(--store-primary);
  color: var(--store-primary-text);
  border-color: var(--store-primary);
}

.business-login .input {
  font-family: inherit;
}

.business-login .input:focus {
  outline: 2px solid var(--store-primary);
  outline-offset: 2px;
}
</style>
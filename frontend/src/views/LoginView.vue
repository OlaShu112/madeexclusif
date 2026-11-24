<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

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
    await auth.login(form)

    const redirect = route.query.redirect

    if (
      typeof redirect === 'string' &&
      redirect.startsWith('/') &&
      !redirect.startsWith('//') &&
      !redirect.startsWith('/\\')
    ) {
      await router.push(redirect)
    } else {
      await router.push({ name: 'account' })
    }

  } catch (e) {
    error.value = e.message || 'Unable to sign in.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <main class="container section login-page">

    <h1>Sign In</h1>

    <p class="muted introduction">
      Welcome back to Made Exclusif.
      Sign in to access your account.
    </p>

    <div v-if="error" class="alert" role="alert">
      {{ error }}
    </div>

    <form @submit.prevent="submit">

      <div class="field">

        <label for="e">Email Address</label>

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

        <label for="p">Password</label>

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

        <router-link
          :to="{ name: 'forgot-password' }"
          class="linkbtn"
        >
          Forgot Password?
        </router-link>

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

      <router-link
        :to="{ name: 'register', query: route.query }"
        class="linkbtn"
      >
        Create an Account
      </router-link>

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
</style>
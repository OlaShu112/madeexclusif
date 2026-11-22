<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const form = reactive({ email: '', password: '' })
const error = ref('')
const busy = ref(false)

async function submit() {
  busy.value = true
  error.value = ''
  try {
    await auth.login(form)
    router.push(route.query.redirect || { name: 'account' })
  } catch (e) {
    error.value = e.message
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="container section" style="max-width:440px">
    <h1>Sign in</h1>
    <div v-if="error" class="alert">{{ error }}</div>
    <form @submit.prevent="submit">
      <div class="field"><label for="e">Email</label><input id="e" v-model="form.email" type="email" class="input" required autocomplete="email" /></div>
      <div class="field"><label for="p">Password</label><input id="p" v-model="form.password" type="password" class="input" required autocomplete="current-password" /></div>
      <button class="btn btn--block" :disabled="busy">{{ busy ? 'Signing in…' : 'Sign in' }}</button>
    </form>
    <p class="muted" style="margin-top:20px">New here? <router-link :to="{ name: 'register', query: route.query }" class="linkbtn">Create an account</router-link></p>
  </div>
</template>

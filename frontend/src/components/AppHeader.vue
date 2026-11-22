<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()
const open = ref(false)
const q = ref('')

function search() {
  if (!q.value.trim()) return
  router.push({ name: 'shop', query: { q: q.value.trim() } })
  q.value = ''
  open.value = false
}

async function logout() {
  await auth.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <header class="header">
    <div class="container header__inner">
      <button class="menu-toggle" aria-label="Menu" @click="open = !open">☰</button>
      <router-link to="/" class="logo">MadeExlusif</router-link>

      <nav class="nav" :class="{ open }" @click="open = false">
        <router-link :to="{ name: 'shop' }">All Fragrances</router-link>
        <router-link :to="{ name: 'shop', query: { gender: 'women' } }">Women</router-link>
        <router-link :to="{ name: 'shop', query: { gender: 'men' } }">Men</router-link>
        <router-link :to="{ name: 'shop', query: { gender: 'unisex' } }">Unisex</router-link>
        <router-link :to="{ name: 'shop', query: { featured: 1 } }">Bestsellers</router-link>
      </nav>

      <div class="header__tools">
        <form @submit.prevent="search"><input v-model="q" class="input" style="padding:6px 10px;width:140px" placeholder="Search" aria-label="Search fragrances" /></form>
        <router-link v-if="auth.isAdmin" to="/admin">Admin</router-link>
        <template v-if="auth.user">
          <router-link to="/account">Account</router-link>
          <button class="linkbtn" @click="logout">Log out</button>
        </template>
        <router-link v-else to="/login">Sign in</router-link>
        <router-link to="/cart">Bag<span class="badge" v-if="cart.count">{{ cart.count }}</span></router-link>
      </div>
    </div>
  </header>
</template>

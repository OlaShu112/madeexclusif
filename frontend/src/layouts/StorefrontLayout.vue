<script setup>
import {
  computed,
  watch,
  onBeforeUnmount
} from 'vue'

import { useRoute } from 'vue-router'
import { useStorefrontStore } from '@/stores/storefront'

import StoreAnnouncementBar
  from '@/components/storefront/StoreAnnouncementBar.vue'

import StoreHeader
  from '@/components/storefront/StoreHeader.vue'

import StoreFooter
  from '@/components/storefront/StoreFooter.vue'

import { websiteVariables }
  from '@/utils/websiteTheme'

const route = useRoute()
const storefront = useStorefrontStore()

const slug = computed(
  () => String(route.params.store || '')
)

const store = computed(() =>
  storefront.store?.slug === slug.value
    ? storefront.store
    : null
)

const theme = computed(
  () => websiteVariables(store.value)
)

const originalTitle = document.title

let descriptionTag = document.querySelector(
  'meta[name="description"]'
)

const originalDescription =
  descriptionTag?.getAttribute('content')

const createdDescriptionTag = !descriptionTag

if (!descriptionTag) {
  descriptionTag = document.createElement('meta')
  descriptionTag.name = 'description'
  document.head.appendChild(descriptionTag)
}

watch(
  slug,
  value => storefront.load(value),
  { immediate: true }
)

watch(
  store,
  value => {
    if (!value) {
      document.title = originalTitle

      descriptionTag.setAttribute(
        'content',
        originalDescription || ''
      )

      return
    }

    const settings = value.website_theme || {}

    document.title =
      settings.seo_title || value.name

    descriptionTag.setAttribute(
      'content',
      settings.seo_description ||
      value.tagline ||
      `Discover products from ${value.name}.`
    )
  },
  { immediate: true }
)

onBeforeUnmount(() => {
  storefront.reset()

  document.title = originalTitle

  if (createdDescriptionTag) {
    descriptionTag.remove()
  } else if (originalDescription === null) {
    descriptionTag.removeAttribute('content')
  } else {
    descriptionTag.setAttribute(
      'content',
      originalDescription
    )
  }
})
</script>

<template>
  <div
    class="tenant-website"
    :style="theme"
  >
    <div
      v-if="storefront.loading"
      class="storefront-message"
      role="status"
    >
      Loading storefront...
    </div>

    <div
      v-else-if="storefront.error"
      class="storefront-message"
    >
      <h1>Store unavailable</h1>

      <p>{{ storefront.error }}</p>

      <button @click="storefront.load(slug)">
        Try Again
      </button>

      <RouterLink :to="{ name: 'platform-home' }">
        Return to IVISION
      </RouterLink>
    </div>

    <template v-else-if="store">

      <StoreAnnouncementBar :store="store" />

      <StoreHeader :store="store" />

      <div class="tenant-content">
        <RouterView v-slot="{ Component }">
          <component
            :is="Component"
            :key="route.path"
          />
        </RouterView>
      </div>

      <StoreFooter :store="store" />

    </template>
  </div>
</template>

<style scoped>
.tenant-website {
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
  background: var(--store-background);
  color: var(--store-text);
  font-family: var(--store-font);
}

.tenant-content {
  flex: 1;
}

.storefront-message {
  text-align: center;
  padding: 100px 24px;
}

.storefront-message button {
  padding: 12px 24px;
  margin-top: 20px;
  cursor: pointer;
}

.storefront-message a {
  display: block;
  margin-top: 20px;
  color: inherit;
}
</style>
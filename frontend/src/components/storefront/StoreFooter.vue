<script setup>
import { computed } from 'vue'

import {
  navigationItems,
  navigationDestination,
  safeExternalUrl
} from '@/utils/websiteTheme'

const props = defineProps({
  store: {
    type: Object,
    required: true
  }
})

const year = new Date().getFullYear()

const theme = computed(
  () => props.store.website_theme || {}
)

const columns = computed(() => {
  const defaults = [
    {
      type: 'business',
      title: 'About Us',
      enabled: true
    },
    {
      type: 'navigation',
      title: 'Quick Links',
      enabled: true
    },
    {
      type: 'contact',
      title: 'Contact Us',
      enabled: true
    },
    {
      type: 'delivery',
      title: 'Delivery Information',
      enabled: true
    }
  ]

  const configured = theme.value.footer_columns

  return (
    Array.isArray(configured)
      ? configured
      : defaults
  ).filter(column => column.enabled !== false)
})

const links = computed(
  () => navigationItems(props.store)
)

const socialLinks = computed(() =>
  Object.entries(theme.value.social_links || {})
    .map(([name, value]) => ({
      name,
      url: safeExternalUrl(value)
    }))
    .filter(link => link.url)
)
</script>

<template>
  <footer class="tenant-footer">
    <div class="footer-inner">

      <div class="footer-grid">

        <section
          v-for="column in columns"
          :key="column.type"
        >
          <h3>{{ column.title }}</h3>

          <template v-if="column.type === 'business'">

            <img
              v-if="store.logo_url"
              :src="store.logo_url"
              :alt="store.name"
              class="footer-logo"
            />

            <h2>{{ store.name }}</h2>

            <p>
              {{
                theme.footer_description ||
                store.tagline ||
                `Welcome to ${store.name}.`
              }}
            </p>

          </template>

          <template v-else-if="column.type === 'navigation'">

            <RouterLink
              v-for="(item, index) in links"
              :key="index"
              :to="navigationDestination(store, item)"
            >
              {{ item.label }}
            </RouterLink>

          </template>

          <template v-else-if="column.type === 'contact'">

            <a
              v-if="theme.contact_email"
              :href="`mailto:${theme.contact_email}`"
            >
              {{ theme.contact_email }}
            </a>

            <p v-if="theme.contact_phone">
              {{ theme.contact_phone }}
            </p>

          </template>

          <template v-else-if="column.type === 'delivery'">

            <p v-if="theme.delivery_information">
              {{ theme.delivery_information }}
            </p>

          </template>
        </section>

      </div>

      <div
        v-if="socialLinks.length"
        class="social-section"
      >
        <h3>Follow Us</h3>

        <div class="social-links">

          <a
            v-for="link in socialLinks"
            :key="link.name"
            :href="link.url"
            target="_blank"
            rel="noopener noreferrer"
          >
            {{ link.name }}
          </a>

        </div>
      </div>

      <div class="footer-bottom">

        <p>
          © {{ year }} {{ store.name }}.
          All rights reserved.
        </p>

        <RouterLink :to="{ name: 'platform-home' }">
          Powered by IVISION
        </RouterLink>

      </div>
    </div>
  </footer>
</template>

<style scoped>
.tenant-footer {
  background: var(--store-footer);
  color: var(--store-footer-text);
  padding: 55px 28px 22px;
}

.footer-inner {
  max-width: 1380px;
  margin: auto;
}

.footer-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(210px, 1fr));
  gap: 35px;
}

.footer-logo {
  max-width: 110px;
  max-height: 80px;
  object-fit: contain;
  margin-bottom: 15px;
}

h2, h3, p {
  color: inherit;
}

h2 {
  font-size: 22px;
}

h3 {
  font-size: 16px;
  margin-bottom: 20px;
}

p {
  line-height: 1.8;
  overflow-wrap: anywhere;
  white-space: pre-wrap;
}

a {
  display: block;
  color: inherit;
  text-decoration: none;
  margin-bottom: 12px;
  overflow-wrap: anywhere;
}

a:hover {
  text-decoration: underline;
}

.social-section {
  margin-top: 35px;
}

.social-links {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  text-transform: capitalize;
}

.footer-bottom {
  margin-top: 35px;
  padding-top: 20px;
  border-top: 1px solid currentColor;
  text-align: center;
  font-size: 12px;
}

.footer-bottom a {
  margin-top: 12px;
}
</style>
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

const footerLayout = computed(
  () => theme.value.header_settings?.footer_layout || 'standard'
)

const isClassic = computed(
  () => footerLayout.value === 'classic'
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
  <footer
    class="tenant-footer"
    :class="{
      'classic-footer': isClassic,
      'standard-footer': !isClassic
    }"
  >
    <div class="footer-inner">

      <!-- Footer columns -->

      <div class="footer-grid">

        <section
          v-for="column in columns"
          :key="column.type"
          class="footer-column"
          :class="`footer-column--${column.type}`"
        >

          <!-- Column heading -->

          <h3
            v-if="!isClassic || column.type !== 'business'"
          >
            {{ column.title }}
          </h3>

          <!-- Business branding -->

          <template v-if="column.type === 'business'">

            <div class="footer-brand">

              <img
                v-if="store.logo_url"
                :src="store.logo_url"
                :alt="store.name"
                class="footer-logo"
              />

              <h2>{{ store.name }}</h2>

            </div>

            <p class="footer-description">
              {{
                theme.footer_description ||
                store.tagline ||
                `Welcome to ${store.name}.`
              }}
            </p>

          </template>

          <!-- Shop navigation -->

          <template v-else-if="column.type === 'navigation'">

            <RouterLink
              v-for="(item, index) in links"
              :key="index"
              :to="navigationDestination(store, item)"
            >
              {{ item.label }}
            </RouterLink>

          </template>

          <!-- Customer support -->

          <template v-else-if="column.type === 'contact'">

            <RouterLink
              v-if="isClassic"
              :to="{
                name: 'store-account',
                params: {
                  store: store.slug
                }
              }"
            >
              Track an order
            </RouterLink>

            <a
              v-if="theme.contact_email"
              :href="`mailto:${theme.contact_email}`"
            >
              {{
                isClassic
                  ? 'Contact us'
                  : theme.contact_email
              }}
            </a>

            <p v-if="theme.contact_phone">
              {{ theme.contact_phone }}
            </p>

          </template>

          <!-- Delivery information -->

          <template v-else-if="column.type === 'delivery'">

            <p v-if="theme.delivery_information">
              {{ theme.delivery_information }}
            </p>

          </template>

        </section>

      </div>

      <!-- Social media -->

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

      <!-- Copyright -->

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
/* =====================================
   EXISTING STANDARD FOOTER
===================================== */

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

.footer-column {
  min-width: 0;
}

.footer-logo {
  max-width: 110px;
  max-height: 80px;
  object-fit: contain;
  margin-bottom: 15px;
}

h2,
h3,
p {
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

/* =====================================
   ADDITIONAL CLASSIC PROFESSIONAL FOOTER
===================================== */

.classic-footer {
  padding: 65px 5% 28px;
}

.classic-footer .footer-inner {
  max-width: 1440px;
}

.classic-footer .footer-grid {
  grid-template-columns:
    repeat(auto-fit, minmax(210px, 1fr));
  align-items: start;
  gap: 45px;
}

.classic-footer .footer-brand {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}

.classic-footer .footer-logo {
  width: 55px;
  height: 55px;
  margin: 0;
}

.classic-footer h2 {
  margin: 0;
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 28px;
  font-weight: 600;
  letter-spacing: 1.5px;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.classic-footer h3 {
  margin: 0 0 18px;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.5px;
}

.classic-footer p {
  margin: 0;
  font-size: 15px;
  line-height: 1.8;
  opacity: 0.85;
}

.classic-footer .footer-description {
  max-width: 340px;
}

.classic-footer a {
  width: fit-content;
  max-width: 100%;
  font-size: 15px;
  line-height: 1.6;
  margin-bottom: 10px;
  opacity: 0.85;
  transition: opacity 0.2s ease;
}

.classic-footer a:hover {
  opacity: 1;
  text-decoration: underline;
  text-underline-offset: 4px;
}

.classic-footer .footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 50px;
  padding-top: 24px;
  border-top: 1px solid rgba(128, 128, 128, 0.35);
  text-align: left;
}

.classic-footer .footer-bottom p,
.classic-footer .footer-bottom a {
  font-size: 12px;
  margin: 0;
}

/* =====================================
   CLASSIC FOOTER RESPONSIVENESS
===================================== */

@media (max-width: 1000px) {
  .classic-footer {
    padding: 45px 28px 24px;
  }

  .classic-footer .footer-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
    gap: 35px;
  }
}

@media (max-width: 600px) {
  .classic-footer {
    padding: 35px 20px 24px;
  }

  .classic-footer .footer-grid {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .classic-footer h2 {
    font-size: 24px;
  }

  .classic-footer .footer-bottom {
    flex-direction: column;
    align-items: flex-start;
    margin-top: 35px;
    gap: 10px;
  }
}
</style>
<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api/http'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useStorefrontStore } from '@/stores/storefront'
import { money } from '@/utils/format'
import ProductImage from '@/components/ProductImage.vue'
import ProductCard from '@/components/ProductCard.vue'

const route = useRoute()
const cart = useCartStore()
const auth = useAuthStore()
const storefront = useStorefrontStore()

/*
|--------------------------------------------------------------------------
| INDEPENDENT BUSINESS STOREFRONT
|--------------------------------------------------------------------------
*/

const storeSlug = computed(
  () => String(route.params.store || '')
)

const isIndependentStore = computed(
  () => Boolean(storeSlug.value)
)

const businessName = computed(() =>
  isIndependentStore.value
    ? storefront.store?.slug === storeSlug.value
      ? storefront.store.name
      : product.value?.store?.slug === storeSlug.value
        ? product.value.store.name
        : 'Store'
    : 'Made Exclusif'
)

const isPerfume = computed(() =>
  !isIndependentStore.value ||
  (
    storefront.store?.slug === storeSlug.value &&
    storefront.store?.business_category?.slug === 'perfume'
  )
)

const shopLink = computed(() =>
  isIndependentStore.value
    ? {
        name: 'store-shop',
        params: {
          store: storeSlug.value
        }
      }
    : {
        name: 'shop'
      }
)

/*
|--------------------------------------------------------------------------
| STATE
|--------------------------------------------------------------------------
*/

const product = ref(null)
const related = ref([])

const loading = ref(true)
const notFound = ref(false)
const loadError = ref('')

const variantId = ref(null)
const qty = ref(1)

const added = ref(false)
const cartError = ref('')

const review = ref({
  rating: 5,
  title: '',
  body: ''
})

const reviewMsg = ref('')
const reviewErr = ref('')

let requestNumber = 0
let addedTimer = null

/*
|--------------------------------------------------------------------------
| PRODUCT VARIANTS
|--------------------------------------------------------------------------
*/

const variants = computed(() => {
  return Array.isArray(product.value?.variants)
    ? product.value.variants
    : []
})

const variant = computed(() => {
  return variants.value.find(
    v => String(v.id) === String(variantId.value)
  ) || null
})

/*
|--------------------------------------------------------------------------
| STOCK
|--------------------------------------------------------------------------
*/

const stock = computed(() => {
  const value = Number(variant.value?.stock ?? 0)

  return Number.isFinite(value)
    ? Math.max(0, Math.floor(value))
    : 0
})

const maxQuantity = computed(() => {
  return Math.min(stock.value, 10)
})

/*
|--------------------------------------------------------------------------
| CART VALIDATION
|--------------------------------------------------------------------------
*/

const canAddToCart = computed(() => {
  return (
    product.value !== null &&
    variant.value !== null &&
    stock.value > 0 &&
    qty.value >= 1 &&
    qty.value <= maxQuantity.value
  )
})

/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
*/

const reviews = computed(() => {
  return Array.isArray(product.value?.reviews)
    ? product.value.reviews
    : []
})

const rating = computed(() => {
  const value = Number(product.value?.rating_avg ?? 0)

  return Number.isFinite(value)
    ? Math.max(0, Math.min(5, value))
    : 0
})

/*
|--------------------------------------------------------------------------
| LOAD PRODUCT FROM LARAVEL
|--------------------------------------------------------------------------
*/

async function load() {
  const currentRequest = ++requestNumber

  loading.value = true
  notFound.value = false
  loadError.value = ''
  cartError.value = ''
  added.value = false

  if (addedTimer) {
    clearTimeout(addedTimer)
    addedTimer = null
  }

  product.value = null
  related.value = []
  variantId.value = null
  qty.value = 1

  try {
    const slug = String(route.params.slug || '')

    /*
    |--------------------------------------------------------------------------
    | STORE-SPECIFIC PRODUCT REQUEST
    |--------------------------------------------------------------------------
    |
    | Independent business storefronts must supply
    | their store slug to the Laravel catalogue API.
    |
    | The backend is responsible for enforcing
    | product ownership and active store status.
    |
    */

    const res = await api(
      `/products/${encodeURIComponent(slug)}`,
      {
        params: storeSlug.value
          ? { store: storeSlug.value }
          : {}
      }
    )

    if (currentRequest !== requestNumber) {
      return
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALISE PRODUCT RESPONSE
    |--------------------------------------------------------------------------
    |
    | Laravel may return the product directly
    | or inside a product/data property.
    |
    */

    const item =
      res?.product ??
      res?.data?.product ??
      res?.data ??
      res

    if (
      !item ||
      typeof item !== 'object' ||
      Array.isArray(item) ||
      !item.id ||
      !item.name
    ) {
      throw new Error(
        'The product information could not be loaded.'
      )
    }

    product.value = item

    /*
    |--------------------------------------------------------------------------
    | RELATED PRODUCTS
    |--------------------------------------------------------------------------
    */

    const relatedItems =
      res?.related ??
      res?.data?.related ??
      []

    related.value = Array.isArray(relatedItems)
      ? relatedItems
      : []

    /*
    |--------------------------------------------------------------------------
    | AUTOMATICALLY SELECT FIRST AVAILABLE OPTION
    |--------------------------------------------------------------------------
    */

    const availableVariants = Array.isArray(item.variants)
      ? item.variants
      : []

    const firstInStock = availableVariants.find(
      v => Number(v.stock ?? 0) > 0
    )

    const firstVariant =
      firstInStock || availableVariants[0]

    variantId.value = firstVariant?.id ?? null

    qty.value = 1

    /*
    |--------------------------------------------------------------------------
    | DYNAMIC BROWSER TITLE
    |--------------------------------------------------------------------------
    */

    document.title =
      `${item.name} · ${businessName.value}`

  } catch (error) {
    if (currentRequest !== requestNumber) {
      return
    }

    console.error(
      `${businessName.value} product loading failed:`,
      error
    )

    if (Number(error?.status) === 404) {
      notFound.value = true
    } else {
      loadError.value =
        error?.message ||
        'Unable to load this product. Please try again.'
    }

  } finally {
    if (currentRequest === requestNumber) {
      loading.value = false
    }
  }
}

/*
|--------------------------------------------------------------------------
| SELECT PRODUCT OPTION
|--------------------------------------------------------------------------
*/

function selectVariant(selectedVariant) {
  if (!selectedVariant) {
    return
  }

  if (Number(selectedVariant.stock ?? 0) < 1) {
    return
  }

  variantId.value = selectedVariant.id
  qty.value = 1

  cartError.value = ''
  added.value = false
}

/*
|--------------------------------------------------------------------------
| QUANTITY CONTROLS
|--------------------------------------------------------------------------
*/

function increaseQuantity() {
  if (!variant.value || stock.value < 1) {
    return
  }

  qty.value = Math.min(
    qty.value + 1,
    maxQuantity.value
  )
}

function decreaseQuantity() {
  qty.value = Math.max(
    1,
    qty.value - 1
  )
}

/*
|--------------------------------------------------------------------------
| ADD TO BAG
|--------------------------------------------------------------------------
*/

function addToBag() {
  cartError.value = ''
  added.value = false

  if (!canAddToCart.value) {
    cartError.value =
      'Please select an available option and quantity.'

    return
  }

  try {
    /*
     * Existing Pinia cart integration.
     */

    cart.add(
      product.value,
      variant.value,
      qty.value
    )

    added.value = true

    if (addedTimer) {
      clearTimeout(addedTimer)
    }

    addedTimer = setTimeout(() => {
      added.value = false
      addedTimer = null
    }, 3000)

  } catch (error) {
    console.error(
      `${businessName.value} cart error:`,
      error
    )

    cartError.value =
      error?.message ||
      'Unable to add this product to your bag.'
  }
}

/*
|--------------------------------------------------------------------------
| SUBMIT REVIEW
|--------------------------------------------------------------------------
*/

async function submitReview() {
  reviewMsg.value = ''
  reviewErr.value = ''

  if (!product.value?.slug) {
    return
  }

  const currentRequest = requestNumber

  try {
    await api(
      `/products/${encodeURIComponent(product.value.slug)}/reviews`,
      {
        method: 'POST',

        params: storeSlug.value
          ? { store: storeSlug.value }
          : {},

        body: {
          rating: Number(review.value.rating),
          title: review.value.title.trim(),
          body: review.value.body.trim()
        }
      }
    )

    if (currentRequest !== requestNumber) {
      return
    }

    review.value = {
      rating: 5,
      title: '',
      body: ''
    }

    const reloadRequest = requestNumber + 1

    await load()

    if (reloadRequest !== requestNumber) {
      return
    }

    reviewMsg.value =
      'Thank you. Your review has been saved.'

  } catch (error) {
    if (currentRequest !== requestNumber) {
      return
    }

    reviewErr.value =
      error?.message ||
      'Unable to submit your review.'
  }
}

/*
|--------------------------------------------------------------------------
| WATCH PRODUCT AND BUSINESS ROUTE
|--------------------------------------------------------------------------
|
| Reload the product when the customer navigates
| to another product or independent business.
|
*/

watch(
  () => [
    route.params.store,
    route.params.slug
  ],

  () => {
    reviewMsg.value = ''
    reviewErr.value = ''

    review.value = {
      rating: 5,
      title: '',
      body: ''
    }

    load()
  },

  {
    immediate: true
  }
)

/*
|--------------------------------------------------------------------------
| WATCH BUSINESS NAME
|--------------------------------------------------------------------------
|
| Update the browser title if the storefront
| information becomes available after the product.
|
*/

watch(
  businessName,

  name => {
    if (product.value?.name) {
      document.title =
        `${product.value.name} · ${name}`
    }
  }
)

/*
|--------------------------------------------------------------------------
| CLEAN UP
|--------------------------------------------------------------------------
*/

onUnmounted(() => {
  requestNumber++

  if (addedTimer) {
    clearTimeout(addedTimer)
  }
})
</script>

<template>
  <div class="container section">

    <!-- Loading -->
    <div
      v-if="loading"
      class="skeleton"
      style="max-width:480px;min-height:280px"
    />

    <!-- Product not found -->
    <div
      v-else-if="notFound"
      class="center"
    >
      <h1>Product not found</h1>

      <p class="muted">
        This product may no longer be available.
      </p>

      <router-link
        class="btn btn--ghost"
        :to="shopLink"
      >
        Back to shop
      </router-link>
    </div>

    <!-- Loading error -->
    <div
      v-else-if="loadError"
      class="center"
    >
      <h1>Unable to load product</h1>

      <p class="muted">
        {{ loadError }}
      </p>

      <button
        type="button"
        class="btn"
        @click="load"
      >
        Try again
      </button>

      <router-link
        class="btn btn--ghost"
        :to="shopLink"
      >
        Back to shop
      </router-link>
    </div>

    <!-- Product details -->
    <template v-else-if="product">

      <div class="pdp">

        <!-- Product image -->
        <div class="pdp__media">
          <ProductImage
            :src="product.image_url"
            :accent="product.accent"
            :label="product.name"
          />
        </div>

        <!-- Product information -->
        <div>

          <!-- Business brand and product category -->
          <div class="eyebrow">
            {{ product.brand?.name || businessName }}

            <template v-if="product.category?.name">
              · {{ product.category.name }}
            </template>
          </div>

          <h1>{{ product.name }}</h1>

          <!-- Rating -->
          <div
            v-if="Number(product.reviews_count) > 0"
            style="margin-bottom:8px"
          >
            <span class="stars">
              {{ '★'.repeat(Math.round(rating)) }}{{ '☆'.repeat(5 - Math.round(rating)) }}
            </span>

            <span class="muted">
              {{ rating.toFixed(1) }}
              ({{ product.reviews_count }})
            </span>
          </div>

          <!-- Fragrance-specific information -->
          <p
            v-if="isPerfume"
            class="muted"
          >
            {{ product.concentration || 'Fragrance' }}

            <template v-if="product.gender">
              · {{ product.gender }}
            </template>
          </p>

          <!-- Description -->
          <p v-if="product.description">
            {{ product.description }}
          </p>

          <!-- Price -->
          <div class="price">
            {{
              variant && variant.price != null
                ? money(variant.price)
                : 'Select an available option'
            }}
          </div>

          <!-- Product options -->
          <div class="eyebrow">
            {{ isPerfume ? 'Size' : 'Options' }}
          </div>

          <div
            v-if="variants.length"
            class="sizes"
          >
            <button
              v-for="v in variants"
              :key="v.id"
              type="button"
              class="size"
              :class="{
                on: String(v.id) === String(variantId)
              }"
              :disabled="Number(v.stock ?? 0) < 1"
              @click="selectVariant(v)"
            >
              {{
                v.label ||
                v.size_label ||
                (v.size_ml ? `${v.size_ml}ml` : 'Standard')
              }}
            </button>
          </div>

          <p
            v-else
            class="muted"
          >
            No product options are currently available.
          </p>

          <!-- Stock information -->
          <p
            v-if="variant && stock > 0 && stock <= 5"
            class="muted"
          >
            Only {{ stock }} left
          </p>

          <p
            v-if="variant && stock < 1"
            class="muted"
          >
            This option is currently sold out.
          </p>

          <!-- Quantity and Add to Bag -->
          <div
            style="
              display:flex;
              gap:12px;
              align-items:center;
              flex-wrap:wrap;
              margin-top:18px;
            "
          >

            <!-- Quantity selector -->
            <div class="qty">

              <button
                type="button"
                aria-label="Decrease quantity"
                :disabled="qty <= 1"
                @click="decreaseQuantity"
              >
                −
              </button>

              <span>{{ qty }}</span>

              <button
                type="button"
                aria-label="Increase quantity"
                :disabled="!variant || qty >= maxQuantity"
                @click="increaseQuantity"
              >
                +
              </button>

            </div>

            <!-- Add to bag -->
            <button
              type="button"
              class="btn"
              :disabled="!canAddToCart"
              @click="addToBag"
            >
              {{
                !variant
                  ? 'Select option'
                  : stock < 1
                    ? 'Sold out'
                    : 'Add to bag'
              }}
            </button>

          </div>

          <!-- Cart error -->
          <p
            v-if="cartError"
            class="alert"
            style="margin-top:12px"
          >
            {{ cartError }}
          </p>

          <!-- Cart success -->
          <div
            v-if="added"
            class="alert alert--ok"
            style="margin-top:16px"
          >
            Added to your bag successfully.

            <router-link
              to="/cart"
              class="linkbtn"
            >
              View bag →
            </router-link>
          </div>

          <!-- Fragrance notes -->
          <div
            v-if="
              isPerfume &&
              (
                product.notes_top ||
                product.notes_heart ||
                product.notes_base
              )
            "
            class="notes"
          >

            <div
              v-if="product.notes_top"
              class="note"
            >
              <b>Top</b>
              {{ product.notes_top }}
            </div>

            <div
              v-if="product.notes_heart"
              class="note"
            >
              <b>Heart</b>
              {{ product.notes_heart }}
            </div>

            <div
              v-if="product.notes_base"
              class="note"
            >
              <b>Base</b>
              {{ product.notes_base }}
            </div>

          </div>

        </div>
      </div>

      <!-- Reviews -->
      <section
        style="margin-top:72px;max-width:760px"
      >
        <h2>Reviews</h2>

        <p
          v-if="!reviews.length"
          class="muted"
        >
          No reviews yet — be the first.
        </p>

        <div
          v-for="r in reviews"
          :key="r.id"
          class="review"
        >
          <span class="stars">
            {{
              '★'.repeat(
                Math.max(
                  0,
                  Math.min(5, Number(r.rating) || 0)
                )
              )
            }}
          </span>

          <strong>{{ r.title }}</strong>

          <p
            class="muted"
            style="margin:4px 0"
          >
            {{ r.user?.name || 'Customer' }}
          </p>

          <p>{{ r.body }}</p>
        </div>

        <!-- Write review -->
        <form
          v-if="auth.user"
          style="margin-top:24px"
          @submit.prevent="submitReview"
        >
          <h3>Write a review</h3>

          <div
            v-if="reviewMsg"
            class="alert alert--ok"
          >
            {{ reviewMsg }}
          </div>

          <div
            v-if="reviewErr"
            class="alert"
          >
            {{ reviewErr }}
          </div>

          <div class="field">
            <label>Rating</label>

            <select
              v-model.number="review.rating"
              class="input"
            >
              <option
                v-for="n in [5,4,3,2,1]"
                :key="n"
                :value="n"
              >
                {{ n }} star{{ n > 1 ? 's' : '' }}
              </option>
            </select>
          </div>

          <div class="field">
            <label>Title</label>

            <input
              v-model="review.title"
              class="input"
              maxlength="120"
              required
            />
          </div>

          <div class="field">
            <label>Your review</label>

            <textarea
              v-model="review.body"
              class="input"
              rows="4"
              maxlength="2000"
              required
            />
          </div>

          <button
            type="submit"
            class="btn"
          >
            Submit review
          </button>
        </form>

        <p v-else>
          <router-link
            :to="{
              name: 'login',
              query: {
                redirect: route.fullPath
              }
            }"
            class="linkbtn"
          >
            Sign in
          </router-link>

          to write a review.
        </p>
      </section>

      <!-- Related products -->
      <section
        v-if="related.length"
        style="margin-top:72px"
      >
        <h2>You may also like</h2>

        <div class="grid">
          <ProductCard
            v-for="p in related"
            :key="p.id"
            :product="p"
          />
        </div>
      </section>

    </template>
  </div>
</template>
import {
  createRouter,
  createWebHistory,
} from 'vue-router'

import { useAuthStore } from '@/stores/auth'

/**
 * Made Exclusif - Vue Router
 *
 * Supports:
 * - Main perfume storefront
 * - Independent seller storefronts
 * - Customer accounts and checkout
 * - Password recovery
 * - Seller dashboard
 * - Dedicated administration portal
 * - Administrator analytics
 * - Administrator business management
 */

const routes = [

  // Main storefront

  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
  },
  {
    path: '/shop',
    name: 'shop',
    component: () => import('@/views/ShopView.vue'),
  },
  {
    path: '/product/:slug',
    name: 'product',
    component: () => import('@/views/ProductView.vue'),
    props: true,
  },

  // Independent seller storefronts

  {
    path: '/s/:store',
    name: 'store-home',
    component: () => import('@/views/StoreHomeView.vue'),
    props: true,
  },
  {
    path: '/s/:store/shop',
    name: 'store-shop',
    component: () => import('@/views/ShopView.vue'),
    props: true,
  },
  {
    path: '/s/:store/product/:slug',
    name: 'store-product',
    component: () => import('@/views/ProductView.vue'),
    props: true,
  },

  // Shopping cart and checkout

  {
    path: '/cart',
    name: 'cart',
    component: () => import('@/views/CartView.vue'),
  },
  {
    path: '/checkout',
    name: 'checkout',
    component: () => import('@/views/CheckoutView.vue'),
  },
  {
    path: '/order-success',
    name: 'order-success',
    component: () => import('@/views/OrderSuccessView.vue'),
  },

  // Authentication

  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: {
      guestOnly: true,
    },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: {
      guestOnly: true,
    },
  },

  // Password recovery

  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/ForgotPasswordView.vue'),
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/ResetPasswordView.vue'),
  },

  // Customer account

  {
    path: '/account',
    name: 'account',
    component: () => import('@/views/AccountView.vue'),
    meta: {
      auth: true,
    },
  },

  // Seller registration

  {
    path: '/open-store',
    name: 'open-store',
    component: () => import('@/views/OpenStoreView.vue'),
    meta: {
      auth: true,
    },
  },

  // Seller dashboard

  {
    path: '/seller',
    name: 'seller',
    component: () => import('@/views/SellerView.vue'),
    meta: {
      auth: true,
    },
  },

  // Administration Portal

  {
    path: '/admin',

    component: () => import('@/layouts/AdminLayout.vue'),

    meta: {
      auth: true,
      admin: true,
      layout: 'admin',
    },

    children: [

      // Administrator analytics dashboard

      {
        path: '',
        name: 'admin',
        component: () =>
          import('@/views/AdminAnalyticsView.vue'),
      },

      // Analytics redirect

      {
        path: 'analytics',
        name: 'admin-analytics',
        redirect: '/admin',
      },

      // Administrator platform management

      {
        path: 'manage',
        name: 'admin-manage',
        component: () =>
          import('@/views/AdminView.vue'),
      },

      // Administrator store management

      {
        path: 'stores',
        name: 'admin-stores',
        component: () =>
          import('@/views/AdminStoresView.vue'),
      },
    ],
  },

  // Unknown pages

  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
  },
]

/**
 * Router configuration.
 */

const router = createRouter({

  history: createWebHistory(),

  routes,

  scrollBehavior(to, from, savedPosition) {

    if (savedPosition) {
      return savedPosition
    }

    if (to.hash) {
      return {
        el: to.hash,
        behavior: 'smooth',
      }
    }

    return {
      top: 0,
    }
  },
})

/**
 * Global navigation guard.
 *
 * Authentication and role permissions are also
 * enforced by the Laravel backend.
 */

router.beforeEach((to) => {

  const auth = useAuthStore()

  // Authentication required

  if (to.meta.auth && !auth.user) {

    return {
      name: 'login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  // Administrator-only pages

  if (to.meta.admin && !auth.isAdmin) {

    return {
      name: 'home',
    }
  }

  // Authenticated users should not see login/register

  if (to.meta.guestOnly && auth.user) {

    const redirect = to.query.redirect

    // Only allow safe internal redirects.

    if (
      typeof redirect === 'string' &&
      redirect.startsWith('/') &&
      !redirect.startsWith('//') &&
      !redirect.startsWith('/\\')
    ) {
      return redirect
    }

    return {
      name: 'account',
    }
  }

  return true
})

export default router
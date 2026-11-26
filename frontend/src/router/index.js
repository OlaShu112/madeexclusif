
import {
  createRouter,
  createWebHistory,
} from 'vue-router'

import { useAuthStore } from '@/stores/auth'

/**
 * IVISION - Vue Router
 *
 * Supports:
 * - IVISION public platform
 * - Independent business registration
 * - Independent portal authentication
 * - Main Made Exclusif storefront
 * - Independent business storefronts
 * - Business-specific customer authentication
 * - Customer accounts and checkout
 * - Password recovery
 * - Independent seller portal
 * - Independent website theme management
 * - Dedicated administration portal
 * - Administrator analytics
 * - Administrator business management
 * - Administrator profile management
 */

const routes = [

  // IVISION public platform

  {
    path: '/',
    component: () =>
      import('@/layouts/PlatformLayout.vue'),

    meta: {
      layout: 'platform',
    },

    children: [
      {
        path: '',
        name: 'platform-home',
        component: () =>
          import('@/views/platform/PlatformHomeView.vue'),
      },
    ],
  },

  // Independent business registration

  {
    path: '/business/register',
    component: () =>
      import('@/layouts/PortalAuthLayout.vue'),

    meta: {
      layout: 'portal-auth',
      guestOnly: true,
    },

    children: [
      {
        path: '',
        name: 'business-register',
        component: () =>
          import('@/views/onboarding/BusinessRegisterView.vue'),
      },
    ],
  },

  // Independent administrator authentication

  {
    path: '/admin/login',
    component: () =>
      import('@/layouts/PortalAuthLayout.vue'),

    meta: {
      layout: 'portal-auth',
      guestOnly: true,
    },

    children: [
      {
        path: '',
        name: 'admin-login',
        component: () =>
          import('@/views/PortalLoginView.vue'),
      },
    ],
  },

  // Independent business authentication

  {
    path: '/seller/login',
    component: () =>
      import('@/layouts/PortalAuthLayout.vue'),

    meta: {
      layout: 'portal-auth',
      guestOnly: true,
    },

    children: [
      {
        path: '',
        name: 'seller-login',
        component: () =>
          import('@/views/PortalLoginView.vue'),
      },
    ],
  },

  // Made Exclusif main storefront

  {
    path: '/made-exclusif',
    name: 'home',
    component: () =>
      import('@/views/HomeView.vue'),
  },

  {
    path: '/shop',
    name: 'shop',
    component: () =>
      import('@/views/ShopView.vue'),
  },

  {
    path: '/product/:slug',
    name: 'product',
    component: () =>
      import('@/views/ProductView.vue'),
    props: true,
  },

  // Independent business storefronts

  {
    path: '/s/:store',
    component: () =>
      import('@/layouts/StorefrontLayout.vue'),

    meta: {
      layout: 'storefront',
    },

    children: [

      // Business homepage

      {
        path: '',
        name: 'store-home',
        component: () =>
          import('@/views/StoreHomeView.vue'),
        props: true,
      },

      // Business shop

      {
        path: 'shop',
        name: 'store-shop',
        component: () =>
          import('@/views/ShopView.vue'),
        props: true,
      },

      // Business custom pages

      {
        path: 'pages/:page',
        name: 'store-page',
        component: () =>
          import('@/views/StorePageView.vue'),
      },

      // Business product details

      {
        path: 'product/:slug',
        name: 'store-product',
        component: () =>
          import('@/views/ProductView.vue'),
        props: true,
      },

      // Business customer login

      {
        path: 'login',
        name: 'store-login',
        component: () =>
          import('@/views/LoginView.vue'),

        meta: {
          guestOnly: true,
        },
      },

      // Business customer registration

      {
        path: 'register',
        name: 'store-register',
        component: () =>
          import('@/views/RegisterView.vue'),

        meta: {
          guestOnly: true,
        },
      },

      // Business password recovery

      {
        path: 'forgot-password',
        name: 'store-forgot-password',
        component: () =>
          import('@/views/ForgotPasswordView.vue'),
      },

      // Business password reset

      {
        path: 'reset-password',
        name: 'store-reset-password',
        component: () =>
          import('@/views/ResetPasswordView.vue'),
      },

      // Business customer account

      {
        path: 'account',
        name: 'store-account',
        component: () =>
          import('@/views/AccountView.vue'),

        meta: {
          auth: true,
        },
      },
    ],
  },

  // Shopping cart and checkout

  {
    path: '/cart',
    name: 'cart',
    component: () =>
      import('@/views/CartView.vue'),
  },

  {
    path: '/checkout',
    name: 'checkout',
    component: () =>
      import('@/views/CheckoutView.vue'),
  },

  {
    path: '/order-success',
    name: 'order-success',
    component: () =>
      import('@/views/OrderSuccessView.vue'),
  },

  // Made Exclusif customer authentication

  {
    path: '/login',
    name: 'login',
    component: () =>
      import('@/views/LoginView.vue'),

    meta: {
      guestOnly: true,
    },
  },

  {
    path: '/register',
    name: 'register',
    component: () =>
      import('@/views/RegisterView.vue'),

    meta: {
      guestOnly: true,
    },
  },

  // Made Exclusif password recovery

  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () =>
      import('@/views/ForgotPasswordView.vue'),
  },

  {
    path: '/reset-password',
    name: 'reset-password',
    component: () =>
      import('@/views/ResetPasswordView.vue'),
  },

  // Made Exclusif customer account

  {
    path: '/account',
    name: 'account',
    component: () =>
      import('@/views/AccountView.vue'),

    meta: {
      auth: true,
    },
  },

  // Independent business onboarding

  {
    path: '/open-store',
    component: () =>
      import('@/layouts/BusinessOnboardingLayout.vue'),

    meta: {
      auth: true,
      layout: 'onboarding',
    },

    children: [
      {
        path: '',
        name: 'open-store',
        component: () =>
          import('@/views/OpenStoreView.vue'),
      },
    ],
  },

  // Independent IVISION Seller Portal

  {
    path: '/seller',

    component: () =>
      import('@/layouts/SellerLayout.vue'),

    meta: {
      auth: true,
      layout: 'seller',
    },

    children: [

      // Seller dashboard

      {
        path: '',
        name: 'seller',
        component: () =>
          import('@/views/SellerView.vue'),
      },

      // Independent website pages management

      {
        path: 'website/pages',
        name: 'seller-website-pages',
        component: () =>
          import('@/views/seller/website/PagesView.vue'),
      },

      // Independent website theme management

      {
        path: 'website/theme',
        name: 'seller-website-theme',
        component: () =>
          import('@/views/seller/website/ThemeEditorView.vue'),
      },

      // Independent website media management

      {
        path: 'website/media',
        name: 'seller-website-media',
        component: () =>
          import('@/views/seller/website/MediaLibraryView.vue'),
      },
    ],
  },

  // Independent Administration Portal

  {
    path: '/admin',

    component: () =>
      import('@/layouts/AdminLayout.vue'),

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

      // Administrator profile

      {
        path: 'profile',
        name: 'admin-profile',
        component: () =>
          import('@/views/AdminProfileView.vue'),
      },
    ],
  },

  // Unknown pages

  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () =>
      import('@/views/NotFoundView.vue'),
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

  const adminRoute =
    to.path === '/admin' ||
    to.path.startsWith('/admin/')

  const sellerRoute =
    to.path === '/seller' ||
    to.path.startsWith('/seller/')

  const storefrontRoute =
    to.meta.layout === 'storefront' &&
    Boolean(to.params.store)

  // Protect authenticated pages

  if (to.meta.auth && !auth.user) {

    if (storefrontRoute) {
      return {
        name: 'store-login',

        params: {
          store: to.params.store,
        },

        query: {
          redirect: to.fullPath,
        },
      }
    }

    return {
      name: adminRoute
        ? 'admin-login'
        : sellerRoute
          ? 'seller-login'
          : 'login',

      query: {
        redirect: to.fullPath,
      },
    }
  }

  // Protect administrator routes

  if (to.meta.admin && !auth.isAdmin) {
    return {
      name: 'account',
    }
  }

  // Handle authenticated business registration

  if (to.name === 'business-register' && auth.user) {

    return auth.isAdmin
      ? { name: 'admin' }
      : { name: 'open-store' }
  }

  // Redirect authenticated users from guest pages

  if (to.meta.guestOnly && auth.user) {

    // Preserve independent business context

    if (storefrontRoute) {
      return {
        name: 'store-account',

        params: {
          store: to.params.store,
        },
      }
    }

    if (to.name === 'admin-login') {

      return auth.isAdmin
        ? { name: 'admin' }
        : { name: 'account' }
    }

    if (to.name === 'seller-login') {

      return auth.isAdmin
        ? { name: 'admin' }
        : { name: 'seller' }
    }

    return auth.isAdmin
      ? { name: 'admin' }
      : { name: 'account' }
  }

  return true
})

export default router

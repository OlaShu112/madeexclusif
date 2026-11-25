
<script setup>
import {
  Store,
  CreditCard,
  ChartNoAxesCombined,
  Package,
  Users,
  Sparkles,
  Globe,
  ShieldCheck,
  ArrowRight,
  CheckCircle,
  ShoppingBag,
  Settings,
  LayoutDashboard
} from 'lucide-vue-next'

const features = [
  {
    icon: Store,
    title: 'Your Own Online Store',
    description: 'Create an independent online storefront that reflects your business and brand.'
  },
  {
    icon: CreditCard,
    title: 'Flexible Payments',
    description: 'Choose integrated payments or connect your own supported payment provider.'
  },
  {
    icon: Package,
    title: 'Product & Inventory Management',
    description: 'Organise your products, manage stock and maintain your online catalogue.'
  },
  {
    icon: Users,
    title: 'Customer Management',
    description: 'Manage customer relationships and build a better shopping experience.'
  },
  {
    icon: ChartNoAxesCombined,
    title: 'Business Analytics',
    description: 'Understand your sales, orders and overall business performance.'
  },
  {
    icon: Sparkles,
    title: 'Intelligent Business Tools',
    description: 'Discover our planned AI-powered tools for smarter business operations.'
  },
  {
    icon: Globe,
    title: 'Independent Business Domains',
    description: 'Our roadmap includes custom domains and hosted business websites.'
  },
  {
    icon: ShieldCheck,
    title: 'Secure Business Management',
    description: 'Manage your business through an authenticated administration portal.'
  }
]

const solutions = [
  'Fashion & Clothing',
  'Beauty & Cosmetics',
  'Electronics & Technology',
  'Food & Grocery',
  'Home & Furniture',
  'Health & Wellness',
  'Jewellery & Accessories',
  'General Retail'
]

const plans = [
  {
    name: 'Starter',
    description: 'For new and small businesses.',
    features: [
      'Independent online store',
      'Product management',
      'Order management',
      'Customer management'
    ]
  },
  {
    name: 'Professional',
    description: 'For established and growing businesses.',
    features: [
      'Everything in Starter',
      'Advanced analytics',
      'Custom domain support',
      'Marketing and staff tools'
    ]
  },
  {
    name: 'Enterprise',
    description: 'For businesses with advanced requirements.',
    features: [
      'Everything in Professional',
      'Advanced integrations',
      'AI business tools',
      'Priority support'
    ]
  }
]

const faqs = [
  {
    question: 'What is IVISION?',
    answer: 'IVISION is an e-commerce software platform designed to help businesses create and manage independent online stores.'
  },
  {
    question: 'Can I use my own payment gateway?',
    answer: 'Businesses can select their preferred payment arrangement during onboarding. Payment activation depends on provider availability and verification.'
  },
  {
    question: 'Can I use my own domain name?',
    answer: 'Custom domain support is part of the IVISION development roadmap.'
  },
  {
    question: 'How do I register my business?',
    answer: 'Create a business account, complete your business application and submit it for approval.'
  },
  {
    question: 'Will other businesses see my private information?',
    answer: 'IVISION is being developed around tenant-specific access controls. Businesses are intended to manage only their authorised business records.'
  }
]

const bars = [22, 35, 28, 45, 38, 58, 49, 68, 56, 79, 67, 92]
</script>

<template>
  <div class="iv-home">

    <section class="iv-hero">
      <div class="iv-container iv-hero-grid">

        <div class="iv-hero-copy">

          <span class="iv-eyebrow">
            INTELLIGENT COMMERCE TECHNOLOGY
          </span>

          <h1>
            Build your business.
            <span>Sell your way.</span>
          </h1>

          <p>
            Everything you need to launch, manage
            and grow your online business from one
            powerful commerce platform.
          </p>

          <div class="iv-hero-actions">

            <RouterLink
              to="/business/register"
              class="iv-primary"
            >
              Start Your Business
              <ArrowRight :size="19" />
            </RouterLink>

            <RouterLink
              to="/#pricing"
              class="iv-secondary"
            >
              Explore Plans
            </RouterLink>

          </div>

          <div class="iv-benefits">
            <span>
              <CheckCircle :size="16" />
              Your own brand
            </span>

            <span>
              <CheckCircle :size="16" />
              Flexible payments
            </span>

            <span>
              <CheckCircle :size="16" />
              Business management
            </span>
          </div>

        </div>

        <div class="iv-preview">

          <div class="iv-preview-top">
            <span>IVISION BUSINESS</span>
            <span>Dashboard preview</span>
          </div>

          <div class="iv-preview-body">

            <aside class="iv-preview-sidebar">
              <LayoutDashboard />
              <ShoppingBag />
              <Package />
              <Users />
              <CreditCard />
              <Settings />
            </aside>

            <div class="iv-preview-main">

              <h3>Business Overview</h3>
              <p>Illustrative business performance</p>

              <div class="iv-preview-metrics">
                <div>
                  <small>Revenue</small>
                  <strong>£24,850</strong>
                </div>
                <div>
                  <small>Orders</small>
                  <strong>1,284</strong>
                </div>
                <div>
                  <small>Customers</small>
                  <strong>892</strong>
                </div>
              </div>

              <div class="iv-preview-chart">
                <strong>Sales Performance</strong>

                <div class="iv-chart-bars">
                  <div
                    v-for="(bar, index) in bars"
                    :key="index"
                    :style="{ height: bar + '%' }"
                  />
                </div>
              </div>

              <div class="iv-preview-order">
                <span>Recent orders</span>
                <span>Example data</span>
              </div>

            </div>

          </div>

        </div>

      </div>
    </section>

    <section id="features" class="iv-section">
      <div class="iv-container">

        <div class="iv-section-heading">
          <span class="iv-eyebrow">PLATFORM FEATURES</span>

          <h2>
            Everything your business needs
          </h2>

          <p>
            Commerce tools designed to support
            businesses at every stage of growth.
          </p>
        </div>

        <div class="iv-feature-grid">

          <article
            v-for="feature in features"
            :key="feature.title"
            class="iv-feature-card"
          >
            <div class="iv-feature-icon">
              <component :is="feature.icon" :size="26" />
            </div>

            <h3>{{ feature.title }}</h3>
            <p>{{ feature.description }}</p>
          </article>

        </div>

      </div>
    </section>

    <section id="solutions" class="iv-section iv-soft">
      <div class="iv-container">

        <div class="iv-section-heading">
          <span class="iv-eyebrow">BUSINESS SOLUTIONS</span>

          <h2>One platform. Many possibilities.</h2>

          <p>
            Designed for businesses across
            different retail industries.
          </p>
        </div>

        <div class="iv-solution-grid">

          <div
            v-for="solution in solutions"
            :key="solution"
            class="iv-solution-card"
          >
            <Store :size="24" />
            <h3>{{ solution }}</h3>
          </div>

        </div>

      </div>
    </section>

    <section id="pricing" class="iv-section">
      <div class="iv-container">

        <div class="iv-section-heading">

          <span class="iv-eyebrow">SUBSCRIPTION PLANS</span>

          <h2>Choose the right plan for your business</h2>

          <p>
            Proposed subscription packages.
            Pricing and availability will be confirmed
            before paid subscriptions launch.
          </p>

        </div>

        <div class="iv-pricing-grid">

          <article
            v-for="plan in plans"
            :key="plan.name"
            class="iv-plan"
          >

            <h3>{{ plan.name }}</h3>

            <p>{{ plan.description }}</p>

            <div class="iv-plan-price">
              Coming soon
            </div>

            <ul>
              <li
                v-for="feature in plan.features"
                :key="feature"
              >
                <CheckCircle :size="17" />
                {{ feature }}
              </li>
            </ul>

            <RouterLink
              to="/business/register"
              class="iv-secondary"
            >
              Register Your Interest
            </RouterLink>

          </article>

        </div>

      </div>
    </section>

    <section id="about" class="iv-section iv-soft">
      <div class="iv-container iv-about">

        <span class="iv-eyebrow">ABOUT IVISION</span>

        <h2>
          Technology built around your business
        </h2>

        <p>
          IVISION is designed to simplify digital
          commerce by bringing online storefronts,
          business management, customer engagement
          and payment technology together.
        </p>

        <p>
          Our objective is to give businesses the
          flexibility to operate independently,
          maintain their own identity and grow
          through intelligent technology.
        </p>

      </div>
    </section>

    <section id="faq" class="iv-section">
      <div class="iv-container">

        <div class="iv-section-heading">
          <h2>Frequently Asked Questions</h2>
        </div>

        <div class="iv-faq">

          <details
            v-for="faq in faqs"
            :key="faq.question"
          >
            <summary>{{ faq.question }}</summary>
            <p>{{ faq.answer }}</p>
          </details>

        </div>

      </div>
    </section>

    <section class="iv-final-cta">
      <div class="iv-container">

        <h2>Your next business starts here.</h2>

        <p>
          Create your IVISION business account today.
        </p>

        <RouterLink
          to="/business/register"
          class="iv-white-button"
        >
          Get Started with IVISION
          <ArrowRight :size="19" />
        </RouterLink>

      </div>
    </section>

  </div>
</template>

<style scoped>
.iv-home {
  color: #142033;
}

.iv-home * {
  box-sizing: border-box;
}

.iv-container {
  width: min(1280px, calc(100% - 64px));
  margin: auto;
}

.iv-hero {
  background: linear-gradient(135deg, #f5f8ff, #fff);
  padding: 110px 0;
}

.iv-hero-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 65px;
  align-items: center;
}

.iv-eyebrow {
  color: #315fc5;
  font-size: 12px;
  letter-spacing: 2px;
  font-weight: 700;
}

.iv-hero h1 {
  font-family: inherit;
  font-size: clamp(40px, 5vw, 68px);
  line-height: 1.12;
  letter-spacing: -2px;
  font-weight: 800;
  margin: 24px 0;
}

.iv-hero h1 span {
  display: block;
  color: #315fc5;
}

.iv-hero-copy > p {
  font-size: 18px;
  color: #667085;
  line-height: 1.8;
}

.iv-hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin: 32px 0;
}

.iv-primary,
.iv-secondary,
.iv-white-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 15px 23px;
  border-radius: 7px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
}

.iv-primary {
  background: #142033;
  color: white;
}

.iv-secondary {
  background: #fff;
  color: #142033;
  border: 1px solid #d0d5dd;
}

.iv-white-button {
  background: #fff;
  color: #142033;
}

.iv-benefits {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

.iv-benefits span {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: #667085;
}

.iv-benefits svg {
  color: #315fc5;
}

.iv-preview {
  border: 1px solid #dce2ec;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 20px 65px rgba(20, 32, 51, .12);
  overflow: hidden;
}

.iv-preview-top {
  padding: 17px;
  background: #142033;
  color: white;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 11px;
}

.iv-preview-body {
  display: flex;
  min-height: 340px;
}

.iv-preview-sidebar {
  background: #f2f4f7;
  width: 55px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 25px;
  padding: 25px 10px;
  color: #667085;
}

.iv-preview-sidebar svg {
  width: 19px;
}

.iv-preview-main {
  flex: 1;
  min-width: 0;
  padding: 20px;
}

.iv-preview-main h3 {
  font-size: 18px;
  margin: 0;
}

.iv-preview-main > p {
  font-size: 11px;
  color: #667085;
}

.iv-preview-metrics {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin: 20px 0;
}

.iv-preview-metrics > div {
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 12px 8px;
}

.iv-preview-metrics small {
  display: block;
  color: #667085;
  font-size: 10px;
}

.iv-preview-metrics strong {
  display: block;
  font-size: clamp(12px, 1.5vw, 21px);
  margin-top: 7px;
}

.iv-preview-chart {
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  padding: 16px;
  font-size: 12px;
}

.iv-chart-bars {
  height: 140px;
  display: flex;
  align-items: flex-end;
  gap: 7px;
  padding-top: 20px;
}

.iv-chart-bars > div {
  flex: 1;
  background: #5276cb;
  border-radius: 3px 3px 0 0;
}

.iv-preview-order {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
  color: #667085;
  font-size: 11px;
}

.iv-section {
  padding: 90px 0;
  scroll-margin-top: 95px;
}

.iv-soft {
  background: #f6f8fc;
}

.iv-section-heading {
  max-width: 750px;
  text-align: center;
  margin: 0 auto 45px;
}

.iv-section h2,
.iv-final-cta h2 {
  font-family: inherit;
  font-size: clamp(28px, 3.5vw, 42px);
  font-weight: 750;
  letter-spacing: -1px;
  line-height: 1.25;
}

.iv-section-heading p,
.iv-about p {
  color: #667085;
  line-height: 1.8;
  font-size: 16px;
}

.iv-feature-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 22px;
}

.iv-feature-card,
.iv-plan,
.iv-solution-card {
  border: 1px solid #e4e7ec;
  border-radius: 12px;
  background: #fff;
  padding: 28px;
}

.iv-feature-icon {
  width: 50px;
  height: 50px;
  background: #edf2ff;
  color: #315fc5;
  display: grid;
  place-items: center;
  border-radius: 10px;
}

.iv-feature-card h3 {
  font-size: 17px;
  margin-top: 22px;
}

.iv-feature-card p,
.iv-plan p {
  color: #667085;
  font-size: 14px;
  line-height: 1.8;
}

.iv-solution-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
}

.iv-solution-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 12px;
}

.iv-solution-card h3 {
  font-size: 15px;
  margin: 0;
}

.iv-pricing-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 25px;
}

.iv-plan {
  display: flex;
  flex-direction: column;
}

.iv-plan h3 {
  font-size: 24px;
  margin: 0;
}

.iv-plan-price {
  font-size: 28px;
  font-weight: 700;
  padding: 25px 0;
}

.iv-plan ul {
  list-style: none;
  padding: 0;
  margin: 0 0 30px;
  flex: 1;
}

.iv-plan li {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 0;
  font-size: 14px;
}

.iv-plan li svg {
  color: #315fc5;
  flex-shrink: 0;
}

.iv-about {
  text-align: center;
  max-width: 850px;
}

.iv-faq {
  max-width: 850px;
  margin: auto;
}

.iv-faq details {
  border-bottom: 1px solid #e4e7ec;
  padding: 22px 0;
}

.iv-faq summary {
  font-weight: 600;
  cursor: pointer;
}

.iv-faq p {
  line-height: 1.8;
  color: #667085;
}

.iv-final-cta {
  background: #142033;
  color: white;
  text-align: center;
  padding: 80px 0;
}

.iv-final-cta h2 {
  color: white;
}

.iv-final-cta p {
  color: #d0d5dd;
  margin-bottom: 30px;
}

@media (max-width: 1000px) {
  .iv-hero-grid {
    grid-template-columns: 1fr;
  }

  .iv-feature-grid,
  .iv-solution-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .iv-hero {
    padding: 70px 0;
  }
}

@media (max-width: 600px) {
  .iv-container {
    width: calc(100% - 36px);
  }

  .iv-feature-grid,
  .iv-solution-grid,
  .iv-pricing-grid {
    grid-template-columns: 1fr;
  }

  .iv-section {
    padding: 55px 0;
  }

  .iv-hero h1 {
    font-size: 40px;
  }

  .iv-preview-sidebar {
    display: none;
  }
}
</style>

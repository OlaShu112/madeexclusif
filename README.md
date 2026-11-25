# IVISION – Multi-Tenant SaaS E-Commerce Marketplace

IVISION is a multi-tenant Software as a Service (SaaS) e-commerce marketplace designed to enable businesses across multiple industries to register, establish independent online stores, manage products, process customer orders and operate their businesses through a centralised digital platform.

The platform evolved from Made Exclusif, a perfume e-commerce application, into a scalable multi-business marketplace supporting independently branded storefronts, business management, customer engagement, financial operations and Artificial Intelligence (AI).

## 1. Technology Stack

### Frontend

- Vue 3
- Vite
- Pinia
- Vue Router
- JavaScript
- HTML5
- CSS3
- RESTful API integration
- Responsive user interface

### Backend

- Laravel
- PHP
- Laravel Sanctum
- RESTful JSON API
- Eloquent ORM
- Laravel Middleware
- Laravel Mail
- Laravel Queues
- Laravel Task Scheduling

### Database

- MySQL / MariaDB
- Relational database architecture
- Database migrations
- Database seeders
- Foreign key constraints
- Transaction management
- Multi-tenant data architecture

### Artificial Intelligence

- Python
- Large Language Model (LLM) integration
- Retrieval-Augmented Generation (RAG)
- AI-powered customer assistance
- Product discovery
- Product recommendations
- Business-specific knowledge retrieval (planned extension)
- Conversation history (planned extension)

### Payment Infrastructure

- Stripe
- Stripe Checkout
- Stripe Connect
- Payment webhooks
- Commission management
- Seller financial transactions
- Refund management
- Seller payouts
- Subscription billing (planned extension)

### DevOps and Deployment

- Git
- GitHub
- GitHub Actions
- Continuous Integration (CI)
- Continuous Delivery / Deployment (CD)
- Docker (recommended production configuration)
- Nginx
- Linux Ubuntu Server
- HTTPS / SSL
- Automated database backups
- Application monitoring

---

## 2. Project Structure

```text
madeexlusif/
│
├── api/                         Active Laravel backend application
│   ├── app/
│   │   ├── Console/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Mail/
│   │   └── Jobs/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── tests/
│   ├── .env
│   ├── artisan
│   └── composer.json
│
├── frontend/                    Vue 3 frontend application
│   ├── public/
│   ├── src/
│   │   ├── api/
│   │   ├── assets/
│   │   ├── components/
│   │   ├── layouts/
│   │   ├── router/
│   │   ├── stores/
│   │   ├── utils/
│   │   └── views/
│   ├── package.json
│   └── vite.config.js
│
├── ai-service/                  Python AI microservice
│
├── backend/                     Legacy Laravel overlay
│
├── .github/
│   └── workflows/               Proposed CI/CD workflows
│
├── .gitignore
└── README.md
```

### Important

The `api/` directory is the active Laravel backend application.

The `backend/` directory contains the original Laravel overlay from
the Made Exclusif application and is retained for historical reference.

Do not create another Laravel application or copy the legacy backend
over the active `api/` directory.

---

## 3. System Architecture

IVISION uses a modular, multi-tenant architecture in which multiple
businesses operate through shared application infrastructure while
maintaining business-specific products, orders, branding, settings
and management operations.

```text
                  Customers and Businesses
                            |
                            v
                     IVISION Platform
                            |
                            v
                     Vue 3 Frontend
                            |
                       RESTful API
                            |
                            v
                     Laravel Backend
                            |
        +-------------------+-------------------+
        |                   |                   |
        v                   v                   v
   MySQL/MariaDB      Stripe Services      Python AI Service
        |                   |                   |
        v                   v                   v
   Business Data      Payment Processing    LLM / RAG
   Customer Data      Financial Records     AI Assistance
   Product Data       Seller Payments       Knowledge Retrieval
   Order Data
```

### Architectural Principles

- Separation of concerns
- Object-oriented programming
- Modular application architecture
- Reusable business services
- Centralised authentication
- Role-based access control
- Tenant-specific data management
- Secure API communication
- Maintainability
- Scalability
- Continuous integration and delivery

---

## 4. Multi-Tenant SaaS Architecture

IVISION enables multiple independent businesses to operate within
one central marketplace.

Each business is identified by a unique store ID and business slug.

Examples:

| Business | Business Slug |
|---|---|
| Made Exclusif | made-exclusif |
| MadeClothings | madeclothings |
| Atelier Mode | atelier-mode |
| Elite Jewellery | elite-jewellery |
| Luxury Motors | luxury-motors |

Each business can have its own:

- Business name
- Business logo
- Business category
- Website branding
- Website colours
- Typography
- Navigation menu
- Homepage content
- Product catalogue
- Product categories
- Product images
- Product variants
- Inventory
- Customer relationships
- Orders
- Financial records
- Website settings
- Business analytics
- Website pages and policies
- Subscription settings
- AI knowledge and recommendations

Some advanced capabilities remain under development and will be
introduced through subsequent releases.

Business-specific information must be protected through server-side
tenant isolation.

A seller must not be able to access or modify another business's
products, customers, orders, financial records or website settings.

---

## 5. Website URL Architecture

IVISION uses path-based storefront routing rather than requiring
a separate subdomain for every registered business.

### Current Local Website Addresses

| Website | URL |
|---|---|
| IVISION Homepage | http://localhost:5173/ |
| Made Exclusif | http://localhost:5173/made-exclusif |
| MadeClothings | http://localhost:5173/s/madeclothings |
| Atelier Mode | http://localhost:5173/s/atelier-mode |
| Laravel Backend | http://127.0.0.1:8000 |
| Laravel API | http://127.0.0.1:8000/api |

### Proposed Production Website Addresses

The intended production URL structure is:

| Website | URL |
|---|---|
| IVISION | https://ivision.com |
| Made Exclusif | https://ivision.com/made-exclusif |
| MadeClothings | https://ivision.com/madeclothings |
| Atelier Mode | https://ivision.com/atelier-mode |
| Elite Jewellery | https://ivision.com/elite-jewellery |
| Luxury Motors | https://ivision.com/luxury-motors |

### Proposed Independent Storefront Routes

Using MadeClothings as an example:

```text
https://ivision.com/madeclothings
https://ivision.com/madeclothings/shop
https://ivision.com/madeclothings/product/black-shirt
https://ivision.com/madeclothings/about-us
https://ivision.com/madeclothings/contact
https://ivision.com/madeclothings/cart
https://ivision.com/madeclothings/checkout
https://ivision.com/madeclothings/account
https://ivision.com/madeclothings/privacy-policy
https://ivision.com/madeclothings/returns-policy
https://ivision.com/madeclothings/terms-and-conditions
```

The clean production URL structure requires final router integration.

Until that integration is completed, independent businesses continue
to use the existing `/s/{store}` route.

### Reserved Business Slugs

The following names must be protected from business registration
to prevent conflicts with platform routes:

```text
admin
administrator
seller
api
business
register
login
logout
account
cart
checkout
shop
product
products
support
help
contact
about
privacy
terms
billing
payment
payments
assets
storage
www
s
```

---

## 6. System Requirements

### Development Environment

- macOS, Windows or Linux
- PHP compatible with the Laravel application's composer.json
- Composer
- Node.js LTS
- npm
- MySQL or MariaDB
- Python compatible with the AI service
- Git
- Visual Studio Code or another code editor

### Current macOS Development Environment

- MacBook Pro
- XAMPP
- MariaDB
- Laravel
- Vue 3
- Python virtual environment

### Current Database Configuration

```text
Database Engine: MariaDB
Database Host: 127.0.0.1
Database Port: 4306
Database Name: madeexclusif
Database Username: root
Database Password: Empty for local development
```

Production must use a dedicated database account with a strong
password and restricted permissions.

---

## 7. Local Installation and Configuration

### Step 1: Open the Project

```bash
cd ~/Desktop/madeexlusif
```

### Step 2: Start XAMPP

Open XAMPP Manager and start the MySQL database service.

The Apache service may also be started if required for phpMyAdmin.

### Step 3: Connect to MySQL/MariaDB

```bash
/Applications/XAMPP/xamppfiles/bin/mysql \
-h 127.0.0.1 \
-P 4306 \
-u root \
-p
```

Press Enter if the local root account has no password.

Check the available databases:

```sql
SHOW DATABASES;
```

Create the database only if it does not already exist:

```sql
CREATE DATABASE IF NOT EXISTS madeexclusif
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Select the database:

```sql
USE madeexclusif;
```

Display existing tables:

```sql
SHOW TABLES;
```

Exit MySQL:

```sql
EXIT;
```

---

## 8. Laravel Backend Configuration

Navigate to the active Laravel application:

```bash
cd ~/Desktop/madeexlusif/api
```

Install PHP dependencies:

```bash
composer install
```

For a new installation, create the environment file if it does not
already exist:

```bash
cp -n .env.example .env
```

Generate an application key only for a new installation without an
existing APP_KEY:

```bash
php artisan key:generate
```

Do not regenerate the application key on an existing installation
without assessing the impact on encrypted data.

### Laravel Environment Configuration

```env
APP_NAME=IVISION
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=4306
DB_DATABASE=madeexclusif
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public
```

Preserve existing application-specific environment variables,
payment credentials and AI configuration.

### Run Database Migrations

```bash
php artisan migrate
```

Check migration status:

```bash
php artisan migrate:status
```

### Database Seeders

For a new development database only:

```bash
php artisan db:seed
```

Review the seeders before running them against an existing database.

### Initialise Business Websites

```bash
php artisan platform:initialise-websites
```

This command initialises website theme settings for existing
independent businesses.

### Create the Storage Link

```bash
php artisan storage:link
```

This enables public access to uploaded images stored on Laravel's
public storage disk.

### Clear Application Cache

```bash
php artisan optimize:clear
```

### Start Laravel

```bash
php artisan serve
```

Laravel should be available at:

```text
http://127.0.0.1:8000
```

### Important Database Warning

Do not run the following command against an existing production
or development database containing important records:

```bash
php artisan migrate:fresh
```

This command drops existing database tables.

Use `php artisan migrate` when applying new migrations.

---

## 9. Vue Frontend Installation

Open a separate terminal window:

```bash
cd ~/Desktop/madeexlusif/frontend
```

Install dependencies:

```bash
npm install
```

Start the frontend development server:

```bash
npm run dev
```

The frontend should be available at:

```text
http://localhost:5173
```

### Frontend Environment Configuration

The development frontend can use the Vite proxy to communicate
with Laravel.

Where an explicit API URL is required:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

### Production Build

```bash
npm run build
```

The compiled application will be generated in:

```text
frontend/dist/
```

For production on the same domain:

```env
VITE_API_URL=/api
```

Vite environment variables are included in the frontend build.

Never place private Stripe keys, database passwords or private
LLM API keys in frontend environment variables.

---

## 10. Python AI Service

Navigate to the AI service:

```bash
cd ~/Desktop/madeexlusif/ai-service
```

Create a virtual environment if one does not already exist:

```bash
python3 -m venv .venv
```

Activate the virtual environment on macOS or Linux:

```bash
source .venv/bin/activate
```

On Windows:

```powershell
.venv\Scripts\activate
```

Install dependencies where a requirements file is provided:

```bash
pip install -r requirements.txt
```

Configure the AI service environment variables according to the
existing Python application.

These may include:

```text
LLM provider configuration
LLM API key
LLM model identifier
AI service authentication
Knowledge retrieval configuration
Vector database configuration
```

The actual variable names and startup command must match the
Python application's implementation.

Do not commit AI API keys to GitHub.

The AI service should be accessed through the Laravel backend
rather than exposing private LLM credentials to the frontend.

---

## 11. Starting the Entire Application

The development application normally requires separate terminal
windows for the frontend, backend, queue worker and AI service.

### Terminal 1 – Laravel Backend

```bash
cd ~/Desktop/madeexlusif/api

php artisan serve
```

### Terminal 2 – Vue Frontend

```bash
cd ~/Desktop/madeexlusif/frontend

npm run dev
```

### Terminal 3 – Laravel Queue Worker

```bash
cd ~/Desktop/madeexlusif/api

php artisan queue:work
```

The queue worker processes background jobs, including queued
transactional email notifications.

### Terminal 4 – Python AI Service

```bash
cd ~/Desktop/madeexlusif/ai-service

source .venv/bin/activate
```

Start the Python service using its configured application
entry point.

### Terminal 5 – Laravel Scheduler

Where scheduled tasks are configured:

```bash
cd ~/Desktop/madeexlusif/api

php artisan schedule:work
```

### Stop Running Services

Press:

```text
CTRL + C
```

in the relevant terminal window.

---

## 12. System Login Credentials

### Development Seeded Accounts

| Role | Email Address | Initial Password |
|---|---|---|
| Administrator | admin@madeexlusif.test | ChangeMe!2026 |
| Seller | seller@madeexlusif.test | password123 |
| Customer | customer@madeexlusif.test | password123 |

These are development seed credentials.

Their availability depends on whether the relevant database
seeders have been executed and whether passwords have subsequently
been changed.

Never use these credentials in production.

### Administrator Login

```text
http://localhost:5173/admin/login
```

### Seller Login

```text
http://localhost:5173/seller/login
```

### Customer Login

```text
http://localhost:5173/login
```

### Customer Registration

```text
http://localhost:5173/register
```

### Business Registration

```text
http://localhost:5173/business/register
```

### Forgot Password

```text
http://localhost:5173/forgot-password
```

### Reset Password

```text
http://localhost:5173/reset-password
```

The password reset page requires a valid password reset token
generated by the application.

---

## 13. Production Login and Registration Links

The following links represent the intended production configuration.

| Portal | Production URL |
|---|---|
| IVISION Homepage | https://ivision.com |
| Administrator Login | https://ivision.com/admin/login |
| Administrator Dashboard | https://ivision.com/admin |
| Seller Login | https://ivision.com/seller/login |
| Seller Dashboard | https://ivision.com/seller |
| Business Registration | https://ivision.com/business/register |
| Customer Login | https://ivision.com/login |
| Customer Registration | https://ivision.com/register |
| Customer Account | https://ivision.com/account |
| Forgot Password | https://ivision.com/forgot-password |
| Reset Password | https://ivision.com/reset-password |
| Made Exclusif | https://ivision.com/made-exclusif |
| MadeClothings | https://ivision.com/madeclothings |
| Atelier Mode | https://ivision.com/atelier-mode |

These production links become operational after domain configuration,
deployment and final storefront routing integration.

---

## 14. User Roles and Permissions

### Platform Administrator

The platform administrator is responsible for managing the entire
IVISION marketplace.

Administrator responsibilities include:

- Manage registered businesses
- Review business applications
- Approve businesses
- Reject business applications
- Suspend businesses
- Reactivate businesses
- Manage platform products
- Monitor customer orders
- Manage platform financial records
- Monitor commissions
- Manage refunds
- Monitor seller transactions
- Review email activity
- Monitor platform analytics
- Manage business categories
- Manage platform security
- Review audit logs where integrated
- Manage subscriptions when billing integration is completed

### Business Owner / Seller

A seller manages their own independent business.

Seller responsibilities include:

- Manage business profile
- Manage business branding
- Customise business website
- Upload business logo
- Manage website images
- Manage products
- Manage product variants
- Manage product pricing
- Manage inventory
- Manage orders
- Update order fulfilment status
- Monitor financial transactions
- Review business performance
- Manage website pages and policies where integrated
- Manage customer relationships where integrated

### Customer

Customers can:

- Browse the IVISION platform
- Visit independent business websites
- Register a customer account
- Sign in
- Browse products
- Search and filter products
- View product information
- Add products to the shopping cart
- Apply valid discount coupons
- Complete checkout
- View order history
- Manage wishlist
- Submit product reviews
- Reset their password
- Receive transactional email notifications
- Access AI assistance where available

### Guest Customer

Guest customers can browse public storefronts and products.

Guest checkout is supported by the existing commerce foundation,
subject to final payment and tenant-isolation verification.

---

## 15. How IVISION Works – Business Registration

### Step 1: Visit the IVISION Platform

```text
http://localhost:5173/
```

### Step 2: Register a Business

Navigate to:

```text
http://localhost:5173/business/register
```

The business owner provides the required registration information,
including account details and business information.

### Step 3: Select a Business Category

The business owner selects the industry in which the business
operates.

### Step 4: Create the Business Account

Laravel validates the registration information and creates the
appropriate user and business records.

A unique business slug is generated.

Example:

```text
Business Name: MadeClothings
Business Slug: madeclothings
```

### Step 5: Business Application Status

A newly registered business may initially have the status:

```text
pending
```

### Step 6: Administrator Review

The administrator signs in through:

```text
http://localhost:5173/admin/login
```

The administrator reviews the business application through the
business management interface.

### Step 7: Business Approval

When approved, the business status becomes:

```text
active
```

The business can access active seller functionality and its
public storefront.

### Step 8: Seller Notification

The business owner receives an approval notification when the
email configuration and queue processing are operational.

### Step 9: Seller Signs In

```text
http://localhost:5173/seller/login
```

### Step 10: Business Website Management

The seller can manage the business website, products, orders
and available business settings.

---

## 16. Supported Business Categories

IVISION is designed to support 27 business industries.

| No. | Business Category |
|---|---|
| 1 | Perfumes & Fragrances |
| 2 | Clothing & Fashion |
| 3 | Jewellery, Gold & Precious Metals |
| 4 | Automotive & Vehicle Parts |
| 5 | Tyres & Wheels |
| 6 | Electronics |
| 7 | Computers & Accessories |
| 8 | Mobile Phones & Accessories |
| 9 | Beauty & Cosmetics |
| 10 | Health & Wellness |
| 11 | Furniture |
| 12 | Home & Garden |
| 13 | Food & Groceries |
| 14 | Sports & Fitness |
| 15 | Footwear |
| 16 | Bags & Accessories |
| 17 | Watches |
| 18 | Books & Stationery |
| 19 | Toys & Games |
| 20 | Baby & Children |
| 21 | Pets & Pet Supplies |
| 22 | Building & Construction |
| 23 | Industrial Equipment |
| 24 | Agriculture & Farming |
| 25 | Art & Collectibles |
| 26 | Gifts & Lifestyle |
| 27 | General Merchandise |

Business categories determine the appropriate storefront
configuration, product categories and website templates.

Detailed product category seeding and industry-specific product
attributes remain subject to further implementation.

---

## 17. Business Website Builder

IVISION provides a website customisation foundation that enables
businesses to manage their storefront appearance without modifying
application source code.

### Website Customisation Address

```text
http://localhost:5173/seller/website/theme
```

### Website Builder Features

- Industry-specific website templates
- Primary colours
- Secondary colours
- Background colours
- Text colours
- Header colours
- Footer colours
- Typography
- Announcement bars
- Navigation menus
- Header settings
- Homepage sections
- Hero banner content
- Featured products
- Business descriptions
- Footer content
- Contact information
- Delivery information
- Social media links
- SEO titles
- SEO descriptions
- Draft website settings
- Website publishing
- Template reset
- Desktop, tablet and mobile previews

### Website Media Management

The media management foundation supports:

- Business logo uploads
- Hero banner images
- Promotional images
- Category images
- General website images
- Image metadata
- Alternative text
- Image dimensions
- File size validation

The media library and its integration with all storefront
components require final verification.

### Website Pages

The website page management extension is designed to support:

- About Us
- Contact Us
- Frequently Asked Questions
- Privacy Policy
- Terms and Conditions
- Returns Policy
- Delivery Policy
- Cookie Policy
- Custom Pages

Draft and publishing functionality has been prepared.

Final navigation, storefront integration and local execution
must be verified before declaring this module complete.

---

## 18. Product Management

Each business maintains its own product catalogue.

### Product Information

Products may contain:

- Product name
- Product description
- Business category
- Product category
- Brand
- Product images
- Product slug
- Product price
- Product variants
- SKU
- Stock quantity
- Featured status
- Active status

### Industry-Specific Attributes

For example:

#### Perfume Products

- Fragrance concentration
- Bottle size
- Top notes
- Heart notes
- Base notes
- Gender
- Brand

#### Clothing Products

- Size
- Colour
- Material
- Style
- Gender
- Brand

#### Electronics Products

- Model
- Manufacturer
- Technical specifications
- Warranty
- Storage capacity
- Colour

#### Jewellery Products

- Material
- Metal type
- Weight
- Purity
- Size
- Gemstone

The general-purpose product attributes and advanced inventory
management extensions require further integration.

### Product Variants

Product variants allow a product to have multiple purchasing options.

Example:

| Product | Variant | Price | Stock |
|---|---|---|---|
| Luxury Perfume | 50ml | £59.99 | 20 |
| Luxury Perfume | 100ml | £89.99 | 15 |
| Black Shirt | Small | £29.99 | 10 |
| Black Shirt | Medium | £29.99 | 25 |
| Black Shirt | Large | £29.99 | 18 |

---

## 19. Customer Shopping Workflow

### Step 1: Visit IVISION

The customer visits the central marketplace.

### Step 2: Select a Business

The customer selects an independent business storefront.

### Step 3: Browse Products

The customer can search, filter and sort available products.

### Step 4: Select a Product

The customer reviews product information, images, pricing,
available variants and stock.

### Step 5: Add to Cart

The selected product and quantity are added to the shopping cart.

### Step 6: Review Cart

The customer reviews:

- Selected products
- Quantities
- Product prices
- Subtotal
- Delivery charges
- Discount coupons
- Total order amount

### Step 7: Checkout

The customer provides the required checkout information.

### Step 8: Payment

Where payment processing is enabled and verified, the customer
is redirected to the configured Stripe payment workflow.

### Step 9: Payment Verification

Laravel verifies payment events received from Stripe.

An order must not be marked as paid solely because the customer
returns to a successful checkout page.

### Step 10: Order Confirmation

After successful order processing, the customer receives the
appropriate order confirmation notification.

### Step 11: Seller Fulfilment

The seller processes the order and updates its status.

### Step 12: Delivery

The order is shipped and subsequently delivered.

Business-specific cart, checkout and customer account isolation
must be verified before production transactions are enabled.

---

## 20. Order Management

The order management system supports the following principal
order lifecycle statuses:

```text
pending
paid
processing
shipped
delivered
cancelled
refunded
```

### Order Lifecycle

```text
Customer Places Order
          |
          v
       Pending
          |
          v
   Payment Verified
          |
          v
         Paid
          |
          v
      Processing
          |
          v
       Shipped
          |
          v
      Delivered
```

Alternative workflows include cancellation and refunds.

### Seller Order Management

The seller can:

- View business orders
- View order details
- Review customer delivery information
- Process paid orders
- Update fulfilment status
- Manage shipment information where supported

### Administrator Order Management

The administrator can monitor orders across the platform,
investigate payment issues and manage supported administrative
order operations.

---

## 21. Discount Coupons

The platform includes discount coupon functionality.

The original development seeder includes:

```text
Coupon Code: WELCOME10
Discount: 10%
Minimum Order Value: £30
```

Coupon availability depends on existing database records.

Business-specific coupon restrictions must be validated
before enabling marketplace-wide coupon functionality.

---

## 22. Payment and Financial Management

IVISION is designed to support marketplace payments using Stripe.

### Stripe Integration

The payment infrastructure includes or is designed to support:

- Stripe Checkout
- Stripe payment sessions
- Payment confirmation
- Verified Stripe webhooks
- Stripe Connect
- Seller financial accounts
- Platform commissions
- Seller transactions
- Refund management
- Seller payouts
- Financial reconciliation

### Financial Workflow

```text
Customer Places Order
          |
          v
     Stripe Checkout
          |
          v
    Payment Verification
          |
          v
      Order Marked Paid
          |
          v
    Financial Ledger Entry
          |
          v
   Commission Calculation
          |
          v
    Seller Balance Update
          |
          v
       Seller Payout
```

The complete financial workflow requires end-to-end verification.

### Stripe Environment Variables

Typical configuration includes:

```env
STRIPE_KEY=your_publishable_key
STRIPE_SECRET=your_private_secret_key
STRIPE_WEBHOOK_SECRET=your_webhook_signing_secret
```

Use the variable names defined by the actual Laravel payment
configuration.

Never commit real Stripe credentials to GitHub.

Use Stripe test mode during development and staging.

Live payments, refunds and payouts must remain disabled until
financial integration and webhook processing have been verified.

---

## 23. Business Subscription Management

IVISION is designed to operate as a subscription-based SaaS platform.

The proposed subscription infrastructure supports:

- Monthly subscriptions
- Annual subscriptions
- Business subscription plans
- Trial periods
- Product limits
- Storage limits
- AI usage limits
- Premium features
- Subscription upgrades
- Subscription downgrades
- Subscription cancellation
- Subscription renewal
- Payment failure handling

### Proposed Subscription Plans

| Feature | Starter | Professional | Enterprise |
|---|---|---|---|
| Independent Storefront | Yes | Yes | Yes |
| Product Management | Yes | Yes | Yes |
| Website Customisation | Basic | Advanced | Advanced |
| Business Analytics | Basic | Advanced | Advanced |
| AI Assistance | Limited | Enhanced | Extended |
| Newsletter Management | Limited | Yes | Yes |
| Custom Domain | No | Optional | Yes |
| Priority Support | No | Yes | Yes |

Subscription pricing and final feature limits have not yet
been established.

Database foundations have been prepared, but recurring Stripe
billing and feature enforcement remain under development.

---

## 24. Artificial Intelligence and RAG

IVISION incorporates an AI service intended to improve customer
engagement and business operations.

### AI Capabilities

- Customer product enquiries
- Product discovery
- Product recommendations
- Conversational shopping assistance
- Business-specific information retrieval
- Product comparison
- Customer support
- Business analytics assistance
- Knowledge-based responses

Advanced capabilities are introduced progressively.

### Retrieval-Augmented Generation

Retrieval-Augmented Generation (RAG) is an AI technique that
retrieves relevant information from authorised data sources
and supplies that information to a Large Language Model
to generate more relevant and contextual responses.

Potential knowledge sources include:

- Business product catalogues
- Product descriptions
- Product specifications
- Website pages
- Business policies
- Frequently asked questions
- Business knowledge documents
- Approved external information sources
- Authorised database records

### AI Workflow

```text
Customer Submits Question
           |
           v
      Laravel API
           |
           v
    Python AI Service
           |
           v
   Identify Relevant Business
           |
           v
    Retrieve Relevant Data
           |
           v
     Construct AI Context
           |
           v
    Large Language Model
           |
           v
    Generate AI Response
           |
           v
     Return to Customer
```

### Example

A customer visiting Made Exclusif asks:

"What perfume would you recommend for an evening occasion?"

The AI service should retrieve relevant products belonging
to Made Exclusif and generate recommendations using available
product information.

The same customer visiting MadeClothings should receive
recommendations based on that business's clothing catalogue.

### AI Security

The AI service must not disclose:

- Another business's private information
- Customer passwords
- Private customer records
- Payment credentials
- Administrative information
- Confidential business documents
- Private API keys

RAG does not automatically train or fine-tune the underlying model.

Business-specific retrieval, conversation persistence and usage
enforcement require final integration and testing.

---

## 25. Administrator Portal

### Administrator Login

```text
http://localhost:5173/admin/login
```

### Administrator Dashboard

```text
http://localhost:5173/admin
```

### Business Management

```text
http://localhost:5173/admin/stores
```

### Platform Management

```text
http://localhost:5173/admin/manage
```

### Administrator Profile

```text
http://localhost:5173/admin/profile
```

The administrator portal provides centralised platform oversight.

Available and planned management capabilities include:

- Platform dashboard
- Business management
- Business approval
- Product management
- Order management
- Customer oversight
- Financial management
- Refund management
- Email monitoring
- Platform analytics
- Business categories
- Security monitoring
- Audit management
- Subscription management
- AI usage monitoring

Advanced modules must only be exposed when their corresponding
backend services and authorisation controls are operational.

---

## 26. Seller Portal

### Seller Login

```text
http://localhost:5173/seller/login
```

### Seller Dashboard

```text
http://localhost:5173/seller
```

### Product Management

```text
http://localhost:5173/seller?tab=products
```

### Order Management

```text
http://localhost:5173/seller?tab=orders
```

### Website Customisation

```text
http://localhost:5173/seller/website/theme
```

### Website Media Library

```text
http://localhost:5173/seller/website/media
```

### Website Page Management

```text
http://localhost:5173/seller/website/pages
```

The website page management route is available after the
corresponding frontend and backend extensions have been applied.

### Seller Dashboard Modules

- Business overview
- Product management
- Order management
- Website management
- Media library
- Website customisation
- Customer management
- Inventory management
- Business analytics
- Financial management
- Subscription management
- Newsletter management
- Domain management
- Business settings

Some modules are planned extensions and must not be represented
as fully operational until implemented and tested.

---

## 27. Customer Account Management

### Customer Registration

```text
http://localhost:5173/register
```

### Customer Login

```text
http://localhost:5173/login
```

### Customer Account

```text
http://localhost:5173/account
```

### Shopping Cart

```text
http://localhost:5173/cart
```

### Checkout

```text
http://localhost:5173/checkout
```

### Order Success

```text
http://localhost:5173/order-success
```

Customer account functionality includes or is designed to support:

- Account registration
- Account authentication
- Profile management
- Order history
- Order details
- Wishlist
- Product reviews
- Password reset
- Email verification
- Delivery information
- Customer support

Independent business account routes are planned as part of
the final tenant-specific storefront integration.

---

## 28. IVISION Email Infrastructure

IVISION uses transactional email notifications to communicate
with customers, sellers and administrators.

### Proposed Production Email Addresses

| Email Address | Purpose |
|---|---|
| no-reply@ivision.com | Automated transactional notifications |
| admin@ivision.com | Platform administration |
| support@ivision.com | Customer and technical support |
| hello@ivision.com | General enquiries |
| info@ivision.com | Business information |
| sellers@ivision.com | Seller support |
| onboarding@ivision.com | Business registration support |
| orders@ivision.com | Order enquiries |
| sales@ivision.com | Sales and business enquiries |
| billing@ivision.com | Subscription billing |
| payments@ivision.com | Payment enquiries |
| refunds@ivision.com | Refund enquiries |
| finance@ivision.com | Financial operations |
| accounts@ivision.com | Account-related enquiries |
| security@ivision.com | Security incidents |
| privacy@ivision.com | Privacy and data protection |
| notifications@ivision.com | Platform notifications |
| marketing@ivision.com | Marketing communications |
| newsletter@ivision.com | Newsletter campaigns |
| legal@ivision.com | Legal and compliance enquiries |
| partnerships@ivision.com | Business partnerships |
| complaints@ivision.com | Customer complaints |

These are proposed operational addresses.

They must be created or configured as aliases through the
production email hosting provider.

---

## 29. Laravel Email Configuration

Configure the Laravel environment file:

```env
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_server
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS=no-reply@ivision.com
MAIL_FROM_NAME="IVISION"
```

Configure SMTP encryption or the mail scheme according to
the installed Laravel version and email provider requirements.

After updating the configuration:

```bash
php artisan optimize:clear
```

Start the queue worker:

```bash
php artisan queue:work
```

Inspect failed background jobs:

```bash
php artisan queue:failed
```

Retry a specific failed job after resolving its underlying issue:

```bash
php artisan queue:retry JOB_ID
```

Production email infrastructure should include:

- SPF
- DKIM
- DMARC
- TLS encryption
- Bounce management
- Delivery monitoring
- Email rate limiting
- Appropriate sender authentication

---

## 30. Transactional Email Templates and Workflows

IVISION's transactional email catalogue includes the following
existing and planned notification workflows.

| No. | Email Template | Recipient | Trigger |
|---|---|---|---|
| 1 | Customer Welcome | Customer | Successful registration |
| 2 | Email Verification | Customer/Seller | Account verification required |
| 3 | Password Reset | Customer/Seller/Admin | Password reset requested |
| 4 | Password Changed | Account Owner | Password successfully changed |
| 5 | Business Registration Confirmation | Seller | Business application submitted |
| 6 | New Business Application | Administrator | New business registered |
| 7 | Business Approval | Seller | Business approved |
| 8 | Business Rejection | Seller | Business rejected |
| 9 | Business Suspension | Seller | Business suspended |
| 10 | Business Reactivation | Seller | Business reactivated |
| 11 | Order Confirmation | Customer | Order successfully processed |
| 12 | New Order Notification | Seller | New business order received |
| 13 | Payment Confirmation | Customer | Payment verified |
| 14 | Payment Failure | Customer | Payment failed |
| 15 | Order Processing | Customer | Seller begins fulfilment |
| 16 | Order Shipped | Customer | Order dispatched |
| 17 | Order Delivered | Customer | Delivery status confirmed |
| 18 | Order Cancellation | Customer | Order cancelled |
| 19 | Refund Confirmation | Customer | Refund successfully processed |
| 20 | Seller Payout Notification | Seller | Payout successfully processed |

The existing application contains transactional email
infrastructure and templates.

Not every notification trigger has been verified end to end.

### Additional Planned Subscription Emails

| Email Template | Trigger |
|---|---|
| Subscription Welcome | Subscription activated |
| Subscription Confirmation | Subscription payment confirmed |
| Subscription Invoice | Invoice generated |
| Subscription Renewal | Recurring payment completed |
| Subscription Payment Failure | Recurring payment failed |
| Trial Ending Reminder | Trial approaching expiry |
| Subscription Upgrade | Plan upgraded |
| Subscription Downgrade | Plan downgraded |
| Subscription Cancellation | Subscription cancelled |
| Subscription Expiry | Subscription access expires |

### Additional Planned Marketing and Operational Emails

| Email Template | Trigger |
|---|---|
| Newsletter Confirmation | Newsletter subscription requested |
| Newsletter Welcome | Newsletter consent confirmed |
| Newsletter Campaign | Approved campaign sent |
| Newsletter Unsubscribe | Subscription removed |
| Low Stock Alert | Inventory threshold reached |
| Out of Stock Alert | Product becomes unavailable |
| Return Request Confirmation | Customer requests a return |
| Return Approved | Seller approves return |
| Return Rejected | Seller rejects return |
| Return Completed | Return workflow completed |
| Domain Verification | Custom domain registered |
| Domain Connected | Domain verification completed |
| Security Alert | Relevant security event detected |
| Data Export Confirmation | Customer data export requested |

Marketing communications require appropriate consent or another
valid legal basis and a functioning unsubscribe mechanism.

---

## 31. Business Email Branding

Transactional notifications should clearly identify the relevant
business while maintaining IVISION platform branding.

Example:

```text
From: MadeClothings via IVISION
Email: no-reply@ivision.com
Subject: Your MadeClothings Order Confirmation
```

Example:

```text
From: Made Exclusif via IVISION
Email: no-reply@ivision.com
Subject: Your Made Exclusif Order Has Been Shipped
```

Platform administration notifications should use IVISION branding.

Example:

```text
From: IVISION Business Team
Email: onboarding@ivision.com
Subject: Your Business Has Been Approved
```

Business-specific sender branding must use authenticated
sender addresses and approved email configuration.

---

## 32. Database Architecture

IVISION uses a central relational database with business-specific
relationships.

### Core Database Tables

```text
users
stores
business_categories
categories
brands
products
product_variants
orders
order_items
addresses
reviews
wishlists
coupons
refunds
email_logs
store_themes
store_media
store_payment_settings
seller_accounts
seller_transactions
seller_payouts
commission_rules
personal_access_tokens
password_reset_tokens
sessions
cache
cache_locks
jobs
job_batches
failed_jobs
migrations
```

### Additional Database Extensions

The following database extensions have been prepared for the
expanded platform:

```text
store_pages
store_domains
newsletter_subscribers
newsletter_campaigns
subscription_plans
store_subscriptions
store_usage_records
store_customers
product_images
inventory_movements
store_shipping_methods
order_returns
order_return_items
audit_logs
ai_conversations
ai_messages
ai_knowledge_documents
```

Their presence in the local database depends on whether the
corresponding migrations have been applied successfully.

### Database Relationships

```text
User
 └── Store
      ├── Products
      │    ├── Product Variants
      │    ├── Product Images
      │    └── Reviews
      ├── Orders
      │    ├── Order Items
      │    ├── Refunds
      │    └── Returns
      ├── Website Theme
      ├── Website Media
      ├── Website Pages
      ├── Customers
      ├── Financial Transactions
      ├── Seller Payouts
      ├── Subscriptions
      ├── Newsletter Subscribers
      ├── Audit Logs
      └── AI Conversations
```

Some relationships shown represent the intended extended
architecture and require final Eloquent model integration.

---

## 33. RESTful API Summary

### Public API

| Method | Endpoint | Purpose |
|---|---|---|
| GET | /api/products | Product catalogue |
| GET | /api/products/{slug} | Product details |
| GET | /api/filters | Product filters |
| GET | /api/business-categories | Business categories |
| GET | /api/business-categories/{slug} | Business category details |
| GET | /api/stores/{slug} | Public business details |
| POST | /api/register | Account registration |
| POST | /api/login | Account authentication |
| POST | /api/forgot-password | Password reset request |
| POST | /api/reset-password | Password reset |
| POST | /api/coupons/check | Validate coupon |
| POST | /api/orders | Create order |
| GET | /api/checkout/confirm | Checkout confirmation |
| POST | /api/ai/chat | AI assistance |
| POST | /api/stripe/webhook | Verified Stripe events |

### Authenticated Customer API

| Method | Endpoint | Purpose |
|---|---|---|
| GET | /api/me | Authenticated user |
| POST | /api/logout | Sign out |
| GET | /api/orders | Customer orders |
| GET | /api/orders/{number} | Order details |
| GET | /api/wishlist | Customer wishlist |
| POST | /api/wishlist/{id} | Wishlist operation |
| POST | /api/products/{slug}/reviews | Submit review |

### Business API

| Method | Endpoint | Purpose |
|---|---|---|
| POST | /api/stores | Register business |
| GET | /api/my-store | Current business information |
| PUT | /api/seller/store | Update business information |
| GET | /api/seller/products | Business products |
| POST | /api/seller/products | Create product |
| PUT | /api/seller/products/{product} | Update product |
| DELETE | /api/seller/products/{product} | Delete product |
| GET | /api/seller/orders | Business orders |
| GET | /api/seller/orders/{order} | Order details |
| PATCH | /api/seller/orders/{order} | Update order |
| GET | /api/seller/website/theme | Website settings |
| PUT | /api/seller/website/theme | Save website draft |
| POST | /api/seller/website/theme/publish | Publish website |
| POST | /api/seller/website/theme/reset | Reset template |
| GET | /api/seller/website/media | Media library |
| POST | /api/seller/website/media | Upload media |
| GET | /api/seller/finance/transactions | Financial transactions |
| GET | /api/seller/finance/payouts | Seller payouts |

### Extended Business API

The following endpoints require the prepared business
management extensions to be installed:

| Method | Endpoint | Purpose |
|---|---|---|
| GET | /api/seller/website/pages | Website pages |
| POST | /api/seller/website/pages | Create page |
| PUT | /api/seller/website/pages/{page} | Save page draft |
| POST | /api/seller/website/pages/{page}/publish | Publish page |
| POST | /api/seller/website/pages/{page}/unpublish | Unpublish page |
| DELETE | /api/seller/website/pages/{page} | Delete page |
| GET | /api/stores/{store}/pages | Public website pages |
| GET | /api/stores/{store}/pages/{slug} | Published page |
| GET | /api/seller/customers | Business customers |
| GET | /api/seller/customers/{customer} | Customer orders |
| GET | /api/seller/business-analytics | Business analytics |

### Administrator API

| Method | Endpoint | Purpose |
|---|---|---|
| GET | /api/admin/dashboard | Platform dashboard |
| GET | /api/admin/analytics | Platform analytics |
| GET | /api/admin/stores | Registered businesses |
| PATCH | /api/admin/stores/{store} | Update business status |
| GET | /api/admin/products | Platform products |
| GET | /api/admin/orders | Platform orders |
| GET | /api/admin/refunds | Refund records |
| POST | /api/admin/orders/{order}/refunds | Process refund |
| GET | /api/admin/emails | Email records |
| GET | /api/admin/emails/statistics | Email statistics |

### Verify Registered API Routes

```bash
cd ~/Desktop/madeexlusif/api

php artisan route:list
```

The Laravel route list is the authoritative reference for
the endpoints currently installed.

---

## 34. Security and Data Protection

IVISION's security architecture includes existing controls
and additional production security requirements.

### Authentication

- Laravel Sanctum
- Password hashing
- Token-based authentication
- Password reset tokens
- Email verification
- Token revocation
- Authentication rate limiting

### Authorisation

- Administrator middleware
- Seller middleware
- Role-based access control
- Business ownership verification
- Active business verification
- Tenant-specific API queries

### Application Security

- Server-side input validation
- SQL injection protection through parameterised queries
- Output escaping
- Secure file upload validation
- File type restrictions
- Rate limiting
- Database transactions
- Secure environment configuration
- Verified payment webhooks

### Tenant Isolation

Every business-specific operation must verify the current
business identity and authenticated user's permissions.

Tenant isolation must apply to:

- Products
- Orders
- Customers
- Product images
- Website settings
- Financial transactions
- Refunds
- Subscriptions
- Newsletter subscribers
- Business analytics
- AI knowledge documents
- AI conversations

### Data Protection

The platform must support appropriate UK GDPR and Data
Protection Act 2018 requirements, including:

- Lawful processing of personal information
- Privacy notices
- Data minimisation
- Access controls
- Data retention policies
- Customer data access requests
- Data correction
- Data deletion where applicable
- Marketing consent management
- Security incident management

The existence of these requirements does not constitute
certification of GDPR compliance.

---

## 35. Business Analytics

Business analytics are designed to provide sellers with
information about their business performance.

### Business Metrics

- Total orders
- Paid orders
- Gross paid order value
- Average order value
- Customer count
- Active products
- Total products
- Inventory quantity
- Low stock products
- Order status distribution
- Daily sales
- Monthly sales
- Refund totals
- Commission totals
- Net seller revenue
- Product performance

Gross paid order value must not be confused with net revenue
or profit.

Advanced financial analytics require reconciliation with
refunds, payment fees, commissions and taxes.

---

## 36. Git and GitHub Version Control

Navigate to the project root:

```bash
cd ~/Desktop/madeexlusif
```

Check the repository:

```bash
git status
```

Check the configured remote repository:

```bash
git remote -v
```

Review changes:

```bash
git diff
```

Stage approved changes:

```bash
git add .
```

Review staged changes:

```bash
git diff --cached
```

Create a commit:

```bash
git commit -m "Update IVISION marketplace platform"
```

Push the current branch:

```bash
git push
```

Review all staged files before committing.

Never commit environment files, database dumps, private keys
or sensitive customer information.

### Recommended Git Branches

| Branch | Purpose |
|---|---|
| main | Stable production releases |
| develop | Ongoing development |
| feature/* | New functionality |
| release/* | Release preparation |
| hotfix/* | Urgent production corrections |

### Example Feature Branch

```bash
git checkout -b feature/business-subscriptions
```

After implementing and verifying the feature:

```bash
git add .

git commit -m "Implement business subscription management"

git push -u origin feature/business-subscriptions
```

---

## 37. Git Ignore Configuration

The repository should exclude sensitive files and generated
dependencies.

Example `.gitignore` rules:

```gitignore
.env
.env.*
!.env.example

vendor/
node_modules/
dist/

.venv/
venv/
__pycache__/
*.pyc

*.log
*.sql
*.sqlite
*.sqlite3

*.pem
*.key

.DS_Store

api/storage/logs/
api/storage/framework/
api/storage/app/
api/public/storage

auth.json
.npmrc
```

Merge these rules with the existing `.gitignore` configuration.

Do not remove required Laravel directory placeholders.

Files already tracked by Git require separate removal from
the Git index.

---

## 38. Continuous Integration and Continuous Deployment

IVISION follows an incremental software development approach.

The initial release can be deployed as a Minimum Viable Product
(MVP), with additional functionality introduced through
controlled CI/CD releases.

### CI/CD Workflow

```text
Developer Implements Feature
             |
             v
      Local Development
             |
             v
        Git Commit
             |
             v
      GitHub Repository
             |
             v
       GitHub Actions
             |
             v
    Automated Build Checks
             |
             v
      Automated Testing
             |
             v
      Staging Deployment
             |
             v
       Release Approval
             |
             v
    Production Deployment
             |
             v
    Monitoring and Feedback
```

### Continuous Integration

The CI pipeline should perform:

- Composer dependency installation
- PHP syntax validation
- Laravel automated tests
- Frontend dependency installation
- Vue production build
- Python dependency installation
- Python automated tests where available
- Security dependency checks
- Migration verification using a disposable database

### Continuous Delivery

The delivery pipeline should:

- Build release artifacts
- Deploy to staging
- Run smoke tests
- Validate database connectivity
- Validate application health
- Require production approval
- Prepare rollback artifacts

### Continuous Deployment

Once the application and deployment pipeline are sufficiently
verified, approved changes can be deployed automatically.

### GitHub Actions Workflow Location

```text
.github/workflows/
```

Recommended workflow files:

```text
.github/workflows/ci.yml
.github/workflows/staging.yml
.github/workflows/production.yml
```

These workflow files must be configured for the selected
production infrastructure.

---

## 39. Production Deployment

### Recommended Production Infrastructure

| Component | Technology |
|---|---|
| Operating System | Ubuntu 24.04 LTS |
| Web Server | Nginx |
| Frontend | Vue 3 production build |
| Backend | Laravel and PHP-FPM |
| Database | MySQL / MariaDB |
| AI Service | Python |
| Background Jobs | Laravel Queue Worker |
| Process Management | Supervisor or systemd |
| SSL | Let's Encrypt |
| Source Control | GitHub |
| CI/CD | GitHub Actions |
| Containerisation | Docker, where appropriate |

### Suggested Initial Server Specification

```text
CPU: 4 vCPU
RAM: 8 GB
Storage: 100 GB SSD
Operating System: Ubuntu 24.04 LTS
```

The actual server specification depends on traffic, database
size, AI processing requirements and hosting budget.

### Production Domain

```text
https://ivision.com
```

### Production API

```text
https://ivision.com/api
```

### Production Environment Configuration

```env
APP_NAME=IVISION
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ivision.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ivision
DB_USERNAME=ivision_app
DB_PASSWORD=your_secure_database_password

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

MAIL_FROM_ADDRESS=no-reply@ivision.com
MAIL_FROM_NAME="IVISION"
```

The production database name, port and credentials must match
the selected server configuration.

### Laravel Production Commands

After creating a verified database backup:

```bash
composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan storage:link

php artisan optimize

php artisan queue:restart
```

### Vue Production Commands

```bash
npm ci

npm run build
```

Serve the generated `dist/` directory through Nginx.

### Nginx Routing Requirements

The web server must:

- Serve the Vue production application
- Forward API requests to Laravel
- Support Vue Router history mode
- Serve approved public storage assets
- Protect private Laravel files
- Enforce HTTPS
- Prevent access to environment files
- Support direct navigation to independent business URLs

The Laravel backend document root must point to its `public/`
directory, not the entire Laravel project.

---

## 40. Database Backup and Recovery

### Create a Backup Directory

```bash
mkdir -p ~/Desktop/madeexlusif-backups
```

### Back Up the Local Database

```bash
/Applications/XAMPP/xamppfiles/bin/mysqldump \
-h 127.0.0.1 \
-P 4306 \
-u root \
-p \
--single-transaction \
madeexclusif \
> ~/Desktop/madeexlusif-backups/database-$(date +%Y%m%d-%H%M%S).sql
```

### Back Up Application Source Code

```bash
cd ~/Desktop/madeexlusif

tar -czf ~/Desktop/madeexlusif-backups/source-$(date +%Y%m%d-%H%M%S).tar.gz \
api/app \
api/routes \
api/database \
frontend/src \
ai-service
```

This source backup does not include every configuration file
or uploaded media asset.

A complete disaster recovery strategy must also protect:

- Database records
- Uploaded business media
- Product images
- Application configuration
- Secure environment secrets
- Email configuration
- AI knowledge documents
- Deployment configuration

Production backups must be encrypted, access-controlled and
periodically tested through restoration exercises.

---

## 41. Testing

### Laravel Tests

```bash
cd ~/Desktop/madeexlusif/api

php artisan test
```

### PHP Syntax Validation

```bash
find app routes database/migrations \
-name '*.php' \
-exec php -l {} \;
```

### Frontend Build Validation

```bash
cd ~/Desktop/madeexlusif/frontend

npm run build
```

### Recommended Testing Categories

- Unit testing
- Integration testing
- API testing
- End-to-end testing
- Authentication testing
- Authorisation testing
- Tenant isolation testing
- Payment testing
- Email testing
- File upload testing
- Database migration testing
- Accessibility testing
- Performance testing
- Security testing
- User acceptance testing

### Essential Release Test Scenarios

- Administrator can sign in
- Seller can sign in
- Customer can register and sign in
- Business can register
- Administrator can approve a business
- Approved business can access its dashboard
- Business storefront displays correct branding
- Business products display correctly
- Seller cannot access another business's private records
- Customer can browse and search products
- Shopping cart calculates totals correctly
- Checkout validates product prices and stock
- Stripe test payments are verified correctly
- Order confirmation emails are delivered
- Password reset emails function correctly
- Website settings can be saved and published
- Uploaded images display correctly
- Application works on desktop and mobile devices
- Database backups can be restored
- Failed deployments can be rolled back

Comprehensive testing must be completed before describing
the platform as production-ready.

---

## 42. Troubleshooting

### Laravel Cache Issues

```bash
php artisan optimize:clear
```

### Database Connection Issues

Check:

```text
XAMPP MySQL service
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

The current macOS XAMPP database port is:

```text
4306
```

### Laravel Dependencies Missing

```bash
composer install
```

### Frontend Dependencies Missing

```bash
npm install
```

### Port 8000 Already in Use

```bash
lsof -i :8000
```

Identify the running process before stopping it.

### Port 5173 Already in Use

```bash
lsof -i :5173
```

### Images Not Displaying

```bash
php artisan storage:link

php artisan optimize:clear
```

Also verify storage permissions and the application's
public asset URL configuration.

### Emails Not Sending

Check:

- SMTP configuration
- Mail credentials
- Queue worker
- Failed jobs
- Application logs
- Sender authentication
- Email provider restrictions

```bash
php artisan queue:failed
```

### Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

### Check API Routes

```bash
php artisan route:list
```

### Check Database Migrations

```bash
php artisan migrate:status
```

### Rebuild Frontend

```bash
npm run build
```

---

## 43. Current Implementation Status

IVISION is being developed incrementally.

### Existing Core Implementation

- Central marketplace architecture
- Made Exclusif perfume storefront
- Independent business registration
- Business approval workflow
- Business-specific storefronts
- Seller dashboard
- Administrator dashboard
- Product catalogue
- Product variants
- Shopping cart
- Customer authentication
- Order management
- Website theme customisation
- Draft and publish website settings
- Website media management foundation
- Stripe payment infrastructure
- Financial management foundation
- Transactional email infrastructure
- AI service foundation

### Modules Under Extension

- Advanced website media integration
- Website pages and legal policies
- Clean business URL routing
- Business-specific customer accounts
- Independent business cart and checkout
- Advanced inventory management
- Business analytics
- Financial reconciliation
- Refund and returns management
- Business subscriptions
- Newsletter management
- Custom domain management
- Advanced AI and RAG
- Audit management
- Advanced security and data protection

### Production Preparation

- Comprehensive testing
- GitHub Actions configuration
- Staging infrastructure
- Production infrastructure
- Domain configuration
- HTTPS configuration
- Monitoring
- Automated backups
- Disaster recovery
- Deployment rollback

The application can be prepared for an initial MVP release
without completing every planned advanced feature.

However, unfinished or unverified functionality must be
disabled or hidden before public production deployment.

---

## 44. Release Management

### Version 1.0 – Initial MVP

Target functionality:

- IVISION marketplace
- Made Exclusif storefront
- Business registration
- Business approval
- Independent business storefronts
- Seller authentication
- Administrator authentication
- Customer authentication
- Product catalogue
- Basic website customisation
- Verified order management
- Verified transactional emails

Payment functionality should only be enabled after successful
payment and financial verification.

### Version 1.1 – Business Management Extensions

Proposed improvements:

- Advanced website media management
- Website pages
- Legal policies
- Clean business URLs
- Advanced product management
- Inventory management
- Customer management
- Business analytics

### Version 1.2 – Financial and Subscription Extensions

Proposed improvements:

- Business subscription plans
- Recurring billing
- Commission reconciliation
- Seller payout management
- Advanced refund management
- Returns management

### Version 1.3 – AI and Marketing Extensions

Proposed improvements:

- Business-specific RAG
- AI product recommendations
- AI conversation history
- AI usage management
- Newsletter campaigns
- Customer engagement automation

### Version 1.4 and Future Releases

Proposed improvements:

- Custom domains
- Advanced business integrations
- Advanced reporting
- Enterprise business features
- Performance optimisation
- Additional security controls
- Infrastructure scalability

Release numbers and feature allocations may change according
to development priorities and business requirements.

---

## 45. Before Going Live

Complete the following production release checklist.

### Application

- [ ] Verify Laravel application functionality
- [ ] Verify Vue production build
- [ ] Verify Python AI service
- [ ] Verify database migrations
- [ ] Verify business registration
- [ ] Verify business approval
- [ ] Verify independent storefronts
- [ ] Verify seller dashboard
- [ ] Verify administrator dashboard
- [ ] Verify customer authentication
- [ ] Disable unfinished functionality

### Security

- [ ] Set APP_DEBUG=false
- [ ] Remove or change seeded development credentials
- [ ] Configure strong production passwords
- [ ] Verify tenant isolation
- [ ] Verify role-based permissions
- [ ] Protect environment files
- [ ] Configure HTTPS
- [ ] Configure firewall
- [ ] Configure secure SSH access
- [ ] Rotate any previously exposed credentials

### Payments

- [ ] Verify Stripe test payments
- [ ] Verify Stripe webhook signatures
- [ ] Verify order payment status
- [ ] Verify duplicate webhook handling
- [ ] Verify stock reservation and restoration
- [ ] Verify commission calculations
- [ ] Verify refund workflows
- [ ] Verify seller financial records
- [ ] Approve live payment activation

### Email

- [ ] Configure production email accounts
- [ ] Configure SMTP
- [ ] Configure SPF
- [ ] Configure DKIM
- [ ] Configure DMARC
- [ ] Verify transactional email delivery
- [ ] Verify password reset emails
- [ ] Verify business approval emails
- [ ] Verify order confirmation emails
- [ ] Configure persistent queue workers

### Deployment

- [ ] Configure GitHub repository
- [ ] Configure GitHub Actions
- [ ] Configure staging environment
- [ ] Configure production server
- [ ] Configure Nginx
- [ ] Configure PHP-FPM
- [ ] Configure Python service
- [ ] Configure database
- [ ] Configure domain
- [ ] Configure SSL certificate
- [ ] Configure application monitoring
- [ ] Configure automated backups
- [ ] Verify backup restoration
- [ ] Configure deployment rollback
- [ ] Approve production release

---

## 46. Software Development Methodology

IVISION follows an Agile and incremental software development
methodology.

The development lifecycle consists of:

```text
Requirements Analysis
        |
        v
System Design
        |
        v
Implementation
        |
        v
Testing
        |
        v
Deployment
        |
        v
Monitoring
        |
        v
Continuous Improvement
```

New functionality is developed, integrated, tested and released
through controlled development cycles.

The platform does not require every future feature to be completed
before releasing a verified Minimum Viable Product.

Continuous Integration and Continuous Delivery enable the
application to evolve while maintaining system stability.

---

## 47. Future Development

Future development may include:

- Mobile applications for Android and iOS
- Advanced AI shopping assistants
- Voice-based shopping assistance
- Personalised product recommendations
- Multi-currency support
- International marketplace support
- Automated tax calculations
- Advanced shipping integrations
- Warehouse management
- Supplier management
- Customer Relationship Management (CRM)
- Loyalty programmes
- Gift cards
- Business-to-business commerce
- Point of Sale (POS)
- Advanced reporting
- Marketing automation
- Enterprise subscription plans
- Custom business domains
- Third-party API integrations
- Distributed infrastructure
- Advanced monitoring and observability

---

## 48. Project Information

```text
Project Name: IVISION
Project Type: Multi-Tenant SaaS E-Commerce Marketplace
Original Application: Made Exclusif
Architecture: Modular Full-Stack Application
Frontend: Vue 3
Backend: Laravel
Database: MySQL / MariaDB
Artificial Intelligence: Python, LLM and RAG
Payment Infrastructure: Stripe
Development Methodology: Agile
Deployment Strategy: CI/CD
Release Strategy: Incremental MVP Releases
```

---

## 49. Developer

**Oladotun A. Ogundari**

BEng (Hons) Software Engineering – First Class Honours

Sheffield Hallam University

Software Engineering | Artificial Intelligence | Database
Engineering | Full-Stack Development | SaaS Architecture

---

## 50. Copyright and Licensing

Copyright © 2026 IVISION.

All rights reserved, subject to the project's final licensing
and ownership arrangements.

IVISION is intended to operate as a proprietary SaaS marketplace
platform.

Third-party libraries, frameworks and dependencies remain
subject to their respective licences.

---

## 51. Disclaimer

IVISION is an evolving software platform.

This documentation describes the existing application architecture,
implemented foundations, prepared extensions and proposed
production configuration.

Features identified as planned, partial or unverified must not
be represented as fully operational until implementation,
integration and testing have been completed.

Development credentials are provided exclusively for local
development purposes.

Production credentials, payment secrets, customer information
and confidential business data must never be committed to
the source code repository.

---

# IVISION

## Empowering Businesses Through Technology

One Platform. Multiple Businesses. Independent Storefronts.
Intelligent Commerce.
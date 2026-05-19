# CLAUDE.md — Corasoft Multi-Vendor Marketplace

Multi-vendor (Alibaba-style) marketplace for the Cambodian market. Shops register and sell independently; platform takes commission and handles payout, escrow, and disputes.

## Stack (non-negotiable)

- **Backend**: Laravel 13, PHP 8.3+, PostgreSQL 16+
- **Frontend**: Inertia.js v2 + React 19 + TypeScript + Tailwind 4 + shadcn/ui (Laravel React starter kit)
- **Admin panel**: FilamentPHP v3 (admin + vendor dashboards both use Filament panels)
- **Mobile/3rd-party API**: Laravel Sanctum (separate from Inertia — do NOT mix)
- **Real-time**: Laravel Reverb (chat, order updates, typing indicators)
- **Queues**: Laravel Horizon on Redis
- **Search**: Meilisearch (Khmer + English, faceted)
- **Cache/Sessions**: Redis
- **Storage**: Cloudflare R2 (S3-compatible) for product images and chat attachments
- **Payments**: ABA PayWay (sandbox → production)
- **Delivery**: provider-agnostic interface (Grab, J&T, Kerry, VET, Cambodia Post)

## Architecture rules

- **Actions pattern**: every business operation is a single-action invokable class under `app/Actions/{Domain}/`. Controllers orchestrate, Actions execute. Never put business logic in controllers or models.
- **Three surfaces, one codebase**:
  1. Public storefront (Inertia + React) — `routes/web.php`, `app/Http/Controllers/Storefront/`
  2. Vendor panel (Filament) — `app/Filament/Vendor/`
  3. Admin panel (Filament) — `app/Filament/Admin/`
  4. Mobile/integrations API (Sanctum) — `routes/api.php`, `app/Http/Controllers/Api/V1/`
- **Tenant isolation is mandatory**: every model that belongs to a shop MUST use a `BelongsToShop` global scope. A vendor must NEVER be able to query another vendor's data. Enforce in tests.
- **Money is always stored as integers (cents)**. Never float. Use `brick/money` package. Every monetary column ends in `_cents` and has a `_currency` sibling (`USD` or `KHR`).
- **All events publish to the event bus** (`app/Events/`) so notifications, webhooks, analytics, and ledger entries stay decoupled.

## Domain model (critical — do not deviate)

- `User` — anyone; role flag: `buyer`, `vendor_owner`, `vendor_staff`, `admin`
- `Shop` — one owner, many staff; has status: `draft` → `submitted` → `approved` → `active` | `suspended` | `rejected`
- `ShopVerification` — KYC docs (business license, ID, bank account)
- `Product` (shop-scoped) → has many `ProductVariant` (the real SKU with stock + price)
- `Category` (platform-wide, hierarchical, bilingual km/en)
- `Cart` → `CartItem` (references variant, snapshots price)
- `Order` (parent, one per checkout) → has many `SubOrder` (one per shop in the cart)
- `OrderItem` belongs to SubOrder, references ProductVariant, snapshots price/name/image at purchase time
- `Payment` (ABA PayWay transaction) — one per Order
- `Shipment` — one per SubOrder, belongs to a DeliveryProvider
- `VendorWallet` → `WalletTransaction` (credit/debit ledger)
- `Payout` → `PayoutBatch` (vendor withdraw requests, admin approves)
- `Dispute` → `DisputeMessage` (buyer ↔ vendor ↔ admin)
- `Conversation` → `Message` → `MessageAttachment` (chat, per buyer–shop pair)
- `Review` (verified purchasers only, one per OrderItem)

Order states: `pending → paid → accepted → packed → picked_up → in_transit → delivered → completed`; plus `cancelled`, `refund_requested`, `refunded`, `disputed`.

## Money flow (escrow)

1. Buyer pays full amount → lands in platform's ABA PayWay account.
2. Payment splits into per-SubOrder holds in `VendorWallet` as `pending_balance`.
3. On `delivered` + 7-day buyer confirmation window → `pending_balance` moves to `available_balance` minus platform commission.
4. Vendor requests payout → admin approves → PayoutBatch pays out via bank transfer (manual or ABA batch).
5. Refunds reverse the ledger and call ABA PayWay refund API.

Commission is per-shop (default 8%, admin-overridable). Every wallet change is a double-entry ledger row. Never mutate balances directly — always via `WalletTransaction`.

## Coding standards

- **PHP**: strict types (`declare(strict_types=1);`), readonly DTOs, named arguments for anything with >2 params, final classes by default.
- **PHPStan level 8** must pass. **Pint** must pass (Laravel preset). **Pest** for tests (not PHPUnit syntax).
- **TypeScript**: `strict: true`, no `any`. All Inertia page props typed via shared `types/` + `@inertiajs/react` generics.
- **React**: function components only, hooks, shadcn/ui components, no class components, no inline styles.
- **Tailwind**: design tokens via CSS variables; never hardcoded hex colors in JSX.
- **Migrations**: one migration per change, never edit a shipped migration; always include `down()`.
- **Naming**: snake_case tables and columns, singular model names, plural route segments, kebab-case URL slugs.
- **Khmer/English**: every user-facing string goes through `__()`; product/category content uses `translatable` JSON columns (name_i18n, description_i18n) keyed by locale.

## Commands (verify before assuming)

```bash
# Setup
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed

# Dev (run all three in parallel)
php artisan serve
npm run dev
php artisan reverb:start
php artisan horizon

# Quality gates (must all pass before commit)
./vendor/bin/pint
./vendor/bin/phpstan analyse
./vendor/bin/pest --parallel
npm run lint && npm run typecheck

# Create scaffolding (always use artisan make:, never hand-write)
php artisan make:model Shop -mfs   # model + migration + factory + seeder
php artisan make:action Shop/ApproveShop
php artisan make:filament-resource ShopResource --panel=admin
```

## Third-party integrations — read before coding

- **ABA PayWay**: use `$TRANSACTION_HASH` HMAC signing on every request; verify callback signature; sandbox keys live in `.env`, never committed. Whitelist callback IP with ABA. Store full raw callback payload in `payment_events` for audit.
- **Delivery providers**: implement `App\Contracts\DeliveryProvider` interface (`createShipment`, `getRate`, `trackShipment`, `cancelShipment`, `handleWebhook`). Start with a stub provider for local dev. Each real provider is a separate class under `app/Services/Delivery/`.
- **Webhooks**: every inbound webhook (payment, delivery, chat) MUST verify signature, be idempotent (store `external_event_id` with unique index), and dispatch to a queued job. Never process inline.

## Security non-negotiables

- Enforce `auth:sanctum` + `ability:*` on API routes; `auth` + role middleware on web.
- 2FA (TOTP) required for `admin` and `vendor_owner`.
- Signed URLs for all private file downloads (invoices, KYC docs).
- Rate-limit: auth endpoints (5/min), order creation (10/min per user), chat messages (60/min).
- Every destructive action (shop suspension, payout approval, refund) writes to `audit_logs`.
- CSP headers, HSTS, CSRF on all non-API POST.
- Never log: passwords, card data, full ABA tokens, chat message bodies.

## Testing rules

- Every Action has a Pest feature test asserting success + authorization failure + tenant isolation.
- Every webhook handler has a test for: valid signature, invalid signature, replay (same event_id), malformed payload.
- Money math tested with explicit cents, never floats.
- Minimum 80% coverage on `app/Actions`, `app/Services`, `app/Http/Controllers`.

## When Claude Code is unsure

- **Never invent ABA PayWay or delivery API endpoints** — ask for the latest developer PDF. Docs are at `developer.payway.com.kh`.
- **Never assume Laravel 13 syntax from memory** — check `laravel.com/docs/13.x` or run `php artisan --version`.
- **Inertia v2 only** — not v1. Use `@inertiajs/react` v2 APIs (`router.visit`, `useForm`, deferred props).
- If a migration would alter a column with data in it, stop and ask — write a data-migration strategy first.
- If unsure whether logic belongs in Action vs Service vs Model, default to Action.

## File layout

```
app/
  Actions/{Shop,Order,Payment,Chat,Payout,Dispute}/
  Contracts/           # interfaces (DeliveryProvider, PaymentGateway)
  Events/
  Filament/{Admin,Vendor}/Resources/
  Http/
    Controllers/{Storefront,Api/V1}/
    Middleware/
    Requests/
  Models/
  Services/{Delivery,Payment,Search,Notification}/
  Support/             # value objects, DTOs, enums
resources/js/
  pages/               # Inertia pages
  components/          # shadcn + custom
  layouts/
  hooks/
  types/               # shared types with backend (generated)
database/
  migrations/
  factories/
  seeders/
tests/{Feature,Unit}/
```

## Do NOT

- Do NOT use Sanctum tokens for Inertia pages (Inertia uses session auth).
- Do NOT query across tenants without explicit admin scope.
- Do NOT store prices as decimal/float.
- Do NOT call payment or delivery APIs from controllers — always through a Service behind an interface.
- Do NOT skip the `subOrder` split: even a single-shop cart creates exactly one SubOrder under one Order.
- Do NOT add features that weren't asked for without flagging them first.

---

## Change Log

### Storefront — Frontend (React / TypeScript)

#### Navigation (`StorefrontLayout.tsx`)
- **Two-row header**: Row 1 = logo + search bar + cart/user actions; Row 2 = Shop Categories dropdown + nav links.
- **Shop Categories dropdown**: dynamically populated from `navCategories` shared prop; uses Lucide icon + coloured bubble per category slug; closes on outside click (`useRef` + `useEffect`).
- **Dropdown z-index fix**: dropdown parent placed **outside** `overflow-x-auto` wrapper (CSS spec forces `overflow-y` clipping when `overflow-x ≠ visible`); dropdown uses `z-999` (Tailwind v4 syntax).
- **`navCategories`** shared globally via `HandleInertiaRequests::share()` — available on every page without per-controller fetching.
- **Category icon map** (`CATEGORY_ICONS`): covers `electronics`, `fashion`, `home-living`, `beauty`, `sports`, `food`, `agri-plants-pet`, `arts`, `baby-kid`, `beverage`, `building-material`, `clothes-shoes`, `food-grocery`, `furniture/s`; falls back to `Package` icon.
- Removed hardcoded category URL strings — all use `route()` helper.

#### Hero Section (`home.tsx` — `HeroSlideshow` component)
- Converted static hero `<section>` to auto-advancing slideshow.
- Slides defined as an array of `HeroSlide` objects — **add a slide by appending to the array only**.
- Auto-advances every 5 s; pauses on mouse hover.
- Crossfade transition (`opacity + duration-700`) via CSS grid stacking (`gridArea: '1 / 1'`).
- `‹ ›` arrow buttons (frosted glass) + pill dot indicators (active dot stretches wide).
- Each slide has: `badge`, `title`, `accent` (yellow text), `description`, `primaryLabel/Href`, `secondaryLabel/Href`, `gradient`.
- Ships with 3 slides: Cambodia Marketplace, Kampot Pepper Season Sale, Latest Electronics.

#### Feature Strip (`home.tsx`)
- Replaced plain horizontal divider bar with **floating card grid**.
- Cards overlap the hero bottom by 6 px (`-mt-6`); `z-10` removed (was creating a stacking context that interfered with nav dropdown — header `z-50` is sufficient).
- Each card: unique coloured icon bubble (blue/green/purple/orange), hover lift + shadow, icon scales on hover.

#### Categories
- **`categories.index` route added** (`GET /categories` → `CategoryController@index`).
- **`CategoryController::index()`** returns all active categories with `products_count`.
- **`categories/index.tsx`** page: gradient hero header, full card grid (gradient banner per card, emoji + product count).
- **"View all →"** link on home page fixed: was `route('categories.show', 'electronics')` → now `route('categories.index')`.
- **`categories/show.tsx` pagination fixed**: interface was wrong — expected `meta.total / links.prev` but Laravel `paginate()` returns flat fields (`total`, `prev_page_url`, `next_page_url`). Rewrote interface and destructuring. Blank page bug eliminated.
- Breadcrumb updated: **Home → Categories → [Category Name]**.

#### Conversations (`conversations/show.tsx`, `conversations/index.tsx`)
- **Duplicate messages**: WebSocket listener now skips messages sent by current user (already added optimistically in `onSuccess`).
- **Wrong role in optimistic message**: was hardcoded `'buyer'`; now uses `auth.user.role`.
- **Conversation preview** in index: was showing `messages[0]` (oldest); fixed to `messages[messages.length - 1]` (latest).
- `flex-shrink-0` → `shrink-0` (Tailwind v4).

#### Authentication (`two-factor-setup.tsx`)
- **Critical security fix**: QR code was generated via `api.qrserver.com` — the `otpauth://` URI (containing the TOTP secret) was sent to a third-party server.
- Replaced with `qrcode.react` (`<QRCodeSVG>`) for fully client-side QR generation.
- Added `qrcode.react` to `package.json` dependencies.

#### Cart & Checkout
- **`cart.tsx`**: "Proceed to Checkout" button was routing to `dashboard`; fixed to `route('checkout.index')`. Fixed `flex-shrink-0` → `shrink-0`.
- **`checkout.tsx`**: Added all required shipping address fields (`shipping_name`, `shipping_phone`, `shipping_address`, `shipping_city`, `shipping_province`, `shipping_postal_code`) and optional `note` field to match `CheckoutRequest` validation.
- **`checkout-qr.tsx`**: Post-payment success redirect changed from home to `route('orders.index')` for better UX.

#### Orders & Dashboard
- **`orders/index.tsx`, `orders/show.tsx`, `dashboard.tsx`**: Status display `replace('_', ' ')` → `replaceAll('_', ' ')` — only the first underscore was replaced in multi-word statuses like `refund_requested`.
- **`orders/show.tsx`**: Fixed Tailwind `min-w-[80px]` → `min-w-20`.

#### UI Components
- **`textarea.tsx`**: `min-h-[60px]` → `min-h-15` (Tailwind v4).

#### `formatPrice` deduplication
- Removed local `formatPrice` definitions from `home.tsx`, `products/show.tsx`, `categories/show.tsx`, `shops/show.tsx`.
- All now import from `@/lib/utils`.

---

### Backend — PHP / Laravel

#### Shared Inertia Props (`HandleInertiaRequests.php`)
- Added `navCategories` lazy prop — queries all active categories ordered by `sort_order`.
- Added `declare(strict_types=1)` + `final class`.

#### Routes (`routes/web.php`)
- Added `GET /categories` → `categories.index` (before `categories/{slug}` to avoid slug conflict).

#### `CategoryController`
- Added `index()` method: returns all active categories with `products_count` (only active products counted).
- Page: `storefront/categories/index`.

#### Admin Panel — Domain Rule Fixes
| Resource | Fix |
|---|---|
| `Admin/Orders/OrderResource` | Removed `create` page from `getPages()` |
| `Admin/Orders/Pages/ListOrders` | Removed `CreateAction` — orders created by buyers via checkout only |
| `Admin/Orders/Pages/EditOrder` | Removed `DeleteAction` — orders are financial records |
| `Admin/Payouts/PayoutResource` | Removed `create` page from `getPages()` |
| `Admin/Payouts/Pages/ListPayouts` | Removed `CreateAction` — payouts initiated by vendors only |
| `Admin/Payouts/Pages/EditPayout` | Removed `DeleteAction` — payouts are financial records |
| `Vendor/Orders/Pages/ListOrders` | Removed `CreateAction` — `create` page was not even registered; dead broken link |
| `Vendor/Payouts/Pages/EditPayout` | Removed `DeleteAction` — payout is a financial record |

#### PHP Standards Enforcement (batch via PowerShell)
- **31 files** patched to add `declare(strict_types=1);` — covered all `app/Filament/**` and `app/Http/Controllers/**` files missing it.
- **36 files** patched to add `final` to `class` declarations — same scope, excluding `Controller.php` base class (other controllers extend it).

---

### Tailwind v4 Migrations (global)
| Old class | New class | Files |
|---|---|---|
| `flex-shrink-0` | `shrink-0` | 6 TSX files |
| `bg-gradient-to-*` | `bg-linear-to-*` | 4 TSX files |
| `min-h-[60px]` | `min-h-15` | `textarea.tsx` |
| `min-w-[80px]` | `min-w-20` | `orders/show.tsx` |
| `z-[999]` | `z-999` | `StorefrontLayout.tsx` |

---

### Known Patterns to Follow

#### Laravel Paginator JSON shape (flat — no `meta` wrapper)
```ts
// CORRECT — matches what Laravel paginate() actually returns
interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}
// Use: products.total  products.prev_page_url  products.current_page
// NOT: products.meta.total  products.links.prev
```

#### Hero Slideshow — adding a slide
```ts
// In resources/js/Pages/storefront/home.tsx → HeroSlideshow() → slides array
{
    badge: '🏷️ Your badge text',
    title: 'First line of heading',
    accent: 'Yellow accent line',
    description: 'Subtitle text shown below heading.',
    primaryLabel: 'CTA Button',
    primaryHref: route('categories.show', 'your-slug'),
    secondaryLabel: 'Secondary CTA',
    secondaryHref: route('shops.index'),
    gradient: 'from-indigo-800 via-indigo-600 to-primary/70',
},
```

#### Category icon map — adding a new slug
```ts
// In resources/js/Layouts/Storefront/StorefrontLayout.tsx → CATEGORY_ICONS
'your-slug': { Icon: SomeLucideIcon, color: 'text-blue-600', bg: 'bg-blue-50' },
```

---

### Session 3 — Storefront Pages & Products Listing

#### About & Contact Pages
- Created `resources/js/Pages/storefront/about.tsx` — hero, stats bar, mission, 6 value cards, team section, vendor CTA strip.
- Created `resources/js/Pages/storefront/contact.tsx` — contact info cards (address, phone, email, hours), social links, contact form with topic dropdown + success state.
- Added routes to `routes/web.php`:
  ```php
  Route::get('/about',   fn () => Inertia::render('storefront/about'))->name('about');
  Route::get('/contact', fn () => Inertia::render('storefront/contact'))->name('contact');
  ```
- **Route cache bug**: `route('about')` and `route('contact')` caused blank pages because the route cache was stale. Fix: `php artisan route:clear`. Always run after adding routes.

#### Home Page — New Coming Products Section
- Added `newArrivals` query to `HomeController` — 12 most recently created active products (`orderByDesc('created_at')`).
- Added "New Coming Products" section in `home.tsx` below Featured Products with animated pulse dot + "Just Added" badge.
- "View all →" on Featured Products → `route('products.index')`.
- "View all →" on New Coming Products → `route('products.index', { sort: 'newest' })`.

#### Products Listing Page (`/products`)
- **New route**: `GET /products` → `ProductListController` → named `products.index`.
- **New controller**: `app/Http/Controllers/Storefront/ProductListController.php` (invokable, final, strict types).
  - Accepts query params: `q` (keyword), `category` (slug), `min_price`, `max_price` (USD, converted to cents), `sort` (`newest` | `featured` | `price_asc` | `price_desc`).
  - Price sorting uses `ORDER BY (SELECT MIN(price_cents) FROM product_variants WHERE product_id = products.id AND is_active = true)` sub-select — avoids JOIN duplication with eager loads.
  - Paginates at 24 per page with `withQueryString()`.
  - Passes: `products` (paginated), `categories` (all active root categories), `filters` (current values).
- **New page**: `resources/js/Pages/storefront/products/index.tsx`
  - Left sidebar (desktop sticky) / slide-in drawer (mobile) with: keyword search, category radio list, min/max price range, Apply + Reset buttons.
  - Toolbar: active filter chips (each individually removable), sort dropdown, grid/list view toggle.
  - `applyFilters()` strips null/empty values and calls `router.get()` — all filters are URL params (shareable/bookmarkable).
  - Empty state with "Clear All Filters" CTA.
  - Prev/Next pagination.
- **Nav & footer**: "Products" links updated from `route('search')` → `route('products.index')` in both `NAV_LINKS` and footer quick links.
- **Header search bar**: now routes to `route('products.index', { q: query })` instead of `/search?q=...`.

#### Bug Fixes
- `qrcode.react` package was missing from `node_modules` (was in `package.json` but never installed). Ran `npm install qrcode.react --legacy-peer-deps` to fix 2FA setup page TS error.
- Products page `applyFilters` TS error: `page: undefined` not assignable to `Record<string, string>` — fixed by building params object with only non-null/non-empty values.

#### `BelongsToShop` scope — storefront safety note
- The global scope only filters for `vendor_owner` / `vendor_staff` roles.
- Unauthenticated users and `buyer` role see **all** active products — correct for storefront queries.
- Admin role also bypasses the scope.

#### Products Listing — filter URL pattern
```
/products                          → all products, newest first
/products?category=electronics     → filtered by category slug
/products?min_price=10&max_price=50 → price range (USD)
/products?q=pepper                 → keyword search
/products?sort=price_asc           → sort options: newest | featured | price_asc | price_desc
```

---

### Session 4 — ABA PayWay Integration, Addresses, Admin Enhancements

#### ABA PayWay KHQR Popup Checkout
- **Replaced** direct-API QR image approach with **popup checkout** (JS SDK `checkout2-0.js`).
- `app/Services/Payment/AbaPayWayGateway.php` — full rewrite:
  - `createCheckout()`: builds signed form fields locally (no HTTP call), HMAC-SHA512 hash, `payment_option=abapay_khqr`, Base64-encoded `return_url`, `array_filter()` to strip nulls.
  - `checkPaymentStatus()`: POST to `/api/payment-gateway/v1/payments/check-transaction-2`.
  - `verifyWebhook()`: sort payload alphabetically, concatenate values, verify HMAC with `hash_equals()`.
  - `handleCallback()`: re-confirm via check-transaction-2, record `PaymentEvent` for idempotency, fire `PaymentReceived`.
- `app/Contracts/PaymentGateway.php` — added `checkPaymentStatus(string $transactionId)` method.
- `resources/views/app.blade.php` — added jQuery 3.7.1 + `checkout2-0.js` + event delegation script.
- `resources/js/Pages/storefront/checkout-qr.tsx` — rewritten for popup: hidden `<form id="aba_merchant_request">`, auto-click `#checkout_button` on mount, 10s polling, 15-min countdown.
- Config: renamed `services.aba_payway` → `services.aba` with `merchant_name`, `payway_url`, `rsa_public_key_path`.

#### ABA PayWay Webhook
- `app/Http/Controllers/Webhook/AbaPayWayWebhookController.php` — dedicated controller, always returns plain-text `200 "Completed"`.
- Registered in `bootstrap/app.php` via `then:` callback, `withoutMiddleware('*')`.
- Exception renderer for `webhooks/*` → always plain text 200.
- Old `POST /checkout/callback` route removed from `routes/web.php`.

#### ABA PayWay Payout to Vendors
- `app/Services/Payment/AbaPayoutService.php` — RSA-encrypted payouts:
  - `addBeneficiary()`, `updateBeneficiaryStatus()`, `payout()`, `completePreAuthWithPayout()`, `getTransactionsByRef()`.
  - Chunked 117-byte RSA encryption, HMAC-SHA512 signing.
  - Amounts in cents internally, converted to dollars at API boundary.
- `app/Models/AbaBeneficiary.php` — tracks vendor ABA whitelist status (shop_id, payee, status).
- Migration: `create_aba_beneficiaries_table` (shop_id FK, payee, payee_name, status, raw_response).
- Migration: `add_aba_columns_to_payouts_table` (aba_transaction_id, aba_external_ref).
- `app/Actions/Payout/WhitelistBeneficiary.php` — calls `addBeneficiary()`, handles PTL148 (already exists).
- `app/Actions/Payout/DeactivateBeneficiary.php` — calls `updateBeneficiaryStatus($payee, 0)`.
- `app/Actions/Payout/ApprovePayout.php` — now calls `AbaPayoutService::payout()` after wallet debit; rolls back on failure.

#### Checkout Flow Updates
- `CheckoutController::store()` — passes `orderReference` + `paymentData` (form_data) to Inertia page.
- `CheckoutController::poll()` — calls `checkPaymentStatus()` on gateway if still pending + not expired (15 min). Returns `{paid, status, expired}`.
- `CheckoutController::index()` — now passes saved `addresses` to checkout page.

#### My Addresses Feature
- `app/Models/UserAddress.php` — label, name, phone, address_line, city, province, is_default.
- Migration: `create_user_addresses_table`.
- `app/Http/Controllers/Storefront/AddressController.php` — full CRUD: index, store, update, destroy, setDefault.
- `resources/js/Pages/storefront/addresses/index.tsx` — add/edit/delete addresses, label selector (Home/Office/Other), set default.
- Routes: `GET/POST /addresses`, `PUT/DELETE /addresses/{address}`, `PATCH /addresses/{address}/default`.
- `checkout.tsx` — saved address selector cards; default auto-filled; clicking saved address fills form; "Manage Addresses" link.
- `dashboard.tsx` — added "My Addresses" quick link with MapPin icon.

#### User Dropdown Menu (`StorefrontLayout.tsx`)
- Replaced simple user link with dropdown menu (click to toggle, outside-click to close).
- Items: Dashboard, My Orders, My Addresses, My Profile, Log Out (red).
- Uses `router.post(route('logout'))` for logout.

#### Buy Now Button Fix (`products/show.tsx`)
- Changed `window.location.href` → `router.visit(route('checkout.index'))` for proper Inertia navigation after cart add.

#### Category Hierarchy in Product Form
- `app/Filament/Vendor/Resources/Products/Schemas/ProductForm.php` — category select now shows indented tree: `Electronics`, `— Smartphones`, `— — iPhone`.
- `app/Filament/Admin/Resources/Categories/Schemas/CategoryForm.php` — parent selector shows tree, excludes current category to prevent circular references.
- Both use recursive `buildCategoryTree()` method.

#### Vendor Panel — Order Detail View
- **Replaced** EditOrder page with `ViewOrder.php` — rich detail page with custom Blade view.
- `resources/views/filament/vendor/orders/view-order.blade.php` — inline styles (Filament doesn't process custom Blade Tailwind):
  - Order info card (ref, buyer, email, date, payment badge, status badge).
  - Shipping address card + shipment section (placeholder for delivery API).
  - Order items list with product images, names, SKUs, options, qty, prices.
  - Summary: subtotal, shipping, total.
- `resources/views/filament/vendor/orders/timeline.blade.php` — 8-step visual flow: Pending → Paid → Accepted → Packed → Picked Up → In Transit → Delivered → Completed. Green circles with checkmarks for completed, ring glow for current, gray for future. Red banner for cancelled/refunded.
- Header actions: Accept Order, Mark as Packed, Mark as Picked Up (contextual by status), Back to Orders.
- Table rows now clickable → navigate to view page via `recordUrl()`.
- `EditOrder.php` deleted.

#### Vendor Panel — Sidebar Redesign
- Navigation groups: **Shop** (Products, Orders, Customers), **Finance** (Payouts).
- Collapsible sidebar on desktop (`sidebarCollapsibleOnDesktop()`).
- Global search with `Ctrl+K` / `Cmd+K`.
- Shop Settings moved to standalone item (sort 99).
- Brand logo on login page (`brandLogo(asset('logo.png'))`).

#### Admin Panel — Dashboard Widgets
- `app/Filament/Admin/Widgets/StatsOverview.php` — 6 stat cards: Total Revenue (with 7-day chart), Total Orders, Active Shops, Total Users, Active Products, Pending Payouts.
- `app/Filament/Admin/Widgets/LatestOrders.php` — table widget showing 10 most recent orders.
- `app/Filament/Admin/Widgets/PendingActions.php` — clickable cards for pending orders, shops awaiting approval, payouts to review, open disputes.
- Removed default AccountWidget and FilamentInfoWidget.

#### Admin Panel — New Resources
- **Product Moderation** (`app/Filament/Admin/Resources/Products/`):
  - Lists ALL products across all shops (bypasses BelongsToShop + SoftDeletingScope).
  - Columns: image, name, shop, category, status badge, featured toggle, variant count.
  - Filters: by status, by shop. Actions: Activate, Archive.
  - Navigation badge shows draft product count.
- **Order Detail View** (`app/Filament/Admin/Resources/Orders/Pages/ViewOrder.php`):
  - Reuses vendor timeline partial. Shows all sub-orders grouped by shop.
  - Order info, buyer, payment, shipping address, items per shop, grand total.
  - Replaced empty EditOrder with rich ViewOrder. Rows clickable.
  - Added status filter to orders table.
- **Payment Resource** (`app/Filament/Admin/Resources/Payments/`):
  - Lists all payments: transaction ID, order ref, buyer, provider, status, amount, paid date.
  - Filters: by status, by provider.
- **Vendor Wallet Overview** (`app/Filament/Admin/Resources/Wallets/`):
  - All vendor wallets: shop name, owner, available balance, escrow balance, lifetime earned, transaction count.
  - Sorted by highest available balance.

#### Admin Panel — Brand Logo
- Both admin and vendor login pages show PG Market logo (`brandLogo(asset('logo.png'))`, `brandLogoHeight('3rem')`).

#### Production Config Fixes
- `QUEUE_CONNECTION`: `sync` → `database` (async, prevents request timeouts under load).
- `SESSION_DRIVER`: `file` → `database`.
- `CACHE_STORE`: `file` → `database`.
- Added `PLATFORM_CURRENCY=USD` (was missing from `.env`).
- Added `GOOGLE_REDIRECT_URI` to `.env`.
- Removed duplicate `REVERB_*` variables (were defined twice with conflicting values).
- DB config grouped together (was split across file).
- All 42 PostgreSQL tables verified present.

#### Demo Data
- `DemoOrderSeeder.php` rewritten: creates 10 orders across multiple shops, varied statuses, multi-vendor carts (1-3 shops per order), payments for paid+ orders, wallet transactions with escrow/commission math.

#### Filament v4 Compatibility Notes
- `$view` property on pages/widgets is **non-static** (use `protected string $view`, not `protected static string $view`).
- Navigation groups use `getNavigationGroup()` method, not `$navigationGroup` property.
- `MaxWidth` enum doesn't exist — removed.
- Custom Blade views in Filament panels **do not** get Tailwind CSS processing — use inline `style` attributes for custom views (timeline, order detail).
- `<x-heroicon-*>` components work in dashboard Blade but `<x-dynamic-component>` with icon names can render as oversized SVGs — use inline SVGs instead.

#### Delivery API Preparation (architecture ready, NOT built)
- `DeliveryProvider` interface exists: `createShipment`, `getRate`, `trackShipment`, `cancelShipment`, `handleWebhook`.
- `Shipment` model exists: provider, tracking_number, status, provider_response, picked_up_at, delivered_at.
- `StubDeliveryProvider` bound in AppServiceProvider.
- Vendor order detail shows "Delivery API integration pending" placeholder.
- When ready: replace `StubDeliveryProvider` with real provider, call `createShipment()` on status transition to `packed`/`picked_up`.

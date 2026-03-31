# OmniPOS v1.0

OmniPOS is a learning project to gain hands-on experience across the full web stack Laravel, Vue.js 3, REST APIs, PHPUnit, third-party integrations and Pinia combined into one real application. A fully working browser-based POS system covering barcode scanning, payments, receipts, inventory and analytics.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 13 (PHP 8.3) |
| Frontend Framework | Vue.js 3 + Vite |
| Styling | Tailwind CSS v4 |
| State Management | Pinia |
| Database | MariaDB 10.4 |
| Authentication | Laravel Sanctum |
| HTTP Client | Guzzle |
| Barcode Scanner | Html5-QRCode |
| Analytics | Chart.js |
| PDF / Invoice | domPDF |
| Excel Export | Laravel Excel (Maatwebsite) |
| Testing | PHPUnit 12 |
| API Testing | Postman |
| IDE | Cursor (VS Code-based) |

---

## Features

### 1. Vision Input Module
- Camera-based barcode scanning via Html5-QRCode
- Supports EAN-13 (standard retail) and QR Codes
- Async product lookup on scan — beep sound on success
- Register New Product pop-up when barcode is not found
- Scan-to-Fill in Add Product form — barcode auto-populates

### 2. Smart Checkout Engine
- Reactive cart — add, remove, +/− quantity without page refresh
- Hold & Resume queue — save active cart and serve next customer
- Fixed (RM) and percentage (%) discount toggle
- Automatic SST 6% calculation on every cart update

### 3. Global API & Integration Layer
- Live currency conversion via ExchangeRate-API (USD, SGD, IDR, EUR)
- Currency toggle on checkout with converted total shown live
- Payment gateway simulation — test card returns 200 OK or 402 Decline
- API latency and response logging in `api_logs` table
- 29 REST API endpoints documented in Postman

### 4. Universal Output Module
- A4 invoice mode — branded layout with invoice number, line items and totals
- Thermal mock mode — CSS `@media print` forces 80mm width, hides nav
- Print button triggers browser print dialog
- QR code digital receipt — unique URL per sale, scannable by phone
- Daily sales export to Excel (.xlsx)

### 5. Inventory & Analytics
- Low stock watchdog — red indicators for stock below threshold
- Inventory audit log — records Who, When, Why on every stock change
- Manual stock adjustment with reason and notes
- Chart.js peak sales hours — line chart by hour of day
- Chart.js top 5 selling products — bar chart by units sold

### 6. Quality Assurance
- PHPUnit automated tests — verifies stock deduction, audit log, validation
- Insufficient stock returns 422 not 500 — proper error handling
- Strict barcode input validation — symbols and text rejected with 422
- API debug log — latency, status and payload stored per request

---

## Project Structure
```
omnipos/
├── app/
│   ├── Exports/          # Excel export classes
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/      # All API controllers
│   └── Models/           # Eloquent models
├── database/
│   └── migrations/       # All 11 table migrations
├── resources/
│   └── js/
│       ├── pages/        # Vue page components
│       ├── stores/       # Pinia state stores
│       ├── router/       # Vue Router config
│       └── app.js        # Vue app entry point
├── routes/
│   ├── api.php           # All 29 API routes
│   └── web.php           # SPA catch-all route
└── tests/
    └── Feature/          # PHPUnit feature tests
```

---

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/products | List all products |
| POST | /api/products | Create product |
| GET | /api/products/{id} | Get product |
| PUT | /api/products/{id} | Update product |
| DELETE | /api/products/{id} | Delete product |
| GET | /api/products/barcode/{barcode} | Find by barcode |
| GET | /api/categories | List categories |
| POST | /api/categories | Create category |
| GET | /api/sales | List sales |
| POST | /api/sales | Process checkout |
| GET | /api/sales/receipt/{token} | Get receipt by token |
| GET | /api/sales/export/daily | Export sales to Excel |
| GET | /api/inventory | Stock overview |
| GET | /api/inventory/logs | Audit log |
| GET | /api/inventory/low-stock | Low stock items |
| POST | /api/inventory/{id}/adjust | Adjust stock |
| GET | /api/held-carts | List held carts |
| POST | /api/held-carts | Save held cart |
| GET | /api/currency/rates | Live exchange rates |
| POST | /api/payment/process | Payment simulation |

---

## Database Schema

| Table | Purpose |
|---|---|
| users | Staff accounts |
| products | Product catalogue |
| categories | Product categories |
| sales | Sale transactions |
| sale_items | Line items per sale |
| inventory_logs | Stock audit trail |
| held_carts | Saved/paused carts |
| api_logs | External API call log |
| personal_access_tokens | Sanctum auth tokens |
| cache | Laravel cache |
| jobs | Laravel queue |

---

## Test Cards (Payment Simulation)

| Card Number | Result |
|---|---|
| 4111111111111111 | ✅ Approved (200 OK) |
| 5500005555555559 | ✅ Approved (200 OK) |
| 4000000000000002 | ❌ Declined (402) |
| 4000000000009995 | ❌ Declined (402) |

---

## Setup Instructions

### Requirements
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL or MariaDB

### Installation

**1. Clone the repository**
```bash
git clone https://github.com/yourusername/omnipos.git
cd omnipos
```

**2. Install dependencies**
```bash
composer install
npm install
```

**3. Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

**4. Set your database credentials in `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=omnipos
DB_USERNAME=root
DB_PASSWORD=
```

**5. Run migrations**
```bash
php artisan migrate
```

**6. Start development servers**
```bash
php artisan serve
npm run dev
```

**7. Visit `http://localhost:8000`**

### For phone/network access (QR receipts)
```bash
php artisan serve --host=0.0.0.0 --port=8000
npm run dev -- --host
```
Then open the network URL Vite displays on your phone browser.

---

## Running Tests
```bash
php artisan test
```

Expected output:
```
PASS  Tests\Feature\SaleStockDeductionTest
✓ stock deduction works correctly
✓ inventory log is created
✓ sale fails if stock is low
✓ invalid barcode format is rejected

Tests: 4 passed
```

---

## API Documentation

Import `OmniPOS-API.postman_collection.json` into Postman to explore and test all endpoints with pre-configured request bodies and headers.

---

## What I Learned

- Designing and building a RESTful API with Laravel from scratch
- Database relationships, migrations and Eloquent ORM
- Vue.js 3 Composition API and reactive state with Pinia
- Integrating third-party APIs (ExchangeRate-API)
- Writing PHPUnit feature tests for real business logic
- CSS `@media print` for thermal and A4 print layouts
- Camera-based barcode scanning in the browser
- Building a full SaaS-style application end to end

---

## Future Roadmap

- [ ] Laravel Sanctum login / logout UI
- [ ] Multi-user roles (cashier vs manager)
- [ ] Real payment gateway (Billplz / iPay88)
- [ ] E-invoice compliance (Malaysia MyInvois / LHDN)
- [ ] Multi-outlet / multi-branch support
- [ ] Customer loyalty points system
- [ ] Mobile app using the existing API
- [ ] Cloud deployment

---

## License

MIT
---

> Built with dxisoon as a learning project, from zero to a working POS system in one session.
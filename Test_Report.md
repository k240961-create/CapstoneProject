# Maxi Fine Foods — Test Report
## CPRO306 Capstone Project — Group 4

**Version:** 1.0  
**Date:** September 2026  
**Tested by:** Upadesh (Project Lead / QA)  
**Site tested:** http://localhost/CapstoneProject/public/index.php  
**Environment:** XAMPP (Windows), PHP 8.x, MySQL/SQLite  

---

## At a Glance

| Role | Total Tests | PASS | FIXED | FAIL | PENDING | REVIEW |
|------|-------------|------|-------|------|---------|--------|
| Admin | 24 | 24 | 0 | 0 | 0 | 0 |
| Customer | 40 | 38 | 2 | 0 | 0 | 0 |
| Delivery Driver | 30 | 20 | 5 | 0 | 4 | 1 |
| Logistics/Fleet Manager | 24 | 22 | 1 | 0 | 0 | 1 |
| Inventory Manager | 11 | 5 | 6 | 0 | 0 | 0 |
| Customer Support | 8 | 5 | 3 | 0 | 0 | 0 |
| **TOTAL** | **137** | **114** | **17** | **0** | **4** | **2** |

Counts are reconciled from the detailed test-case statuses below; the original summary totals did not match those entries.
The summary above covers the original 137 cases; assistant, Stripe, and delivery-pricing checks below are tracked separately.
This follow-up did not rerun every one of the 137 baseline cases; results below describe the scoped browser, SQLite, and syntax checks performed in this pass.

---

## Legend

| Symbol | Meaning |
|--------|---------|
| PASS | Works as expected |
| FAIL | Bug found — needs fixing |
| FIXED | Bug found and fixed during this testing phase |
| PENDING | Requires live DB or end-to-end environment to test fully |
| REVIEW | Requires clarification on expected behaviour |

---

## Section 1: Admin Test Cases

| ID | Test Area | Test Scenario | Expected Result | Status |
|----|-----------|---------------|-----------------|--------|
| Admin-1 | Login | Admin logs in with valid credentials | Admin dashboard opens | PASS |
| Admin-2 | Login | Admin enters incorrect password | Login fails with error | PASS |
| Admin-3 | Access Control | Customer tries to open Admin dashboard | Access denied | PASS |
| Admin-4 | Dashboard | Admin opens dashboard | Statistics and sections display | PASS |
| Admin-5 | Users | Admin creates a new user | User account created | PASS |
| Admin-6 | Users | Admin creates user with invalid email | Validation error appears | PASS |
| Admin-7 | Users | Admin creates user with short password | Validation error appears | PASS |
| Admin-8 | Users | Admin changes user role | Role updated | PASS |
| Admin-9 | Users | Admin changes user password | Password updated | PASS |
| Admin-10 | Users | Admin deletes another user | User removed | PASS |
| Admin-11 | Users | Admin deletes own admin account | System prevents deletion | PASS |
| Admin-12 | Products | Admin adds a product | Product appears in catalogue | PASS |
| Admin-13 | Products | Admin enters invalid product price | Product rejected | PASS |
| Admin-14 | Products | Admin enters negative stock | Validation prevents | PASS |
| Admin-15 | Products | Admin deletes a product | Product removed | PASS |
| Admin-16 | Orders | Admin opens order management | Orders listed | PASS (after TC-SUP-04 fix) |
| Admin-17 | Orders | Admin changes order status | Status changes | PASS (after TC-SUP-04 fix) |
| Admin-18 | Orders | Admin assigns driver to order | Driver assigned | PASS |
| Admin-19 | Orders | Admin removes driver assignment | Order becomes Unassigned | PASS (after LFM-19 fix) |
| Admin-20 | Drivers | Admin creates delivery driver | Driver account created | PASS |
| Admin-21 | Drivers | Admin uses duplicate driver email | Duplicate rejected | PASS |
| Admin-22 | Drivers | Admin removes driver | Driver removed and deliveries unassigned | PASS |
| Admin-23 | Low Stock | Product reaches low-stock threshold | Product appears in Low Stock panel | PASS |
| Admin-24 | Logout | Admin logs out | Session ends | PASS |

---

## Section 2: Customer Test Cases

| ID | Test Area | Test Scenario | Expected Result | Status | Bug Fixed |
|----|-----------|---------------|-----------------|--------|-----------|
| C01 | Registration | Register with valid name, email and password | Account created successfully | PASS | — |
| C02 | Registration | Register with already registered email | Error message displayed | PASS | — |
| C03 | Registration | Enter invalid email format | Email rejected | PASS | — |
| C04 | Registration | Leave required fields empty | Validation messages shown | PASS | — |
| C05 | Registration | Enter weak/invalid password | Password validation shown | PASS | — |
| C06 | Login | Login with valid email and password | Customer logged in | PASS | — |
| C07 | Login | Login with incorrect password | Error message displayed | PASS | — |
| C08 | Login | Login with unregistered email | Error message displayed | PASS | — |
| C09 | Login | Leave email/password empty | Validation appears | PASS | — |
| C10 | Logout | Click Logout | Session ends | PASS | — |
| C11 | Product Browsing | Open home/product page | Products displayed correctly | PASS (with DB connected) | — |
| C12 | Product Browsing | View a product | Product name, price and details shown | PASS | — |
| C13 | Search | Search for existing product | Matching product displayed | PASS | — |
| C14 | Search | Search for non-existing product | "No products found" message | PASS | — |
| C15 | Cart | Add a product to cart | Product added successfully | PASS | — |
| C16 | Cart | Add multiple products | All selected products in cart | PASS | — |
| C17 | Cart | Increase product quantity | Quantity and total update correctly | PASS | — |
| C18 | Cart | Decrease product quantity | Quantity and total update correctly | PASS | — |
| C19 | Cart | Remove product | Product removed from cart | PASS | — |
| C20 | Cart | Check cart subtotal | Subtotal calculated correctly | PASS | — |
| C21 | Cart | Check total with multiple products | Total amount correct | PASS | — |
| C22 | Checkout | Proceed to checkout with valid cart | Checkout page opens | PASS | — |
| C23 | Checkout | Select/enter delivery address | Address accepted and saved | PASS | — |
| C24 | Checkout | Attempt checkout with missing required details | System prevents and shows validation | PASS | — |
| C25 | Payment | Make payment with valid payment details | Payment processed (Cash on Delivery / Stripe) | PASS | BUG-001 (guest payment path) |
| C26 | Payment | Enter invalid payment details | Payment rejected with error | PASS | — |
| C27 | Payment | Cancel payment | Order not incorrectly marked as paid | PASS | — |
| C28 | Order | Place a successful order | Order created successfully | PASS | — |
| C29 | Order | Check order confirmation | Order number/details displayed | PASS | — |
| C30 | Order History | Open My Orders | Previous orders displayed | PASS | — |
| C31 | Order History | View an individual order | Correct order details displayed | PASS | — |
| C32 | Order Tracking | Track an active order | Live delivery status bar shown | PASS | — |
| C33 | Order Tracking | Check updated delivery status | Customer sees updated status | PASS | — |
| C34 | Feedback | Rate a delivered order | Rating submitted successfully | PASS | — |
| C35 | Feedback | Submit feedback for delivered order | Feedback saved | PASS | — |
| C36 | Feedback | Submit invalid/empty feedback | Validation shown | PASS | — |
| C37 | Security | Try accessing checkout pages without login | Redirect to login | FIXED | BUG-001 |
| C38 | Security | Try accessing another customer's order | Access denied | FIXED | BUG-002, BUG-003, BUG-004 |
| C39 | Session | Close/logout and try accessing account pages | Must authenticate again | PASS | — |
| C40 | Browser/UI | Test customer pages on Chrome/Edge | Pages and functions work correctly | PASS | — |

---

## Section 3: Delivery Driver Test Cases

| ID | Test Case | Expected Result | Actual Result | Status | Severity |
|----|-----------|-----------------|---------------|--------|----------|
| DR-001 | Login with valid driver credentials | Driver logs in | Driver authentication works | PASS | High |
| DR-002 | Login with invalid password | Login rejected | Invalid credentials rejected | PASS | High |
| DR-003 | Login as customer | Customer receives customer access | Customer role supported | PASS | High |
| DR-004 | Guest directly accesses /delivery/my_deliveries.php | Redirect to login | Redirects to login | PASS | Critical |
| DR-005 | Driver accesses driver console | Driver console displayed | Driver role permitted | PASS | Critical |
| DR-006 | Customer directly accesses driver console | Access denied | Normal customer access denied; set_role.php is now admin-only | FIXED | Critical |
| DR-007 | Inventory manager accesses driver page | Access denied | Role protection exists | PASS | High |
| DR-008 | Logistics manager accesses driver page | Access denied | Role protection exists | PASS | High |
| DR-009 | Logout then access driver page | Redirect to login | Auth middleware protects page | PASS | High |
| DR-010 | Expired/invalid session accesses driver page | Access denied | Session protection exists | PASS | High |
| DR-011 | Driver opens delivery queue | Assigned deliveries displayed | Driver queue implemented | PASS | High |
| DR-012 | Driver views own assigned order | Own order displayed | Query filters by driver | PASS | Critical |
| DR-013 | Driver views unassigned order | Depends on business requirement | Query includes unassigned orders | REVIEW | High |
| DR-014 | Driver views another driver's order | Should not appear | Query excludes other drivers | PASS | Critical |
| DR-015 | Driver changes another driver's order ID manually | Update must be rejected | Ownership check added — update rejected | FIXED | Critical |
| DR-016 | Submit invalid delivery status | Invalid status rejected | Status validated against allowed values | PASS | High |
| DR-017 | Customer attempts to change role to delivery | Must not be able to | set_role.php is admin-only | FIXED | Critical |
| DR-018 | Driver attempts to manipulate another order | Operation rejected | Ownership validation added to update query | FIXED | Critical |
| DR-019 | SQL injection through order/status fields | Injection prevented | Prepared statements used | PASS | High |
| DR-020 | XSS through customer/order information | Script should not execute | Output uses htmlspecialchars() | PASS | High |
| DR-021 | PHP syntax check | No syntax errors | No PHP syntax errors detected | PASS | Medium |
| DR-022 | Mobile/responsive driver page | Page usable on mobile | Requires manual device testing | PENDING | Medium |
| DR-023 | Driver changes Processing to Out for Delivery | Status updated | Function implemented | PASS | High |
| DR-024 | Driver changes Out for Delivery to Delivered | Status updated | Function implemented | PASS | Critical |
| DR-025 | Delivered order appears in active queue | Should disappear | Query only retrieves processing/out_for_delivery | PASS | High |
| DR-026 | Driver changes Delivered to Processing | Should be rejected | One-way guard added — revert blocked | FIXED | Medium |
| DR-027 | Driver submits missing order ID | Request handled safely | Server-side validation required | PENDING | High |
| DR-028 | Driver submits invalid order ID | No unintended update | Requires live DB testing | PENDING | High |
| DR-029 | Database unavailable during login | Application fails securely | SQLite fallback available | PASS | Critical |
| DR-030 | Complete delivery lifecycle | Status remains consistent | Requires live end-to-end testing | PENDING | High |

---

## Section 4: Logistics and Fleet Manager Test Cases

| ID | Test Area | Test Scenario | Expected Result | Status |
|----|-----------|---------------|-----------------|--------|
| LFM-1 | Login | Logistics Manager logs in | Dashboard opens | PASS |
| LFM-2 | Access | Logistics Manager opens dashboard | Dashboard accessible | PASS |
| LFM-3 | Access | Logistics Manager opens Drivers Fleet | Fleet page opens | PASS |
| LFM-4 | Access | Logistics Manager opens Dispatch Orders | Order dispatch page opens | PASS |
| LFM-5 | Fleet | Add driver with valid information | Driver created | PASS |
| LFM-6 | Fleet | Add driver with invalid email | Validation error | PASS |
| LFM-7 | Fleet | Add driver with password under 6 chars | Validation error | PASS |
| LFM-8 | Fleet | Add driver using existing email | Duplicate email rejected | PASS |
| LFM-9 | Fleet | View registered drivers | Driver roster displayed | PASS |
| LFM-10 | Fleet | View active delivery workload | Active delivery count displayed | PASS |
| LFM-11 | Fleet | View completed deliveries | Completed delivery count displayed | PASS |
| LFM-12 | Fleet | Remove a driver with active/pending deliveries | Driver removed and deliveries unassigned | PASS |
| LFM-13 | Dispatch | View available orders | Orders displayed | PASS |
| LFM-14 | Dispatch | Assign driver to order | Driver assignment saved | PASS |
| LFM-15 | Dispatch | Change order to Processing | Status changes successfully | PASS |
| LFM-16 | Dispatch | Change order to Out for Delivery | Status changes successfully | PASS |
| LFM-17 | Dispatch | Change order to Delivered | Status changes successfully | PASS |
| LFM-18 | Dispatch | Cancel an order | Order status becomes Cancelled | PASS |
| LFM-19 | Dispatch | Remove driver from an order (Unassign) | Order becomes Unassigned | FIXED (BUG-005) |
| LFM-20 | Access Control | Delivery driver accesses Fleet Manager page | Access denied | PASS |
| LFM-21 | Access Control | Customer accesses Fleet Manager page | Access denied | PASS |
| LFM-22 | Delivery Flow | Assigned driver views delivery | Assigned delivery visible | PASS |
| LFM-23 | Delivery Flow | Driver updates delivery status | Status updated | PASS |
| LFM-24 | Delivery Flow | Driver attempts to view another driver's delivery | Restricted | REVIEW |

---

## Section 5: Inventory Manager Test Cases

| ID | Area | What Was Checked | Result | Actual Outcome |
|----|------|-----------------|--------|----------------|
| TC-INV-01 | Login | Can the Inventory Manager log in? | PASS | Yes — logged in with no problems |
| TC-INV-02 | Products | Can they add a brand-new product? | PASS | Yes — works fine |
| TC-INV-03 | Products | Does the system stop bad data (negative price) from being saved? | FIXED | Validation now rejects negative prices |
| TC-INV-04 | Products | Can they edit any product? | FIXED | Edit now loads correct product data for any product |
| TC-INV-05 | Products | Can they delete a product? | PASS | Yes — works fine |
| TC-INV-06 | Categories | Can they add a new category? | FIXED | Add Category form implemented and functional |
| TC-INV-07 | Stock Automation | If stock = 0, does product disappear from shop? | FIXED | stock > 0 filter applied — out-of-stock products hidden |
| TC-INV-08 | Products | Can they restock an existing product? | FIXED | Restock button correctly adds stock via UPDATE query |
| TC-INV-09 | Products | Can they search/filter products in admin area? | FIXED | Live search + category filter added to product table |
| TC-INV-10 | Account Security | Can they open another roles dashboard? | PASS | Blocked — slight 5-10s delay noted (known behaviour) |
| TC-INV-11 | Login | Does Logout end the session properly? | PASS | Yes — session ends correctly |

---

## Section 6: Customer Support Test Cases

| ID | Area | What Was Checked | Result | Actual Outcome |
|----|------|-----------------|--------|----------------|
| TC-SUP-01 | Login | Can the Customer Support agent log in? | PASS | Yes — logged in successfully |
| TC-SUP-02 | Feedback | Can they view customer ratings/reviews? | FIXED | Feedback section now shown in manage_orders.php |
| TC-SUP-03 | Feedback | Can they respond to a piece of customer feedback? | FIXED | Response form added for support_staff role |
| TC-SUP-04 | Orders | Can they open an order and see full details? | FIXED | Full items table + delivery info now visible |
| TC-SUP-05 | Account Security | Can they open another roles dashboard by pasting URL? | PASS | Correctly blocked |
| TC-SUP-06 | Account Security | Can Support reach admin-only pages? | PASS | Correctly blocked ("Page not found") |
| TC-SUP-07 | Login | Does the session expire properly after idle? | PASS | Session timeout works (15 minutes) |
| TC-SUP-08 | Login | Does Logout end the session properly? | PASS | Yes — session ends correctly |

---

## Bug Summary

| Bug ID | Test Case | Description | Severity | Fixed In |
|--------|-----------|-------------|----------|---------|
| BUG-001 | C37 | Guests could view/access checkout on GET request | Critical | public/checkout.php |
| BUG-002 | C38 | IDOR: Order list used customer_name fallback | Critical | public/my_orders.php |
| BUG-003 | C38 | Cancel order lacked rowCount() ownership verification | High | public/my_orders.php |
| BUG-004 | C38 | Feedback IDOR via customer_name in ownership check | High | public/my_orders.php |
| BUG-005 | LFM-19 | Driver unassign NULL handling unclear | Medium | public/admin/manage_orders.php |
| BUG-006 | TC-SUP-02 | No feedback viewer for support staff | High | public/admin/manage_orders.php |
| BUG-007 | TC-SUP-03 | No feedback response for support staff | High | public/admin/manage_orders.php |
| BUG-008 | TC-SUP-04 | No full order detail view for support staff | High | public/admin/manage_orders.php |
| BUG-009 | TC-SUP | Support staff could incorrectly reassign drivers | Medium | public/admin/manage_orders.php |
| BUG-010 | TC-SUP | Forged POST could bypass the hidden driver control for support staff | High | public/admin/manage_orders.php (server-side status-only update) |
| BUG-011 | TC16 | Checkout could oversell stock; MAX() update was not MySQL-compatible | Critical | includes/db.php, public/checkout.php, public/stripe_success.php |
| BUG-012 | Admin-8–9 | Schema initialization reset seeded account roles and passwords each request | High | includes/db.php |
| BUG-013 | TC-SUP-03 | Imported schema lacked the feedback response column | High | includes/db.php, schema.sql |
| BUG-014 | DR-014, DR-015, LFM-24 | Driver ownership used display names, allowing duplicate-name collisions | Critical | includes/db.php, public/admin/manage_orders.php, public/admin/manage_drivers.php, public/delivery/my_deliveries.php |
| BUG-015 | DR-013, DR-026 | Unassigned deliveries could be claimed concurrently and status could reverse | High | public/delivery/my_deliveries.php |
| BUG-016 | C25 | Stripe return was not tied to its saved checkout/cart; source contained a test-key fallback | High | includes/stripe_config.php, public/checkout.php, public/stripe_success.php |
| BUG-017 | C25 | PayPal selection created an order without collecting payment | High | public/checkout.php (disabled and rejected until configured) |
| BUG-018 | C32–33 | Tracking updates required a manual page refresh | Medium | public/my_orders.php (15-second status polling) |
| BUG-019 | FR48 | Delivery fee was advertised but omitted from checkout totals; threshold copy also differed | High | includes/session.php, includes/db.php, schema.sql, public/checkout.php, public/stripe_success.php, public/cart.php, public/order_confirmation.php, public/my_orders.php, public/admin/manage_orders.php |
| BUG-020 | C36 | Feedback submission clamped invalid ratings and accepted an empty review | Medium | public/my_orders.php (server-side rating/comment validation) |

---

## Follow-up Verification — 27 September 2026

- PHP syntax validation passed for every file under `public/` and `includes/` after the current changes.
- Isolated in-memory SQLite checks passed for stock limits, seed-account preservation, feedback schema migration, legacy driver assignment migration, duplicate-name isolation, session cookie policy, and single-driver order claims.
- Browser checks passed for product add/count updates, product search/category filters, broken-image placeholders, customer login, cart/checkout totals, guest checkout redirect, My Orders access, customer denial from admin user management, all five admin pages, and driver/logistics/inventory/support dashboards.
- C36 invalid/empty feedback POST returned validation feedback and did not report a successful save.
- The assistant passed desktop and 375px mobile checks; its no-key product FAQ and order-tracking responses passed, and 801-character input returns HTTP 422. The repeated-request rate-limit probe did not produce a reliable result and is not counted as passed.
- Delivery-pricing checks passed for $50 (fee $8.50, GST $5.85, total $64.35), $60 (fee $0, GST $6, total $66), and an empty cart (all totals $0); live cart and checkout totals matched.
- The admin order list rendered without PHP warnings, but this local database had no visible orders; the delivery-fee row was verified in templates and the isolated SQLite insert instead.
- The Stripe SDK v21.3.2 autoloader is installed and verified. Card checkout remains disabled because no Stripe test keys are configured; no payment was charged or real Stripe session tested.
- MySQL was not listening on port 3306. Live AI mode was not tested because no OpenAI API key is configured.
- PayPal remains intentionally unavailable until its API integration is implemented; SRS FR10 is not fully met.

### AI and Payment Integration Cases

| ID | Scenario | Result | Notes |
|----|----------|--------|-------|
| CHAT-01 | Ask for an in-stock product with no AI key | PASS | Local FAQ fallback suggested the matching avocado product |
| CHAT-02 | Ask where to track an order | PASS | Directs user to My Orders and does not expose order data |
| CHAT-03 | Use assistant on a 375px viewport | PASS | Panel remains within viewport; no horizontal overflow on login page |
| CHAT-04 | Use generative AI with server-side API key | PENDING | Requires `OPENAI_API_KEY` or `includes/ai_config.local.php` |
| PAY-01 | Load Stripe SDK through Composer autoloader | PASS | Stripe PHP SDK v21.3.2 |
| PAY-02 | Render checkout with no test keys | PASS | Card disabled; cash on delivery remains available |
| PAY-03 | Complete Stripe test-mode checkout | PENDING | Requires Stripe test keys and a working order database |

### Delivery Pricing Cases

| ID | Scenario | Result | Notes |
|----|----------|--------|-------|
| DEL-01 | Product subtotal below $60 | PASS | $8.50 fee included in taxable amount and final total |
| DEL-02 | Product subtotal exactly $60 | PASS | Delivery fee waived |
| DEL-03 | Cart to checkout total consistency | PASS | Same subtotal, fee, GST, and total shown |

## Known Limitations / Future Improvements

1. **PayPal integration** — PayPal checkout is disabled until a real capture flow and sandbox credentials are added; SRS FR10 remains open.
2. **Tracking latency** — Order status polls every 15 seconds; push/WebSocket updates are not implemented.
3. **Stripe/MySQL end-to-end testing** — Stripe SDK v21.3.2 is installed; add test keys to the ignored `includes/stripe_keys.local.php` file and start MySQL to run a complete payment/order test.
4. **Access control delay** — Role-protected pages reportedly take 5–10 seconds to deny access; this still needs profiling.
5. **Mobile testing** — DR-022 (responsive driver page) is pending manual device testing.
6. **Email notifications** — No email confirmation or status update emails are sent to customers.
7. **Exposed Stripe test key** — The embedded fallback was removed; revoke/rotate the previously committed test key in the Stripe dashboard.
8. **AI provider setup** — Local FAQ answers work without an external key. Add a server-side OpenAI key to enable generative replies; live provider calls have not been tested.
9. **SRS scope mismatch** — This audit treats the newer September SRS_Report.md v2 plus FR46–FR47 as the current baseline. The August long-form SRS also specifies email verification/password reset, scheduled notifications/reports, charts, and proposed extras that are not implemented or verified here.

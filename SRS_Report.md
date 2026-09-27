# Maxi Fine Foods — Software Requirements Specification (SRS)
## CPRO306 Capstone Project — Group 4

**Version:** 2.0  
**Date:** September 2026  
**Prepared by:** Upadesh (Project Lead / Testing & QA) & Sachin Goray (Developer)  
**Institution:** Kent Institute Australia  
**Project:** Maxi Fine Foods — Online Grocery Ordering, Order Tracking & Delivery Management System  

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Overall Description](#2-overall-description)
3. [Specific Functional Requirements](#3-specific-functional-requirements)
4. [Non-Functional Requirements](#4-non-functional-requirements)
5. [System Architecture](#5-system-architecture)
6. [User Roles and Access Control](#6-user-roles-and-access-control)
7. [Database Schema Summary](#7-database-schema-summary)
8. [Bug Fix Log (Testing Phase)](#8-bug-fix-log-testing-phase)

---

## 1. Introduction

### 1.1 Purpose
This Software Requirements Specification (SRS) defines the functional and non-functional requirements for the **Maxi Fine Foods Online Grocery Management System**. It serves as the primary reference for development, testing, and final submission of the CPRO306 Capstone Project.

### 1.2 Scope
The Maxi Fine Foods system is a web-based PHP/MySQL application that supports:
- Online grocery browsing and ordering for registered customers
- Order dispatch, delivery management, and driver fleet control
- Inventory and stock management for warehouse staff
- Customer support, feedback, and order query resolution
- Administrative control of all system users and data

### 1.3 Intended Audience
- Development team (Sachin Goray)
- QA/Testing team (Upadesh)
- Academic assessors (Kent Institute Australia)

### 1.4 Definitions and Acronyms

| Term | Definition |
|------|-----------|
| SRS  | Software Requirements Specification |
| FR   | Functional Requirement |
| NFR  | Non-Functional Requirement |
| IDOR | Insecure Direct Object Reference (a security vulnerability) |
| GST  | Goods and Services Tax (10% applied to all orders in Australia) |
| LFM  | Logistics and Fleet Manager |
| PDO  | PHP Data Objects (database abstraction layer) |

---

## 2. Overall Description

### 2.1 Product Perspective
Maxi Fine Foods is a standalone web application hosted via XAMPP (Apache + MySQL) locally, with a PHP backend. The system operates as a multi-role platform where customers, staff, and administrators interact with the same underlying MySQL (or SQLite fallback) database.

### 2.2 Product Functions (Summary)
- Customer registration, login, session management
- Grocery browsing, search, cart, and checkout
- Stripe credit card payment integration
- Order tracking and delivery status updates
- Driver assignment and fleet management
- Inventory management (add/edit/restock/delete products)
- Customer feedback and star ratings
- Admin user management, access control
- AI shopping assistant for public product, delivery, payment, refund, and order-tracking guidance

### 2.3 Operating Environment
- **Server:** XAMPP (Apache 2.x, PHP 8.x, MySQL 8.x)
- **Fallback:** SQLite (persistent file-based storage when MySQL is unavailable)
- **Client:** Modern browsers (Chrome, Edge, Firefox, Safari)
- **OS:** Windows (XAMPP), deployable to Linux

---

## 3. Specific Functional Requirements

### 3.1 Customer Module

| ID    | Requirement | Priority |
|-------|-------------|----------|
| FR01  | System shall allow new customers to register with name, email, and password | High |
| FR02  | System shall validate email format and reject duplicate accounts | High |
| FR03  | System shall enforce password minimum length (6 characters) | High |
| FR04  | System shall authenticate customers via email + password | High |
| FR05  | System shall restrict checkout to registered/logged-in users only (no guest orders) | Critical |
| FR06  | System shall display all in-stock products on the shop homepage | High |
| FR07  | System shall hide out-of-stock products from customers automatically | High |
| FR08  | System shall support product category filtering and keyword search | Medium |
| FR09  | System shall maintain a session-based shopping cart | High |
| FR10  | System shall support Credit Card (Stripe), Cash on Delivery, and PayPal payment methods | High |
| FR11  | System shall create an order record upon successful checkout | Critical |
| FR12  | System shall display order confirmation with order number and details | High |
| FR13  | System shall show all of a customers past orders in My Orders | High |
| FR14  | System shall display full order details: items, quantities, prices, delivery info | High |
| FR15  | System shall provide live delivery status tracking (4-stage progress bar) | High |
| FR16  | System shall allow customers to cancel pending orders | Medium |
| FR17  | System shall allow customers to submit star ratings and written reviews for delivered orders | Medium |
| FR18  | System shall restrict order access strictly to the owning customer (IDOR protection) | Critical |
| FR19  | System shall enforce session timeout after 15 minutes of inactivity | High |

### 3.2 Delivery Driver Module

| ID    | Requirement | Priority |
|-------|-------------|----------|
| FR20  | System shall restrict driver console to delivery role only | Critical |
| FR21  | System shall display only the assigned drivers orders (or unassigned orders) | High |
| FR22  | System shall allow drivers to update order status (Processing to Out for Delivery to Delivered) | High |
| FR23  | System shall prevent drivers from updating other drivers orders (IDOR protection) | Critical |
| FR24  | System shall prevent reversal of delivered status (one-way workflow) | High |

### 3.3 Logistics and Fleet Manager Module

| ID    | Requirement | Priority |
|-------|-------------|----------|
| FR25  | System shall allow logistics managers to view all orders | High |
| FR26  | System shall allow logistics managers to assign drivers to orders | High |
| FR27  | System shall allow logistics managers to unassign drivers from orders | High |
| FR28  | System shall allow logistics managers to change order status | High |
| FR29  | System shall allow logistics managers to add and remove delivery drivers | High |

### 3.4 Inventory Manager Module

| ID    | Requirement | Priority |
|-------|-------------|----------|
| FR30  | System shall allow inventory managers to add new products | High |
| FR31  | System shall validate product price (must be greater than $0) and stock (must be 0 or more) | High |
| FR32  | System shall allow editing of any products details | High |
| FR33  | System shall support quick +25 restock action from the product table | Medium |
| FR34  | System shall allow adding new product categories | Medium |
| FR35  | System shall allow product deletion | High |
| FR36  | System shall include product search and category filter in the admin inventory view | Medium |
| FR37  | System shall hide products with stock = 0 from the customer-facing shop | High |

### 3.5 Customer Support Module

| ID    | Requirement | Priority |
|-------|-------------|----------|
| FR38  | System shall allow support staff to view full order details (items, address, payment) | High |
| FR39  | System shall allow support staff to view all customer feedback and ratings | High |
| FR40  | System shall allow support staff to respond to customer feedback | High |
| FR41  | System shall restrict support staff from reassigning delivery drivers | Medium |

### 3.6 Admin Module

| ID    | Requirement | Priority |
|-------|-------------|----------|
| FR42  | System shall allow admins to create and manage all user accounts | High |
| FR43  | System shall prevent admins from deleting their own admin account | High |
| FR44  | System shall allow admins to manage all orders, products, drivers, and feedback | High |
| FR45  | System shall restrict admin role switching to administrators only | Critical |

### 3.7 AI Store Assistant

| ID    | Requirement | Priority |
|-------|-------------|----------|
| FR46  | System shall provide guests and customers an assistant for public product, payment, delivery, refund, and order-tracking guidance | Medium |
| FR47  | When configured, the assistant shall use a server-side AI API; otherwise it shall provide local FAQ answers and shall not access, disclose, or modify private account, order, or payment records | High |

### 3.8 Delivery Pricing

| ID    | Requirement | Priority |
|-------|-------------|----------|
| FR48  | System shall charge an $8.50 delivery fee when the product subtotal is below $60, waive it at $60 or more, and show the fee before order placement | High |

---

## 4. Non-Functional Requirements

| ID     | Requirement | Category |
|--------|-------------|----------|
| NFR01  | Pages shall load within 3 seconds under normal network conditions | Performance |
| NFR02  | System shall use HTTPS-ready session cookies | Security |
| NFR03  | All user inputs shall be validated server-side (not only client-side) | Security |
| NFR04  | All database queries shall use prepared statements (PDO) to prevent SQL injection | Security |
| NFR05  | All HTML output shall be escaped with htmlspecialchars() to prevent XSS | Security |
| NFR06  | Passwords shall be hashed with PHP password_hash() (bcrypt) | Security |
| NFR07  | System shall fall back to SQLite if MySQL is unavailable | Reliability |
| NFR08  | System shall display meaningful error messages without exposing system internals | Usability |
| NFR09  | System shall be responsive and usable on mobile devices | Usability |
| NFR10  | System shall comply with Australian GST (10% applied to all orders) | Legal |
| NFR11  | Sessions shall expire after 15 minutes of inactivity (idle timeout) | Security |
| NFR12  | AI provider credentials shall remain server-side; chat requests shall be size/rate limited and must not contain payment credentials | Security |

---

## 5. System Architecture

**Technology Stack:**
- Frontend: HTML5, CSS3 (custom vanilla CSS), JavaScript (vanilla)
- Backend: PHP 8.x
- Database: MySQL 8.x (primary), SQLite (fallback)
- Web Server: Apache via XAMPP
- Payment: Stripe (via Stripe PHP SDK)
- AI assistant: OpenAI Chat Completions API when configured, with local FAQ fallback
- Icons: Lucide Icons CDN
- Fonts: Google Fonts (DM Sans, Playfair Display)

**Key Files:**

| File | Purpose |
|------|---------|
| includes/db.php | Database connection, schema init, all data functions |
| includes/session.php | Session, roles, cart, flash messages |
| includes/header.php | Shared navigation header |
| includes/footer.php | Shared footer with scripts |
| public/ | All customer-facing PHP pages |
| public/admin/ | Staff and admin management pages |
| public/delivery/ | Delivery driver console |
| schema.sql | MySQL database schema and seed data |

---

## 6. User Roles and Access Control

| Role | Access |
|------|--------|
| guest | Browse products, view cart (cannot checkout) |
| customer | All guest access + checkout, my orders, feedback |
| delivery | Delivery driver console (own orders only) |
| inventory_manager | Product catalog management |
| logistics_manager | Driver fleet + order dispatch management |
| support_staff | Order viewing (full details) + feedback response |
| admin | Full system access including user management |

**Security note:** Role switching (set_role.php) is restricted to admins only. No user can self-elevate their role.

---

## 7. Database Schema Summary

| Table | Purpose |
|-------|---------|
| users | All user accounts (customers, staff, admin, drivers) |
| categories | Product categories |
| products | Grocery product catalog with stock levels |
| orders | Customer orders with delivery and payment info |
| order_items | Individual line items per order |
| feedback | Customer star ratings and reviews |

---

## 8. Bug Fix Log (Testing Phase)

The following bugs were identified by Upadesh during the testing phase (September 2026) and fixed in the codebase.

### Critical and High Severity Bugs Fixed

| Bug ID | Test Case | Description | Severity | Fix Applied | File |
|--------|-----------|-------------|----------|-------------|------|
| BUG-001 | C37 | Guests could view the checkout form (GET request was not blocked — only POST was) | Critical | Added guest redirect for all access (GET + POST) | public/checkout.php |
| BUG-002 | C38 | Customer orders queried using customer_name OR user_id, allowing IDOR if names are shared | Critical | Removed customer_name fallback; strict user_id ownership only | public/my_orders.php |
| BUG-003 | C38 | Cancel order action did not verify ownership with rowCount() check | High | Added rowCount() verification after UPDATE | public/my_orders.php |
| BUG-004 | C38 | Feedback submission used customer_name in ownership check (IDOR vulnerability) | High | Replaced with strict user_id check | public/my_orders.php |
| BUG-005 | LFM-19 | Driver unassign may produce incorrect NULL handling | Medium | Explicit null coercion: empty string sets driver to NULL | public/admin/manage_orders.php |
| BUG-006 | TC-SUP-02 | Support staff had no way to view customer feedback or ratings | High | Added feedback viewing section to manage_orders.php | public/admin/manage_orders.php |
| BUG-007 | TC-SUP-03 | Support staff could not respond to customer feedback | High | Added feedback response form for support_staff and admin | public/admin/manage_orders.php |
| BUG-008 | TC-SUP-04 | Support staff could only see order status, not full item/delivery details | High | Added complete order detail view (items table, delivery info) | public/admin/manage_orders.php |
| BUG-009 | TC-SUP | Support staff could incorrectly reassign delivery drivers | Medium | Driver reassignment hidden for support_staff role (read-only) | public/admin/manage_orders.php |

### Pre-Existing Fixes (In Codebase Before Testing Phase Began)

| Test Case | Description | Status |
|-----------|-------------|--------|
| DR-006 / DR-017 | set_role.php restricted to admin only (no self-role-elevation) | Fixed |
| DR-015 / DR-018 | Driver ownership check prevents cross-driver IDOR in my_deliveries.php | Fixed |
| TC-INV-03 | Negative price and stock validation in manage_products.php | Fixed |
| TC-INV-04 | Edit product now correctly loads the selected product data | Fixed |
| TC-INV-06 | Add category functionality implemented and working | Fixed |
| TC-INV-07 | Out-of-stock products hidden from customer shop (stock > 0 filter) | Fixed |
| TC-INV-08 | Restock button correctly adds stock via UPDATE query | Fixed |
| TC-INV-09 | Admin product search and category filter implemented | Fixed |
| C31 | Individual order details fully visible in My Orders page | Fixed |
| C32 | Live 4-stage delivery status tracking bar implemented | Fixed |
| C34 to C36 | Customer feedback/rating form implemented for delivered orders | Fixed |

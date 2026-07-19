# Nesto Loyalty System — PHP Backend Engineer Assignment

An API-only backend implementation for a supermarket loyalty system designed for **Nesto Supermarkets, Sri Lanka**. 

This system allows customers to register via cashiers, activate their profiles online, log in securely, earn loyalty points on eligible branch purchases, and track their point history via a customer dashboard.

---

## 🛠️ Technology Stack

* **Framework:** Laravel 11 (API-only architecture)
* **Language:** PHP 8.4
* **Database:** MySQL 8.0 (ACID compliant primary storage)
* **Caching & Queues:** Redis 7.0 (Queue driver for async point calculation)
* **API Authentication:** Laravel Sanctum (Stateful tokens with role-based abilities)
* **DevOps/Containerization:** Docker Compose
* **Database Visualizer:** phpMyAdmin 5

---

## 🏗️ Architectural Design & SOLID Mapping

This project was built to showcase clean, production-ready, object-oriented code without AI-style fluff or over-engineering:

* **S — Single Responsibility (SRP):** Controllers deal purely with HTTP requests/responses, Form Requests handle validation, Services coordinate the domain business logic, and Repositories handle database interactions.
* **O — Open/Closed (OCP):** New loyalty point calculators (e.g., promotional or tiered multipliers) can be introduced by creating a new strategy class implementing `PointsCalculatorInterface` and binding it in `AppServiceProvider`, leaving the core `OrderService` untouched.
* **L — Liskov Substitution (LSP):** Any calculation strategy implements `PointsCalculatorInterface` guaranteeing it returns an integer and behaves identically to callers.
* **I — Interface Segregation (ISP):** Scoped repository contracts (e.g., `CustomerRepositoryInterface`, `OrderRepositoryInterface`) ensure models only inherit operations they require.
* **D — Dependency Inversion (DIP):** Service classes and Controllers depend on abstraction interfaces, which are bound to Eloquent implementations inside the Service Provider.

### Design Patterns Used
1. **Repository Pattern:** Separates persistence from core logic, simplifying testing and database swaps.
2. **Strategy Pattern:** Utilized in `PointsCalculatorInterface` for flexible rules calculation.
3. **Event-Listener Pattern:** Decouples order capture from points calculation. Creating an order dispatches `OrderCreated`, which is handled asynchronously by a queued listener.
4. **Database Transaction Guard:** Protects data integrity in `AccountActivationService` to ensure a linked user account and customer profile status update succeed together or roll back entirely.

---

## ⚡ Quick Start & Run (Docker Containerized)

Ensure you have [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running.

### Windows (One-Click Setup)
Double-click on the **`start.bat`** script in the project root. This automates:
1. Copying `.env.example` to `.env`
2. Starting Docker containers (`app`, `webserver`, `mysql`, `redis`, `queue-worker`, `phpmyadmin`)
3. Installing Composer dependencies inside the container
4. Generating the Laravel app security key
5. Running database migrations and seeders

### Manual Run (Any OS)
If you prefer to run commands manually:
```bash
# 1. Copy environment template
cp .env.example .env

# 2. Build and start containers
docker compose up -d --build

# 3. Install composer packages inside the container
docker compose exec app composer install

# 4. Generate app security key
docker compose exec app php artisan key:generate

# 5. Run migrations and database seeders
docker compose exec app php artisan migrate:fresh --seed
```

---

## 👤 Sample Dataset & Credentials (Seeded Automatically)

You can use the following pre-seeded user accounts for testing:

| Role | Email | Password | Details |
|---|---|---|---|
| **Admin** | `admin@nesto.lk` | `password` | Admin management |
| **Cashier** | `cashier01@nesto.lk` | `password` | Registers customers & captures purchases |
| **Customer (Active)** | `dilshan@gmail.com` | `password` | Has 2 pre-seeded orders (395 total points) |
| **Customer (Active)** | `kasun@gmail.com` | `password` | Brand new active customer |
| **Customer (Pending)** | *(No login yet)* | *(Must activate)* | Registered under NIC `199806158521` (Mobile: `0719876543`) |

---

## 🔍 Visualizing the Database (phpMyAdmin)

To inspect the MySQL database tables (`users`, `customers`, `branches`, `orders`, `loyalty_transactions`):
1. Navigate to: **[http://localhost:8081](http://localhost:8081)**
2. Input credentials:
   * **Server:** `mysql`
   * **Username:** `nesto_user`
   * **Password:** `1234`

---

## 🚀 API Endpoints Overview (V1)

All endpoints are versioned under `/api/v1`.

### 1. Authentication
* `POST /api/v1/auth/login` - Public. Logs in using email & password. Returns a Sanctum bearer token.
* `POST /api/v1/auth/logout` - Protected. Revokes the current API token.

### 2. Customer Management
* `POST /api/v1/customers` - Protected (Cashier/Admin). Cashier registers customer (name, mobile, NIC/Passport).
* `POST /api/v1/customers/activate` - Public. Customer activates account by matching NIC/Passport, providing login email, and setting a password.
* `GET /api/v1/customers/me` - Protected (Customer). Customer retrieves their own profile details.

### 3. Points Accumulation
* `POST /api/v1/orders` - Protected (Cashier/Admin). Captures order data. Automatically triggers async points calculation if amount is ≥ 10,000 LKR (1 point per 100 LKR).

### 4. Tracking Loyalty Points (Customer only)
* `GET /api/v1/loyalty/balance` - Protected. Returns current points balance.
* `GET /api/v1/loyalty/transactions` - Protected. Paginated ledger of point transactions (earn, redeem, adjustment).
* `GET /api/v1/loyalty/dashboard` - Protected. Returns points balance, 5 recent orders, and 5 recent point transactions.

---

## 🧪 Running Tests

To run the automated PHPUnit test suite (which tests Sri Lankan format validations, duplicate order blocks, points threshold calculation, login gates, and security policies):

```bash
docker compose exec app php artisan test
```

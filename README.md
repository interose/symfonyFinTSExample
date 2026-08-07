# Symfony phpFinTS Adapter & Wizard Demo

[![Symfony](https://img.shields.io/badge/Symfony-8.1%2B-black?logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A clean, production-ready Symfony adapter and integration demo for the [`phpFinTS`](https://github.com/nemiah/phpFinTS) library.

This project provides a **multi-step assistant** to safely authenticate and persist German bank accounts, along with working implementations to retrieve account transactions and securities portfolio holdings.

---

## Features

- **Multi-Step Account Wizard:** Seamless user flow to connect, authenticate, and save bank accounts.
- **Secure Persistence:** Uses `ext-openssl` for database-level encryption of sensitive FinTS credentials.
- **Transaction Fetching:** Out-of-the-box example showcasing transaction retrieval for the last 5 days.
- **Portfolio / Investment Accounts:** Supports fetching statements of holdings for investment and share accounts (Depot).
- **Zero-Friction DB:** Pre-configured with SQLite for instant local development.

---

## Prerequisites

- **PHP:** `^8.4` (requires `ext-openssl`)
- **Symfony:** `^8.1`
- **FinTS Registration:** To connect to real bank accounts, you must register your application with **Die Deutsche Kreditwirtschaft** to obtain a valid registration number (`Product Registration Key`).

> [!NOTE]  
> There is currently no public sandbox/test bank available. Testing requires real credentials.

---

## Quick Start

1. **Clone the repository:**
   ```bash
   git clone https://https://github.com/interose/symfonyFinTSExample.git
   cd symfonyFinTSExample
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure Environment Variables:**
   Copy `.env` to `.env.local` and set your FinTS Application Registration Key:
   ```env
   PRODUCT_NAME=your_registration_number
   PRODUCT_VERSION=your_version_number
   ```

4. **Setup Database:**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:schema:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Run the Application:**
   ```bash
   symfony server:start
   ```
   Navigate to `http://127.0.0.1:8000` to view the account overview table, or go directly to `/account/step1` to launch the account connection wizard.

---

## How It Works

1. **Account Overview (`/`):** Lists all connected accounts and provides an action link to add a new bank connection.
2. **Setup Assistant (`/account/step1`):** Guides the user through bank selection (BLZ), credential submission, and 2FA/TAN challenge handling.
3. **Transaction & Holdings Fetching::**
   - Demonstrates fetching and parsing statement records for the preceding 5 days via the `phpFinTS` adapter service.
   - Retrieves statements of holdings (securities/depot portfolio data) for connected investment accounts via the `phpFinTS` adapter service.

---

## License

Distributed under the **MIT License**. See `LICENSE` for more information.

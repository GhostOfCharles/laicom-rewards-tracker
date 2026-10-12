<!-- LRTS README -->

<p align="center">
  <img width="1917" height="884" alt="image" src="https://github.com/user-attachments/assets/6cfd1191-a6c9-45e8-a916-ca847a501742" />
</p>

<h1 align="center">Laicom Rewards Tracker System</h1>
<p align="center"><strong>LRTS capstone project for Laicom Sales &amp; Promotions, Inc.</strong></p>
<p align="center">A responsive platform for reviewing customer orders, managing promotional rewards, and tracking reward claims.</p>

<p align="center">
  🌐 <strong>Live Site:</strong> <a href="https://LaicomRewardsTracker.com">LaicomRewardsTracker.com</a>
</p>

---

## Contents

- [Overview](#overview)
- [How It Works](#how-it-works)
- [Roles and Features](#roles-and-features)
- [Application Pages](#application-pages)
- [Architecture](#architecture)
- [Data Model](#data-model)
- [Technology and Open-Source Software](#technology-and-open-source-software)
- [Project Structure](#project-structure)
- [Screenshots](#screenshots)
- [Local Setup](#local-setup)
- [Configuration](#configuration)
- [Running the App](#running-the-app)
- [Testing](#testing)
- [Security and Deployment](#security-and-deployment)
- [Known Test Status](#known-test-status)

## Overview

LRTS is a Laravel web application that centralizes customer-submitted order details and helps Laicom staff review promotional reward eligibility, manage reward stock, and handle customer support.

Customers submit an order number, order date, and one or more purchased-product lines selected from the inventory catalog. Staff review each submission and its qualifying promotions. When an order is approved, the system records the earned rewards and reserves the required premium-product stock.

LRTS has two product catalogs with different purposes:

- **Inventory** contains customer-purchased products used when recording order lines.
- **Premium products** are the reward items offered through promotions.

The application supports customer and administrator roles. There is no separate salesman role or point-of-sale integration in this Laravel project; customer order details are submitted through the web dashboard or API and reviewed by staff.

## How It Works

1. A customer registers, signs in, and submits an order number, order date, purchased products, quantities, and an optional receipt image.
2. The system normalizes order numbers, checks for an existing active submission, and validates the order date and product lines.
3. An administrator reviews the receipt. The reward calculator finds qualifying promotions; when multiple reward choices are available for a purchased-product group, the administrator selects one.
4. Approval creates reward records and reserves premium-product stock. Rejected or cancelled order numbers can be submitted again.
5. The customer requests a claim code. Staff confirm the physical handover, and the customer confirms receipt in the dashboard.
6. The system records notifications, stock movements, and audit events. A scheduled task expires overdue reward claims and writes off expired premium stock.

Key business limits are configurable in **config/lrts.php**: the current claim window is 30 days, the maximum order age is 60 days, and the receipt-image upload limit is 5 MB.

## Roles and Features

### Customer

- Register with CAPTCHA verification and sign in to a personal dashboard.
- Submit orders from the inventory catalog; attach a receipt image if available.
- Track receipt states: processing, approved, rejected, or cancelled.
- View active promotions, earned rewards, claim codes, and claim history.
- Confirm receipt after staff records a reward handover.
- Read notifications and create support tickets with attachments.

### Administrator

- Manage premium reward products, stock, categories, images, and expiry information.
- Manage customer-purchased inventory, including category, name search, and dates.
- Create and maintain promotions with purchase requirements, reward products, and date ranges.
- Review receipts, choose qualifying rewards, and approve or reject submissions.
- Find claim records, confirm reward handovers, and manage reward exceptions.
- Respond to support tickets and add internal notes.
- View activity history and generate filtered reports with CSV or XLSX export.

## Application Pages

| Route | Page | Summary |
|---|---|---|
| / | Portal | Entry point for customer and administrator sign-in |
| /login | Customer login | Customer authentication and registration link |
| /register | Customer registration | Customer account form with password feedback and CAPTCHA |
| /admin/login | Administrator login | Administrator sign-in |
| /dashboard | Customer dashboard | Order submission, reward tracking, promotions, claims, notifications, and support |
| /admin/dashboard | Premium products and promotions | Reward catalog and promotion management |
| /admin/inventory | Inventory | Purchased-product catalog with category and name filters |
| /admin/receipts | Receipts | Search and filter order submissions and reward states; review and approve/reject |
| /admin/claims | Claims | Claim lookup and tabs for release, confirmation, completion, and void/expiry states |
| /admin/tickets | Support tickets | Ticket queue, conversation, status changes, and internal notes |
| /admin/reports | Reports | Promotion, premium-stock, and inventory-movement reports |
| /admin/activity-log | Activity log | Filterable audit history |

The premium-stock history view is opened from the relevant product actions in the administrator workspace.

## Architecture

LRTS is a single Laravel application organized around MVC, with service classes for shared business rules.

~~~text
Customer/Admin browser                 API client
        |                                  |
        +---------- Laravel routes --------+
                   web.php / api.php
                          |
           Session auth, role checks, Sanctum
                          |
                     Controllers
                          |
     Receipt, reward, claim, stock, and audit services
                          |
                 Eloquent models
                          |
             MySQL database and file storage
                          |
            Blade views / JSON responses
~~~

- **Web routes** use Laravel session authentication and role-checking middleware.
- **API routes** provide token-based authentication with Laravel Sanctum.
- **Controllers** handle HTTP requests and responses; services hold reusable business rules.
- **Blade views** render the customer and administrator interfaces.
- **Migrations** define and evolve the relational schema.
- **Private storage** holds receipt images and support attachments; authorized controllers serve them.

## Data Model

The principal tables and their responsibilities are:

| Area | Tables | Purpose |
|---|---|---|
| Accounts | users, personal_access_tokens | Customer/admin accounts and API tokens |
| Catalogs | inventories, premium_products | Purchased-product catalog and reward-product catalog |
| Promotions | promotions | Buy requirements, reward product, dates, and active state |
| Orders | receipts, receipt_items, receipt_order_guards | Submitted orders, line items, and duplicate-submission protection |
| Rewards | earned_rewards | Reward quantities, claim codes, lifecycle states, and expiry |
| Stock history | stock_movements, premium_stock_movements | Inventory and premium-product balance changes |
| Support | tickets, ticket_replies | Customer support conversations and attachments |
| Notifications and audit | customer_notifications, activity_logs | Customer alerts and append-only action history |
| Laravel support | sessions, password_reset_tokens, cache, jobs, failed_jobs | Framework session, cache, and queue support |

An ERD should show users with many receipts and tickets; receipts with many line items and earned rewards; tickets with many replies; and products with their corresponding stock movements. Promotions refer to a premium reward product and store the purchased-product name used for qualification. Activity records can refer to different subject models.

Use the migrations in **database/migrations** as the authoritative schema history. For a paper or presentation, diagram the final schema rather than copying the first version of a table before later migrations changed it.

## Technology and Open-Source Software

### Runtime and application stack

| Technology | Use |
|---|---|
| PHP 8.2+ | Application runtime |
| Laravel 12 | Backend framework, routing, validation, authentication, and database access |
| MySQL | Database connection selected in the current local configuration |
| Blade | Server-rendered templates |
| Bootstrap 5.3.0 | Responsive components and layout, loaded from jsDelivr |
| Bootstrap Icons 1.11.1 | Interface icons, loaded from jsDelivr |
| Vanilla JavaScript and custom CSS | Dashboard interactions and Laicom styling in public/css/laicom.css |
| Composer | PHP dependency management |

### Selected packages and services

| Package or service | Purpose |
|---|---|
| Laravel Sanctum 4.3 | API token authentication |
| edulazaro/laracaptcha 1.0 | CAPTCHA integration; Turnstile is the default configured driver, with reCAPTCHA drivers available |
| Maatwebsite/Laravel-Excel 3.1 | CSV and XLSX report downloads |
| Cloudflare Turnstile | Registration bot protection when configured with valid keys |
| PHPUnit 11 | Automated tests |

The Composer lockfile contains resolved PHP package versions and license metadata. Bootstrap and Bootstrap Icons are separate CDN assets; retain their upstream attribution and license notices. Composer development tools such as Faker, Pint, Sail, and Pail are development tooling rather than application features.

Vite and Tailwind starter files are present in the repository, but the main Blade pages currently load Bootstrap from the CDN and the custom Laicom stylesheet directly. Confirm the asset pipeline before describing Vite/Tailwind as active runtime dependencies.

## Project Structure

~~~text
app/
  Exports/                 Report export formatting
  Http/Controllers/
    Admin/                  Administrator workflows
    Api/                    Token-authenticated API
    Auth/                   Web authentication
  Http/Middleware/          Administrator/customer role checks
  Models/                   Eloquent data models
  Services/                 Receipt, reward, claim, stock, and audit rules
config/
  lrts.php                  Claim window, order age, and upload limit
database/
  migrations/               Database schema history
  seeders/                  Demo accounts and catalog data
public/
  css/laicom.css            LRTS design system
  images/                   Logos and portal background
resources/views/
  admin/                    Administrator pages and partials
  auth/                     Login and registration pages
  customer/                 Customer dashboard
  layouts/                  Shared administrator layout
routes/
  web.php                   Browser routes
  api.php                   JSON API routes
  console.php               Scheduled reward-expiry task
storage/app/private/         Private receipt and ticket uploads
tests/
  Feature/                  Workflow and integration tests
  Unit/                     Unit tests
~~~

## Screenshots

This repository currently includes the Laicom logos and portal background, but no application screenshots. Add current, privacy-safe screenshots under a documentation folder and link them here when available.

## Local Setup

### Prerequisites

Install these before you begin:

- **PHP 8.2 or later** — https://windows.php.net/download
- **Composer** — https://getcomposer.org/download
- **Node.js 18+** (includes npm) — https://nodejs.org
- **MySQL 8** — https://dev.mysql.com/downloads
- **Git** — https://git-scm.com

### Enable required PHP extensions

Open your `php.ini` file and uncomment these lines (remove the `;` at the start):

~~~ini
extension=gd
extension=zip
extension=pdo_mysql
~~~

- `gd` and `zip` — required by the Excel export package
- `pdo_mysql` — required for MySQL connections

Restart your web server (Apache, nginx, or XAMPP) after editing, or restart your terminal if using PHP's built-in server.

### Clone the repository

~~~bash
git clone <github-repo-link> lrts
cd lrts
~~~

Replace `<github-repo-link>` with the actual URL. The `lrts` at the end names the folder. This creates a new `lrts` folder in your current directory containing the project.

### Install PHP dependencies

~~~bash
composer install
~~~

This installs all the packages listed in `composer.lock` into the `vendor/` folder. It's the same command every teammate runs after cloning. It does **not** add new packages — it just installs what's already declared.

### Install frontend dependencies

~~~bash
npm install
~~~

Installs JavaScript packages declared in `package.json`. Only required if the project's Vite/Tailwind asset pipeline is active.

### Create the `.env` file

If `.env` does not exist, copy the example file:

~~~bash
# Windows
copy .env.example .env

# macOS / Linux / Git Bash
cp .env.example .env
~~~

Then generate the application key:

~~~bash
php artisan key:generate
~~~

Never commit a real `.env` file or credentials. Use a sanitized `.env.example` as the template.

### Create the database

Create a database named `lrts` in MySQL:

~~~sql
CREATE DATABASE lrts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
~~~

You can do this via MySQL Workbench, phpMyAdmin, or the command line:

~~~bash
mysql -u root -p
~~~

Then paste the `CREATE DATABASE` line above.

### Run migrations with demo data

~~~bash
php artisan migrate --seed
~~~

The seeder creates demo accounts and sample catalog data. Treat seeded credentials as development-only; replace or remove them before any public deployment.

### Create the public storage symlink

~~~bash
php artisan storage:link
~~~

This links `public/storage` to `storage/app/public` so uploaded product images render properly. The application stores receipt images and support attachments on a private disk — do not move those files to a publicly accessible directory.

### Build frontend assets

~~~bash
npm run build
~~~

Only required if the project's Vite/Tailwind asset pipeline is active.

### Clear caches

~~~bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear
~~~

Good habit after the first install or after editing `.env`.

## Configuration

Set local values in the ignored `.env` file. Common settings include:

| Variable | Purpose |
|---|---|
| APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL | Application identity, mode, encryption key, debugging, and base URL |
| DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD | Database connection |
| CAPTCHA_DRIVER | CAPTCHA provider; defaults to Turnstile |
| TURNSTILE_KEY, TURNSTILE_SECRET | Turnstile site and secret keys |
| RECAPTCHA_KEY, RECAPTCHA_SECRET | Optional reCAPTCHA v2 keys |
| RECAPTCHA_V3_KEY, RECAPTCHA_V3_SECRET, RECAPTCHA_V3_MIN_SCORE | Optional reCAPTCHA v3 configuration |

Example local `.env` values:

~~~env
APP_NAME=LRTS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lrts
DB_USERNAME=root
DB_PASSWORD=your_mysql_password_here

CAPTCHA_DRIVER=turnstile
TURNSTILE_KEY=1x00000000000000000000AA
TURNSTILE_SECRET=1x0000000000000000000000000000000AA
~~~

Adjust these three things:

1. **`DB_USERNAME` and `DB_PASSWORD`** — match your local MySQL credentials. If you use XAMPP with default settings, `DB_USERNAME=root` and `DB_PASSWORD=` (empty) usually work.
2. **`TURNSTILE_KEY` and `TURNSTILE_SECRET`** — the values above are Cloudflare's official **always-pass test keys**. For local development they work fine. For real captcha verification, get your own keys from [dash.cloudflare.com](https://dash.cloudflare.com) → Turnstile.
3. **`APP_URL`** — only needs changing if you serve on a different port or host.

Use real provider keys only in local or deployment secrets. Do not paste them into README files, screenshots, issue trackers, or source control.

## Running the App

Start the local development server:

~~~bash
php artisan serve
~~~

The app runs at:

~~~text
http://127.0.0.1:8000
~~~

Open that in your browser and log in with the demo accounts (or register a new customer).

For phone testing on the same Wi-Fi network, start it with:

~~~bash
php artisan serve --host=0.0.0.0 --port=8000
~~~

The `0.0.0.0` is important — it makes Laravel listen on your LAN IP, not just localhost.

Then find your computer's LAN IP. Open a new Command Prompt or PowerShell window and run:

~~~bash
ipconfig
~~~

Look for **IPv4 Address** under your active Wi-Fi adapter. It looks like `192.168.1.45` or `192.168.254.112`.

On your phone's browser, open:

~~~text
http://<your-ipv4-address>:8000
~~~

For example:

~~~text
http://192.168.168.54:8000
~~~

Replace `192.168.168.54` with your actual IPv4 address.

If your phone can't connect:

- Make sure the phone is on the **same Wi-Fi network** as your computer
- Check Windows Firewall — allow `php.exe` on **Private networks** when prompted
- Some routers have "AP isolation" or "client isolation" turned on — disable it in router settings
- If you're using a VPN, disconnect it

For local scheduled-task testing, run:

~~~bash
php artisan schedule:work
~~~

### Captcha on phone may fail

Cloudflare Turnstile treats `192.168.x.x` addresses as non-localhost domains and may reject HTTP-only origins. Two options:

- **For quick local testing** — temporarily disable the captcha in `.env`:
  ~~~env
  CAPTCHA_DRIVER=null
  ~~~
  Then run `php artisan config:clear` and reload. Remember to switch it back.

- **For real testing over HTTPS** — use a tunneling service like [ngrok](https://ngrok.com) or [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/) to get a public HTTPS URL for your local server, then add that URL to your Turnstile widget's allowed domains.

### Quick command summary

~~~bash
git clone <repo-url> lrts
cd lrts
composer install
npm install
cp .env.example .env
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE lrts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Edit .env with your database credentials
php artisan migrate --seed
php artisan storage:link
php artisan serve
~~~

## Testing

Run the automated suite:

~~~bash
php artisan test
~~~

The test configuration in phpunit.xml uses an in-memory SQLite database, independent of the MySQL connection used by the current local application configuration.

## Security and Deployment

Before deploying:

- Set APP_ENV=production, APP_DEBUG=false, a strong APP_KEY, and HTTPS with the correct APP_URL.
- Replace/remove demo accounts and rotate any seeded passwords.
- Configure real CAPTCHA keys and verify registration against the intended provider.
- Keep receipt images and ticket attachments on private storage and test authorization on download routes.
- Set up the Laravel scheduler to run every minute; the reward-expiry command is scheduled daily at 00:10.
- Back up the database and uploaded files and document a restore test.
- Apply migrations deliberately; do not use migrate:fresh on a database containing data.
- Test the complete receipt approval, claim release, customer confirmation, and expired-stock workflows using the deployment database.

## Known Test Status

At the latest local test run, 27 tests passed and 2 failed in ReceiptRewardWorkflowTest:

1. A test expects a missing receipt image to fail, but the current UI and service validation make the image optional.
2. A test expects the reward state to become claimed immediately after staff release; the current flow records released first and changes to claimed after the customer confirms receipt.

Decide which behavior is intended, align the implementation and test expectations, and rerun the full suite before presenting the project as fully passing.

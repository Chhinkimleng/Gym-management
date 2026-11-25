# Gym Management System (PulseCore Fitness OS)

A full-stack Laravel 12, PHP, MySQL, and Tailwind CSS Gym Management System featuring an **Electric Lime** editorial SaaS interface, role-based access control, member & membership lifecycle management, partial/full payment ledgers with PDF receipts, live attendance check-in/out, business reports, and database backups.

---

## Requirements

- PHP 8.2+ (with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`/`dom` extensions)
- Composer
- MySQL 8.0+ or MariaDB 10.4+
- Node.js 18+ & npm

---

## Installation & Setup

1. **Install PHP & Frontend Dependencies**
   ```bash
   composer install
   npm install
   npm run build
   ```

2. **Environment Configuration**
   Copy `.env.example` to `.env` (if not already present) and generate the application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **MySQL Database Configuration**
   Create a MySQL database (e.g., `gym_management`) and update `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=gym_management
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

4. **Run Migrations & Seed Initial Data**
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

5. **Run the Application**
   ```bash
   php artisan serve
   ```
   Visit `http://127.0.0.1:8000` in your browser.

---

## Default Seeded Accounts

| Role  | Email               | Password   |
|-------|---------------------|------------|
| Admin | `admin@example.com` | `password` |
| Staff | `staff@example.com` | `password` |

> Change these passwords immediately in **Settings → My Account** before deploying to production.

---

## Key Modules & Capabilities

- **Dashboard**: Real-time KPIs (total/active/new members, expired memberships, monthly & today's revenue, pending balances, today's attendance) and Chart.js analytics.
- **Members**: Unique Gym IDs, profile photos, emergency contacts, search/filter/sort, deactivation without losing history, and complete profile timelines.
- **Membership Plans**: Monthly, Quarterly, Semi-Annual, Annual, and Custom-day plans with benefits lists; referenced plans are protected from deletion.
- **Memberships**: Automatic expiration calculation, historical price/duration snapshots, overlap policies, renewal preserving history, and cancellation.
- **Payments**: Full & partial payments, balance installments without overwriting history, duplicate submission guards, Admin refunds with audit trail, and branded DomPDF receipts.
- **Attendance**: Live check-in/out desk with active membership eligibility checks and concurrency-safe locking.
- **Reports & Settings**: Date-filtered revenue/sales/member/attendance reports with CSV, PDF, and print exports, plus Admin staff management and JSON database backup downloads.

---

## Running Tests

```bash
php artisan test
```

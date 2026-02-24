# ShopFlix - E-Commerce Platform

A full-featured, modern e-commerce platform built on **Bagisto** (Laravel-based) with comprehensive functionality for online retail operations.

## Project Overview

**Cream Website** is a sophisticated Laravel 10 & Vue.js-powered e-commerce platform designed for managing online stores with advanced features, multiple payment gateways, international support, and a robust admin dashboard.

## Key Information

- **Project Name**: Cream Website
- **Framework**: Laravel 10.0 & Bagisto
- **PHP Version**: ^8.2
- **Database**: MySQL
- **Frontend**: Vue.js with Vite bundler
- **Currency**: INR (Indian Rupees)
- **Timezone**: Asia/Kolkata
- **Locale**: English (with multi-language support)
- **Admin Panel**: /admin

## 🛠 Developer Guide

### Project Architecture
This project is built on **Bagisto v2.x**, which uses a modular package-based architecture.
- **Core Logic**: Found in `packages/Webkul/`. Each module (Customer, Product, Sales, etc.) is a standalone package.
- **Business logic override**: If you need to change core behavior, look into `packages/Webkul`.
- **API**: Bagisto provides a robust REST API for integrating other platforms (mobile apps, external sites). Refer to the official Bagisto API documentation for endpoints.

### 🚀 Rapid Project Initialization
We have implemented a custom automation tool to help you spin up new instances of this platform quickly using your SQL backups.

#### 1. Setup Master Template
Ensure your master SQL structure is saved in:
`database/master_template.sql`

#### 2. Initialize from SQL
Run the following command to import the structure and data defined in your `.env`:
```bash
php artisan project:init
```

#### 3. Fresh Start (Clean Data)
If you want to use the same structure but **clear all customer data, orders, and carts** (keeping only products and system settings), run:
```bash
php artisan project:init --clean
```

> [!NOTE]
> The `--clean` flag specifically targets transactional tables like `orders`, `cart`, `addresses`, and `invoices`, giving you a clean slate for a new customer platform.

## The Importance of the `.env` File

The `.env` file is the heart of your project's configuration. It contains sensitive and environment-specific settings that should never be committed to version control.

- **APP_NAME**: Changes the branding of the site throughout the application.
- **APP_KEY**: Used for encryption. Must be generated using `php artisan key:generate`.
- **DB_* Settings**: Determines which database the application connects to.
- **MAIL_* Settings**: Configures how the system sends emails (e.g., invoices, order updates).
- **RAZORPAY_* / PAYPAL_***: Connects your site to payment processors to receive money.

## How to Start the Project (From Scratch)

Follow these steps exactly to get your project running:

### 1. Database Creation
Before doing anything else, you must create a database manually:
- Open **phpMyAdmin** or your MySQL terminal.
- Create a new database (e.g., `aalamsto_aalamstore` or `cream_db`).
  ```sql
  CREATE DATABASE aalamsto_aalamstore;
  ```

### 2. Environment Setup
- Copy `.env.example` to a new file named `.env`.
- Open `.env` and update the database section:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=aalamsto_aalamstore
  DB_USERNAME=your_username
  DB_PASSWORD=your_password
  ```

### 3. Install Dependencies
Open your terminal in the project directory:
```bash
# Install PHP packages
composer install

# Install Node.js packages
npm install
```

### 4. Initialize Application
```bash
# Generate the encryption key
php artisan key:generate

# Create database tables
php artisan migrate

# Populate database with initial data (Categories, Settings, etc.)
php artisan db:seed

# Link storage for images
php artisan storage:link
```

### 5. Build & Start
```bash
# Build frontend assets
npm run build

# Start the development server
php artisan serve
```
Your store will be available at `http://127.0.0.1:8000`.

## Technology Stack

### Backend
- **Laravel Framework** 10.0
- **Bagisto** - E-commerce platform built on Laravel
- **PHP 8.2+**
- **MySQL**
- **Redis** - Caching and session management
- **Elasticsearch** 8.10+ - Advanced search functionality

### Frontend
- **Vue.js**
- **Vite** 5.0
- **Axios**

## Core Features
1. **Payment Gateway Integration** (Razorpay, PayPal)
2. **Social Login** (Google, Facebook, etc.)
3. **Multi-language & Multi-currency** support
4. **Admin Dashboard** for full control over products and orders.
5. **SEO Optimized** with sitemap generation.

## Troubleshooting

- **Check Logs**: If something fails, check `storage/logs/laravel.log`.
- **Clear Cache**: Run `php artisan optimize:clear` to refresh settings.
- **Database Error**: Ensure your `.env` credentials match your phpMyAdmin setup.

---

**Last Updated**: February 2026  
**License**: [MIT](LICENSE)

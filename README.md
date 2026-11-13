# IRONCLAD GAMING SANCTUARY
### Full-Stack Gaming Cafe Booking, Management & Customer Loyalty Platform

**Tagline:** *Gaming. Competition. Community.*  
**Confirmed Location:** Behind Redtape Showroom, Near PSPCL Office, Guru Angad Dev Nagar, Sri Muktsar Sahib, Punjab – 152026.  
**Default Local URL:** `http://localhost/GamingCafe/`  
**Admin Portal:** `http://localhost/GamingCafe/admin/login.php`  
**Release Version:** `v2.2.0 (November 2026)`

---

## 1. Project Overview

**IRONCLAD GAMING SANCTUARY** is a production-ready, dynamic full-stack web application designed for esports gaming cafes and lounges. 

The platform delivers an immersive dark esports arena aesthetic with **RGB ambient glow**, high-visibility atmospheric cafe and GTA 6 backgrounds, dynamic scroll-driven visual reveals, and a server-validated booking engine. It provides a complete customer self-service suite, an owner/admin command center with 1-click WhatsApp festival/offer broadcasting, lead capturing, and multi-user role-based security.

---

## 2. Tech Stack & Architecture

- **Backend:** Native PHP 8.2+ (OOP & procedural helper architecture, PDO with prepared statements, native session management)
- **Database:** MariaDB 10.4+ / MySQL 8.0+ (`ironclad_gaming` normalized schema with foreign key constraints and transactional integrity)
- **Frontend:** HTML5, CSS3 with modern CSS custom properties, Bootstrap 5.3.3, Bootstrap Icons 1.11.3, Google Fonts (*Rajdhani* for futuristic headings & *Segoe UI* for crisp legibility)
- **Visual Engine:** Dynamic scroll-driven progressive background reveals, frosted glassmorphism (`backdrop-filter: blur(14px)`), desktop parallax (`background-attachment: fixed`), and cache-busted styling
- **Client-Side Libraries:**
  - `html2pdf.js` v0.10.1 (rasterized vector A4 single-page passes)
  - `Chart.js` (dynamic esports revenue and station utilization analytics)
  - Vanilla ES6+ JavaScript (zero jQuery dependency for fast, lightweight performance)
- **Server:** Apache 2.4 (XAMPP local environment & shared hosting ready)

---

## 3. Core Modules & Key Features

### 3.1. Customer Account & Authentication Suite
- **Player Sign-In (`login.php`):** Dual-mode identifier login supporting either customer mobile number or email address, password verification, persistent remember-me tokens, and automatic redirection to previous actions.
- **Player Registration (`register.php`):** Instant gamer registration capturing full name, verified 10-digit mobile number, email, and secure hashed password (`password_hash` with BCRYPT).
- **Gamer Dashboard (`my-account.php`):** Real-time customer hub showing verified gamer badge, active station reservations, historical gameplay logs, total hours played, total amount spent, profile details update, and password changes.
- **Navigation Dropdown:** Compact, high-density navbar featuring the gamer's profile badge, quick link to Book Station, Active Passes, and Sign Out.

### 3.2. Admin Customer Hub & WhatsApp Broadcast System
- **Customer CRM (`admin/customers.php`):** Centralized directory of all customer mobile numbers, emails, booking totals, and lifetime spend.
- **Festival & Promo WhatsApp Broadcaster:** Allows the cafe owner to dispatch pre-typed festival greetings (Diwali, New Year, Eid, Christmas), weekend tournament invitations, or custom promotional messages to any or all customers with 1-click WhatsApp integration.
- **Search & Filtering:** Real-time search across customer names, phone numbers, and email addresses with CSV export capability.

### 3.3. Contact & Inquiry Lead Generation System
- **Public Contact Form (`contact.php`):** Clean, validated inquiry submission capturing customer name, contact info, subject, and message.
- **Automatic Lead Capture:** Submissions automatically check the customer registry; new inquiries are immediately registered as customer leads.
- **Admin Inquiries Inbox (`admin/inquiries.php`):** Inbox for the arena manager with unread badge counters, status updates (New, Replied, Closed), internal notes, and 1-click WhatsApp reply with pre-filled acknowledgment.

### 3.4. Role-Based Admin & Account Recovery Architecture
- **Multi-User Role Permissions (RBAC):**
  - **Super Admin (Owner):** Full control over users, settings, financial reports, station management, and booking cancellations.
  - **Staff (Receptionist):** Daily operational access to check in players, verify payments, and manage reservations, with restrictions preventing modification of owner credentials.
  - **Developer Account:** Documented, authorized technical support account for system maintenance.
- **Account Recovery:** Multi-layered security preventing staff from locking out the owner, email-based password recovery, and documented hosting/database restoration procedures.

### 3.5. Atmospheric Cyber & Gaming Cafe Visual Engine
- **Immersive Background Art:** Integrates authentic gaming cafe interior photography (`images/cafe_bg.jpeg`) and vibrant GTA 6 cyberpunk artwork (`images/gta6_bg.jpeg`).
- **Dynamic Scroll Visibility Enhancer:** Real-time scroll listener in `assets/js/main.js` using `requestAnimationFrame` that dynamically lightens the overlay as players scroll down, revealing rich gaming visuals.
- **High-Contrast Glassmorphism:** Frosted glass cards (`background: rgba(13, 17, 24, 0.82-0.88)` with `backdrop-filter: blur(14px)`), dual-layer text shadows (`text-shadow: 0 2px 16px rgba(0, 0, 0, 0.98)`), and championship gold/cyan glowing borders.
- **Global Page Consistency:** Background visuals deployed across all frontend pages (`about.php`, `experiences.php`, `pricing.php`, `offers.php`, `gallery.php`, `book.php`, `check-booking.php`, `contact.php`, `reviews.php`, `my-account.php`, `login.php`, `register.php`), while keeping the Hero section and "Visual Experience Battle Arena & Video Reels" clean and exempt.

### 3.6. Atomic Collision-Free Booking Engine (`book.php`)
- **Race Condition Prevention:** Database transaction with `SELECT ... FOR UPDATE` row-level locking to eliminate double bookings.
- **Midnight-Crossing Support:** Fully supports overnight sessions crossing 00:00 midnight without time calculation errors.
- **Real-Time Slot Verification (`api/availability.php`):** Live AJAX slot radar checking operating hours (10:00 AM – 11:00 PM) and existing bookings.

### 3.7. UPI QR Payment Verification & Razorpay Gateway
- **Dynamic UPI QR (`admin/upi-qr.php`):** Generates real-time NPCI-compliant UPI QR codes for GPay, PhonePe, Paytm, and BHIM.
- **Transaction Reference Matching:** Customers submit their 12-digit UTR number; staff verifies payments with a single click in the admin terminal.
- **Razorpay Integration:** Full Razorpay Checkout support with webhook and callback validation configurable via `.env` and `admin/settings.php`.

### 3.8. 1-Page Print-Ready Digital Receipt & Vector A4 PDF Pass
- **Receipt Portal (`receipt.php`):** Clean, single-page print layout formatted for standard A4 and thermal receipt printers with zero page spillover.
- **Downloadable PDF:** One-click client-side vector PDF generation powered by `html2pdf.js`.

### 3.9. 100% Dynamic White-Label Rebranding Engine
- **Admin Settings (`admin/settings.php`):** Modify Cafe Name, Short Brand, Tagline, Address, Phone, Email, Logo Crest, Video Tour, and Razorpay credentials without touching any PHP code.

### 3.10. Gamer Comfort & Hospitality Policies
- **4K + QLED Screens:** High-refresh displays across PC battle rigs and console lounges.
- **Cozy & Aesthetic RGB Ambience:** Climate-controlled space with customizable RGB lighting.
- **In-House Refreshments:** Chilled energy drinks (Red Bull, Monster), sodas, and packaged snacks.
- **100% Outside Food Allowed:** Squads can bring their own outside food, takeout, or pizza.

---

## 4. Directory & File Structure

```
GamingCafe/
├── .env                          # Environment variables & API keys
├── .gitignore                    # Git exclusions
├── about.php                     # About the Sanctuary & Three Pillars
├── book.php                      # Interactive atomic booking engine
├── check-booking.php             # Self-service booking status & payment lookup
├── contact.php                   # Contact page with lead generation form
├── experiences.php               # Gaming rigs & console categories
├── gallery.php                   # Visual gallery & video reels
├── index.php                     # Homepage with atmospheric background engine
├── login.php                     # Player account sign-in
├── logout.php                    # Player session termination
├── my-account.php                # Gamer dashboard & booking history
├── offers.php                    # Promotional offers & battle pass discounts
├── pricing.php                   # Transparent pricing & hourly rates
├── receipt.php                   # 1-page printable digital receipt & PDF pass
├── register.php                  # New player profile registration
├── reviews.php                   # Customer reviews & player rating submission
├── robots.txt                    # Search engine crawler instructions
├── sitemap.xml                   # XML sitemap for SEO indexing
├── admin/                        # Owner / Administrator Control Center
│   ├── index.php                 # Admin entry router
│   ├── login.php                 # Administrative authentication
│   ├── logout.php                # Admin session logout
│   ├── dashboard.php             # Analytics, quick actions & metrics
│   ├── bookings.php              # Comprehensive reservation manager
│   ├── customers.php             # Customer CRM & WhatsApp broadcaster
│   ├── inquiries.php             # Contact inquiries & lead management
│   ├── stations.php              # Station & hardware inventory
│   ├── pricing.php               # Pass & pricing tier configuration
│   ├── offers.php                # Promo codes & discount campaigns
│   ├── reviews.php               # Review moderation & approval
│   ├── gallery.php               # Visual media & video upload manager
│   ├── settings.php              # White-label rebranding & payment settings
│   ├── timetable.php             # Real-time hourly slot timetable
│   ├── upi-qr.php                # Dynamic UPI QR generator
│   └── includes/
│       ├── admin-header.php      # Admin navigation & sidebar
│       └── admin-footer.php      # Admin scripts & footer
├── api/                          # Asynchronous JSON Endpoints
│   ├── availability.php          # Slot availability & clash detection
│   └── verify-payment.php        # Real-time payment verification
├── assets/
│   ├── css/
│   │   └── style.css             # Unified Ironclad theme CSS
│   └── js/
│       ├── main.js               # Client-side validation & scroll visual engine
│       └── booking.js            # Booking calculator & availability AJAX
├── config/
│   ├── config.php                # Application constants & configuration
│   ├── database.php              # PDO database connection provider
│   └── functions.php             # Core utility helpers & business logic
├── database/
│   ├── schema.sql                # Complete database structure
│   ├── migrate_customers.php     # Customer account migration
│   └── migrate_inquiries.php     # Inquiries table migration
├── images/
│   ├── cafe_bg.jpeg              # Authentic gaming cafe interior background
│   ├── gta6_bg.jpeg              # GTA 6 cyberpunk neon background
│   ├── ironclad_logo.jpeg        # 3D embossed metallic brand crest
│   ├── NOBG_IRONCLADLOGO.png     # Transparent brand logo
│   ├── intro_clad.mp4            # Arena tour video reel
│   └── ironclad_boss.mp4         # Arena gameplay reel
└── uploads/                      # Dynamic administrator media uploads
    ├── branding/                 # Custom logo & video uploads
    ├── gallery/                  # Uploaded photo & video files
    └── qr/                       # Custom UPI QR codes
```

---

## 5. Installation & Setup Guide

### Local Deployment (XAMPP):
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Clone or copy the project into your web root:
   ```
   C:\xampp\htdocs\GamingCafe\
   ```
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin/`) and create a database named:
   ```sql
   CREATE DATABASE ironclad_gaming CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Import `database/schema.sql`.
5. Run migrations if needed:
   - `http://localhost/GamingCafe/database/migrate_customers.php`
   - `http://localhost/GamingCafe/database/migrate_inquiries.php`
6. Verify database credentials in `config/database.php` or `.env`:
   ```
   DB_HOST=127.0.0.1
   DB_NAME=ironclad_gaming
   DB_USER=root
   DB_PASS=
   ```
7. Open in your browser:
   - **Frontend:** `http://localhost/GamingCafe/`
   - **Player Login:** `http://localhost/GamingCafe/login.php`
   - **Admin Control Center:** `http://localhost/GamingCafe/admin/login.php`

---

## 6. Default Administrative Credentials

- **Username:** `admin`
- **Password:** `IroncladAdmin2026!`
- **Access Level:** Super Admin (Owner)

*(Note: Change credentials immediately in production via `admin/settings.php`.)*

---

## 7. Security Implementation

- **CSRF Defense:** Cryptographic tokens generated per session and validated on every `POST` request.
- **SQL Injection Prevention:** 100% parameter binding through PDO prepared statements across all queries.
- **XSS Mitigation:** Strict HTML character escaping via `e()` helper applied to all user-facing output.
- **Password Security:** Hashed using `password_hash()` with BCRYPT and secure cost factor.
- **Audit Trail:** Sensitive administrative events recorded in `activity_logs` with admin ID, timestamp, and IP address.
- **Brute-Force Protection:** Rate-limiting safeguards on login attempts.

---

## 8. License & Ownership

Developed by **shubhdeep689** for **IRONCLAD GAMING SANCTUARY**, Sri Muktsar Sahib, Punjab.  
All rights reserved. Fully white-labeled for global commercial gaming lounge operations.

# IRONCLAD GAMING SANCTUARY
### Full-Stack Gaming Cafe Booking & Management Platform

**Tagline:** *Gaming. Competition. Community.*  
**Confirmed Location:** Behind Redtape Showroom, Near PSPCL Office, Guru Angad Dev Nagar, Sri Muktsar Sahib, Punjab – 152026.

---

## 1. Project Overview

**IRONCLAD GAMING SANCTUARY** is a complete, production-oriented, dynamic full-stack web application designed for a real gaming cafe in Sri Muktsar Sahib, Punjab. 

The platform delivers an immersive dark esports arena aesthetic with tasteful **RGB ambient lighting** while providing a rock-solid, server-validated booking engine and an owner-controlled administrative suite.

---

## 2. Tech Stack & Architecture

- **Backend:** PHP 8.2+ (OOP & Procedural helpers, PDO with Prepared Statements)
- **Database:** MySQL / MariaDB (`ironclad_gaming` normalized database schema)
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla ES6+), Bootstrap 5.3, Bootstrap Icons
- **Typography:** *Rajdhani*, *Segoe UI*, Modern Sans-Serif
- **Server:** Apache 2.4 (XAMPP Local Deployment & Shared Hosting Ready)

---

## 3. Confirmed Business Identity & Asset Integration

- **Actual Business Logo:** Uses the authentic 3D embossed metallic crest with horned warrior helm and ember sparks (`images/ironclad_logo.jpeg`).
- **Brand Palette:**
  - Primary Background: `#090B10`
  - Secondary Background: `#151922`
  - Elevated Cards: `#1B2230`
  - Accent Gold: `#D6A84B`
  - Electric Red: `#E63946`
  - RGB Ambient Accents: `#00E5FF` (Cyan), `#B026FF` (Purple), `#39FF88` (Green)
  - Main Text: `#F5F7FA`, Muted: `#A7AFBD`
- **Accessibility:** Fully supports `prefers-reduced-motion` to tone down keyframe glows on request.

---

## 4. Public Pages

1. **Home (`index.php`)**: Full-screen esports hero featuring the real business logo, gaming experiences overview, station live status, transparent pricing passes, limited-time promo banners, booking CTA, gallery showcase, approved reviews, and Google Maps location guide.
2. **About Us (`about.php`)**: The Ironclad story, philosophy (*Gaming. Competition. Community.*), hardware standards, and sanctuary code of honor.
3. **Gaming Experiences (`experiences.php`)**: Deep dive into PC Battle Stations (high-refresh FPS setups), 4K Console Lounges, and 5v5 Squad LAN Bays.
4. **Pricing (`pricing.php`)**: Hourly station rates, multi-hour passes (Quick Battle, Arena Pass, Champion Pass, Night Owl Pass), and pricing FAQs.
5. **Book Your Session (`book.php`)**: Interactive real-time booking engine with live station availability lookup, duration selection, instant promo code discount application, atomic collision prevention, and customer registration.
6. **Check Booking Status & UPI Payment (`check-booking.php`)**: Lookup reservation via reference code + phone number. Displays official UPI QR scanner, accepts 12-digit UTR submission and screenshot proof upload, and displays real-time verification status.
7. **Special Offers (`offers.php`)**: Active discount codes with one-click copy buttons and validity terms.
8. **Arena Gallery (`gallery.php`)**: Categorized photography of PC arena, console lounges, tournaments, and RGB ambience.
9. **Player Reviews (`reviews.php`)**: Verified customer ratings and honest review submission form with anti-spam admin moderation.
10. **Contact & Location (`contact.php`)**: Full address, landmark guidance, Google Maps navigation, direct directions link, and customer inquiry form.

---

## 5. Booking & Payment Engine

- **Server-Side Slot Validation:** Verifies station status, checks business operating hours (10:00 AM – 11:00 PM), checks blocked holiday dates, and prevents overlapping active reservations using MySQL row transactions.
- **Unique Reference Format:** Generates non-guessable, human-friendly references (e.g. `IC-2026-X8K9M`).
- **Manual UPI Workflow:**
  - Owner uploads authentic business UPI QR scanner from admin panel.
  - Customer views payable amount and scans QR using GPay, PhonePe, Paytm, or BHIM.
  - Customer submits 12-digit UPI Transaction ID (UTR) + optional screenshot.
  - Payment status remains strictly **Pending Verification** until owner manually verifies it.
  - Owner approves payment in one click, transitioning booking to **Confirmed**.

---

## 6. Admin Control Center (`/admin`)

**Access URL:** `http://localhost/GamingCafe/admin/login.php`  
**Default Superadmin Credentials:**
- **Username:** `admin`
- **Password:** `IroncladAdmin2026!`

### Modules & Capabilities:
1. **Dashboard (`dashboard.php`)**: Real-time KPIs (Total Bookings, Today's Bookings, Pending Verification, Confirmed Slots, Today's Verified Revenue, Monthly Revenue, Station Utilization Radar, and Recent Reservations).
2. **Bookings Management (`bookings.php`)**: Search, filter by date, station, and status.
3. **Walk-In Booking Creator (`booking-create.php`)**: Register walk-in players directly at the counter.
4. **Booking Detail & Action Panel (`booking-detail.php`)**: Status updater, internal counter notes, customer history, and UTR lookup.
5. **Gaming Stations CRUD (`stations.php`)**: Add, edit, activate/deactivate stations, set category, hourly rates, and hardware specs.
6. **Availability & Hours (`availability.php`)**: Configure weekly business hours (Monday–Sunday) and add scheduled closure/holiday dates.
7. **Pricing & Battle Passes (`pricing.php`)**: CRUD for multi-hour passes, feature bullets, popular badge toggle.
8. **Payment Verification Queue (`payments.php`)**: View submitted UTRs, inspect payment screenshots, approve or reject payments with customer-facing reason.
9. **UPI QR Management (`upi-qr.php`)**: Upload or replace authentic UPI QR code image, update UPI VPA ID, merchant name, and customer instructions.
10. **Customers CRM (`customers.php`)**: Customer directory with lifetime spend, visit count, and direct link to their booking logs.
11. **Offers & Promos (`offers.php`)**: Create coupon codes with percentage or fixed discounts, minimum booking hours, and expiry dates.
12. **Gallery Management (`gallery.php`)**: Upload arena photography with category tags and homepage feature toggles.
13. **Reviews Moderation (`reviews.php`)**: Approve, feature, or delete customer-submitted reviews.
14. **Reports & Analytics (`reports.php`)**: Revenue by category, peak traffic hours, payment channel breakdown.
15. **Website Settings (`settings.php`)**: Edit business name, address, contact phone, email, WhatsApp, hero copy, social handles, and Google Maps iframe without touching code.
16. **Admin Profile (`profile.php`)**: Secure password change and email update.
17. **Activity Logs (`logs.php`)**: Full administrative audit trail with timestamps and IP addresses.

---

## 7. Security Hardening

- **Prepared Statements:** 100% of SQL queries containing input use PDO prepared statements.
- **Output Escaping:** Strict UTF-8 escaping via `htmlspecialchars()` protects against Cross-Site Scripting (XSS).
- **CSRF Tokens:** All state-changing POST forms and sensitive GET actions are protected by cryptographic CSRF tokens.
- **Password Hashing:** Passwords hashed with `PASSWORD_BCRYPT`.
- **Brute Force Lockout:** 5 consecutive failed login attempts trigger an automated 15-minute account lockout.
- **Session Security:** `session_regenerate_id(true)` prevents session fixation; session cookies configured with `HttpOnly`, `SameSite=Lax`, and `Secure` (when on HTTPS).
- **File Upload Protection:** Uploads strictly verified by MIME type, file extension whitelist (`jpg`, `jpeg`, `png`, `webp`, `svg`), and randomized filenames. Script execution is completely disabled inside `uploads/` via `.htaccess`.

---

## 8. Local SEO Optimization (Sri Muktsar Sahib)

- **Targeted Keywords:** *Gaming Cafe in Sri Muktsar Sahib*, *Gaming Zone in Muktsar*, *Gaming Lounge in Sri Muktsar Sahib*, *PC Gaming in Muktsar*, *Multiplayer Gaming in Muktsar*.
- **LocalBusiness Structured Data:** JSON-LD schema with address in Guru Angad Dev Nagar, Sri Muktsar Sahib, Punjab, PIN 152026.
- **Social Metadata:** Complete Open Graph (`og:*`) and Twitter Card (`twitter:*`) tags referencing the real business logo.
- **Crawler Files:** `robots.txt` and `sitemap.xml` included and configured.

---

## 9. Local Installation & Setup (XAMPP)

1. **Place Project in XAMPP:**
   Ensure the project folder is at `C:\xampp\htdocs\GamingCafe`.
2. **Start Services in XAMPP Control Panel:**
   Start **Apache** and **MySQL**.
3. **Database Setup:**
   - Open phpMyAdmin at `http://localhost/phpmyadmin/`
   - Create database `ironclad_gaming` (utf8mb4_unicode_ci)
   - Import `database/schema.sql`
4. **Open Application in Browser:**
   - **Public Sanctuary:** `http://localhost/GamingCafe/`
   - **Owner Command Center:** `http://localhost/GamingCafe/admin/login.php`


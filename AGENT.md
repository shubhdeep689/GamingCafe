# AGENT.md --- IRONCLAD GAMING SANCTUARY

## 1. Project mission

Build a polished, dynamic, mobile-first website for **IRONCLAD GAMING
SANCTUARY**, a gaming cafe in Sri Muktsar Sahib, Punjab.

The website must feel like a premium esports gaming arena: dark,
immersive, energetic, and professional. Use tasteful **RGB ambient
lighting effects** throughout the interface without making the design
visually noisy.

The site must use the real business logo already present in the
project's `images/` folder. **Do not invent, redraw, replace, or use a
placeholder logo.** Find the existing logo asset, inspect its filename
and dimensions, and use that exact file wherever the brand logo is
required.

## 2. Confirmed business identity

Use these details consistently:

-   **Business name:** IRONCLAD GAMING SANCTUARY
-   **Tagline:** Gaming. Competition. Community.
-   **Category:** Gaming Cafe / Gaming Lounge
-   **Address:** Behind Redtape Showroom, Near PSPCL Office, Guru Angad
    Dev Nagar, Sri Muktsar Sahib, Punjab -- 152026
-   **City:** Sri Muktsar Sahib
-   **State:** Punjab
-   **PIN:** 152026
-   **Country:** India

Suggested hero copy:

> **IRONCLAD GAMING SANCTUARY**\
> Enter the Arena. Experience the Battle.\
> Your gaming destination in Sri Muktsar Sahib.

Treat all facilities, hardware specifications, prices, opening hours,
contact numbers, offers, and availability as **unconfirmed until
supplied by the owner**. Do not make up claims such as number of PCs,
PS5 availability, game titles, refresh rates, or prices. Where
appropriate, show editable admin-managed content or a neutral "Details
coming soon" state.

## 3. Required technology

Use the following stack unless the existing project structure gives a
compelling reason not to:

-   HTML5
-   CSS3
-   JavaScript (vanilla JS; use only where needed)
-   PHP (server-side backend)
-   MySQL / MariaDB
-   Bootstrap 5
-   Bootstrap Icons or Font Awesome (choose one icon set and use it
    consistently)

Use PHP with MySQLi or PDO consistently. Prefer PDO with prepared
statements for database access. Do not mix database access styles
unnecessarily.

The project should run locally in XAMPP. Keep the code compatible with a
normal PHP + MySQL shared-hosting deployment.

## 4. Brand and visual design system

### Core palette

-   Main background: `#090B10`
-   Secondary background: `#151922`
-   Elevated cards: `#1B2230`
-   Gold accent: `#D6A84B`
-   Electric red: `#E63946`
-   RGB cyan: `#00E5FF`
-   RGB magenta: `#B026FF`
-   RGB green: `#39FF88`
-   Main text: `#F5F7FA`
-   Muted text: `#A7AFBD`
-   Borders: `rgba(255,255,255,0.10)`

Gold and red establish the core brand identity. Cyan, magenta, and green
are ambient RGB lighting accents, not competing primary brand colors.

### RGB ambience requirements

Create a premium RGB atmosphere using CSS effects:

-   Soft blurred RGB radial gradients behind hero sections and selected
    content areas.
-   Subtle animated gradient borders or edge glows on selected cards and
    buttons.
-   Low-intensity cyan/magenta/red glow around key visual elements.
-   Gentle hover illumination on navigation links, cards, and calls to
    action.
-   Optional slow ambient background movement, kept subtle and
    performant.
-   Use `pointer-events: none` and appropriate stacking so decorative
    effects never block interaction.
-   Keep text contrast high and all important content readable.
-   Avoid flashing, strobing, rapid color cycling, excessive neon, and
    distracting animations.
-   Respect `prefers-reduced-motion`; disable or greatly reduce
    nonessential animation when requested by the operating system.
-   Avoid applying expensive blur/filter animations to large full-screen
    elements on mobile.

RGB should create the feeling of light reflecting around a gaming
arena---not make every element glow.

### Typography and layout

-   Use a strong, modern sans-serif font.
-   Use bold uppercase display headings for major sections.
-   Keep body text comfortable to read.
-   Use consistent spacing, rounded corners, restrained shadows, and
    clear visual hierarchy.
-   Make the experience responsive from small phones to large desktop
    screens.
-   Ensure buttons and touch targets are comfortable on mobile.

## 5. Logo and image asset rules

1.  Inspect the existing `images/` directory before creating or
    referencing image assets.
2.  Identify the actual IRONCLAD logo by filename and visual content.
3.  Use the actual logo in the navbar, footer, admin login/dashboard
    branding, and other appropriate brand locations.
4.  Preserve the logo's original proportions. Use `object-fit: contain`;
    never stretch or crop it.
5.  If the logo has a dark or transparent background, choose a suitable
    container/background so it remains legible.
6.  Do not add a fake glow that obscures the logo. A subtle surrounding
    ambient glow is acceptable.
7.  Reuse existing project images where suitable. Do not reference
    nonexistent image paths.
8.  If no suitable image exists for a section, use a well-designed CSS
    background or a clearly labelled admin-managed empty state. Do not
    use random stock images as if they show this cafe.

## 6. Public website structure

Build a coherent public-facing website with these sections/pages:

-   Home
-   About
-   Gaming Experiences
    -   PC Gaming
    -   Console Gaming
    -   Multiplayer
-   Pricing
-   Book Your Session
-   Check Booking
-   Offers
-   Gallery
-   Reviews
-   Contact / Find Us

Use a consistent navigation system and footer. Pages may be separate PHP
files or cleanly organized includes, depending on the project structure.

### Homepage sections

Recommended order:

1.  Premium hero with real logo, business name, tagline, and clear
    booking CTA.
2.  Gaming experiences overview (only verified facilities; otherwise
    editable placeholders).
3.  Why visit / experience highlights without unsupported claims.
4.  Pricing preview driven by database content.
5.  Booking CTA.
6.  Offers section driven by database content.
7.  Gallery driven by admin uploads.
8.  Reviews driven by approved reviews.
9.  Location and directions section.
10. Footer with business details and contact links when configured.

## 7. Booking and payment workflow

Create a booking flow that supports online booking and walk-in bookings
entered by the owner.

### Customer booking

-   Customer selects a service/station type that is active in the admin
    panel.
-   Customer selects an available date, start time, and session
    duration.
-   Customer enters name, phone number, and any required booking
    details.
-   Server validates the requested slot and checks for conflicting
    confirmed/pending bookings.
-   Show a booking summary before submission.
-   Generate a unique, non-guessable booking reference.
-   Allow customers to check booking status using a reference plus an
    appropriate verification detail.
-   Clearly explain whether the booking is pending, confirmed, rejected,
    cancelled, or completed.

### Availability rules

-   Never trust availability calculated only in browser JavaScript.
-   Recheck availability on the server immediately before saving.
-   Prevent overlapping active bookings for the same station/resource.
-   Use database transactions where appropriate to avoid race
    conditions.
-   Store timestamps consistently and display them in the business's
    local timezone (`Asia/Kolkata`).
-   Make business hours, slot duration, buffer time, and booking rules
    configurable.

### UPI QR payment

-   Owner uploads and manages the real UPI QR image from the admin
    panel.
-   Display the currently active QR code during the booking/payment
    step.
-   Do not hardcode a sample QR code or invent a UPI ID.
-   Customer can submit a UPI transaction/reference number and, if
    required, payment proof.
-   Payment status must remain **Pending Verification** until the owner
    verifies it.
-   Admin can mark payment as verified, rejected, or refunded (if refund
    tracking is implemented).
-   Do not claim that a payment has been automatically verified unless a
    real payment gateway integration exists.
-   Validate uploaded file type, size, and storage location. Never
    execute uploaded files.

## 8. Admin panel

Create a protected owner/admin panel with a dashboard and these modules:

1.  Dashboard overview
2.  Bookings
3.  Gaming stations / resources
4.  Availability and operating hours
5.  Pricing
6.  Payment verification
7.  UPI QR management
8.  Customers
9.  Offers
10. Gallery
11. Reviews
12. Reports
13. Website settings

### Admin capabilities

-   Secure login and logout.
-   Change password.
-   View, search, filter, and paginate bookings.
-   Confirm, reject, cancel, and complete bookings.
-   Add and edit walk-in bookings.
-   Manage station/resource names and active status.
-   Configure prices and session durations.
-   Manage operating hours and unavailable dates.
-   Upload/replace the UPI QR code.
-   Verify or reject submitted payment references.
-   Add/edit/deactivate offers.
-   Upload, caption, reorder, and remove gallery images.
-   Approve or hide customer reviews.
-   Edit business contact details, social links, address display, and
    SEO metadata.
-   View useful booking and revenue summaries.

All important public-facing business information should be editable
through the admin panel wherever practical. Do not hardcode unconfirmed
prices, facilities, hours, phone numbers, or social media links.

## 9. Database expectations

Design a normalized MySQL schema. Use appropriate primary keys, foreign
keys, indexes, unique constraints, and timestamps.

Suggested tables (adjust when the implementation requires it):

-   `admins`
-   `customers`
-   `stations` or `resources`
-   `services`
-   `pricing`
-   `bookings`
-   `payments`
-   `business_hours`
-   `blocked_dates`
-   `offers`
-   `gallery_images`
-   `reviews`
-   `site_settings`
-   `admin_activity_logs` (recommended)

Use explicit status values and document them. Store money as `DECIMAL`,
not floating-point types. Store dates/times in a consistent format. Add
indexes for booking lookups, station/date availability checks, payment
status, and customer contact lookup.

Provide a complete SQL schema/installation file and a safe first-admin
setup process. Never ship a publicly known default admin password.

## 10. Security requirements

Security is mandatory, not optional.

-   Use prepared statements for every query containing user input.
-   Validate and sanitize all server-side input.
-   Escape output with `htmlspecialchars` using appropriate flags and
    UTF-8.
-   Use password hashing with `password_hash()` and verify with
    `password_verify()`.
-   Regenerate session IDs after successful login.
-   Set secure session cookie options where deployment supports them.
-   Protect state-changing forms with CSRF tokens.
-   Enforce authorization on every admin route and action---not just by
    hiding buttons.
-   Validate uploaded files by MIME type, extension, size, and safe
    generated filename.
-   Store uploads in a controlled directory; prevent PHP/script
    execution in upload directories.
-   Do not expose database credentials, secrets, stack traces, or
    sensitive server paths.
-   Keep configuration/secrets outside publicly accessible files where
    practical.
-   Add login throttling or rate limiting.
-   Validate booking availability again on the server.
-   Use POST for state-changing actions.
-   Do not trust price, booking status, role, or payment status values
    sent by the browser.
-   Use HTTPS in production.
-   Provide safe error handling and log technical errors privately.

## 11. Local SEO and discoverability

Target real local search intent without keyword stuffing.

Primary phrases to use naturally:

-   Gaming Cafe in Sri Muktsar Sahib
-   Gaming Zone in Muktsar
-   Gaming Lounge in Sri Muktsar Sahib
-   PC Gaming in Muktsar
-   PS5 Gaming in Muktsar (only if PS5 is confirmed)
-   Multiplayer Gaming in Sri Muktsar Sahib

Suggested homepage title:

`IRONCLAD GAMING SANCTUARY | Gaming Cafe in Sri Muktsar Sahib`

Suggested meta description:

`Visit IRONCLAD GAMING SANCTUARY in Guru Angad Dev Nagar, Sri Muktsar Sahib. Explore gaming experiences, check availability, view pricing and book your gaming session online.`

Implement:

-   Unique title and meta description for each public page.
-   Canonical URLs when the production domain is known.
-   Open Graph metadata.
-   Semantic HTML and a logical heading hierarchy.
-   XML sitemap and robots.txt.
-   LocalBusiness structured data using only verified information.
-   Consistent business name, address, and phone number (when provided).
-   Click-to-call and WhatsApp links only when the real number is
    supplied.
-   Google Maps embed/directions link for the confirmed address.
-   Fast-loading, compressed images with descriptive alt text.
-   Mobile-first responsive design.
-   Helpful location copy; avoid repetitive keyword stuffing.

Do not invent a Google Business Profile URL, reviews, rating, phone
number, or social profile.

## 12. Accessibility and performance

-   Use semantic HTML elements.
-   Provide labels for form fields and meaningful validation messages.
-   Ensure keyboard navigation and visible focus states.
-   Maintain readable contrast.
-   Add alt text to meaningful images; decorative images should have
    empty alt text.
-   Use responsive images and lazy loading below the fold.
-   Minimize unnecessary JavaScript and dependencies.
-   Avoid layout shifts and oversized assets.
-   Ensure the site remains usable if decorative animation is disabled.

## 13. Suggested project structure

Adapt to the existing repository instead of blindly replacing it. A
maintainable starting structure could be:

``` text
ironclad-gaming-sanctuary/
├── index.php
├── about.php
├── experiences.php
├── pricing.php
├── book.php
├── check-booking.php
├── offers.php
├── gallery.php
├── reviews.php
├── contact.php
├── config/
│   ├── config.php
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── functions.php
│   └── auth.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── images/                 # Preserve and use the supplied real logo here
├── uploads/
│   ├── gallery/
│   └── payments/
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── bookings/
│   ├── stations/
│   ├── pricing/
│   ├── payments/
│   ├── gallery/
│   ├── offers/
│   ├── reviews/
│   └── settings/
├── api/
├── database/
│   └── schema.sql
├── .htaccess
├── .gitignore
└── README.md
```

Keep secrets and local environment files out of version control. Do not
create duplicate `images/` or `assets/images/` folders if the project
already has a clear asset convention; preserve and reuse the supplied
logo location.

## 14. Implementation workflow for the coding agent

Work in small, verifiable stages. Do not generate the entire application
in one unreviewed dump.

### Stage 1 --- Inspect before coding

-   Inspect the complete existing project tree.
-   Identify the current entry point, PHP version assumptions, database
    setup, CSS/JS conventions, and existing assets.
-   Locate and inspect the real logo inside `images/`.
-   Report the discovered logo filename and the existing project
    structure.
-   Preserve useful existing code and assets.
-   Do not delete or overwrite user files without a clear need.

### Stage 2 --- Plan and foundation

-   Propose the final folder structure.
-   Define reusable header/footer, configuration, database connection,
    and helper functions.
-   Create the database schema and setup instructions.
-   Establish responsive design tokens and the RGB ambience system.
-   Build the navbar and footer using the actual logo.

### Stage 3 --- Public frontend

-   Build the homepage and remaining public pages.
-   Connect dynamic content to MySQL.
-   Ensure all unknown business facts are editable or shown as
    unconfirmed.
-   Test mobile, tablet, and desktop layouts.

### Stage 4 --- Booking backend

-   Implement booking validation, slot conflict prevention, booking
    references, and status tracking.
-   Implement customer booking lookup.
-   Implement UPI QR display and manual payment verification workflow.
-   Test invalid input, duplicate submissions, and overlapping bookings.

### Stage 5 --- Admin panel

-   Implement secure authentication and role checks.
-   Build dashboard and management modules.
-   Connect all editable website content to admin-managed database
    records.
-   Add safe image/QR upload and validation.

### Stage 6 --- SEO, security, and QA

-   Add metadata, structured data, sitemap, robots.txt, and local
    location content.
-   Test all forms and admin actions.
-   Review SQL injection, XSS, CSRF, session, upload, and authorization
    protections.
-   Test booking concurrency and payment status transitions.
-   Check browser console, PHP logs, and responsive behavior.
-   Verify the real logo displays correctly on every relevant page.

### Stage 7 --- Handover

Provide:

-   Complete working source code.
-   SQL schema and installation instructions.
-   XAMPP setup steps.
-   First-admin creation instructions.
-   List of required owner-supplied details still missing.
-   Deployment checklist for shared hosting.
-   A concise test report describing what was actually tested.

## 15. Coding standards and agent behavior

-   First inspect; then plan; then implement.
-   Do not assume files, assets, tables, or features exist---verify
    them.
-   Do not replace the real logo with text, generated art, or a
    placeholder.
-   Do not invent business facts or fake customer reviews.
-   Do not hardcode values that the owner needs to manage.
-   Keep PHP, SQL, CSS, and JavaScript readable and organized.
-   Use reusable includes/components instead of duplicating large
    blocks.
-   Keep database logic out of presentation templates where practical.
-   Comment only where logic is not self-explanatory.
-   Do not leave dead buttons, fake forms, or nonfunctional navigation.
-   Do not claim a feature is complete until it has been tested.
-   If a required owner detail is missing, build the configurable
    mechanism and clearly document the missing value instead of blocking
    unrelated work.
-   After each stage, summarize changed files, functionality completed,
    tests run, and remaining issues.

## 16. Definition of done

The project is ready for handover when:

-   The actual logo from `images/` is used correctly.
-   The visual identity is premium, dark, and enhanced by subtle RGB
    ambience.
-   Public pages work on mobile and desktop.
-   Public-facing content is database-driven where owner management is
    required.
-   Admin authentication and authorization are enforced server-side.
-   The owner can manage bookings, resources, pricing, QR payments,
    offers, gallery, reviews, and settings.
-   Booking conflicts are checked securely on the server.
-   UPI payments are clearly marked pending until manually verified.
-   Local SEO metadata and structured data use accurate business
    details.
-   Forms, uploads, and admin actions have been tested.
-   Setup, database import, and deployment instructions are documented.

**Final instruction:** Build this as a real, maintainable business
website for **IRONCLAD GAMING SANCTUARY**---not a static template or a
generic demo. Preserve the supplied logo, create a restrained RGB-lit
gaming atmosphere, and make the owner's daily operations manageable
through the admin panel.

# Folder Structure Explanation — Epsilon

This document explains what lives where and, more importantly, **why**.

The project follows one rule throughout:

> **Pages that display things contain no business logic.
> Files that change data contain no HTML.**

Any file ending in `_process.php` validates input, talks to the database, sets a
message in the session, and redirects. It never prints a page. Every other file
fetches what it needs and displays it.

---

## Top Level

```
PROJECT12/
├── index.php              Home page — the marketplace feed
├── product.php            Product details (images, seller, bids, actions)
├── search.php             Search results with keyword/category/price filters
├── README.md              Main documentation
├── INSTALLATION.md        Step-by-step setup guide
├── TESTING.md             Test report
└── FOLDER_STRUCTURE.md    This file
```

These three PHP files sit at the root because they are the public pages a guest can
reach without logging in. Everything else is grouped by responsibility.

---

## `/assets` — Static front-end files

```
assets/
├── bootstrap/
│   ├── css/bootstrap.min.css      Bootstrap 5.3.3
│   └── js/bootstrap.bundle.min.js Bootstrap JS (includes Popper)
├── icons/
│   ├── bootstrap-icons.min.css    Icon stylesheet
│   └── fonts/                     Icon font files (woff, woff2)
├── css/style.css                  All custom styling
├── js/script.js                   All custom JavaScript
├── images/no-image.svg            Placeholder for products with no photo
└── uploads/
    ├── products/                  Product photos
    │                              (starts empty; filled as students post)
    └── profiles/                  Reserved for future profile pictures
```

This folder starts empty. Photos uploaded by students land here under generated
names such as `product_7_66af12c3d4e5.jpg`, so two people uploading `photo.jpg`
never overwrite each other.

**Why Bootstrap is self-hosted rather than loaded from a CDN:** the project must run
inside XAMPP on a machine that may have no internet connection — for example during a
lab demonstration. A CDN link would leave the site unstyled. Everything needed is in
the folder.

**Why uploads live under `/assets`:** the web server must be able to serve them
directly as images. They are stored with generated unique filenames
(`product_7_66af12c3d4e5.jpg`) so two students uploading `photo.jpg` never collide.

---

## `/config` — Configuration

```
config/
├── config.php    Session start, BASE_URL, file paths, category list, timezone
└── db.php        MySQL connection
```

These two files are the only place you need to edit when moving the project to a
different machine or folder. Every page includes them first, before any output.

`config.php` defines `BASE_URL` once so that every link and asset path in the project
is built from a single constant — rename the folder and one line fixes the whole site.

---

## `/database` — Everything about the data

```
database/
├── database.sql      THE single import file (tables, keys, indexes,
│                     views, procedures, triggers, seed data)
├── ER_Diagram.pdf    Printable ER diagram, classic Chen notation
├── generate_erd.php  Rebuilds that PDF from the schema
├── ER_DIAGRAM.md     Entity relationship diagram (Mermaid version)
└── DATABASE.md       Table-by-table reference + relationship diagram
```

There is deliberately **one** SQL file. Importing it into phpMyAdmin is the entire
database setup — no second script, no manual edits.

---

## `/includes` — Shared page parts and helpers

```
includes/
├── header.php          Opens the HTML document, loads CSS, includes the navbar
├── navbar.php          Responsive navigation (search, icons, user dropdown)
├── footer.php          Closes the page, loads JavaScript
├── sidebar_left.php    Category navigation
├── sidebar_right.php   Call-to-action + safety tips
├── product_card.php    One product card
├── functions.php       Shared helper functions
├── admin_header.php    Admin layout + the admin-only access check
├── admin_footer.php    Closes the admin layout
└── fpdf/               FPDF library for generating PDF invoices
```

**Why `product_card.php` exists:** the same card appears on the home feed, in search
results and in the wishlist. Keeping it in one file means a change to the card design
updates all three at once.

**Why there is a separate `admin_header.php`:** it carries the role check. Because
every admin page includes it, no admin page can accidentally be left unprotected.

**Key helpers in `functions.php`:**

| Function | Purpose |
|---|---|
| `isLoggedIn()` | Is anyone logged in? |
| `requireLogin()` | Send guests to login, remembering where they were going |
| `sanitize()` | Escape output to prevent XSS |
| `time_ago()` | "5 mins ago" instead of a raw timestamp |
| `fetchOne()` | Run a prepared SELECT and return one row |
| `getCategories()` | The live category list, read from the database |
| `resolveCategoryId()` | Pick an existing category, or create the one a seller typed |
| `getTransactionForUser()` | Load an order **only** if this user is its buyer or seller |
| `saveProductImages()` | Validate and store uploaded photos |
| `getWishlistCount()` / `getUnreadNotificationCount()` | Navbar badges |
| `getSellerRating()` | Average rating for a seller |

`getTransactionForUser()` is the security backbone of the checkout flow — the
ownership check is inside its SQL `WHERE` clause, so payment, delivery, invoice and
review pages all inherit it automatically.

---

## `/auth` — Authentication

```
auth/
├── register.php           Registration form (display only)
├── register_process.php   Validates, hashes the password, creates the account
├── login.php              Login form (display only)
├── login_process.php      Verifies credentials, starts the session
└── logout.php             Destroys the session
```

The form/process split means the form can be redisplayed with an error message
without ever re-running the account creation.

---

## `/user` — Everything a logged-in student can do

```
user/
├── profile.php              Profile, activity stats, reviews received
│
├── product/
│   ├── post.php + post_process.php        Create a listing
│   ├── edit.php + edit_process.php        Update a listing, add/remove images
│   ├── delete_process.php                 Delete a listing and its image files
│   └── my_products.php                    The seller's own listings
│
├── bid/
│   ├── place_process.php     Calls sp_place_bid
│   ├── accept.php            Calls sp_accept_bid (auto-rejects the rest)
│   ├── reject.php            Calls sp_reject_bid
│   └── my_bids.php           Bids this student has placed
│
├── transaction/
│   ├── index.php             All orders (as buyer and as seller)
│   └── details.php           The order hub — drives every step below
│
├── payment/
│   ├── pay.php + pay_process.php          Choose method and pay
│
├── delivery/
│   ├── address.php + address_process.php  Buyer's shipping address
│   └── update_process.php                 Seller updates the shipment status
│
├── invoice/
│   ├── invoice_data.php      Shared data loader (used by both files below)
│   ├── view.php              Printable HTML invoice
│   └── download.php          PDF invoice via FPDF
│
├── wishlist/
│   ├── toggle.php            Add or remove in one link
│   └── index.php             Saved products
│
├── review/
│   └── create.php + create_process.php    Rate the seller after delivery
│
├── report/
│   └── create.php + create_process.php    Flag a listing for the admin
│
└── notification/
    ├── index.php             Notification list
    └── mark_read.php         Mark one or all as read
```

**Why each module has its own folder:** with roughly forty files, grouping by feature
means you can find everything about bidding in one place instead of scrolling a flat
directory.

**Why `invoice_data.php` exists:** the printable view and the PDF must always show
identical information. Both call the same loader, so they can never drift apart.

**`transaction/details.php` is the hub.** It shows the order summary and then reveals
the right action depending on state and who is looking:

| State | Buyer sees | Seller sees |
|---|---|---|
| Just created | Add address → Pay Now | "Waiting for the buyer" |
| Paid | Invoice, delivery progress | Delivery status controls |
| Delivered | "Write a Review" | The posted review |

---

## `/admin` — Admin panel

```
admin/
├── index.php                 Dashboard with statistics
├── users/
│   ├── index.php             List and search students
│   └── toggle_status.php     Ban / unban
├── products/
│   ├── index.php             List and filter listings
│   └── delete.php            Remove a rule-breaking listing
├── categories/
│   ├── index.php             List, add, rename
│   ├── save.php              Handles add and rename
│   └── delete.php            Delete (only when unused)
├── reports/
│   ├── index.php             Moderation queue with status tabs
│   ├── update.php            Mark Reviewed / Dismissed
│   └── resolve.php           Remove the product and close the report
├── transactions/index.php    All transactions (read-only)
└── payments/index.php        All payments + totals by method (read-only)
```

The admin mirrors the `/user` structure so the layout is predictable. Transactions
and payments are intentionally read-only — an admin monitors money, they do not alter
records after the fact.

Note what is **absent**: there is no product-approval screen. Products publish
immediately, and the admin only steps in when something is reported.

---

## Request Flow Example

Posting a product, from click to page:

```
1. Student clicks "Post a Product"
      ↓
2. user/product/post.php
      - includes config/config.php  → session, BASE_URL
      - includes config/db.php      → database connection
      - includes includes/functions.php
      - calls requireLogin()        → guests bounce to auth/login.php
      - includes includes/header.php → navbar
      - displays the form
      - includes includes/footer.php
      ↓
3. Form submits to user/product/post_process.php
      - validates every field
      - INSERT INTO Product (prepared statement)
      - calls saveProductImages() for the uploads
      - redirects to product.php?id=NEW_ID
      ↓
4. product.php displays the finished listing
```

Every module in the project follows this same shape.

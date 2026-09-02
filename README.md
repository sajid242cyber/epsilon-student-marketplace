# Epsilon — Second-Hand Book/Gadget Exchange for Students

A marketplace built exclusively for university students to buy and sell second-hand
books and gadgets. Every registered student is **both a buyer and a seller** — there
are no separate account types. Guests can browse freely; logging in is only required
for actions that change something.

Built as a Database Management Systems (DBMS) semester project.

---

## Table of Contents

1. [Technology Stack](#technology-stack)
2. [Features](#features)
3. [Installation Guide](#installation-guide)
4. [Default Login Accounts](#default-login-accounts)
5. [Folder Structure](#folder-structure)
6. [Module Documentation](#module-documentation)
7. [Database Documentation](#database-documentation)
8. [Responsive Design](#responsive-design)
9. [Security Notes](#security-notes)
10. [Troubleshooting](#troubleshooting)

### All documentation files

| File | Contents |
|---|---|
| [`README.md`](README.md) | This file — overview, features, every module explained |
| [`INSTALLATION.md`](INSTALLATION.md) | Step-by-step XAMPP setup guide |
| [`FOLDER_STRUCTURE.md`](FOLDER_STRUCTURE.md) | Every folder explained, plus request flow |
| [`database/DATABASE.md`](database/DATABASE.md) | Table reference, relationship diagram, views, procedures, triggers |
| [`database/ER_Diagram.pdf`](database/ER_Diagram.pdf) | ER diagram in Chen notation, ready to print |
| [`database/ER_DIAGRAM.md`](database/ER_DIAGRAM.md) | The same diagram in Mermaid, for viewing online |
| [`TESTING.md`](TESTING.md) | Full test report with results |

---

## Technology Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Bootstrap 5.3.3, JavaScript |
| Backend | PHP 8 (procedural, `mysqli` with prepared statements) |
| Database | MySQL / MariaDB (XAMPP) |
| PDF Export | FPDF 1.86 (bundled, no Composer needed) |
| Icons | Bootstrap Icons 1.11.3 |

Bootstrap and the icon font are **self-hosted** in `/assets`, so the project runs
fully offline with no internet connection.

---

## Features

### Guest (not logged in)
- Browse the marketplace feed
- Search products by keyword
- Filter by category and price range
- View full product details

Clicking any protected action sends a guest to the login page, and after logging
in they are returned to the page they originally wanted.

### Registered Student (buyer **and** seller)
- Post, edit and delete products with multiple image uploads
- Add their own category while posting if none of the existing ones fit
- Place bids on other students' products, and answer a seller's counter offer
- Accept, reject, or counter bids on their own products
- Pay via bKash / Nagad / Rocket / Bank Transfer
- Provide a delivery address and track the shipment
- Download or print an automatically generated invoice
- Save products to a wishlist
- Review and rate a seller after delivery
- Report suspicious listings
- Receive in-app notifications for every important event

### Admin
- Dashboard with live statistics
- Manage users (ban / unban)
- Manage products (remove rule-breaking listings)
- Manage categories (add, rename, delete unused)
- Moderate reported products
- View all transactions and payments

> Admin **does not** approve products. Listings are published immediately.
> The admin only moderates listings that have been reported.

---

## Installation Guide

### Requirements
- XAMPP (Apache + MySQL), PHP 8.0 or newer

### Steps

1. **Copy the project**

   Place the `PROJECT12` folder inside your XAMPP `htdocs` directory:

   ```
   C:\xampp\htdocs\PROJECT12
   ```

2. **Start Apache and MySQL**

   Open the XAMPP Control Panel and click **Start** next to both *Apache* and *MySQL*.

3. **Import the database**

   - Open <http://localhost/phpmyadmin>
   - Click the **Import** tab
   - Choose the file `PROJECT12/database/database.sql`
   - Click **Go**

   That single file creates the database, all 14 tables, foreign keys, constraints,
   indexes, 2 views, 4 stored procedures, 4 triggers, and the starter data.
   **No manual SQL editing is required.**

4. **Open the site**

   <http://localhost/PROJECT12>

That's it — the project is ready to use.

### If your folder name is different

The project assumes it lives at `htdocs/PROJECT12`. If you rename the folder,
update one line in `config/config.php`:

```php
define('BASE_URL', '/YOUR_FOLDER_NAME');
```

### If your MySQL has a password

Edit `config/db.php`:

```php
$DB_USER = 'root';
$DB_PASS = 'your_password_here';
```

---

## Default Login Accounts

The SQL file includes a starter admin account for local testing. **Change or remove the starter admin credentials before deploying to production.**

| Role | Email | Student ID | Password |
|---|---|---|---|
| Admin | `admin@epsilon.edu` | `242-15-782` | **Set a new password before deployment** |

You can log in with **either** the email address **or** the Student ID.

This is the **only** account the SQL file creates. There are no sample students and
no sample products — the marketplace starts empty, so everything you see afterwards
is data you created yourself.

The six categories (Books, Laptop, Phone, Calculator, Accessories, Others) are seeded
because a product needs one; students can add more while posting.

To try the site, register a couple of student accounts from the registration page and
post products with them. Photos students upload are stored in
`assets/uploads/products/`.

---

## Folder Structure

A fuller explanation of every folder, why it exists, and how a request flows through
the project is in [`FOLDER_STRUCTURE.md`](FOLDER_STRUCTURE.md).

```
PROJECT12/
│
├── index.php                  Home page — the marketplace feed
├── product.php                Product details page (images, seller, bids)
├── search.php                 Search results with filters
│
├── assets/                    All static front-end files
│   ├── bootstrap/             Bootstrap 5 CSS + JS (self-hosted)
│   ├── icons/                 Bootstrap Icons font
│   ├── css/style.css          Custom styles
│   ├── js/script.js           Custom JavaScript
│   ├── images/                Site images (e.g. no-image placeholder)
│   └── uploads/products/      Uploaded product photos
│
├── config/
│   ├── config.php             Session start, BASE_URL, paths, categories
│   └── db.php                 MySQL connection
│
├── database/
│   ├── database.sql           THE single import file
│   ├── ER_Diagram.pdf         Printable ER diagram (Chen notation)
│   ├── generate_erd.php       Rebuilds that PDF from the schema
│   ├── ER_DIAGRAM.md          Entity relationship diagram (Mermaid)
│   └── DATABASE.md            Full table-by-table documentation
│
├── includes/                  Reusable page parts and helpers
│   ├── header.php             <head> + navbar
│   ├── navbar.php             Responsive navigation bar
│   ├── footer.php             Footer + JS includes
│   ├── sidebar_left.php       Category sidebar
│   ├── sidebar_right.php      Call-to-action + safety tips
│   ├── product_card.php       One product card (used by feed and search)
│   ├── functions.php          Shared helper functions
│   ├── admin_header.php       Admin layout + admin-only access check
│   ├── admin_footer.php       Admin layout closing
│   └── fpdf/                  FPDF library for PDF invoices
│
├── auth/                      Authentication
│   ├── register.php           Registration form
│   ├── register_process.php   Creates the account
│   ├── login.php              Login form
│   ├── login_process.php      Verifies credentials, starts session
│   └── logout.php             Destroys the session
│
├── user/                      Everything a logged-in student can do
│   ├── profile.php            Profile, activity stats, reviews received
│   ├── product/               post, edit, delete, my_products
│   ├── bid/                   place, accept, reject, my_bids
│   ├── transaction/           list and details (the order hub)
│   ├── payment/               pay form and processing
│   ├── delivery/              address form, status updates
│   ├── invoice/               printable view + PDF download
│   ├── wishlist/              toggle and list
│   ├── review/                rating and review after delivery
│   ├── report/                report a listing
│   └── notification/          list and mark as read
│
└── admin/                     Admin panel
    ├── index.php              Dashboard with statistics
    ├── users/                 List, search, ban / unban
    ├── products/              List, filter, delete
    ├── categories/            Add, rename, delete
    ├── reports/               Moderate reported products
    ├── transactions/          All transactions
    └── payments/              All payments + totals by method
```

**Separation of concerns:** pages ending in `_process.php` contain no HTML — they
only validate input, talk to the database, and redirect. Pages that display
something contain no business logic beyond the query that fetches their data.

---

## Module Documentation

### 1. Authentication (`/auth`)
Registration collects Student ID, Full Name, Department, Batch, Email, Phone and
Password. Passwords are hashed with `password_hash()` (bcrypt) and verified with
`password_verify()`. Student ID and Email are both `UNIQUE`, so duplicates are
rejected with a friendly message.

Login accepts **either** the email or the Student ID. A banned account cannot log
in. Login state is kept in `$_SESSION`. `requireLogin()` in `includes/functions.php`
protects every private page and remembers where the guest was heading.

### 2. Home Feed (`index.php`)
A Facebook-Marketplace-style feed. On desktop it is a three-column layout
(categories sidebar, product feed, call-to-action sidebar). Each card shows the
image, title, category, price, condition, seller name, seller rating, posted time,
and View Details / Wishlist / Place Bid buttons. Only `Available` products appear.

### 3. Product Module (`/user/product`)
Post, edit and delete listings with multiple image uploads. Uploads are validated
three ways: allowed extension, size limit (3 MB), and a real `getimagesize()` check
so a renamed script cannot be uploaded. Files are stored under a generated unique
name. Products go live immediately — no admin approval. Status is
`Available` → `Pending` → `Sold`. Editing and deleting always verify ownership in
the SQL `WHERE` clause, so one student can never touch another's listing.

### 4. Category Module
The project starts with six categories: Books, Laptop, Phone, Calculator,
Accessories and Others.

Sellers are not limited to that list. On the post and edit forms the category
dropdown ends with **"+ Add a new category"**, which reveals a text box for typing
their own. If a category with that name already exists it is reused instead of
creating a near-duplicate, and the match ignores capitalisation — typing `books`
puts the product into the existing `Books`.

New categories appear immediately in the sidebar, the search filter and the admin
category manager, because every one of those reads the live list from the `Category`
table through `getCategories()` rather than a hardcoded list.

The admin can still add, rename, and delete categories — deletion is only allowed
when no product is using the category.

### 5. Search Module (`search.php`)
Combines keyword search (`LIKE` on title and description), category filter, minimum
and maximum price, and sorting (latest / price low→high / price high→low). Every
filter is bound as a parameter, and the sort option is matched against a fixed
whitelist, so the query cannot be injected.

### 6. Bidding Module (`/user/bid`)
There is no chat. Buyers place bids instead. The stored procedure `sp_place_bid`
rejects a bid if the product is not available or if the seller is bidding on their
own product.

A seller has three answers to a bid:

| Answer | What happens |
|---|---|
| **Accept** | The deal is agreed at the buyer's price |
| **Reject** | The bid is closed |
| **Counter** | The seller names their own price and the ball goes back to the buyer |

**Counter offers.** If the seller doesn't like the price they enter their own using
`sp_counter_bid`. The bid becomes `Countered`, the amount is stored in
`counter_amount`, and the buyer is notified. The buyer then sees the counter on the
product page and in *My Bids*, and can **Accept** it or **Decline** it. Accepting
creates the transaction at the counter price; declining closes the bid and tells the
seller. Only the buyer can answer a counter, and only the seller can raise one — both
checked in the SQL `WHERE` clause.

Whichever way a deal is agreed, `sp_accept_bid` runs inside a database transaction and
atomically: marks that bid `Accepted`, **rejects every other open bid**, sets the
product to `Pending`, and **creates the Transaction**.

The agreed price is always `COALESCE(counter_amount, bid_amount)` — the counter if
there was one, otherwise the original bid — so payment, delivery and the invoice all
use the right figure automatically.

### 7. Transaction Module (`/user/transaction`)
Stores buyer, seller, product, accepted bid, date and status. `details.php` is the
order hub — it shows the summary and drives payment, delivery, invoice and review.
Only the buyer or the seller on a transaction can open it.

### 8. Payment Module (`/user/payment`)
Supports bKash, Nagad, Rocket and Bank Transfer, with status Pending / Paid / Failed.
A delivery address is required before paying, and a transaction can never be paid
twice. Marking a payment `Paid` fires the trigger described below.

### 9. Delivery Module (`/user/delivery`)
The buyer supplies Receiver Name, Phone, District, Area and Full Address. The address
locks once payment is made. The seller updates the status through
Pending → Packed → Shipped → Delivered and can record a tracking number. Each status
change notifies the buyer automatically.

### 10. Invoice Module (`/user/invoice`)
An invoice is generated **automatically by a database trigger** the moment a payment
becomes `Paid`. It contains the invoice number, buyer and seller information, product
details, accepted bid, payment method, delivery address, transaction date and total.
`view.php` is print-optimised (site chrome is hidden when printing) and
`download.php` produces a real PDF with FPDF.

### 11. Wishlist Module (`/user/wishlist`)
One link toggles a product in and out of the wishlist. A `UNIQUE (user_id, product_id)`
constraint prevents duplicates at the database level.

### 12. Review Module (`/user/review`)
After the item is marked `Delivered`, the buyer can rate the seller 1–5 and leave a
comment. One review per transaction, enforced both in PHP and by a `UNIQUE`
constraint. Ratings feed straight into the seller rating shown on every product card.

### 13. Report Module (`/user/report`)
Students can report a listing as Fake Product, Spam, Scam, Wrong Information or Other.
You cannot report your own listing, and you cannot file a second report while your
first one is still pending.

### 14. Notification Module (`/user/notification`)
Notifications are created by **database triggers**, so they can never be missed:
New Bid, Counter Offer, Bid Accepted, Bid Rejected, Payment Successful, and Delivery
Update. Unread counts appear as a badge in the navbar.

Every notification is **clickable** and opens whatever it is about. The trigger stores
`product_id` or `transaction_id` alongside the message, so bid-related notifications
open the product's bid section and payment/delivery ones open the transaction page.
Opening a notification marks it read on the way through. Both columns use
`ON DELETE SET NULL`, so the message stays readable even if the product is later
removed — it just stops being a link.

### 15. Admin Panel (`/admin`)
Dashboard statistics, user management with ban/unban, product moderation, category
management, report moderation, and read-only views of all transactions and payments
with totals grouped by payment method. Every admin page includes `admin_header.php`,
which blocks anyone who is not logged in with the `admin` role.

---

## Database Documentation

Full details are in [`database/DATABASE.md`](database/DATABASE.md). The ER diagram comes
in two forms: [`database/ER_Diagram.pdf`](database/ER_Diagram.pdf) for printing and
submission (classic Chen notation, primary keys underlined) and
[`database/ER_DIAGRAM.md`](database/ER_DIAGRAM.md) for viewing online.

### Summary

| Object | Count |
|---|---|
| Tables | 14 |
| Views | 2 |
| Stored Procedures | 4 |
| Triggers | 4 |
| Foreign Keys | 23 |
| Indexes | 43 |

### Tables
`User`, `Category`, `Product`, `ProductImage`, `Bid`, `Transaction`, `Payment`,
`DeliveryAddress`, `Delivery`, `Wishlist`, `Review`, `Report`, `Notification`, `Invoice`

### Views
- **`vw_product_feed`** — a product joined with its category, seller, primary image,
  live seller rating and bid count. Powers the home feed, search and wishlist.
- **`vw_seller_rating`** — average rating and review count per seller.

### Stored Procedures
- **`sp_place_bid`** — validates and inserts a bid.
- **`sp_accept_bid`** — accepts one bid, rejects the rest, updates the product and
  creates the transaction, all in one database transaction with rollback on error.
- **`sp_reject_bid`** — rejects a single pending bid.

### Triggers
- **`trg_bid_after_insert`** — notifies the seller of a new bid.
- **`trg_bid_after_update`** — notifies the buyer when their bid is accepted/rejected.
- **`trg_payment_after_update`** — on payment success: completes the transaction,
  marks the product `Sold`, **generates the invoice**, and notifies both students.
- **`trg_delivery_after_update`** — notifies the buyer on every delivery status change.

### Normalization (3NF)
- **1NF** — every column holds one atomic value; product images live in their own
  table rather than a comma-separated column.
- **2NF** — every table has a single-column surrogate key, so no partial dependencies.
- **3NF** — no transitive dependencies. Seller rating, for example, is never stored on
  `Product`; it is derived from `Review` through a view so it can never go stale.

---

## Responsive Design

One responsive website — never a separate mobile site. Built on the Bootstrap 5 grid.

| Breakpoint | Layout |
|---|---|
| Desktop (≥ 992px) | Full navbar, left sidebar, centre feed, right sidebar, 3 cards per row |
| Tablet (768–991px) | Collapsed navigation, 2 cards per row, responsive forms |
| Mobile (< 768px) | Hamburger menu, single-column feed, 44px touch targets |

Verified in the browser at 1280px, 768px and 375px:
- No horizontal scrolling at any width (`scrollWidth === clientWidth`)
- All tables wrapped in `.table-responsive`
- All images fluid and constrained to the viewport
- All buttons at least 44×44px on mobile

---

## Security Notes

- **Passwords** are bcrypt hashed; plain text is never stored.
- **SQL injection** — every query uses `mysqli` prepared statements with bound
  parameters. Sort options and status values are validated against whitelists.
- **XSS** — all user-supplied output passes through `sanitize()` (`htmlspecialchars`).
- **Access control** — ownership is checked in the SQL `WHERE` clause, not just in the
  UI, so a crafted URL cannot edit another student's product, accept their bids, open
  their transaction, or download their invoice.
- **File uploads** — extension whitelist, size limit and a real image check.

---

## Troubleshooting

**"Database connection failed"**
MySQL is not running, or the credentials in `config/db.php` don't match. Start MySQL
in the XAMPP Control Panel.

**Styles look broken / links 404**
Your folder name is not `PROJECT12`. Update `BASE_URL` in `config/config.php`.

**"Table doesn't exist"**
`database.sql` was not imported. Re-import it via phpMyAdmin.

**Images don't upload**
Make sure `assets/uploads/products/` exists and is writable. Also check
`upload_max_filesize` and `post_max_size` in `php.ini` if you are uploading large photos.

**Port 80 already in use**
Another program (often Skype or IIS) is using port 80. Change Apache's port in the
XAMPP config, then use `http://localhost:PORT/PROJECT12`.

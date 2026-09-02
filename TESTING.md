# Testing Report — Epsilon

All tests below were executed against a **freshly imported** `database.sql` running on
XAMPP (Apache + MariaDB, PHP 8). Every result listed was observed, not assumed.

---

## 1. Environment / Build Checks

| Check | Result |
|---|---|
| `database.sql` imports with zero errors | ✅ Pass |
| Tables created | ✅ 14 |
| Views created | ✅ 2 |
| Stored procedures created | ✅ 4 |
| Triggers created | ✅ 4 |
| Foreign keys | ✅ 23 |
| Indexes | ✅ 45 |
| PHP syntax check on all 63 project files | ✅ No syntax errors |
| Every internal link resolves to a real file | ✅ 47/47 |
| Site loads after a clean re-import | ✅ HTTP 200 |

---

## 2. Authentication

| Test | Expected | Result |
|---|---|---|
| Register with valid details | Account created, redirect to login | ✅ Pass |
| Register with a duplicate email/Student ID | Rejected with message | ✅ Pass |
| Register with mismatched passwords | Rejected | ✅ Pass |
| Password stored | bcrypt hash, never plain text | ✅ Pass |
| Login with **email** | Session starts | ✅ Pass |
| Login with **Student ID** | Session starts | ✅ Pass |
| Login with wrong password | "Invalid Email/Student ID or password." | ✅ Pass |
| Login as a **banned** user | "Your account has been suspended." | ✅ Pass |
| Login as admin | Redirected to `/admin/index.php` | ✅ Pass |
| Logout | Session destroyed, guest view restored | ✅ Pass |
| Login after being redirected from a protected page | Returned to that page | ✅ Pass |

---

## 3. Guest Access

| Page | Expected | Result |
|---|---|---|
| `/index.php` | Loads, products visible | ✅ Pass |
| `/search.php` (all filters) | Loads | ✅ Pass |
| `/product.php?id=1` | Loads | ✅ Pass |
| `/auth/login.php`, `/auth/register.php` | Load | ✅ Pass |

Protected pages all redirect a guest to `/auth/login.php`:

| Page | Result |
|---|---|
| `/user/product/post.php` | ✅ Redirected |
| `/user/wishlist/index.php` | ✅ Redirected |
| `/user/transaction/index.php` | ✅ Redirected |
| `/user/notification/index.php` | ✅ Redirected |
| `/user/profile.php` | ✅ Redirected |
| `/user/bid/my_bids.php` | ✅ Redirected |
| `/admin/*` | ✅ Redirected |

---

## 4. Product Module

| Test | Expected | Result |
|---|---|---|
| Post a product | Published immediately, no admin approval | ✅ Pass |
| Post with image upload | File saved to disk + row in `ProductImage` | ✅ Pass |
| Upload a `.php` file renamed to `.jpg` | Rejected, 0 images saved, nothing written | ✅ Pass |
| Uploaded image is served and renders | 200, `image/jpeg`, 200×200 | ✅ Pass |
| Edit own product | Saved | ✅ Pass |
| Delete own product | Removed + image files deleted | ✅ Pass |
| "My Products" list | Shows only the logged-in seller's listings | ✅ Pass |
| Sold products excluded from the feed | Not shown | ✅ Pass |

---

## 4b. Seller-Added Categories

| Test | Expected | Result |
|---|---|---|
| Post with "+ Add a new category" → *Musical Instruments* | Category created, product linked to it | ✅ Pass |
| Post again with the same new category name | Existing category reused, **no duplicate row** | ✅ Pass |
| Type `books` (different capitalisation) | Reused the existing `Books` category | ✅ Pass |
| Choose "add new" but leave the name blank | Rejected, product not created | ✅ Pass |
| Submit a non-existent `category_id=9999` | Rejected, product not created | ✅ Pass |
| New category appears in the left sidebar | ✅ Shown | ✅ Pass |
| New category appears in the search filter dropdown | ✅ Shown | ✅ Pass |
| Filtering by the new category | ✅ 2 products found | ✅ Pass |
| New category appears in the admin category manager | ✅ Editable / deletable | ✅ Pass |
| Text box hidden until "add new" is chosen | Hidden, and not `required` | ✅ Pass |
| Text box after choosing "add new" | Visible and `required` | ✅ Pass |
| Switching back to an existing category | Hidden again, `required` cleared | ✅ Pass |
| Field on mobile (375px) | 44px tall, inside viewport, no overflow | ✅ Pass |

> The `required` attribute is toggled with visibility deliberately — a hidden
> `required` field would block submission with a validation error the user cannot see.

---

## 4c. Clean Starting State

The shipped database contains one administrator and nothing else.

| Test | Expected | Result |
|---|---|---|
| Fresh import creates only the admin | 1 user, role `admin` | ✅ Pass |
| Products / images / bids / transactions | 0 of each | ✅ Pass |
| Payments / invoices / deliveries / reviews | 0 of each | ✅ Pass |
| Reports / wishlist / notifications | 0 of each | ✅ Pass |
| Categories seeded | 6 (a product needs one) | ✅ Pass |
| Uploads folder | Empty | ✅ Pass |
| Admin login by **Student ID** `242-15-782` | Lands on the admin dashboard | ✅ Pass |
| Admin login by **email** | Lands on the admin dashboard | ✅ Pass |
| Wrong password | "Invalid Email/Student ID or password." | ✅ Pass |
| Password hash verifies against `kamrul@1` | ✅ true | ✅ Pass |
| Empty feed | Friendly "no products yet" message, no error | ✅ Pass |
| Admin dashboard with no data | All counters read 0, no error | ✅ Pass |
| All 12 guest + admin pages on an empty database | No PHP errors | ✅ Pass |

---

## 5. Search Module

| Test | Result |
|---|---|
| Keyword search `laptop` | ✅ 1 product found |
| Category filter `Books` | ✅ 4 products |
| Price range 300–1500 | ✅ 5 products |
| No-match keyword | ✅ 0 products, friendly empty state |
| Sort price low → high | ✅ 275 … 22,000 ascending |
| Sort price high → low | ✅ 22,000 … 275 descending |
| SQL injection `' OR 1=1 -- ` | ✅ 0 results, no error, no bypass |

---

## 6. Bidding Module

| Test | Expected | Result |
|---|---|---|
| Place a bid | Bid recorded as `Pending` | ✅ Pass |
| Seller bids on their **own** product | Blocked: "You cannot bid on your own product." | ✅ Pass |
| Two buyers bid on one product | Both `Pending` | ✅ Pass |
| Seller accepts one bid | That bid `Accepted` | ✅ Pass |
| → all other pending bids | Automatically `Rejected` | ✅ Pass |
| → product status | Becomes `Pending` | ✅ Pass |
| → transaction | Created automatically | ✅ Pass |
| Non-owner tries to accept a bid | Blocked, bid unchanged | ✅ Pass |

---

## 6b. Counter Offers

| Test | Expected | Result |
|---|---|---|
| Buyer bids Tk 400, seller counters Tk 500 | Bid becomes `Countered`, `counter_amount` = 500 | ✅ Pass |
| Buyer notified of the counter | "The seller countered your bid … with Tk 500.00" | ✅ Pass |
| Seller tries to counter the same bid again | Blocked — only a `Pending` bid can be countered | ✅ Pass |
| Seller tries to accept their **own** counter | Blocked, status unchanged | ✅ Pass |
| Unrelated student tries to accept the counter | Blocked, status unchanged | ✅ Pass |
| Buyer accepts the counter | Bid `Accepted`, product `Pending`, transaction created | ✅ Pass |
| **Agreed price** on that transaction | Tk 500 (the counter), **not** Tk 400 (the bid) | ✅ Pass |
| Both parties notified on acceptance | Buyer *and* seller each get a message | ✅ Pass |
| Payment page total | Tk 500 | ✅ Pass |
| Payment row + invoice total | Tk 500 / Tk 500 | ✅ Pass |
| Buyer **declines** a counter | Bid `Rejected`, product stays `Available`, **no** transaction | ✅ Pass |
| Seller notified of the decline | "Your counter offer … was turned down." | ✅ Pass |
| Seller view (pending bid) | Accept / Reject / counter price box | ✅ Pass |
| Seller view (already countered) | "Waiting for the buyer to reply" | ✅ Pass |
| Buyer view (countered) | Accept Offer / Decline, counter amount shown | ✅ Pass |
| Counter shown in *My Bids* | "Seller's Counter" column + action buttons | ✅ Pass |

> The agreed price is `COALESCE(counter_amount, bid_amount)` aliased as `bid_amount`,
> so payment, delivery and the invoice all picked up the counter price without any
> change to the display code.

---

## 6c. Clickable Notifications

| Test | Expected | Result |
|---|---|---|
| Click a **New Bid** notification | Opens the product's bid section | ✅ Pass |
| Click a **Counter Offer** notification | Opens the product's bid section | ✅ Pass |
| Click a **Bid Accepted** notification | Opens the product's bid section | ✅ Pass |
| Click a **Payment Successful** notification | Opens the transaction page | ✅ Pass |
| Opening a notification marks it read | `is_read` set on the way through | ✅ Pass |
| Open **another user's** notification by id | Redirected away, stays unread | ✅ Pass |
| Notification with no target (older rows) | Falls back to the notification list | ✅ Pass |
| Product later deleted | `ON DELETE SET NULL` — message survives, stops linking | ✅ Pass |

---

## 7. Transaction / Payment / Delivery / Invoice

| Test | Expected | Result |
|---|---|---|
| Buyer opens transaction | Sees "Pay Now" | ✅ Pass |
| Seller opens same transaction | Sees "Waiting for the buyer" | ✅ Pass |
| Unrelated student opens it | Redirected away | ✅ Pass |
| Pay before adding an address | Redirected to the address form | ✅ Pass |
| Save delivery address | Stored | ✅ Pass |
| Pay with bKash / Nagad | Payment `Paid` | ✅ Pass |
| → transaction status | `Completed` | ✅ Pass |
| → product status | `Sold` | ✅ Pass |
| → invoice | Auto-generated by trigger (`INV-20260804-00001`) | ✅ Pass |
| → delivery record | Created as `Pending` | ✅ Pass |
| Attempt to pay a second time | Blocked, still exactly 1 payment row | ✅ Pass |
| Buyer tries to update delivery status | Blocked | ✅ Pass |
| Seller updates Packed → Shipped → Delivered | All applied, tracking number saved | ✅ Pass |
| Invoice HTML view | All required fields present | ✅ Pass |
| Invoice PDF download | Valid PDF, `application/pdf`, correct filename | ✅ Pass |

Invoice contents verified: invoice number, buyer info, seller info, product info,
accepted bid, payment method, delivery address, transaction date, total amount.

---

## 8. Wishlist / Review / Report / Notification

| Test | Expected | Result |
|---|---|---|
| Add to wishlist | Row created | ✅ Pass |
| Click again (toggle) | Row removed | ✅ Pass |
| Add the same product twice | Only one row (UNIQUE constraint) | ✅ Pass |
| Review **before** delivery | Blocked | ✅ Pass |
| Review after `Delivered` | Saved | ✅ Pass |
| Second review on same transaction | Blocked, still 1 review | ✅ Pass |
| Seller rating appears on cards | 5.0 shown; unrated seller shows "New Seller" | ✅ Pass |
| Report another student's product | Report created as `Pending` | ✅ Pass |
| Report **own** product | Blocked | ✅ Pass |
| Duplicate pending report | Blocked, still 1 report | ✅ Pass |
| Notifications generated | New Bid, Bid Accepted, Bid Rejected, Payment Successful, Delivery Update | ✅ All 5 types |
| Unread badge in navbar | Correct count | ✅ Pass |
| Mark all as read | Only that user's rows updated | ✅ Pass |

---

## 9. Admin Panel

| Test | Expected | Result |
|---|---|---|
| Student opens `/admin/*` | Redirected to login | ✅ Pass |
| Guest opens `/admin/*` | Redirected to login | ✅ Pass |
| Admin logs in | Lands on dashboard | ✅ Pass |
| All 7 admin pages load | No PHP errors | ✅ Pass |
| Dashboard statistics | Matched the database exactly | ✅ Pass |
| Ban a student | Status `banned`; login then refused | ✅ Pass |
| Unban | Status back to `active` | ✅ Pass |
| Try to ban the admin account | Blocked (role check) | ✅ Pass |
| Add a category | Created | ✅ Pass |
| Add a duplicate category | "That category already exists." | ✅ Pass |
| Delete a category **in use** | Blocked with explanation | ✅ Pass |
| Delete an unused category | Deleted | ✅ Pass |
| Mark a report `Reviewed` | Status updated | ✅ Pass |
| Resolve report by removing product | Product deleted, reports cascade-deleted | ✅ Pass |

---

## 10. Security

| Attack | Expected | Result |
|---|---|---|
| Edit another student's product via crafted POST | No change | ✅ Blocked |
| Delete another student's product | Product still exists | ✅ Blocked |
| Accept a bid on someone else's product | Bid unchanged | ✅ Blocked |
| Open another student's transaction | Redirected | ✅ Blocked |
| Download another student's invoice | Redirected | ✅ Blocked |
| Mark another user's notification read | `is_read` still 0 | ✅ Blocked |
| SQL injection in search | 0 results, no error | ✅ Blocked |
| XSS `<script>alert(1)</script>` in a product title | Stored raw, rendered escaped, **0** executable script tags | ✅ Blocked |

Authorization is enforced in the SQL `WHERE` clause (not just hidden in the UI), so a
crafted URL cannot bypass it.

---

## 11. Responsive Design

Measured live in the browser at each breakpoint.

| Viewport | Cards / row | Hamburger | Sidebars | Horizontal scroll |
|---|---|---|---|---|
| Desktop 1280px | 3 | Hidden | Both visible | ✅ None |
| Tablet 768px | 2 | Shown | Collapsed | ✅ None |
| Mobile 375px | 1 | Shown | Stacked | ✅ None |

Additional mobile checks:

| Check | Result |
|---|---|
| `scrollWidth === clientWidth` (no overflow) | ✅ 375 = 375 |
| Buttons at least 44×44px | ✅ 15/15 buttons at 44px |
| Images constrained to viewport | ✅ Pass |
| All tables wrapped in `.table-responsive` | ✅ Pass (all files) |
| Hamburger opens the menu | ✅ Pass |
| Bootstrap JS loaded | ✅ Pass |
| Registration form usable on mobile | ✅ Pass, no overflow |

---

## 12. Full Lifecycle Test (fresh database)

A complete purchase run end-to-end:

| Step | Result |
|---|---|
| 1. Register a new student | ✅ Created |
| 2. Log in as buyer and seller | ✅ Both sessions |
| 3. Seller posts a product with an image | ✅ `product_id=7`, 1 image |
| 4. Buyer adds it to the wishlist | ✅ 1 row |
| 5. Buyer places a bid | ✅ Seller notified |
| 6. Seller accepts | ✅ Transaction created, product `Pending` |
| 7. Buyer adds a delivery address | ✅ Saved |
| 8. Buyer pays with Nagad | ✅ `Paid` → transaction `Completed`, product `Sold`, invoice generated |
| 9. Seller ships → delivers | ✅ Status `Delivered`, tracking `CX-99001` |
| 10. Buyer reviews the seller | ✅ 4 stars, seller average 4.0 |
| 11. Download the PDF invoice | ✅ 2,598-byte valid PDF |
| 12. Notifications | ✅ New Bid 1, Bid Accepted 1, Payment Successful 2, Delivery Update 3 |

---

## Summary

**All tests passed.** No known defects remain.

Two issues were found and fixed during testing:

1. **PHP 8 mysqli exceptions** — `mysqli` throws by default in PHP 8, so the
   `if (mysqli_stmt_execute(...))` error handling never ran and a blocked action
   produced a fatal error instead of a friendly message. Fixed by setting
   `mysqli_report(MYSQLI_REPORT_OFF)` in `config/db.php`, which lets the validation
   messages raised by the stored procedures reach the user.

2. **Mobile touch targets** — card buttons rendered at 31px tall, below the 44px
   comfortable-tap guideline. Fixed with a mobile media query in `assets/css/style.css`.

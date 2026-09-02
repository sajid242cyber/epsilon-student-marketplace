# Installation Guide — Epsilon

A step-by-step walkthrough for getting the project running in XAMPP.
Total time: about 5 minutes.

---

## Step 1 — Install XAMPP

If you don't already have it, download XAMPP from
<https://www.apachefriends.org> and install it (the default options are fine).

You need **PHP 8.0 or newer**. To check, open the XAMPP Control Panel → *Shell* and run:

```bash
php -v
```

---

## Step 2 — Copy the project into htdocs

Place the whole `PROJECT12` folder inside your XAMPP `htdocs` directory.

**Windows**
```
C:\xampp\htdocs\PROJECT12
```

**macOS**
```
/Applications/XAMPP/htdocs/PROJECT12
```

**Linux**
```
/opt/lampp/htdocs/PROJECT12
```

When you're done, the path `C:\xampp\htdocs\PROJECT12\index.php` should exist.

---

## Step 3 — Start Apache and MySQL

Open the **XAMPP Control Panel** and click **Start** next to:

- **Apache**
- **MySQL**

Both should turn green. If Apache refuses to start, see
[Port 80 is already in use](#port-80-is-already-in-use) below.

---

## Step 4 — Import the database

1. Open <http://localhost/phpmyadmin> in your browser.
2. Click the **Import** tab at the top.
3. Click **Choose File** and select:
   ```
   C:\xampp\htdocs\PROJECT12\database\database.sql
   ```
4. Leave every setting at its default and click **Go**.

You should see a green *"Import has been successfully finished"* message, and
`epsilon_db` will appear in the left-hand list with 14 tables.

> This one file does everything: creates the database, all tables, foreign keys,
> constraints, indexes, views, stored procedures, triggers, and the demo data.
> **You never need to edit any SQL by hand.**

### Importing from the command line instead (optional)

```bash
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\PROJECT12\database\database.sql
```

---

## Step 5 — Open the website

Go to:

<http://localhost/PROJECT12>

You should see the marketplace home page. It starts empty — no products have been
posted yet.

---

## Step 6 — Log in and try it

| Role | Email | Student ID | Password |
|---|---|---|---|
| Admin | `admin@epsilon.edu` | `242-15-782` | `kamrul@1` |

You can log in with **either** the email **or** the Student ID.

Logging in as the admin takes you straight to the admin dashboard.

### A quick end-to-end tour

The database starts with only the admin, so create two students first.

1. **Register two accounts** from the registration page — one will sell, one will buy.
2. As the **seller**, post a product with a photo.
3. As the **buyer**, open that product and **place a bid**.
4. As the **seller**, **accept** the bid — or **counter** it with your own price, and
   let the buyer accept that instead. Either way a transaction is created and every
   other open bid on that product is rejected automatically.
5. As the **buyer** → *My Transactions* → add a **delivery address**, then
   **Confirm Order** (Cash on Delivery — nothing is charged yet).
6. As the **seller**, add a **pickup point** so the buyer knows where to collect it,
   then move the delivery through *Packed → Shipped → Delivered*. Marking it
   Delivered records the cash as received and generates the invoice.
7. As the **buyer**, leave a **review** and download the **PDF invoice**.

Check the notification bell at each step — every event creates a notification.

---

## Configuration (only if needed)

### Different folder name

The project assumes it is at `htdocs/PROJECT12`. If you renamed the folder, open
`config/config.php` and change one line:

```php
define('BASE_URL', '/YOUR_FOLDER_NAME');
```

### MySQL username or password

Default XAMPP uses `root` with no password. If yours differs, open `config/db.php`:

```php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';              // put your password here
$DB_NAME = 'epsilon_db';
```

### Running on a different port

If Apache runs on port 8080, use:

```
http://localhost:8080/PROJECT12
```

No configuration change is required for this.

---

## Troubleshooting

### "Database connection failed"
MySQL is not running, or the credentials in `config/db.php` are wrong.
Start MySQL from the XAMPP Control Panel.

### "Table 'epsilon_db.user' doesn't exist"
The SQL file was not imported, or the import failed partway.
Re-do [Step 4](#step-4--import-the-database).

### The page loads but has no styling, and links give 404
`BASE_URL` doesn't match your folder name. See
[Different folder name](#different-folder-name).

### Port 80 is already in use
Another program (commonly Skype, IIS, or Windows' World Wide Web Publishing Service)
is holding port 80. Either close it, or change Apache's port:

1. XAMPP Control Panel → Apache → **Config** → `httpd.conf`
2. Change `Listen 80` to `Listen 8080` and `ServerName localhost:80` to `localhost:8080`
3. Restart Apache and browse to `http://localhost:8080/PROJECT12`

### Images won't upload
Confirm the folder `assets/uploads/products/` exists and is writable.
For large photos, also raise these in `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 12M
```

Then restart Apache. (The project itself limits uploads to 3 MB per image, 5 images.)

### PDF invoice download does nothing
The FPDF library must be present at `includes/fpdf/fpdf.php`. It ships with the
project — if it's missing, re-copy the project folder.

### I want to reset all the data
Re-import `database/database.sql`. It begins with `DROP DATABASE IF EXISTS`, so it
wipes everything and rebuilds a clean database with the demo data.

You may also want to clear old uploaded photos:

```
C:\xampp\htdocs\PROJECT12\assets\uploads\products\
```

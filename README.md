# Epsilon — Student Marketplace

<p align="center">
  <strong>A second-hand book & gadget marketplace built exclusively for university students.</strong>
</p>

<p align="center">
  <a href="https://epsilonstudentmarket.free.nf/">🌐 Live Website</a>
  &nbsp;•&nbsp;
  <a href="https://github.com/sajid242cyber/epsilon-student-marketplace">💻 GitHub Repository</a>
</p>

---

## 📌 About the Project

**Epsilon** is a student-focused marketplace designed to make it easier for university students to buy and sell second-hand books, laptops, phones, calculators, accessories, and other useful items.

Every registered student can act as **both a buyer and a seller**. Guests can browse listings freely, while authentication is required for actions that modify data.

This project was developed as a **Database Management Systems (DBMS) semester project** by a team of five students.

---

## ✨ Key Features

### 👀 Guest Users
- Browse the marketplace feed
- Search products by keyword
- Filter by category and price range
- View complete product details
- Browse without creating an account

### 🎓 Registered Students
- Register and log in using email or Student ID
- Post, edit, and delete products
- Upload multiple product images
- Create a new category while posting
- Place bids on other students' products
- Accept, reject, or counter bids
- Accept or decline counter offers
- Pay using **bKash, Nagad, Rocket, or Bank Transfer**
- Add delivery addresses
- Track delivery status
- Download or print invoices
- Add products to a wishlist
- Review and rate sellers after delivery
- Report suspicious listings
- Receive in-app notifications

### 🛡️ Admin
- Dashboard with live statistics
- Manage users and ban/unban accounts
- Manage products
- Manage categories
- Moderate reported listings
- View transactions and payments

---

## 🛠️ Technology Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Bootstrap 5.3.3, JavaScript |
| Backend | PHP 8, procedural PHP, `mysqli` |
| Database | MySQL / MariaDB |
| Local Development | XAMPP |
| PDF Generation | FPDF 1.86 |
| Icons | Bootstrap Icons 1.11.3 |

Bootstrap and Bootstrap Icons are self-hosted inside the project, allowing the application to run without relying on external CDN resources.

---

# 👥 Meet Our Team

We are **Team Beta**, a five-member student team that collaboratively designed and developed Epsilon.

<div align="center">

<table>
<tr>
<td align="center" width="20%">
<img src="kamrul-hasan-kabir.png" width="150" height="150" alt="Kamrul Hasan Kabir"><br>
<b>Kamrul Hasan Kabir</b><br>
<sub>242-15-782</sub>
</td>

<td align="center" width="20%">
<img src="prethila-bepari.png" width="150" height="150" alt="Prethila Bepari"><br>
<b>Prethila Bepari</b><br>
<sub>242-15-472</sub>
</td>

<td align="center" width="20%">
<img src="adib-mahamud-sajid.png" width="150" height="150" alt="Adib Mahamud Sajid"><br>
<b>Adib Mahamud Sajid</b><br>
<sub>242-15-137</sub>
</td>

<td align="center" width="20%">
<img src="shutopa-kundu.png" width="150" height="150" alt="Shutopa Kundu"><br>
<b>Shutopa Kundu</b><br>
<sub>242-15-091</sub>
</td>

<td align="center" width="20%">
<img src="ashraful-haque.png" width="150" height="150" alt="Ashraful Haque"><br>
<b>Ashraful Haque</b><br>
<sub>242-15-025</sub>
</td>
</tr>
</table>

</div>

### 🎓 Academic Information

- **Course:** Database Management Systems
- **Department:** Computer Science and Engineering
- **Institution:** Daffodil International University
- **Course Teacher:** Lamia Rukhsara, Lecturer
- **Team:** Team Beta

---

## 🗄️ Database Design

The project uses a relational database designed around the marketplace workflow.

### Database Summary

| Object | Count |
|---|---:|
| Tables | 14 |
| Views | 2 |
| Stored Procedures | 4 |
| Triggers | 4 |
| Foreign Keys | 23 |
| Indexes | 43 |

### Main Tables

`User`, `Category`, `Product`, `ProductImage`, `Bid`, `Transaction`, `Payment`, `DeliveryAddress`, `Delivery`, `Wishlist`, `Review`, `Report`, `Notification`, `Invoice`

### Stored Procedures

- `sp_place_bid` — validates and creates a bid
- `sp_accept_bid` — accepts a bid, rejects competing open bids, updates the product, and creates a transaction
- `sp_reject_bid` — rejects a pending bid
- Counter-offer workflow support

### Triggers

- `trg_bid_after_insert`
- `trg_bid_after_update`
- `trg_payment_after_update`
- `trg_delivery_after_update`

The database design follows **Third Normal Form (3NF)** principles, with related data separated into appropriate tables and relationships enforced through foreign keys and constraints.

---

## 🔄 Marketplace Workflow

```text
Register
   ↓
Browse / Search Products
   ↓
Place a Bid
   ↓
Seller Accepts / Rejects / Counters
   ↓
Transaction Created
   ↓
Buyer Adds Delivery Address
   ↓
Payment
   ↓
Invoice Generated
   ↓
Delivery
   ↓
Review & Rating
```

A product normally moves through:

```text
Available → Pending → Sold
```

---

## 🔐 Security

The project includes several application-level security measures:

- Passwords are stored using `password_hash()` / bcrypt
- Password verification uses `password_verify()`
- SQL queries use prepared statements where applicable
- Sort options and status values are validated against whitelists
- User-supplied output is escaped with `htmlspecialchars()`
- Ownership is checked in SQL queries
- Product image uploads use extension, size, and real-image validation
- Authentication and admin access are protected by session-based checks

---

## 📱 Responsive Design

Epsilon uses a responsive Bootstrap grid rather than a separate mobile website.

| Device | Layout |
|---|---|
| Desktop | Full navbar, sidebars, multi-column product feed |
| Tablet | Responsive navigation and product grid |
| Mobile | Hamburger navigation and single-column feed |

---

## 📂 Project Structure

```text
PROJECT12/
│
├── index.php
├── product.php
├── search.php
│
├── assets/
│   ├── bootstrap/
│   ├── icons/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── uploads/products/
│
├── config/
│   ├── config.php
│   └── db.php
│
├── database/
│   ├── database.sql
│   ├── DATABASE.md
│   ├── ER_Diagram.pdf
│   └── ER_DIAGRAM.md
│
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   ├── functions.php
│   ├── product_card.php
│   └── fpdf/
│
├── auth/
│   ├── register.php
│   ├── register_process.php
│   ├── login.php
│   ├── login_process.php
│   └── logout.php
│
├── user/
│   ├── product/
│   ├── bid/
│   ├── transaction/
│   ├── payment/
│   ├── delivery/
│   ├── invoice/
│   ├── wishlist/
│   ├── review/
│   ├── report/
│   └── notification/
│
└── admin/
    ├── users/
    ├── products/
    ├── categories/
    ├── reports/
    ├── transactions/
    └── payments/
```

---

## 🚀 Run Locally

### Requirements

- XAMPP
- Apache
- MySQL / MariaDB
- PHP 8.0 or newer

### Setup

1. Clone the repository:

```bash
git clone https://github.com/sajid242cyber/epsilon-student-marketplace.git
```

2. Place the project inside:

```text
C:\xampp\htdocs\PROJECT12
```

3. Start **Apache** and **MySQL** from XAMPP.

4. Open phpMyAdmin and import:

```text
PROJECT12/database/database.sql
```

5. Configure the database connection in:

```text
PROJECT12/config/db.php
```

6. Open:

```text
http://localhost/PROJECT12
```

For the complete installation guide, see [`INSTALLATION.md`](INSTALLATION.md).

---

## 📚 Documentation

| Document | Description |
|---|---|
| [`INSTALLATION.md`](INSTALLATION.md) | Complete local installation guide |
| [`FOLDER_STRUCTURE.md`](FOLDER_STRUCTURE.md) | Project folders and request flow |
| [`database/DATABASE.md`](database/DATABASE.md) | Database and table documentation |
| [`database/ER_Diagram.pdf`](database/ER_Diagram.pdf) | Printable ER diagram |
| [`database/ER_DIAGRAM.md`](database/ER_DIAGRAM.md) | Online Mermaid ER diagram |
| [`TESTING.md`](TESTING.md) | Testing report |

---

## 🌐 Live Demo

**Live Website:**  
https://epsilonstudentmarket.free.nf/

**GitHub Repository:**  
https://github.com/sajid242cyber/epsilon-student-marketplace

---

## 🙏 Acknowledgement

This project was developed as part of our **Database Management Systems** course at **Daffodil International University**.

Special thanks to our course teacher **Lamia Rukhsara, Lecturer**, for her guidance and support throughout the project.

---

## ⭐ Support

If you find this project useful or interesting, consider giving the repository a ⭐ on GitHub.

---

<p align="center">
  <b>Built with teamwork, database design, and a lot of debugging. ❤️</b>
</p>

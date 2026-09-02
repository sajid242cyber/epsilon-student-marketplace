# Deployment Checklist

## Before GitHub
- Remove local secrets and development-only files.
- Never commit `.env`.
- Replace the default admin password before production deployment.
- Review `config/db.php` and move credentials to environment variables.
- Disable PHP error display in production.

## Hosting
- Create a PHP 8+ hosting account with MySQL/MariaDB.
- Create a production database and database user.
- Import the schema/data from `database/database.sql` after removing or adapting database CREATE/DROP statements if the host requires it.
- Upload the application to the web root.
- Configure production database credentials and `BASE_URL`.
- Ensure `assets/uploads/products` and `assets/uploads/profiles` are writable by PHP.
- Enable HTTPS/SSL.

## Post-deployment test
- Registration/login/logout
- Product create/edit/delete and image upload
- Search/category/price filters
- Bidding and counter offers
- Transactions, payment records and delivery address
- Invoice generation
- Wishlist, notifications and reviews
- Admin functions

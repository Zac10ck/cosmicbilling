# Inventory V2 deployment (XAMPP)

## Before you begin

1. In phpMyAdmin, export a fresh backup of the `cosmic_billing` database.
2. Copy the existing `C:\xampp\htdocs\xamp-cosmic` folder to a safe backup location.
3. Do not delete the current `config\database.php` without noting its database password and company details.

## Upgrade the database

1. Open phpMyAdmin and select `cosmic_billing`.
2. Choose **Import**.
3. Import `migrations/002_inventory_v2.sql` from this application.
4. Confirm that the new `stock_movements` table appears.

The SQL can also be downloaded from the **Inventory** page while signed in as Admin.

## Deploy the application

1. Replace the application code with Version 2, preserving the office PC's `config\database.php` values.
2. Ensure Apache can write to `uploads\products`. On normal XAMPP installations this works automatically.
3. Restart Apache, sign in, and open **Inventory**.
4. Enter the real starting balance for each product using **Add stock** or the Admin correction screen.

## Mobile access on office Wi-Fi

1. Run `ipconfig` on the office PC and note its IPv4 address, for example `192.168.1.20`.
2. Allow Apache through Windows Defender Firewall for **Private networks** only.
3. Connect the Android phone to the same Wi-Fi.
4. Open `http://192.168.1.20/xamp-cosmic/` in Chrome.

Billing and manual stock entry work over local HTTP. Live camera scanning and installation as a PWA require a secure HTTPS connection on non-localhost addresses. Configure Apache HTTPS with a certificate trusted by the Android phones before relying on live scanning. Until then, the Scan screen provides manual barcode entry. Do not expose the XAMPP PC directly to the public internet.

## Role rules

- **Staff:** view inventory/history, add products, add stock, and create invoices.
- **Admin:** all staff actions plus edit/deactivate products and correct stock balances.
- No stock history entry can be edited or deleted from the application.

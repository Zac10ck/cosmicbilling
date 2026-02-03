# COSMIC SURGICALS - GST Billing & Invoice Management System

A complete GST-compliant billing and invoice management system built with PHP and MySQL, designed for XAMPP deployment on Windows.

## Features

- **Invoice Management** - Create, save, search, and print GST-compliant invoices
- **Customer Management** - Maintain customer database with GSTIN and state codes
- **Product Master** - Pre-defined products with HSN codes and GST rates
- **GST Compliance** - Supports CGST/SGST (intra-state) and IGST (inter-state)
- **Multiple GST Rates** - Per-item GST rates (0%, 5%, 12%, 18%, 28%)
- **Tax Modes** - Tax Inclusive and Tax Exclusive pricing
- **Reports** - Daily and monthly sales reports (admin only)
- **User Roles** - Admin (full access) and Staff (limited access)
- **Professional Print** - Beautiful invoice printouts with all GST details

## Requirements

- XAMPP (or any Apache + MySQL + PHP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

## Installation

### Step 1: Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP on your Windows machine
3. Start Apache and MySQL from XAMPP Control Panel

### Step 2: Download the Application

**Option A: Clone from GitHub**
```bash
cd C:\xampp\htdocs
git clone https://github.com/Zac10ck/cosmicbilling.git xamp-cosmic
```

**Option B: Download ZIP**
1. Download the ZIP file from GitHub
2. Extract to `C:\xampp\htdocs\xamp-cosmic`

### Step 3: Run the Installer

1. Open your browser and navigate to:
   ```
   http://localhost/xamp-cosmic/install.php
   ```

2. Enter your MySQL root password (leave empty if no password is set)

3. Click "Connect & Install"

4. The installer will:
   - Create the `cosmic_billing` database
   - Create all required tables
   - Add default admin and staff users
   - Add sample products

### Step 4: Login

Navigate to `http://localhost/xamp-cosmic/` and login with:

| Role  | Username | Password   |
|-------|----------|------------|
| Admin | admin    | admin123   |
| Staff | staff    | staff123   |

## Usage

### Creating an Invoice

1. Click "Invoices" > "+ New Invoice"
2. Enter customer details or search existing customers
3. Add items - search from products or type custom items
4. Each item can have its own GST rate
5. Toggle between CGST+SGST (Kerala) or IGST (other states)
6. Click "Save & Print" to save and open print preview

### Managing Customers

1. Click "Customers" in the navigation
2. Add new customers with name, phone, GSTIN, and state
3. Customers can be searched during invoice creation

### Managing Products

1. Click "Products" in the navigation
2. Add products with name, HSN code, GST rate, and MRP
3. Products appear in autocomplete during invoice creation

### Reports (Admin Only)

1. Click "Reports" in the navigation
2. View daily sales reports by date
3. View monthly sales summaries

## File Structure

```
xamp-cosmic/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── header.php            # Common header with navigation
│   ├── footer.php            # Common footer
│   ├── auth.php              # Authentication functions
│   └── functions.php         # Helper functions
├── assets/
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   └── js/
│       └── app.js            # Common JavaScript
├── modules/
│   ├── auth/                 # Login/Logout
│   ├── dashboard/            # Admin dashboard
│   ├── invoices/             # Invoice management
│   ├── customers/            # Customer management
│   ├── products/             # Product management
│   ├── reports/              # Sales reports
│   └── users/                # User management
├── index.php                 # Entry point
├── install.php               # Database installer
└── README.md                 # This file
```

## Company Configuration

Edit `config/database.php` to update company details:

```php
$company = [
    'name' => 'YOUR COMPANY NAME',
    'address' => 'Your Address Line 1',
    'city' => 'City, State - PIN',
    'phone' => 'Phone Numbers',
    'email' => 'email@example.com',
    'website' => 'www.yourwebsite.com',
    'gstin' => 'YOUR_GSTIN_NUMBER',
    'state_code' => '32',  // Your state code
    'bank_name' => 'Your Bank Name',
    'bank_account' => 'Account Number',
    'bank_ifsc' => 'IFSC Code'
];
```

## Default Users

| Username | Password   | Role  | Access                          |
|----------|------------|-------|---------------------------------|
| admin    | admin123   | Admin | Full access including reports   |
| staff    | staff123   | Staff | Invoices, customers, products   |

**Important:** Change default passwords after first login!

## GST Features

- **CGST + SGST**: Applied for sales within the same state (Kerala - 32)
- **IGST**: Applied for inter-state sales
- **HSN Codes**: Support for Harmonized System of Nomenclature codes
- **Multiple Rates**: 0%, 5%, 12%, 18%, 28% per item
- **Tax Summary**: Grouped by GST rate on invoice printout
- **Reverse Charge**: Indicated on invoice (default: No)

## Troubleshooting

### MySQL Connection Error
- Ensure MySQL is running in XAMPP Control Panel
- Check if the password in `config/database.php` matches your MySQL root password
- Try running `install.php` again with the correct password

### Blank Page or PHP Errors
- Enable error display: Add to top of PHP file:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Check Apache error logs in `C:\xampp\apache\logs\error.log`

### Invoice Not Saving
- Check browser console for JavaScript errors (F12)
- Ensure all required fields are filled
- Check PHP error logs

## License

This project is proprietary software for COSMIC SURGICALS.

## Support

For support, contact: cosmicsurgical@gmail.com

<?php
/**
 * Helper Functions
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Escape HTML output
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency (Indian Rupees)
 */
function formatCurrency($amount) {
    return number_format((float)$amount, 2);
}

/** Format stock without unnecessary trailing zeroes. */
function formatStock($amount) {
    $formatted = number_format((float)$amount, 2, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('d-M-Y', strtotime($date));
}

/**
 * Generate next invoice number
 */
function generateInvoiceNumber() {
    $db = getDB();

    // Format: CS-YYMM-XXXX (e.g., CS-2602-0001)
    $prefix = 'CS-' . date('ym') . '-';

    $stmt = $db->prepare("SELECT invoice_number FROM invoices WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();

    if ($last) {
        $lastNum = (int)substr($last, -4);
        $nextNum = $lastNum + 1;
    } else {
        $nextNum = 1;
    }

    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

/**
 * Convert number to words (Indian format)
 */
function numberToWords($number) {
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
             'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
             'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $number = round($number, 2);
    $rupees = floor($number);
    $paise = round(($number - $rupees) * 100);

    if ($rupees == 0) {
        $rupeesWords = 'Zero';
    } else {
        $rupeesWords = convertToWords($rupees, $ones, $tens);
    }

    $result = 'Rupees ' . $rupeesWords;

    if ($paise > 0) {
        $result .= ' and ' . convertToWords($paise, $ones, $tens) . ' Paise';
    }

    return $result . ' Only';
}

function convertToWords($num, $ones, $tens) {
    if ($num < 20) {
        return $ones[$num];
    }

    if ($num < 100) {
        return $tens[floor($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
    }

    if ($num < 1000) {
        return $ones[floor($num / 100)] . ' Hundred' . ($num % 100 ? ' ' . convertToWords($num % 100, $ones, $tens) : '');
    }

    if ($num < 100000) {
        return convertToWords(floor($num / 1000), $ones, $tens) . ' Thousand' . ($num % 1000 ? ' ' . convertToWords($num % 1000, $ones, $tens) : '');
    }

    if ($num < 10000000) {
        return convertToWords(floor($num / 100000), $ones, $tens) . ' Lakh' . ($num % 100000 ? ' ' . convertToWords($num % 100000, $ones, $tens) : '');
    }

    return convertToWords(floor($num / 10000000), $ones, $tens) . ' Crore' . ($num % 10000000 ? ' ' . convertToWords($num % 10000000, $ones, $tens) : '');
}

/**
 * Get all active products
 */
function getProducts($search = '') {
    $db = getDB();
    $sql = "SELECT * FROM products WHERE active = 1";
    $params = [];

    if ($search) {
        $sql .= " AND (name LIKE ? OR hsn_code LIKE ? OR barcode LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get all active customers
 */
function getCustomers($search = '') {
    $db = getDB();
    $sql = "SELECT * FROM customers WHERE active = 1";
    $params = [];

    if ($search) {
        $sql .= " AND (name LIKE ? OR phone LIKE ? OR gstin LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get product by ID
 */
function getProduct($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get customer by ID
 */
function getCustomer($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get invoice by ID with items
 */
function getInvoice($id) {
    $db = getDB();

    $stmt = $db->prepare("SELECT i.*, u.name as created_by_name FROM invoices i LEFT JOIN users u ON i.created_by = u.id WHERE i.id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();

    if ($invoice) {
        $stmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        $invoice['items'] = $stmt->fetchAll();
    }

    return $invoice;
}

/**
 * Indian state codes
 */
function getStateCodes() {
    return [
        '01' => 'Jammu & Kashmir',
        '02' => 'Himachal Pradesh',
        '03' => 'Punjab',
        '04' => 'Chandigarh',
        '05' => 'Uttarakhand',
        '06' => 'Haryana',
        '07' => 'Delhi',
        '08' => 'Rajasthan',
        '09' => 'Uttar Pradesh',
        '10' => 'Bihar',
        '11' => 'Sikkim',
        '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland',
        '14' => 'Manipur',
        '15' => 'Mizoram',
        '16' => 'Tripura',
        '17' => 'Meghalaya',
        '18' => 'Assam',
        '19' => 'West Bengal',
        '20' => 'Jharkhand',
        '21' => 'Odisha',
        '22' => 'Chhattisgarh',
        '23' => 'Madhya Pradesh',
        '24' => 'Gujarat',
        '26' => 'Dadra & Nagar Haveli and Daman & Diu',
        '27' => 'Maharashtra',
        '29' => 'Karnataka',
        '30' => 'Goa',
        '31' => 'Lakshadweep',
        '32' => 'Kerala',
        '33' => 'Tamil Nadu',
        '34' => 'Puducherry',
        '35' => 'Andaman & Nicobar',
        '36' => 'Telangana',
        '37' => 'Andhra Pradesh',
        '38' => 'Ladakh'
    ];
}

/**
 * Show flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        setFlash($type, $message);
    }
    header("Location: $url");
    exit;
}

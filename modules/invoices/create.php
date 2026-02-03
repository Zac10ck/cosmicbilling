<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/auth.php';

// Check login
if (!isLoggedIn()) {
    header('Location: /xamp-cosmic/modules/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Get next invoice number
$db = getDB();
$prefix = 'CS-' . date('ym') . '-';
$stmt = $db->prepare("SELECT invoice_number FROM invoices WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$prefix . '%']);
$last = $stmt->fetchColumn();
$nextNum = $last ? ((int)substr($last, -4) + 1) : 1;
$nextInvoiceNumber = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

// Get products for autocomplete
$products = $db->query("SELECT * FROM products WHERE active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get customers for autocomplete
$customers = $db->query("SELECT * FROM customers WHERE active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice - COSMIC SURGICALS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        .app-container { max-width: 1000px; margin: 0 auto; }
        .app-header { text-align: center; color: white; margin-bottom: 30px; }
        .app-header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #f5af19, #f12711);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .app-header p { color: rgba(255,255,255,0.8); font-size: 15px; font-weight: 300; }
        .nav-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .nav-bar a { color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.1); border-radius: 8px; transition: all 0.3s; }
        .nav-bar a:hover { background: rgba(255,255,255,0.2); }
        .card { background: rgba(255,255,255,0.98); border-radius: 20px; box-shadow: 0 25px 80px rgba(0,0,0,0.3); overflow: hidden; margin-bottom: 25px; }
        .card-header { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; padding: 18px 28px; font-weight: 600; font-size: 17px; letter-spacing: 0.5px; }
        .card-body { padding: 28px; }
        .section-title { font-size: 13px; font-weight: 600; color: #1e3c72; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e8f0fe; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .form-group { position: relative; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; font-size: 13px; }
        .form-group input, .form-group select { width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 14px; font-family: inherit; transition: all 0.3s ease; background: #f9fafb; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #1e3c72; background: white; box-shadow: 0 0 0 4px rgba(30, 60, 114, 0.1); }
        .form-group .hint { font-size: 11px; color: #6b7280; margin-top: 5px; }

        /* Invoice Number with toggle */
        .invoice-number-wrapper { display: flex; gap: 10px; align-items: center; }
        .invoice-number-wrapper input { flex: 1; }
        .invoice-number-wrapper .btn-toggle {
            padding: 14px 16px;
            background: #e0e7ff;
            color: #1e3c72;
            border: 2px solid #1e3c72;
            border-radius: 12px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.3s;
        }
        .invoice-number-wrapper .btn-toggle:hover { background: #1e3c72; color: white; }
        .invoice-number-wrapper .btn-toggle.active { background: #1e3c72; color: white; }

        /* GST Toggle */
        .gst-toggle-container { background: #f0f4ff; border-radius: 16px; padding: 20px; margin-bottom: 25px; }
        .gst-toggle-label { font-size: 13px; font-weight: 600; color: #1e3c72; margin-bottom: 12px; display: block; }
        .gst-toggle { display: flex; background: white; border-radius: 12px; padding: 5px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.06); }
        .gst-toggle-btn { flex: 1; padding: 14px 20px; border: none; background: transparent; cursor: pointer; border-radius: 10px; font-family: inherit; font-weight: 600; font-size: 14px; color: #6b7280; transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .gst-toggle-btn span { font-size: 11px; font-weight: 400; opacity: 0.8; }
        .gst-toggle-btn.active { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3); }
        .gst-toggle-btn:hover:not(.active) { background: #f0f4ff; color: #1e3c72; }

        /* Items Table */
        .items-wrapper { overflow-x: auto; margin: 0 -10px; padding: 0 10px; }
        .items-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 800px; }
        .items-table th { background: linear-gradient(135deg, #374151, #4b5563); color: white; padding: 14px 12px; text-align: center; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        .items-table th:first-child { border-radius: 12px 0 0 0; }
        .items-table th:last-child { border-radius: 0 12px 0 0; }
        .items-table td { padding: 12px 8px; border-bottom: 1px solid #f0f0f0; text-align: center; vertical-align: middle; }
        .items-table tbody tr:hover { background: #f8fafc; }
        .items-table input { width: 100%; padding: 10px 8px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; font-family: inherit; text-align: center; transition: all 0.2s; background: white; }
        .items-table input:focus { outline: none; border-color: #1e3c72; background: #f0f4ff; }
        .items-table .item-name { text-align: left; }
        .items-table select.item-gst { width: 100%; padding: 10px 4px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; font-weight: 600; font-family: inherit; text-align: center; background: #fff; cursor: pointer; }
        .items-table select.item-gst:focus { outline: none; border-color: #1e3c72; background: #f0f4ff; }
        .items-table .item-qty { font-size: 18px !important; font-weight: 700 !important; color: #000 !important; background: #fffbeb !important; padding: 12px 8px !important; text-align: center !important; border: 2px solid #f59e0b !important; }
        .items-table .item-mrp { font-size: 16px !important; font-weight: 600 !important; color: #1e3c72 !important; padding: 12px 8px !important; text-align: right !important; }
        .items-table .calc-cell { background: linear-gradient(135deg, #f0f4ff, #e8f0fe); font-weight: 600; color: #1e3c72; font-size: 13px; }
        .items-table .total-cell { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; font-weight: 700; font-size: 14px; border-radius: 6px; }

        .btn { padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600; font-family: inherit; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-add { background: linear-gradient(135deg, #059669, #10b981); color: white; box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3); margin-top: 15px; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4); }
        .btn-remove { background: #fee2e2; color: #dc2626; padding: 8px 12px; font-size: 16px; border-radius: 8px; }
        .btn-remove:hover { background: #fecaca; }
        .btn-more { background: #e0e7ff; color: #1e3c72; padding: 6px 10px; font-size: 18px; border-radius: 6px; margin-right: 4px; }
        .btn-more:hover { background: #c7d2fe; }

        /* Totals */
        .totals-grid { display: grid; grid-template-columns: 1fr 350px; gap: 30px; margin-top: 30px; align-items: start; }
        .gst-info-box { background: #f0f4ff; border-radius: 16px; padding: 20px; border-left: 4px solid #1e3c72; }
        .gst-info-box h4 { font-size: 13px; font-weight: 600; color: #1e3c72; margin-bottom: 12px; }
        .gst-info-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; border-bottom: 1px dashed #d1d5db; }
        .gst-info-row:last-child { border-bottom: none; }
        .gst-info-row .label { color: #6b7280; }
        .gst-info-row .value { font-weight: 600; color: #374151; }
        .totals-box { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 2px solid #e5e7eb; }
        .total-row { display: flex; justify-content: space-between; padding: 14px 20px; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
        .total-row .label { color: #6b7280; }
        .total-row .value { font-weight: 600; color: #374151; }
        .total-row.highlight { background: #f0f4ff; }
        .total-row.grand { background: linear-gradient(135deg, #1e3c72, #2a5298); padding: 18px 20px; border: none; }
        .total-row.grand .label, .total-row.grand .value { color: white; font-size: 18px; font-weight: 700; }

        .print-section { text-align: center; margin-top: 35px; }
        .btn-print { background: linear-gradient(135deg, #f5af19, #f12711); color: white; padding: 20px 60px; font-size: 18px; border-radius: 50px; box-shadow: 0 10px 40px rgba(241, 39, 17, 0.3); }
        .btn-print:hover { transform: translateY(-3px); box-shadow: 0 15px 50px rgba(241, 39, 17, 0.4); }
        .btn-print svg { width: 24px; height: 24px; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; border-radius: 16px; padding: 25px; width: 90%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .modal-header { font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; }
        .modal-body .form-group { margin-bottom: 15px; }
        .modal-body .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; font-size: 13px; }
        .modal-body .form-group input { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; }
        .modal-footer { display: flex; gap: 10px; margin-top: 20px; }
        .modal-footer .btn { flex: 1; padding: 12px; text-align: center; justify-content: center; }
        .btn-save { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; }
        .btn-cancel { background: #f3f4f6; color: #374151; }

        /* Autocomplete */
        .autocomplete-list { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #1e3c72; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 100; display: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .autocomplete-list.show { display: block; }
        .autocomplete-item { padding: 12px 14px; cursor: pointer; border-bottom: 1px solid #eee; transition: background 0.2s; }
        .autocomplete-item:hover { background: #f0f4ff; }
        .autocomplete-item:last-child { border-bottom: none; }
        .autocomplete-item .name { font-weight: 600; color: #1e3c72; }
        .autocomplete-item .details { font-size: 11px; color: #666; margin-top: 2px; }

        @media (max-width: 768px) {
            .totals-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr 1fr; }
        }

        /* Print Styles */
        @media print {
            body { background: white !important; padding: 0 !important; }
            .app-container, .nav-bar { display: none !important; }
            .print-invoice { display: block !important; }
        }
        .print-invoice { display: none; }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="nav-bar">
            <a href="/xamp-cosmic/modules/invoices/index.php">← Back to Invoices</a>
            <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="/xamp-cosmic/modules/auth/logout.php">Logout</a>
        </div>

        <div class="app-header">
            <h1>GST Invoice Generator</h1>
            <p>COSMIC SURGICALS - Create Professional GST Compliant Invoices</p>
        </div>

        <!-- Customer & Invoice Details -->
        <div class="card">
            <div class="card-header">Invoice Details</div>
            <div class="card-body">
                <div class="section-title">Customer Information</div>
                <div class="form-grid">
                    <div class="form-group" style="position: relative;">
                        <label>Customer Name</label>
                        <input type="text" id="customerName" value="Walk-in Customer" autocomplete="off">
                        <input type="hidden" id="customerId">
                        <div class="autocomplete-list" id="customerList"></div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" id="customerPhone" placeholder="Enter phone">
                    </div>
                    <div class="form-group">
                        <label>Customer GSTIN</label>
                        <input type="text" id="customerGstin" placeholder="e.g., 32XXXXX1234X1ZX" maxlength="15" style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <select id="customerState" onchange="checkGstType()">
                            <option value="32">Kerala (32)</option>
                            <option value="33">Tamil Nadu (33)</option>
                            <option value="29">Karnataka (29)</option>
                            <option value="28">Andhra Pradesh (28)</option>
                            <option value="36">Telangana (36)</option>
                            <option value="27">Maharashtra (27)</option>
                            <option value="07">Delhi (07)</option>
                            <option value="09">Uttar Pradesh (09)</option>
                            <option value="19">West Bengal (19)</option>
                            <option value="24">Gujarat (24)</option>
                        </select>
                        <div class="hint">Select customer's state for correct GST type</div>
                    </div>
                </div>

                <div class="section-title">Invoice Settings</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Invoice Date</label>
                        <input type="date" id="invoiceDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Invoice Number</label>
                        <div class="invoice-number-wrapper">
                            <input type="text" id="invoiceNumber" value="<?php echo htmlspecialchars($nextInvoiceNumber); ?>" data-auto="<?php echo htmlspecialchars($nextInvoiceNumber); ?>">
                            <button type="button" class="btn-toggle active" id="btnAutoInvoice" onclick="toggleInvoiceNumber()" title="Toggle Auto/Manual">AUTO</button>
                        </div>
                        <div class="hint">Click AUTO to switch to manual entry</div>
                    </div>
                    <div class="form-group">
                        <label>Invoice Type</label>
                        <select id="invoiceType">
                            <option value="upi" selected>UPI Payment</option>
                            <option value="cash">Cash Bill</option>
                            <option value="credit">Credit Bill</option>
                            <option value="card">Card Payment</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Copy Type</label>
                        <select id="copyType">
                            <option value="original">Original</option>
                            <option value="duplicate">Duplicate</option>
                            <option value="triplicate">Triplicate</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price Entry Mode</label>
                        <select id="priceMode" onchange="calculateAll()">
                            <option value="inclusive" selected>MRP is Tax Inclusive (Extract tax)</option>
                            <option value="exclusive">MRP is Tax Exclusive (Add tax)</option>
                        </select>
                        <div class="hint">Inclusive: System calculates tax from final price</div>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Drug License No. (Optional)</label>
                        <input type="text" id="drugLicense" placeholder="e.g., WLF21B2023KL000876, WLF20B2023KL000884">
                        <div class="hint">D/L NO.'s for pharmaceutical items</div>
                    </div>
                </div>

                <!-- GST Type Toggle -->
                <div class="gst-toggle-container">
                    <span class="gst-toggle-label">GST Type (Auto-selected based on customer state)</span>
                    <div class="gst-toggle">
                        <button type="button" class="gst-toggle-btn active" id="btnCgstSgst" onclick="setGstType('cgst_sgst')">
                            CGST + SGST
                            <span>Intra-State (Within Kerala)</span>
                        </button>
                        <button type="button" class="gst-toggle-btn" id="btnIgst" onclick="setGstType('igst')">
                            IGST
                            <span>Inter-State (Outside Kerala)</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="card">
            <div class="card-header">Invoice Items</div>
            <div class="card-body">
                <div class="items-wrapper">
                    <table class="items-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th style="min-width:200px">Item Description</th>
                                <th style="width:80px">Qty</th>
                                <th style="width:100px">MRP (₹)</th>
                                <th style="width:80px">GST %</th>
                                <th style="width:110px">Total (₹)</th>
                                <th style="width:70px">More</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>

                <button class="btn btn-add" onclick="addItem()">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Add Item
                </button>

                <!-- Totals -->
                <div class="totals-grid">
                    <div class="gst-info-box">
                        <h4>Tax Breakdown</h4>
                        <div class="gst-info-row">
                            <span class="label">GST Rate Applied:</span>
                            <span class="value" id="infoGstRate">Per item</span>
                        </div>
                        <div class="gst-info-row">
                            <span class="label">Tax Type:</span>
                            <span class="value" id="infoTaxType">CGST + SGST (split equally)</span>
                        </div>
                        <div class="gst-info-row">
                            <span class="label">Place of Supply:</span>
                            <span class="value" id="infoPlaceOfSupply">Kerala (32)</span>
                        </div>
                        <div class="gst-info-row">
                            <span class="label">Price Mode:</span>
                            <span class="value" id="infoPriceMode">Tax Inclusive</span>
                        </div>
                    </div>

                    <div class="totals-box">
                        <div class="total-row">
                            <span class="label">Taxable Amount</span>
                            <span class="value">₹ <span id="dispSubtotal">0.00</span></span>
                        </div>
                        <div class="total-row highlight" id="rowCgst">
                            <span class="label">CGST</span>
                            <span class="value">₹ <span id="dispCgst">0.00</span></span>
                        </div>
                        <div class="total-row highlight" id="rowSgst">
                            <span class="label">SGST</span>
                            <span class="value">₹ <span id="dispSgst">0.00</span></span>
                        </div>
                        <div class="total-row highlight" id="rowIgst" style="display:none;">
                            <span class="label">IGST</span>
                            <span class="value">₹ <span id="dispIgst">0.00</span></span>
                        </div>
                        <div class="total-row">
                            <span class="label">Total Tax</span>
                            <span class="value">₹ <span id="dispTotalTax">0.00</span></span>
                        </div>
                        <div class="total-row grand">
                            <span class="label">Grand Total</span>
                            <span class="value">₹ <span id="dispGrandTotal">0.00</span></span>
                        </div>
                    </div>
                </div>

                <div class="print-section">
                    <button class="btn btn-print" onclick="saveInvoice()">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                        Save & Print Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Details Modal -->
    <div class="modal-overlay" id="itemModal">
        <div class="modal-content">
            <div class="modal-header">Item Details (Optional)</div>
            <div class="modal-body">
                <div class="form-group">
                    <label>HSN/SAC Code</label>
                    <input type="text" id="modalHsn" placeholder="e.g., 87139010">
                </div>
                <div class="form-group">
                    <label>Batch Number</label>
                    <input type="text" id="modalBatch" placeholder="e.g., BT2024001">
                </div>
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="month" id="modalExpiry">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn btn-save" onclick="saveItemDetails()">Save</button>
            </div>
        </div>
    </div>

    <script>
        // ========== CONFIGURATION ==========
        const SELLER_STATE = '32'; // Kerala
        const SELLER_STATE_NAME = 'Kerala';

        // State names mapping
        const STATES = {
            '07': 'Delhi', '09': 'Uttar Pradesh', '19': 'West Bengal', '24': 'Gujarat',
            '27': 'Maharashtra', '28': 'Andhra Pradesh', '29': 'Karnataka',
            '32': 'Kerala', '33': 'Tamil Nadu', '36': 'Telangana'
        };

        // Products and Customers from PHP
        const products = <?php echo json_encode($products); ?>;
        const customers = <?php echo json_encode($customers); ?>;

        // ========== STATE VARIABLES ==========
        let gstType = 'cgst_sgst';
        let itemCount = 0;
        let currentEditRow = null;
        let isAutoInvoiceNumber = true;

        // ========== INITIALIZATION ==========
        addItem();
        updateInfoBox();

        // ========== INVOICE NUMBER TOGGLE ==========
        function toggleInvoiceNumber() {
            const btn = document.getElementById('btnAutoInvoice');
            const input = document.getElementById('invoiceNumber');
            const autoValue = input.dataset.auto;

            isAutoInvoiceNumber = !isAutoInvoiceNumber;

            if (isAutoInvoiceNumber) {
                btn.textContent = 'AUTO';
                btn.classList.add('active');
                input.value = autoValue;
                input.readOnly = true;
                input.style.background = '#e5e7eb';
            } else {
                btn.textContent = 'MANUAL';
                btn.classList.remove('active');
                input.readOnly = false;
                input.style.background = '#f9fafb';
                input.focus();
                input.select();
            }
        }

        // ========== CUSTOMER AUTOCOMPLETE ==========
        const customerNameInput = document.getElementById('customerName');
        const customerList = document.getElementById('customerList');

        customerNameInput.addEventListener('input', function() {
            const val = this.value.toLowerCase();
            if (val.length < 2) { customerList.classList.remove('show'); return; }

            const matches = customers.filter(c => c.name.toLowerCase().includes(val) || (c.phone && c.phone.includes(val)));
            if (matches.length === 0) { customerList.classList.remove('show'); return; }

            customerList.innerHTML = matches.slice(0, 5).map(c => `
                <div class="autocomplete-item" onclick="selectCustomer(${c.id})">
                    <div class="name">${c.name}</div>
                    <div class="details">${c.phone || ''} ${c.gstin ? '| ' + c.gstin : ''}</div>
                </div>
            `).join('');
            customerList.classList.add('show');
        });

        customerNameInput.addEventListener('blur', () => setTimeout(() => customerList.classList.remove('show'), 200));

        function selectCustomer(id) {
            const c = customers.find(x => x.id == id);
            if (c) {
                document.getElementById('customerName').value = c.name;
                document.getElementById('customerId').value = c.id;
                document.getElementById('customerPhone').value = c.phone || '';
                document.getElementById('customerGstin').value = c.gstin || '';
                document.getElementById('customerState').value = c.state_code || '32';
                checkGstType();
            }
            customerList.classList.remove('show');
        }

        // ========== GST TYPE HANDLING ==========
        function checkGstType() {
            const customerState = document.getElementById('customerState').value;
            if (customerState === SELLER_STATE) {
                setGstType('cgst_sgst');
            } else {
                setGstType('igst');
            }
        }

        function setGstType(type) {
            gstType = type;
            const btnCgstSgst = document.getElementById('btnCgstSgst');
            const btnIgst = document.getElementById('btnIgst');

            btnCgstSgst.classList.toggle('active', type === 'cgst_sgst');
            btnIgst.classList.toggle('active', type === 'igst');

            // Update totals display
            if (type === 'cgst_sgst') {
                document.getElementById('rowCgst').style.display = 'flex';
                document.getElementById('rowSgst').style.display = 'flex';
                document.getElementById('rowIgst').style.display = 'none';
            } else {
                document.getElementById('rowCgst').style.display = 'none';
                document.getElementById('rowSgst').style.display = 'none';
                document.getElementById('rowIgst').style.display = 'flex';
            }

            updateInfoBox();
            calculateAll();
        }

        function updateInfoBox() {
            const customerState = document.getElementById('customerState').value;
            const stateName = STATES[customerState] || 'Unknown';
            const priceMode = document.getElementById('priceMode').value;

            document.getElementById('infoGstRate').textContent = 'Per item';
            document.getElementById('infoPlaceOfSupply').textContent = stateName + ' (' + customerState + ')';

            if (gstType === 'cgst_sgst') {
                document.getElementById('infoTaxType').textContent = 'CGST + SGST (split equally)';
            } else {
                document.getElementById('infoTaxType').textContent = 'IGST (full rate)';
            }

            document.getElementById('infoPriceMode').textContent = priceMode === 'inclusive' ? 'Tax Inclusive (Extract)' : 'Tax Exclusive (Add)';
        }

        // ========== ITEMS MANAGEMENT ==========
        function addItem() {
            itemCount++;
            const tbody = document.getElementById('itemsBody');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${itemCount}</td>
                <td style="position:relative;">
                    <input type="text" class="item-name" placeholder="Search or type item..." autocomplete="off">
                    <input type="hidden" class="item-product-id">
                    <input type="hidden" class="item-hsn">
                    <input type="hidden" class="item-batch">
                    <input type="hidden" class="item-expiry">
                    <div class="autocomplete-list product-list"></div>
                </td>
                <td><input type="number" class="item-qty" value="1" min="1" onchange="calculateAll()" onkeyup="calculateAll()"></td>
                <td><input type="number" class="item-mrp" placeholder="0.00" step="0.01" min="0" onchange="calculateAll()" onkeyup="calculateAll()"></td>
                <td>
                    <select class="item-gst" onchange="calculateAll()">
                        <option value="0">0%</option>
                        <option value="5" selected>5%</option>
                        <option value="12">12%</option>
                        <option value="18">18%</option>
                        <option value="28">28%</option>
                    </select>
                </td>
                <td class="total-cell item-total">0.00</td>
                <td>
                    <button class="btn btn-more" onclick="openItemDetails(this)" title="HSN, Batch, Expiry">⋯</button>
                    <button class="btn btn-remove" onclick="removeItem(this)" title="Remove">×</button>
                </td>
            `;
            tbody.appendChild(tr);

            // Product autocomplete
            const nameInput = tr.querySelector('.item-name');
            const prodList = tr.querySelector('.product-list');

            nameInput.addEventListener('input', function() {
                const val = this.value.toLowerCase();
                if (val.length < 2) { prodList.classList.remove('show'); return; }

                const matches = products.filter(p => p.name.toLowerCase().includes(val) || (p.hsn_code && p.hsn_code.includes(val)));
                if (matches.length === 0) { prodList.classList.remove('show'); return; }

                prodList.innerHTML = matches.slice(0, 5).map(p => `
                    <div class="autocomplete-item" data-id="${p.id}">
                        <div class="name">${p.name}</div>
                        <div class="details">HSN: ${p.hsn_code || '-'} | MRP: ₹${p.mrp || 0} | GST: ${p.gst_rate}%</div>
                    </div>
                `).join('');
                prodList.classList.add('show');

                prodList.querySelectorAll('.autocomplete-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const p = products.find(x => x.id == this.dataset.id);
                        if (p) {
                            nameInput.value = p.name;
                            tr.querySelector('.item-product-id').value = p.id;
                            tr.querySelector('.item-hsn').value = p.hsn_code || '';
                            tr.querySelector('.item-mrp').value = p.mrp || 0;
                            tr.querySelector('.item-gst').value = p.gst_rate || 5;
                            calculateAll();
                        }
                        prodList.classList.remove('show');
                    });
                });
            });

            nameInput.addEventListener('blur', () => setTimeout(() => prodList.classList.remove('show'), 200));
        }

        function removeItem(btn) {
            const tbody = document.getElementById('itemsBody');
            if (tbody.children.length > 1) {
                btn.closest('tr').remove();
                renumberItems();
                calculateAll();
            } else {
                alert('At least one item is required');
            }
        }

        function renumberItems() {
            const rows = document.querySelectorAll('#itemsBody tr');
            rows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
            });
            itemCount = rows.length;
        }

        // ========== CALCULATIONS ==========
        function calculateAll() {
            const priceMode = document.getElementById('priceMode').value;
            const isInclusive = (priceMode === 'inclusive');

            let totalTaxable = 0;
            let totalTax = 0;
            let totalAmount = 0;
            let totalCgst = 0;
            let totalSgst = 0;
            let totalIgst = 0;

            document.querySelectorAll('#itemsBody tr').forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const mrp = parseFloat(row.querySelector('.item-mrp').value) || 0;
                const itemGstRate = parseFloat(row.querySelector('.item-gst').value) || 0;

                let taxable = 0, tax = 0, total = 0;

                if (isInclusive) {
                    // MRP is inclusive of tax - extract tax from it
                    // Total = MRP × Qty
                    // Taxable = Total / (1 + GST/100)
                    // Tax = Total - Taxable
                    total = qty * mrp;
                    taxable = total / (1 + itemGstRate / 100);
                    tax = total - taxable;
                } else {
                    // MRP is exclusive of tax - add tax to it
                    // Taxable = MRP × Qty
                    // Tax = Taxable × GST / 100
                    // Total = Taxable + Tax
                    taxable = qty * mrp;
                    tax = taxable * itemGstRate / 100;
                    total = taxable + tax;
                }

                // Round to 2 decimals
                taxable = Math.round(taxable * 100) / 100;
                tax = Math.round(tax * 100) / 100;
                total = Math.round(total * 100) / 100;

                row.querySelector('.item-total').textContent = total.toFixed(2);

                totalTaxable += taxable;
                totalTax += tax;
                totalAmount += total;

                // Split tax for CGST/SGST or IGST
                if (gstType === 'cgst_sgst') {
                    totalCgst += tax / 2;
                    totalSgst += tax / 2;
                } else {
                    totalIgst += tax;
                }
            });

            // Update display
            document.getElementById('dispSubtotal').textContent = totalTaxable.toFixed(2);

            if (gstType === 'cgst_sgst') {
                document.getElementById('dispCgst').textContent = totalCgst.toFixed(2);
                document.getElementById('dispSgst').textContent = totalSgst.toFixed(2);
            } else {
                document.getElementById('dispIgst').textContent = totalIgst.toFixed(2);
            }

            document.getElementById('dispTotalTax').textContent = totalTax.toFixed(2);
            document.getElementById('dispGrandTotal').textContent = totalAmount.toFixed(2);

            updateInfoBox();
        }

        // ========== ITEM DETAILS MODAL ==========
        function openItemDetails(btn) {
            currentEditRow = btn.closest('tr');
            document.getElementById('modalHsn').value = currentEditRow.querySelector('.item-hsn').value || '';
            document.getElementById('modalBatch').value = currentEditRow.querySelector('.item-batch').value || '';
            document.getElementById('modalExpiry').value = currentEditRow.querySelector('.item-expiry').value || '';
            document.getElementById('itemModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('itemModal').classList.remove('active');
            currentEditRow = null;
        }

        function saveItemDetails() {
            if (currentEditRow) {
                currentEditRow.querySelector('.item-hsn').value = document.getElementById('modalHsn').value;
                currentEditRow.querySelector('.item-batch').value = document.getElementById('modalBatch').value;
                currentEditRow.querySelector('.item-expiry').value = document.getElementById('modalExpiry').value;
            }
            closeModal();
        }

        // Close modal on overlay click
        document.getElementById('itemModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // ========== SAVE INVOICE ==========
        function saveInvoice() {
            // Validate items
            const items = [];
            let valid = false;

            document.querySelectorAll('#itemsBody tr').forEach(row => {
                const name = row.querySelector('.item-name').value.trim();
                const mrp = parseFloat(row.querySelector('.item-mrp').value) || 0;
                if (name && mrp > 0) {
                    valid = true;
                    items.push({
                        product_id: row.querySelector('.item-product-id').value || '',
                        description: name,
                        hsn_code: row.querySelector('.item-hsn').value || '',
                        batch_no: row.querySelector('.item-batch').value || '',
                        expiry_date: row.querySelector('.item-expiry').value || '',
                        quantity: row.querySelector('.item-qty').value,
                        mrp: mrp,
                        gst_rate: row.querySelector('.item-gst').value
                    });
                }
            });

            if (!valid) {
                alert('Please add at least one item with description and MRP.');
                return;
            }

            // Validate invoice number
            const invoiceNumber = document.getElementById('invoiceNumber').value.trim();
            if (!invoiceNumber) {
                alert('Invoice number is required.');
                return;
            }

            const data = {
                csrf_token: '<?php echo $csrfToken; ?>',
                invoice_number: invoiceNumber,
                invoice_date: document.getElementById('invoiceDate').value,
                invoice_type: document.getElementById('invoiceType').value,
                copy_type: document.getElementById('copyType').value,
                customer_id: document.getElementById('customerId').value || '',
                customer_name: document.getElementById('customerName').value || 'Walk-in Customer',
                customer_phone: document.getElementById('customerPhone').value,
                customer_gstin: document.getElementById('customerGstin').value,
                customer_state_code: document.getElementById('customerState').value,
                price_mode: document.getElementById('priceMode').value,
                gst_type: gstType,
                drug_license: document.getElementById('drugLicense').value,
                subtotal: document.getElementById('dispSubtotal').textContent,
                total_cgst: document.getElementById('dispCgst').textContent,
                total_sgst: document.getElementById('dispSgst').textContent,
                total_igst: document.getElementById('dispIgst').textContent,
                total_tax: document.getElementById('dispTotalTax').textContent,
                grand_total: document.getElementById('dispGrandTotal').textContent,
                items: items
            };

            // Send to server
            fetch('/xamp-cosmic/modules/invoices/ajax/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    // Show email status
                    if (result.email_sent) {
                        console.log('Email notification sent successfully');
                    } else {
                        console.warn('Email notification failed: ' + result.email_message);
                    }
                    // Open print page
                    window.open('/xamp-cosmic/modules/invoices/print.php?id=' + result.invoice_id, '_blank');
                    // Redirect to invoice list with email status
                    const emailParam = result.email_sent ? '&email=sent' : '&email=failed';
                    window.location.href = '/xamp-cosmic/modules/invoices/index.php?saved=1' + emailParam;
                } else {
                    alert('Error: ' + (result.error || 'Failed to save'));
                }
            })
            .catch(err => {
                alert('Error saving invoice: ' + err.message);
            });
        }
    </script>
</body>
</html>

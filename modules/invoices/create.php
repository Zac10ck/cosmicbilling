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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .app-container { max-width: 1000px; margin: 0 auto; }
        .app-header { text-align: center; color: white; margin-bottom: 25px; }
        .app-header h1 { font-size: 32px; margin-bottom: 5px; color: #f5af19; }
        .app-header p { color: rgba(255,255,255,0.8); font-size: 14px; }
        .nav-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .nav-bar a { color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.1); border-radius: 8px; }
        .nav-bar a:hover { background: rgba(255,255,255,0.2); }
        .card { background: rgba(255,255,255,0.98); border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; padding: 15px 25px; font-weight: 600; font-size: 16px; }
        .card-body { padding: 25px; }
        .section-title { font-size: 12px; font-weight: 600; color: #1e3c72; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e8f0fe; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group { position: relative; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #374151; font-size: 12px; }
        .form-group input, .form-group select { width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: #f9fafb; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #1e3c72; background: white; }
        .form-group .hint { font-size: 10px; color: #6b7280; margin-top: 4px; }

        /* GST Toggle */
        .gst-toggle-container { background: #f0f4ff; border-radius: 12px; padding: 15px; margin-bottom: 20px; }
        .gst-toggle { display: flex; background: white; border-radius: 10px; padding: 4px; }
        .gst-toggle-btn { flex: 1; padding: 12px; border: none; background: transparent; cursor: pointer; border-radius: 8px; font-weight: 600; font-size: 13px; color: #6b7280; }
        .gst-toggle-btn.active { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; }
        .gst-toggle-btn span { display: block; font-size: 10px; font-weight: 400; opacity: 0.8; margin-top: 2px; }

        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { background: linear-gradient(135deg, #374151, #4b5563); color: white; padding: 12px 8px; text-align: center; font-size: 11px; text-transform: uppercase; }
        .items-table td { padding: 10px 6px; border-bottom: 1px solid #f0f0f0; text-align: center; }
        .items-table input, .items-table select { width: 100%; padding: 10px 6px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; text-align: center; }
        .items-table input:focus, .items-table select:focus { outline: none; border-color: #1e3c72; background: #f0f4ff; }
        .items-table .item-name { text-align: left; }
        .items-table .item-qty { font-size: 18px !important; font-weight: 700; background: #fffbeb !important; border-color: #f59e0b !important; width: 70px; }
        .items-table .item-mrp { font-weight: 600; color: #1e3c72; width: 90px; }
        .items-table .total-cell { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; font-weight: 700; border-radius: 6px; }

        .btn { padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .btn-add { background: linear-gradient(135deg, #059669, #10b981); color: white; margin-top: 15px; }
        .btn-remove { background: #fee2e2; color: #dc2626; padding: 8px 12px; font-size: 16px; border-radius: 8px; }
        .btn-more { background: #e0e7ff; color: #1e3c72; padding: 6px 10px; font-size: 16px; border-radius: 6px; }

        /* Totals */
        .totals-grid { display: grid; grid-template-columns: 1fr 300px; gap: 25px; margin-top: 25px; }
        .totals-box { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 2px solid #e5e7eb; }
        .total-row { display: flex; justify-content: space-between; padding: 12px 18px; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
        .total-row .label { color: #6b7280; }
        .total-row .value { font-weight: 600; color: #374151; }
        .total-row.grand { background: linear-gradient(135deg, #1e3c72, #2a5298); padding: 16px 18px; }
        .total-row.grand .label, .total-row.grand .value { color: white; font-size: 18px; font-weight: 700; }

        .btn-print { background: linear-gradient(135deg, #f5af19, #f12711); color: white; padding: 18px 50px; font-size: 16px; border-radius: 50px; margin-top: 25px; }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(241, 39, 17, 0.3); }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; border-radius: 16px; padding: 25px; width: 90%; max-width: 400px; }
        .modal-header { font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        .modal-footer { display: flex; gap: 10px; margin-top: 20px; }
        .modal-footer .btn { flex: 1; justify-content: center; }
        .btn-save { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; }
        .btn-cancel { background: #f3f4f6; color: #374151; }

        /* Autocomplete */
        .autocomplete-list { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #1e3c72; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 100; display: none; }
        .autocomplete-list.show { display: block; }
        .autocomplete-item { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #eee; }
        .autocomplete-item:hover { background: #f0f4ff; }
        .autocomplete-item .name { font-weight: 600; }
        .autocomplete-item .details { font-size: 11px; color: #666; }

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
                            <option value="27">Maharashtra (27)</option>
                            <option value="36">Telangana (36)</option>
                        </select>
                    </div>
                </div>

                <div class="section-title">Invoice Settings</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Invoice Number</label>
                        <input type="text" id="invoiceNumber" value="<?php echo htmlspecialchars($nextInvoiceNumber); ?>" readonly style="background: #e5e7eb;">
                    </div>
                    <div class="form-group">
                        <label>Invoice Date</label>
                        <input type="date" id="invoiceDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Invoice Type</label>
                        <select id="invoiceType">
                            <option value="cash">Cash Bill</option>
                            <option value="credit">Credit Bill</option>
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
                        <label>Price Mode</label>
                        <select id="priceMode" onchange="calculateAll()">
                            <option value="exclusive">Tax Exclusive (Add tax)</option>
                            <option value="inclusive">Tax Inclusive (Extract tax)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Drug License No.</label>
                        <input type="text" id="drugLicense" placeholder="Optional">
                    </div>
                </div>

                <div class="gst-toggle-container">
                    <label style="font-size: 12px; font-weight: 600; color: #1e3c72; margin-bottom: 10px; display: block;">GST Type</label>
                    <div class="gst-toggle">
                        <button type="button" class="gst-toggle-btn active" id="btnCgstSgst" onclick="setGstType('cgst_sgst')">
                            CGST + SGST<span>Intra-State (Kerala)</span>
                        </button>
                        <button type="button" class="gst-toggle-btn" id="btnIgst" onclick="setGstType('igst')">
                            IGST<span>Inter-State</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="card">
            <div class="card-header">Invoice Items</div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width:35px">#</th>
                                <th style="min-width:180px">Item Description</th>
                                <th style="width:70px">Qty</th>
                                <th style="width:90px">MRP (₹)</th>
                                <th style="width:70px">GST %</th>
                                <th style="width:100px">Total (₹)</th>
                                <th style="width:80px">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>

                <button class="btn btn-add" onclick="addItem()">+ Add Item</button>

                <div class="totals-grid">
                    <div></div>
                    <div class="totals-box">
                        <div class="total-row">
                            <span class="label">Taxable Amount</span>
                            <span class="value">₹ <span id="dispSubtotal">0.00</span></span>
                        </div>
                        <div class="total-row" id="rowCgst">
                            <span class="label">CGST</span>
                            <span class="value">₹ <span id="dispCgst">0.00</span></span>
                        </div>
                        <div class="total-row" id="rowSgst">
                            <span class="label">SGST</span>
                            <span class="value">₹ <span id="dispSgst">0.00</span></span>
                        </div>
                        <div class="total-row" id="rowIgst" style="display:none;">
                            <span class="label">IGST</span>
                            <span class="value">₹ <span id="dispIgst">0.00</span></span>
                        </div>
                        <div class="total-row grand">
                            <span class="label">Grand Total</span>
                            <span class="value">₹ <span id="dispGrandTotal">0.00</span></span>
                        </div>
                    </div>
                </div>

                <div style="text-align: center;">
                    <button class="btn btn-print" onclick="saveInvoice()">💾 Save & Print Invoice</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Details Modal -->
    <div class="modal-overlay" id="itemModal">
        <div class="modal-content">
            <div class="modal-header">Item Details (Optional)</div>
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
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn btn-save" onclick="saveItemDetails()">Save</button>
            </div>
        </div>
    </div>

    <script>
        // Products and Customers from PHP
        const products = <?php echo json_encode($products); ?>;
        const customers = <?php echo json_encode($customers); ?>;

        let gstType = 'cgst_sgst';
        let itemCount = 0;
        let currentEditRow = null;

        // Initialize
        addItem();

        // Customer autocomplete
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

        // GST Type
        function checkGstType() {
            const state = document.getElementById('customerState').value;
            setGstType(state === '32' ? 'cgst_sgst' : 'igst');
        }

        function setGstType(type) {
            gstType = type;
            document.getElementById('btnCgstSgst').classList.toggle('active', type === 'cgst_sgst');
            document.getElementById('btnIgst').classList.toggle('active', type === 'igst');
            document.getElementById('rowCgst').style.display = type === 'cgst_sgst' ? 'flex' : 'none';
            document.getElementById('rowSgst').style.display = type === 'cgst_sgst' ? 'flex' : 'none';
            document.getElementById('rowIgst').style.display = type === 'igst' ? 'flex' : 'none';
            calculateAll();
        }

        // Add item row
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
                    <button class="btn btn-remove" onclick="removeItem(this)">×</button>
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
            }
        }

        function renumberItems() {
            document.querySelectorAll('#itemsBody tr').forEach((row, i) => {
                row.cells[0].textContent = i + 1;
            });
            itemCount = document.querySelectorAll('#itemsBody tr').length;
        }

        // Calculate totals
        function calculateAll() {
            const isInclusive = document.getElementById('priceMode').value === 'inclusive';
            let totalTaxable = 0, totalCgst = 0, totalSgst = 0, totalIgst = 0, totalAmount = 0;

            document.querySelectorAll('#itemsBody tr').forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const mrp = parseFloat(row.querySelector('.item-mrp').value) || 0;
                const gstRate = parseFloat(row.querySelector('.item-gst').value) || 0;

                let taxable, tax, total;
                if (isInclusive) {
                    total = qty * mrp;
                    taxable = total / (1 + gstRate / 100);
                    tax = total - taxable;
                } else {
                    taxable = qty * mrp;
                    tax = taxable * gstRate / 100;
                    total = taxable + tax;
                }

                row.querySelector('.item-total').textContent = total.toFixed(2);
                totalTaxable += taxable;
                totalAmount += total;

                if (gstType === 'cgst_sgst') {
                    totalCgst += tax / 2;
                    totalSgst += tax / 2;
                } else {
                    totalIgst += tax;
                }
            });

            document.getElementById('dispSubtotal').textContent = totalTaxable.toFixed(2);
            document.getElementById('dispCgst').textContent = totalCgst.toFixed(2);
            document.getElementById('dispSgst').textContent = totalSgst.toFixed(2);
            document.getElementById('dispIgst').textContent = totalIgst.toFixed(2);
            document.getElementById('dispGrandTotal').textContent = totalAmount.toFixed(2);
        }

        // Item details modal
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

        // Save invoice
        function saveInvoice() {
            // Collect data
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

            const data = {
                csrf_token: '<?php echo $csrfToken; ?>',
                invoice_number: document.getElementById('invoiceNumber').value,
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
                    // Open print page
                    window.open('/xamp-cosmic/modules/invoices/print.php?id=' + result.invoice_id, '_blank');
                    // Redirect to invoice list
                    window.location.href = '/xamp-cosmic/modules/invoices/index.php?saved=1';
                } else {
                    alert('Error: ' + (result.error || 'Failed to save'));
                }
            })
            .catch(err => {
                alert('Error saving invoice: ' + err.message);
            });
        }

        // Close modal on overlay click
        document.getElementById('itemModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>

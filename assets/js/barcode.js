(function () {
    'use strict';
    var activeStream = null;
    var scanning = false;

    function stopCamera() {
        scanning = false;
        if (activeStream) activeStream.getTracks().forEach(function (track) { track.stop(); });
        activeStream = null;
        var modal = document.getElementById('barcodeScannerModal');
        if (modal) modal.remove();
    }

    function completeScan(target, value, submitAfter) {
        target.value = value;
        target.dispatchEvent(new Event('input', { bubbles: true }));
        target.dispatchEvent(new Event('change', { bubbles: true }));
        stopCamera();
        if (submitAfter && target.form) target.form.submit();
    }

    function openScanner(target, submitAfter) {
        var modal = document.createElement('div');
        modal.id = 'barcodeScannerModal';
        modal.className = 'scanner-modal';
        modal.innerHTML = '<div class="scanner-sheet"><div class="scanner-header"><div><strong>Scan barcode</strong><small>Hold the code inside the box</small></div><button type="button" class="scanner-close" aria-label="Close">×</button></div><video class="scanner-video" playsinline muted></video><div class="scanner-frame"></div><p class="scanner-status">Starting camera…</p><div class="scanner-fallback"><label class="btn btn-secondary">Take barcode photo<input type="file" accept="image/*" capture="environment" hidden></label><div class="manual-barcode"><input class="form-control" placeholder="Or type barcode" inputmode="numeric"><button type="button" class="btn btn-primary">Use</button></div></div></div>';
        document.body.appendChild(modal);
        modal.querySelector('.scanner-close').addEventListener('click', stopCamera);
        modal.addEventListener('click', function (event) { if (event.target === modal) stopCamera(); });
        var manual = modal.querySelector('.manual-barcode input');
        modal.querySelector('.manual-barcode button').addEventListener('click', function () { if (manual.value.trim()) completeScan(target, manual.value.trim(), submitAfter); });
        manual.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); if (manual.value.trim()) completeScan(target, manual.value.trim(), submitAfter); } });

        var photoInput = modal.querySelector('input[type=file]');
        photoInput.addEventListener('change', async function () {
            if (!photoInput.files[0] || !('BarcodeDetector' in window)) return;
            try {
                var bitmap = await createImageBitmap(photoInput.files[0]);
                var codes = await new BarcodeDetector().detect(bitmap);
                if (codes.length) completeScan(target, codes[0].rawValue, submitAfter);
                else modal.querySelector('.scanner-status').textContent = 'No barcode found. Try again or type it below.';
            } catch (error) { modal.querySelector('.scanner-status').textContent = 'Could not read that photo. Type the barcode below.'; }
        });

        if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !('BarcodeDetector' in window)) {
            modal.querySelector('.scanner-video').style.display = 'none';
            modal.querySelector('.scanner-frame').style.display = 'none';
            if (!('BarcodeDetector' in window)) photoInput.parentNode.style.display = 'none';
            modal.querySelector('.scanner-status').textContent = window.isSecureContext ? 'Automatic scanning is unavailable on this browser. Type the barcode below.' : 'Live scanning needs HTTPS. Type the barcode below until HTTPS is configured.';
            return;
        }

        var video = modal.querySelector('video');
        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false }).then(async function (stream) {
            activeStream = stream; video.srcObject = stream; await video.play(); scanning = true;
            modal.querySelector('.scanner-status').textContent = 'Looking for a barcode…';
            var detector = new BarcodeDetector({ formats: ['ean_13','ean_8','code_128','code_39','upc_a','upc_e','itf','qr_code'] });
            async function detect() {
                if (!scanning) return;
                try { var codes = await detector.detect(video); if (codes.length) return completeScan(target, codes[0].rawValue, submitAfter); } catch (error) {}
                setTimeout(detect, 180);
            }
            detect();
        }).catch(function () { modal.querySelector('.scanner-status').textContent = 'Camera permission was not available. Take a photo or type the barcode.'; });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-barcode-target]');
        if (!button) return;
        var target = document.getElementById(button.dataset.barcodeTarget);
        if (target) openScanner(target, button.dataset.submitAfterScan === 'true');
    });

    var quickInput = document.getElementById('quickProductSearch');
    var quickResults = document.getElementById('quickProductResults');
    if (quickInput && quickResults && window.inventoryProducts) {
        function escapeHtml(value) { var div=document.createElement('div'); div.textContent=value==null?'':value; return div.innerHTML; }
        function renderQuickResults() {
            var query=quickInput.value.trim().toLowerCase();
            var matches=window.inventoryProducts.filter(function(p){return !query||p.name.toLowerCase().indexOf(query)!==-1||(p.barcode&&p.barcode.toLowerCase()===query);}).slice(0,20);
            quickResults.innerHTML=matches.map(function(p){return '<a class="quick-result" href="/xamp-cosmic/modules/inventory/stock.php?id='+p.id+'"><div><strong>'+escapeHtml(p.name)+'</strong><small>'+(p.barcode?'Barcode '+escapeHtml(p.barcode):'No barcode')+'</small></div><span>'+escapeHtml(p.stock_count)+' '+escapeHtml(p.unit)+'</span></a>';}).join('') || '<div class="empty-state"><p>No matching product.</p><a class="btn btn-primary" href="/xamp-cosmic/modules/products/create.php?barcode='+encodeURIComponent(quickInput.value.trim())+'">Add new product</a></div>';
        }
        quickInput.addEventListener('input',renderQuickResults); renderQuickResults();
    }
})();

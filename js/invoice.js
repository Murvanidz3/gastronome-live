/**
 * Invoice Generator Logic
 * Autocomplete search, add/remove products, qty editing, totals, print/PDF
 */
(function () {
    'use strict';

    const CURRENCY_SYMBOLS = { GEL: '₾', EUR: '€', USD: '$' };

    const searchInput = document.getElementById('invoiceSearch');
    const autocompleteEl = document.getElementById('autocompleteList');
    const invoiceBody = document.getElementById('invoiceBody');
    const emptyRow = document.getElementById('invoiceEmptyRow');
    const summaryEl = document.getElementById('invoiceSummary');
    const actionsEl = document.getElementById('invoiceActions');
    const subtotalEl = document.getElementById('invoiceSubtotal');
    const grandTotalEl = document.getElementById('invoiceGrandTotal');
    const clearBtn = document.getElementById('clearInvoiceBtn');
    const printBtn = document.getElementById('printInvoiceBtn');

    if (!searchInput) return;

    // Invoice items: { id, image_url, name, barcode, price, quantity }
    let invoiceItems = [];
    let debounceTimer = null;

    // ─── Search Autocomplete ───
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 1) {
            // Show all products when input is cleared
            fetchProducts('');
            return;
        }
        debounceTimer = setTimeout(() => fetchProducts(q), 250);
    });

    // Show product list on focus (click into search box)
    searchInput.addEventListener('focus', function () {
        if (!autocompleteEl.classList.contains('visible')) {
            fetchProducts(this.value.trim());
        }
    });

    function fetchProducts(query) {
        fetch(`/api/search.php?q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(products => showAutocomplete(products))
            .catch(() => hideAutocomplete());
    }

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') hideAutocomplete();
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !autocompleteEl.contains(e.target)) {
            hideAutocomplete();
        }
    });

    function showAutocomplete(products) {
        if (!products.length) {
            autocompleteEl.innerHTML = `
                <div style="padding:16px;text-align:center;color:var(--text-muted);font-size:0.85rem;">
                    No products found
                </div>`;
            autocompleteEl.classList.add('visible');
            return;
        }

        autocompleteEl.innerHTML = products.map(p => `
            <div class="autocomplete-item" data-id="${p.id}">
                ${p.image_url
                ? `<img src="${escHtml(p.image_url)}" alt="" onerror="this.style.display='none'">`
                : `<div style="width:36px;height:36px;border-radius:6px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;font-size:0.9rem;color:var(--text-muted);">🖼</div>`}
                <div class="item-info">
                    <div class="item-name">${escHtml(p.name)}</div>
                    <div class="item-barcode">${escHtml(p.barcode)}</div>
                </div>
                <div class="item-price">${CURRENCY_SYMBOLS[p.currency] || '₾'}${parseFloat(p.price).toFixed(2)}</div>
            </div>`).join('');

        autocompleteEl.classList.add('visible');

        // Bind click events
        autocompleteEl.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.dataset.id;
                const product = products.find(p => String(p.id) === String(id));
                if (product) addToInvoice(product);
                hideAutocomplete();
                searchInput.value = '';
            });
        });
    }

    function hideAutocomplete() {
        autocompleteEl.classList.remove('visible');
        autocompleteEl.innerHTML = '';
    }

    // ─── Invoice Management ───
    function addToInvoice(product) {
        // Check if already in invoice
        const existing = invoiceItems.find(i => String(i.id) === String(product.id));
        if (existing) {
            existing.quantity++;
            renderInvoice();
            return;
        }

        invoiceItems.push({
            id: product.id,
            image_url: product.image_url || '',
            name: product.name,
            barcode: product.barcode,
            price: parseFloat(product.price),
            currency: product.currency || 'GEL',
            quantity: 1,
        });

        renderInvoice();
    }

    function removeFromInvoice(id) {
        invoiceItems = invoiceItems.filter(i => String(i.id) !== String(id));
        renderInvoice();
    }

    function updateQuantity(id, qty) {
        const item = invoiceItems.find(i => String(i.id) === String(id));
        if (!item) return;
        item.quantity = Math.max(1, parseInt(qty) || 1);
        renderInvoice();
    }

    function renderInvoice() {
        if (!invoiceItems.length) {
            emptyRow.style.display = '';
            summaryEl.style.display = 'none';
            actionsEl.style.display = 'none';
            // Clear any product rows
            invoiceBody.querySelectorAll('tr:not(#invoiceEmptyRow)').forEach(r => r.remove());
            return;
        }

        emptyRow.style.display = 'none';

        // Remove old product rows
        invoiceBody.querySelectorAll('tr:not(#invoiceEmptyRow)').forEach(r => r.remove());

        let invoiceCurrency = invoiceItems.length > 0 ? (invoiceItems[0].currency || 'GEL') : 'GEL';
        let symbol = CURRENCY_SYMBOLS[invoiceCurrency] || '₾';

        let subtotal = 0;

        invoiceItems.forEach(item => {
            const rowTotal = item.price * item.quantity;
            subtotal += rowTotal;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    ${item.image_url
                    ? `<img src="${escHtml(item.image_url)}" alt="" class="product-img" onerror="this.outerHTML='<div class=\\'product-img-placeholder\\' style=\\'width:40px;height:40px;font-size:0.9rem;\\'>🖼</div>'">`
                    : `<div class="product-img-placeholder" style="width:40px;height:40px;font-size:0.9rem;">🖼</div>`}
                </td>
                <td>
                    <strong>${escHtml(item.name)}</strong>
                </td>
                <td>
                    <input type="number" class="qty-input" value="${item.quantity}" min="1"
                           data-id="${item.id}">
                </td>
                <td class="nowrap"><span class="price-tag">${symbol} ${item.price.toFixed(2)}</span></td>
                <td class="nowrap"><span class="price-tag">${symbol} ${rowTotal.toFixed(2)}</span></td>
                <td class="no-print">
                    <button class="btn btn-icon btn-danger btn-sm remove-btn" data-id="${item.id}" title="Remove">✕</button>
                </td>`;
            invoiceBody.appendChild(tr);
        });

        // Update totals
        if (subtotalEl) {
            subtotalEl.textContent = `${symbol}${subtotal.toFixed(2)}`;
        }
        grandTotalEl.textContent = `${symbol}${subtotal.toFixed(2)}`;
        summaryEl.style.display = '';
        actionsEl.style.display = '';

        // Bind events
        invoiceBody.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function () {
                updateQuantity(this.dataset.id, this.value);
            });
        });

        invoiceBody.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                removeFromInvoice(this.dataset.id);
            });
        });
    }

    // ─── Clear ───
    clearBtn.addEventListener('click', () => {
        invoiceItems = [];
        renderInvoice();
    });

    // ─── Print ───
    const originalTitle = document.title;
    let prePrintCompanyName = '';
    let prePrintCompanyId = '';

    window.addEventListener('beforeprint', () => {
        document.title = " ";
        const cInp = document.getElementById('billToCompany');
        const iInp = document.getElementById('billToId');
        if (cInp) {
            prePrintCompanyName = cInp.value;
            if (!cInp.value.trim()) cInp.value = 'Company Name';
        }
        if (iInp) {
            prePrintCompanyId = iInp.value;
            if (!iInp.value.trim()) iInp.value = 'Company ID';
        }
    });

    window.addEventListener('afterprint', () => {
        document.title = originalTitle;
        const cInp = document.getElementById('billToCompany');
        const iInp = document.getElementById('billToId');
        if (cInp && cInp.value === 'Company Name' && !prePrintCompanyName) cInp.value = '';
        if (iInp && iInp.value === 'Company ID' && !prePrintCompanyId) iInp.value = '';
    });

    printBtn.addEventListener('click', async () => {
        // If a company is selected (via badge/localStorage), attempt to save it to DB
        const companyName = localStorage.getItem('invoice_billToCompany');
        const companyIdNumber = localStorage.getItem('invoice_billToId');
        const invoiceNum = invNumberDisplay ? invNumberDisplay.textContent : generateInvoiceNumber();
        const totalText = grandTotalEl ? grandTotalEl.textContent.replace(/[^0-9.-]+/g, "") : '0';
        const totalAmount = parseFloat(totalText);

        if (companyName && companyIdNumber) {
            // We need the internal company ID from the autocomplete or we can look it up API side. 
            // To simplify, we previously stored only name and id number in local storage.
            // We need to fetch the internal DB company ID to link it properly.
            try {
                // Find company by company_id_number
                const searchRes = await fetch(`/api/search_companies.php?q=${encodeURIComponent(companyIdNumber)}`);
                const comps = await searchRes.json();
                const comp = comps.find(c => c.company_id_number === companyIdNumber);

                if (comp) {
                    await fetch('/api/save_invoice.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            company_id: comp.id,
                            invoice_number: invoiceNum,
                            total_amount: totalAmount
                        })
                    });
                }
            } catch (e) {
                console.error('Failed to save invoice to history:', e);
            }
        }

        window.print();
    });

    // Generate incremental invoice number based on sequence
    function generateInvoiceNumber() {
        let lastNum = parseInt(localStorage.getItem('invoice_lastNumber') || '203000', 10);
        lastNum += 1;
        localStorage.setItem('invoice_lastNumber', lastNum.toString());
        return lastNum.toString();
    }

    const invNumberDisplay = document.getElementById('invNumberDisplay');
    if (invNumberDisplay) {
        invNumberDisplay.textContent = generateInvoiceNumber();
    }

    // ─── LocalStorage for BILL TO ───
    const billToCompanyInput = document.getElementById('billToCompany');
    const billToIdInput = document.getElementById('billToId');

    const selectedCompanyBadge = document.getElementById('selectedCompanyBadge');
    const selectedCompanyNameText = document.getElementById('selectedCompanyNameText');
    const clearCompanyBtn = document.getElementById('clearCompanyBtn');
    const compSearchInput = document.getElementById('companySearch');

    function updateCompanyBadge(name) {
        if (!selectedCompanyBadge || !compSearchInput) return;
        if (name) {
            selectedCompanyNameText.textContent = name;
            selectedCompanyBadge.style.display = 'flex';
            compSearchInput.style.paddingLeft = '180px'; // make room for badge
            compSearchInput.value = '';
            compSearchInput.placeholder = '';
        } else {
            selectedCompanyBadge.style.display = 'none';
            compSearchInput.style.paddingLeft = '';
            compSearchInput.placeholder = 'Search company by name or ID…';
            compSearchInput.value = '';
        }
    }

    if (billToCompanyInput && billToIdInput) {
        // Load saved values
        const savedCompany = localStorage.getItem('invoice_billToCompany') || '';
        billToCompanyInput.value = savedCompany;
        billToIdInput.value = localStorage.getItem('invoice_billToId') || '';

        updateCompanyBadge(savedCompany);

        // Save on change
        billToCompanyInput.addEventListener('input', (e) => {
            localStorage.setItem('invoice_billToCompany', e.target.value);
            if (e.target.value === '') updateCompanyBadge('');
        });
        billToIdInput.addEventListener('input', (e) => {
            localStorage.setItem('invoice_billToId', e.target.value);
        });
    }

    if (clearCompanyBtn) {
        clearCompanyBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (billToCompanyInput) billToCompanyInput.value = '';
            if (billToIdInput) billToIdInput.value = '';
            localStorage.removeItem('invoice_billToCompany');
            localStorage.removeItem('invoice_billToId');
            updateCompanyBadge('');
            if (compSearchInput) compSearchInput.focus();
        });
    }

    // ─── Company Autocomplete ───
    const compAutocompleteEl = document.getElementById('companyAutocompleteList');
    let compDebounceTimer = null;

    if (compSearchInput && compAutocompleteEl) {
        compSearchInput.addEventListener('input', function () {
            clearTimeout(compDebounceTimer);
            const q = this.value.trim();
            if (q.length < 1) {
                fetchCompanies('');
                return;
            }
            compDebounceTimer = setTimeout(() => fetchCompanies(q), 250);
        });

        compSearchInput.addEventListener('focus', function () {
            if (!compAutocompleteEl.classList.contains('visible')) {
                fetchCompanies(this.value.trim());
            }
        });

        compSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideCompAutocomplete();
        });

        document.addEventListener('click', function (e) {
            if (!compSearchInput.contains(e.target) && !compAutocompleteEl.contains(e.target)) {
                hideCompAutocomplete();
            }
        });
    }

    function fetchCompanies(query) {
        fetch(`/api/search_companies.php?q=${encodeURIComponent(query)}`)
            .then(r => {
                if (!r.ok) {
                    throw new Error('HTTP status ' + r.status);
                }
                return r.text();
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Failed to parse JSON: ' + text.substring(0, 100));
                }
            })
            .then(companies => showCompAutocomplete(companies))
            .catch(err => {
                if (compAutocompleteEl) {
                    compAutocompleteEl.innerHTML = `<div style="padding:10px;color:red;font-size:0.85rem;">Error: ${escHtml(err.message)}</div>`;
                    compAutocompleteEl.classList.add('visible');
                }
                console.error(err);
            });
    }

    function showCompAutocomplete(companies) {
        if (!companies.length) {
            compAutocompleteEl.innerHTML = `
                <div style="padding:10px;text-align:center;color:var(--text-muted);font-size:0.85rem;">
                    No companies found
                </div>`;
            compAutocompleteEl.classList.add('visible');
            return;
        }

        compAutocompleteEl.innerHTML = companies.map(c => `
            <div class="autocomplete-item" data-id="${c.id}" style="padding: 10px; cursor: pointer; border-bottom: 1px solid var(--glass-border);">
                <div class="item-info">
                    <div class="item-name" style="font-weight: bold; font-size: 0.9rem;">${escHtml(c.name)}</div>
                    <div class="item-barcode" style="font-size: 0.8rem; color: var(--text-muted);">ID: ${escHtml(c.company_id_number)}</div>
                </div>
            </div>`).join('');

        compAutocompleteEl.classList.add('visible');

        compAutocompleteEl.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.dataset.id;
                const company = companies.find(c => String(c.id) === String(id));
                if (company) {
                    const compInput = document.getElementById('billToCompany');
                    const compIdInput = document.getElementById('billToId');

                    if (compInput) {
                        compInput.value = company.name;
                        localStorage.setItem('invoice_billToCompany', company.name);
                    }
                    if (compIdInput) {
                        compIdInput.value = company.company_id_number;
                        localStorage.setItem('invoice_billToId', company.company_id_number);
                    }
                    updateCompanyBadge(company.name);
                }
                hideCompAutocomplete();
                compSearchInput.value = '';
            });
        });
    }

    function hideCompAutocomplete() {
        if (!compAutocompleteEl) return;
        compAutocompleteEl.classList.remove('visible');
        compAutocompleteEl.innerHTML = '';
    }

    // ─── Utilities ───
    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

})();

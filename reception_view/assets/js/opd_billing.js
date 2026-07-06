/**
 * OPD Billing Manager
 * GM Hospital Management System — Reception Module
 */
class OpdBillingManager {
    constructor() {
        this.apiBase = '/GM_HMS/api/index.php';
        this.selectedPatient = null;
        this.items = [];
        this.paymentMode = 'Cash';
        this.services = [];
        this.doctors = [];
        this.allBills = [];
        this.searchDebounce = null;
        this.doctorDebounce = null;
        this.referralDebounce = null;
        this.sponsorDebounce = null;
        
        // Pagination state
        this.pageSize = 10;
        this.currentPage = 1;
        this.filteredBills = [];
    }

    // ─── Init ────────────────────────────────────────────────
    async init() {
        this.attachSearchListener();
        this.attachReferralListener();
        this.attachSponsorListener();
        this.attachBillSearchListener();

        // Referral Type Conditional logic
        const refType = document.getElementById('referralType');
        if (refType) {
            refType.addEventListener('change', () => this.handleReferralTypeChange());
        }

        // Close service dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#serviceSearchInput') && !e.target.closest('#serviceDropdown')) {
                const dd = document.getElementById('serviceDropdown');
                if (dd) dd.style.display = 'none';
            }
        });
        await Promise.all([
            this.loadStats(),
            this.loadRecentBills(),
            this.loadServices(),
            this.loadDoctors()
        ]);

        // Close dropdowns on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.suggestion-wrapper')) {
                document.getElementById('referralSuggestions')?.classList.remove('active');
                document.getElementById('sponsorSuggestions')?.classList.remove('active');
            }
        });
    }

    handleReferralTypeChange() {
        const type = document.getElementById('referralType').value;
        const referredByGroup = document.getElementById('referredBy').closest('.form-group');
        const referredByInput = document.getElementById('referredBy');
        const addReferralBtn = referredByGroup ? referredByGroup.querySelector('.btn-inside-action') : null;
        
        // Always show the container now
        if (referredByGroup) referredByGroup.style.display = 'block';

        // Only show Add button if type is exactly 'External'
        if (addReferralBtn) {
            addReferralBtn.style.display = (type === 'External') ? 'flex' : 'none';
        }

        if (type === 'Internal') {
            if (referredByInput) {
                referredByInput.placeholder = "Search internal doctor...";
                referredByInput.style.borderColor = "";
            }
        } else {
            if (type === 'External' && referredByInput) {
                referredByInput.placeholder = "Enter name (Required)";
                referredByInput.style.borderColor = "var(--teal)";
            } else if (referredByInput) {
                referredByInput.placeholder = "Enter name";
                referredByInput.style.borderColor = "";
            }
        }
        
        if (referredByInput) referredByInput.value = ''; // clear on switch
        this.hideReferralSuggestions();
    }

    // ─── API Helper ──────────────────────────────────────────
    async api(method, path, body = null) {
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(this.apiBase + path, opts);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || json.error || 'API error');
        return json.data;
    }

    /**
     * Helper to print using POST method (to hide IDs from URL)
     */
    triggerPrint(billId, receiptId = '') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'print_opd_bill.php';
        form.target = '_blank';

        const bInput = document.createElement('input');
        bInput.type = 'hidden';
        bInput.name = 'bill_id';
        bInput.value = billId;
        form.appendChild(bInput);

        if (receiptId) {
            const rInput = document.createElement('input');
            rInput.type = 'hidden';
            rInput.name = 'receipt_id';
            rInput.value = receiptId;
            form.appendChild(rInput);
        }

        document.body.appendChild(form);
        form.submit();
        form.remove();
    }

    // ─── Stats ───────────────────────────────────────────────
    async loadStats() {
        try {
            const s = await this.api('GET', '/api/billing/opd/stats');
            this._setText('statToday', '₹' + this._fmt(s.today_revenue));
            this._setText('statMonth', '₹' + this._fmt(s.month_revenue));
            this._setText('statPending', s.pending_bills);
            this._setText('statOutstanding', '₹' + this._fmt(s.outstanding_amount));
        } catch (e) { /* stats optional */ }
    }

    // ─── Doctors ─────────────────────────────────────────────
    async loadDoctors() {
        try {
            this.doctors = await this.api('GET', '/api/doctors?limit=200');
        } catch (e) {
            console.warn('Could not load doctors:', e.message);
        }
    }

    // ─── Services (radiology_services) ───────────────────────
    async loadServices() {
        try {
            this.services = await this.api('GET', '/api/billing/opd/services');
            this._buildServiceList(this.services);
        } catch (e) {
            console.warn('Could not load services:', e.message);
        }
    }

    _buildServiceList(list) {
        const container = document.getElementById('serviceDropdownList');
        if (!container) return;
        if (!list || list.length === 0) {
            container.innerHTML = '<div style="padding:.75rem 1rem;color:var(--gray-400);font-size:.82rem;">No services found</div>';
            return;
        }

        // Group by modality_name
        const grouped = {};
        list.forEach(s => {
            const cat = s.modality_name || 'General';
            if (!grouped[cat]) grouped[cat] = [];
            grouped[cat].push(s);
        });

        let html = '';
        Object.entries(grouped).forEach(([cat, items]) => {
            html += `<div style="padding:.4rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--teal);background:var(--gray-50);
                        border-bottom:1px solid var(--gray-100);">${cat}</div>`;
            items.forEach(s => {
                const price = parseFloat(s.opd_price) || 0;
                const encoded = encodeURIComponent(JSON.stringify({
                    id: s.service_id,
                    name: s.billing_name,
                    category: cat,
                    price: price
                }));
                html += `<div class="svc-item" data-svc="${encoded}"
                     onclick="opdBilling.pickService(this)"
                     style="padding:.55rem 1rem;cursor:pointer;font-size:.84rem;
                            display:flex;justify-content:space-between;align-items:center;
                            border-bottom:1px solid var(--gray-100);transition:background .12s;"
                     onmouseover="this.style.background='var(--teal-light)'"
                     onmouseout="this.style.background='white'">
                    <span>${this._escape(s.billing_name)}</span>
                    <span style="color:var(--teal);font-weight:600;font-size:.8rem;">₹${this._fmt(price)}</span>
                </div>`;
            });
        });
        container.innerHTML = html;
    }

    openServiceDropdown() {
        const dd = document.getElementById('serviceDropdown');
        if (dd) dd.style.display = 'block';
        this._buildServiceList(this.services); // show all on focus
    }

    filterServiceDropdown(query) {
        const dd = document.getElementById('serviceDropdown');
        if (dd) dd.style.display = 'block';
        const q = query.trim().toLowerCase();
        const filtered = q
            ? this.services.filter(s =>
                (s.billing_name  || '').toLowerCase().includes(q) ||
                (s.modality_name || '').toLowerCase().includes(q)
            )
            : this.services;
        this._buildServiceList(filtered);
    }

    pickService(el) {
        try {
            this._selectedService = JSON.parse(decodeURIComponent(el.dataset.svc));
            document.getElementById('serviceSearchInput').value = this._selectedService.name;
            document.getElementById('serviceDropdown').style.display = 'none';
        } catch { }
    }

    addFromService() {
        if (!this._selectedService) { this.toast('Please search and select a service first', 'info'); return; }
        const s = this._selectedService;
        this.addItemRow(s.category, s.name, 1, parseFloat(s.price) || 0, s.id || null);
        document.getElementById('serviceSearchInput').value = '';
        this._selectedService = null;
    }

    // ─── Patient Search ───────────────────────────────────────
    attachSearchListener() {
        const input = document.getElementById('patientSearchInput');
        if (!input) return;
        input.addEventListener('input', (e) => {
            clearTimeout(this.searchDebounce);
            const q = e.target.value.trim();
            if (q.length < 2) {
                this._renderNoResult('Enter at least 2 characters to search');
                return;
            }
            this._renderLoading();
            this.searchDebounce = setTimeout(() => this.searchPatients(q), 450);
        });
    }

    async searchPatients(q) {
        try {
            const results = await this.api('GET', `/api/billing/opd/search-patients?q=${encodeURIComponent(q)}`);
            this.renderPatientCards(results);
        } catch (e) {
            this._renderNoResult('Search failed: ' + e.message);
        }
    }

    renderPatientCards(list) {
        const container = document.getElementById('patientResults');
        if (!list || list.length === 0) {
            this._renderNoResult('No patients found for this search');
            return;
        }
        container.innerHTML = list.map(p => {
            const statusColor = p.appointment_status == 1 ? '#16a34a' : '#94a3b8';
            const statusText = p.appointment_status == 1 ? 'Active' : 'Completed';
            const date = p.appointment_date ? new Date(p.appointment_date).toLocaleDateString('en-IN') : '—';
            return `
            <div class="patient-card" onclick="opdBilling.selectPatient(${JSON.stringify(p).replace(/"/g, '&quot;')})">
                <div class="pt-name">${this._escape(p.patient_name || p.patient_id)}</div>
                <div class="pt-meta">
                    <span class="pt-badge"><i class="fas fa-id-card"></i> ${p.patient_id}</span>
                    <span class="pt-badge"><i class="fas fa-phone"></i> ${p.phone || '—'}</span>
                    ${p.age ? `<span class="pt-badge"><i class="fas fa-birthday-cake"></i> ${p.age}y</span>` : ''}
                </div>
                <div class="pt-meta" style="margin-top:.25rem;">
                    <span class="pt-badge"><i class="fas fa-calendar"></i> ${date}</span>
                    <span class="pt-badge"><i class="fas fa-user-md"></i> ${this._escape(p.doctor_name || '—')}</span>
                    <span class="pt-badge" style="color:${statusColor};">${statusText}</span>
                </div>
            </div>`;
        }).join('');
    }

    selectPatient(p) {
        this.selectedPatient = p;

        // Mark card as selected
        document.querySelectorAll('.patient-card').forEach(c => {
            c.classList.remove('selected');
        });
        // Find the card by patient ID or appointment ID reference
        const cards = document.querySelectorAll('.patient-card');
        cards.forEach(card => {
            if (card.onclick && card.onclick.toString().includes(p.patient_id)) {
                card.classList.add('selected');
            }
        });

        const grid = document.getElementById('patientInfoGrid');
        if (!grid) return;

        // Prepare date value for input[type=date] (YYYY-MM-DD)
        let dateForInput = '';
        if (p.appointment_date) {
            const d = new Date(p.appointment_date);
            if (!isNaN(d)) {
                dateForInput = d.toISOString().split('T')[0];
            }
        }

        grid.innerHTML = `
            <div class="info-item">
                <label>Patient Name</label>
                <span>${this._escape(p.patient_name || '—')}</span>
            </div>
            <div class="info-item">
                <label>Patient ID</label>
                <span>${p.patient_id || '—'}</span>
            </div>
            <div class="info-item">
                <label>Phone</label>
                <span>${p.phone || '—'}</span>
            </div>
            <div class="info-item">
                <label>Appointment / Visit</label>
                <span>${p.appointment_id || p.bill_id || 'Walk-in'}</span>
            </div>
            <div class="info-item" style="position:relative;">
                <label>Doctor <i class="fas fa-pen-to-square" style="font-size:.7rem;color:var(--teal);margin-left:3px;"></i></label>
                <input type="text"
                       id="editDoctorName"
                       value="${this._escape(p.doctor_name || '')}"
                       placeholder="Search or type doctor name…"
                       autocomplete="off"
                       style="border:1.5px solid var(--teal);border-radius:6px;padding:.28rem .55rem;font-size:.85rem;width:100%;background:#f0fafa;color:var(--gray-800);outline:none;"
                       oninput="opdBilling._onDoctorInput(this.value)"
                       onfocus="opdBilling._showDoctorDropdown(this.value)">
            </div>
            <div class="info-item">
                <label>Date <i class="fas fa-pen-to-square" style="font-size:.7rem;color:var(--teal);margin-left:3px;"></i></label>
                <input type="date"
                       id="editAppointmentDate"
                       value="${dateForInput}"
                       style="border:1.5px solid var(--teal);border-radius:6px;padding:.28rem .55rem;font-size:.85rem;width:100%;background:#f0fafa;color:var(--gray-800);outline:none;"
                       onchange="opdBilling.selectedPatient.appointment_date = this.value">
            </div>
        `;

        // Close portal dropdown on outside click
        setTimeout(() => {
            document.addEventListener('click', this._closeDoctorDropdown = (e) => {
                const inp = document.getElementById('editDoctorName');
                const portal = document.getElementById('doctorPortalDd');
                if (inp && !inp.contains(e.target) && portal && !portal.contains(e.target)) {
                    this._hideDoctorPortal();
                    document.removeEventListener('click', this._closeDoctorDropdown);
                }
            });
        }, 100);

        this.showBillingModal();

        // Add a default Consultation row
        if (this.items.length === 0) {
            this.addItemRow('Consultation', 'General Consultation', 1, 0);
        }
    }

    showBillingModal() {
        if (this.selectedPatient) {
            document.getElementById('billingModalOverlay').classList.add('active');
        }
    }

    hideBillingModal() {
        document.getElementById('billingModalOverlay').classList.remove('active');
    }

    // ── Doctor autocomplete helpers ───────────────────────────
    _onDoctorInput(query) {
        // Save whatever is typed as doctor_name immediately (free-text / outside doctor)
        if (this.selectedPatient) {
            this.selectedPatient.doctor_name = query;
            this.selectedPatient.doctor_id   = null;
        }
        clearTimeout(this.doctorDebounce);
        this.doctorDebounce = setTimeout(() => this._showDoctorDropdown(query), 200);
    }

    /** Get or create the body-level portal dropdown */
    _getDoctorPortal() {
        let portal = document.getElementById('doctorPortalDd');
        if (!portal) {
            portal = document.createElement('div');
            portal.id = 'doctorPortalDd';
            portal.style.cssText = [
                'position:fixed',
                'z-index:99999',
                'background:white',
                'border:1.5px solid var(--teal)',
                'border-radius:8px',
                'box-shadow:0 8px 32px rgba(0,0,0,.18)',
                'max-height:220px',
                'overflow-y:auto',
                'display:none',
                'font-family:Inter,sans-serif'
            ].join(';');
            document.body.appendChild(portal);
        }
        return portal;
    }

    _hideDoctorPortal() {
        const p = document.getElementById('doctorPortalDd');
        if (p) p.style.display = 'none';
    }

    _showDoctorDropdown(query) {
        const inp = document.getElementById('editDoctorName');
        if (!inp) return;

        const portal = this._getDoctorPortal();

        // Position portal exactly below the input
        const rect = inp.getBoundingClientRect();
        portal.style.top    = (rect.bottom + window.scrollY) + 'px';
        portal.style.left   = rect.left + 'px';
        portal.style.width  = rect.width + 'px';
        // Reset to fixed (not affected by scroll)
        portal.style.position = 'fixed';
        portal.style.top      = rect.bottom + 'px';

        const q = (query || '').trim().toLowerCase();
        const list = q
            ? this.doctors.filter(d => (d.full_name || '').toLowerCase().includes(q) || (d.specialization || '').toLowerCase().includes(q))
            : this.doctors.slice(0, 20);

        if (list.length === 0) {
            portal.innerHTML = '<div style="padding:.7rem 1rem;color:#94a3b8;font-size:.82rem;">No doctors found — typed name will be saved</div>';
            portal.style.display = 'block';
            return;
        }

        portal.innerHTML = list.map(d => `
            <div data-did="${this._escape(d.doctor_id)}" data-dname="${this._escape(d.full_name || '')}"
                 onclick="opdBilling._pickDoctorFromEl(this)"
                 style="padding:.55rem 1rem;cursor:pointer;font-size:.84rem;
                        display:flex;justify-content:space-between;align-items:center;
                        border-bottom:1px solid #f1f5f9;transition:background .12s;"
                 onmouseover="this.style.background='rgba(31, 107, 74,.1)'"
                 onmouseout="this.style.background='white'">
                <span style="font-weight:500;color:#1e293b;">${this._escape(d.full_name)}</span>
                <span style="color:var(--teal);font-size:.75rem;margin-left:.5rem;">${this._escape(d.specialization || '')}</span>
            </div>`).join('');
        portal.style.display = 'block';
    }

    _pickDoctorFromEl(el) {
        if (!this.selectedPatient) return;
        const doctorId   = el.dataset.did;
        const doctorName = el.dataset.dname;
        this.selectedPatient.doctor_id   = doctorId;
        this.selectedPatient.doctor_name = doctorName;
        const inp = document.getElementById('editDoctorName');
        if (inp) inp.value = doctorName;
        this._hideDoctorPortal();
    }

    clearPatient() {
        this.selectedPatient = null;
        this.items = [];
        document.getElementById('itemsTableBody').innerHTML = '';
        this.hideBillingModal();
        document.getElementById('patientSearchInput').value = '';
        this._renderNoResult('Enter at least 2 characters to search');
        this.recalculate();
    }

    // ─── Items ────────────────────────────────────────────────
    // Valid enum values in opd_billing_items.item_type
    _validItemTypes() {
        return ['Consultation', 'Investigation', 'Procedure', 'Radiology', 'Scan', 'X-Ray', 'Blood Test', 'Medicine', 'Other', 'Emergency', 'Registration Fee'];
    }

    _resolveItemType(type) {
        const valid = this._validItemTypes();
        if (valid.includes(type)) return type;
        // Map common modality names to valid enum values
        const t = (type || '').toLowerCase();
        if (t.includes('consult')) return 'Consultation';
        if (t.includes('xray') || t.includes('x-ray')) return 'X-Ray';
        if (t.includes('scan') || t.includes('usg') || t.includes('ultrasound') || t.includes('echo')) return 'Scan';
        if (t.includes('ct') || t.includes('mri') || t.includes('radiol') || t.includes('imaging')) return 'Radiology';
        if (t.includes('blood') || t.includes('lab') || t.includes('test') || t.includes('path')) return 'Blood Test';
        if (t.includes('medicine') || t.includes('drug') || t.includes('tablet')) return 'Medicine';
        if (t.includes('procedure') || t.includes('ecg') || t.includes('dressing')) return 'Procedure';
        if (t.includes('emergency') || t.includes('casualty')) return 'Emergency';
        if (t.includes('registration') || t.includes('reg ') || t.includes('reg fee')) return 'Registration Fee';
        return 'Investigation'; // safe fallback for unknown types
    }

    addItemRow(type = '', name = '', qty = 1, price = 0, itemCode = null) {
        const resolvedType = this._resolveItemType(type);
        const id = Date.now();
        this.items.push({ id, type: resolvedType, name, qty, price, discount: 0, itemCode });

        const tbody = document.getElementById('itemsTableBody');
        const tr = document.createElement('tr');
        tr.id = 'row-' + id;
        tr.innerHTML = `
            <td>
                <select onchange="opdBilling._updateItem(${id},'type',this.value)">
                    ${['Consultation', 'Investigation', 'Procedure', 'Radiology', 'Scan', 'X-Ray', 'Blood Test', 'Medicine', 'Other', 'Emergency', 'Registration Fee']
                .map(t => `<option value="${t}" ${t === type ? 'selected' : ''}>${t}</option>`).join('')}
                </select>
            </td>
            <td><input type="text" value="${this._escape(name)}" placeholder="Item name…" onchange="opdBilling._updateItem(${id},'name',this.value)"></td>
            <td><input type="number" min="1" value="${qty}" onchange="opdBilling._updateItem(${id},'qty',this.value)"></td>
            <td><input type="number" min="0" step="0.01" value="${price}" onchange="opdBilling._updateItem(${id},'price',this.value)"></td>
            <td><input type="number" min="0" step="0.01" value="0" onchange="opdBilling._updateItem(${id},'discount',this.value)"></td>
            <td><input type="number" readonly value="${(qty * price).toFixed(2)}" id="rowTotal-${id}" style="background:var(--gray-50);color:var(--gray-500);"></td>
            <td><button class="btn-remove-row" onclick="opdBilling.removeItemRow(${id})"><i class="fas fa-trash-alt"></i></button></td>
        `;
        tbody.appendChild(tr);
        this.recalculate();
    }

    removeItemRow(id) {
        this.items = this.items.filter(i => i.id !== id);
        const row = document.getElementById('row-' + id);
        if (row) row.remove();
        this.recalculate();
    }

    _updateItem(id, field, value) {
        const item = this.items.find(i => i.id === id);
        if (!item) return;
        if (field === 'qty' || field === 'price' || field === 'discount') value = parseFloat(value) || 0;
        item[field] = value;
        const totalEl = document.getElementById('rowTotal-' + id);
        if (totalEl) totalEl.value = (item.qty * item.price - (item.discount || 0)).toFixed(2);

        // Auto-fill Item Name when Type is selected
        if (field === 'type') {
            let autoName = value;
            if (value === 'Consultation') autoName = 'General Consultation';
            
            // Only autofill if the current name is empty or was likely auto-filled
            const defaultNames = ['Consultation', 'General Consultation', 'Registration Fee', 'Emergency', 'Investigation', 'Procedure', 'Radiology', 'Scan', 'X-Ray', 'Blood Test', 'Medicine', 'Other'];
            if (!item.name || defaultNames.includes(item.name)) {
                item.name = autoName;
                const row = document.getElementById('row-' + id);
                if (row) {
                    const nameInput = row.querySelector('td:nth-child(2) input[type="text"]');
                    if (nameInput) nameInput.value = autoName;
                }
            }
        }

        this.recalculate();
    }

    // ─── Discount Handlers ────────────────────────────────────
    // Called when user types in the Discount (₹) field — back-calculate %
    onDiscountAmountChange() {
        const subtotal = this.items.reduce((s, i) => s + (i.qty * i.price), 0);
        const discount = parseFloat(document.getElementById('billDiscount')?.value || 0);
        const pctEl = document.getElementById('billDiscountPct');
        if (pctEl) {
            pctEl.value = subtotal > 0 ? ((discount / subtotal) * 100).toFixed(2) : '0.00';
        }
        this.recalculate();
    }

    // Called when user types in the Discount (%) field — calculate amount
    onDiscountPctChange() {
        const subtotal = this.items.reduce((s, i) => s + (i.qty * i.price), 0);
        const pct = parseFloat(document.getElementById('billDiscountPct')?.value || 0);
        const amtEl = document.getElementById('billDiscount');
        if (amtEl) {
            amtEl.value = ((subtotal * pct) / 100).toFixed(2);
        }
        this.recalculate();
    }

    recalculate() {
        // subtotal is the sum of items' base prices BEFORE item discounts
        const subtotalBase = this.items.reduce((s, i) => s + (i.qty * i.price), 0);
        // sum of item discounts
        const itemDiscounts = this.items.reduce((s, i) => s + (i.discount || 0), 0);
        // subtotal after item discounts
        const subtotalAfterItems = Math.max(0, subtotalBase - itemDiscounts);
        
        const billDiscount = parseFloat(document.getElementById('billDiscount')?.value || 0);
        const grandTotal = Math.max(0, subtotalAfterItems - billDiscount);

        const totalDiscount = itemDiscounts + billDiscount;

        this._setText('sumSubtotal', '₹' + this._fmt(subtotalBase));
        this._setText('sumDiscount', '₹' + this._fmt(totalDiscount));
        this._setText('sumTotal', '₹' + this._fmt(grandTotal));

        // Always sync Amount Paid to Grand Total so they stay in agreement
        const amtInput = document.getElementById('amountPaid');
        if (amtInput) amtInput.value = grandTotal.toFixed(2);
    }

    // ─── Payment Mode ─────────────────────────────────────────
    setMode(btn) {
        document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        this.paymentMode = btn.dataset.mode;
    }

    // ─── Submit Bill ──────────────────────────────────────────
    async submitBill() {
        if (!this.selectedPatient) { this.toast('Please select a patient first', 'error'); return; }
        if (this.items.length === 0) { this.toast('Add at least one billing item', 'error'); return; }

        const discount    = parseFloat(document.getElementById('billDiscount').value || 0);
        const discountPct = parseFloat(document.getElementById('billDiscountPct').value || 0);
        const notes       = document.getElementById('billNotes').value;
        const amountPaid  = parseFloat(document.getElementById('amountPaid').value || 0);
        const refNo       = document.getElementById('refNo').value;

        // Referral / Sponsor Data
        const referralType = document.getElementById('referralType').value;
        const referredBy   = document.getElementById('referredBy').value;
        const sponsor      = document.getElementById('sponsorName').value;

        if (referralType === 'External' && !referredBy.trim()) {
            this.toast('Please provide "Referred By" name for External referral', 'error');
            document.getElementById('referredBy').focus();
            return;
        }

        // Derive the primary service_id and item_name from the first service-picked item
        const firstSvcItem = this.items.find(i => i.itemCode);

        const payload = {
            patient_id:          this.selectedPatient.patient_id,
            name:                this.selectedPatient.patient_name || this.selectedPatient.name || null,
            mobile:              this.selectedPatient.phone || this.selectedPatient.mobile || null,
            doctor_id:           this.selectedPatient.doctor_id   || null,
            doctor_name:         this.selectedPatient.doctor_name || null,
            appointment_id:      this.selectedPatient.appointment_id,
            referral_type:       referralType || null,
            referred_by:         referredBy   || null,
            sponsor:             sponsor      || null,
            discount_amount:     discount,
            discount_percentage: discountPct,
            service_id:          firstSvcItem ? firstSvcItem.itemCode : null,
            item_name:           firstSvcItem ? firstSvcItem.name    : (this.items[0]?.name || null),
            notes,
            items: this.items.map(i => ({
                item_type:       i.type,
                item_code:       i.itemCode || null,
                item_name:       i.name,
                quantity:        i.qty,
                unit_price:      i.price,
                is_taxable:      false,
                tax_percentage:  0,
                discount_amount: i.discount || 0
            })),
            payment: amountPaid > 0 ? {
                amount:       amountPaid,
                payment_mode: this.paymentMode,
                reference_no: refNo
            } : null
        };

        const btn = document.getElementById('btnGenerateBill');
        if (btn) { btn.disabled = true; btn.innerHTML = '<div class="spinner"></div> Saving…'; }

        try {
            const result = await this.api('POST', '/api/billing/opd', payload);
            let msg = `Bill ${result.bill_id} generated successfully!`;
            if (result.receipt_id) {
                msg += ` <a href="javascript:void(0)" onclick="opdBilling.triggerPrint('${result.bill_id}', '${result.receipt_id}')" style="color:white;text-decoration:underline;margin-left:10px;font-weight:600;"><i class="fas fa-print"></i> Print Receipt</a>`;
            }
            this.toast(msg, 'success');
            // Reset form and hide modal
            this.clearPatient();
            this.hideBillingModal();
            this.loadStats();
            this.loadRecentBills();
        } catch (e) {
            this.toast('Failed: ' + e.message, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-file-invoice-dollar"></i> Generate Bill & Close'; }
        }
    }

    resetForm() {
        this.items = [];
        document.getElementById('itemsTableBody').innerHTML = '';
        document.getElementById('billDiscount').value = 0;
        document.getElementById('billDiscountPct').value = 0;
        document.getElementById('billNotes').value = '';
        document.getElementById('amountPaid').value = 0;
        document.getElementById('refNo').value = '';
        
        // Reset referral
        const refType = document.getElementById('referralType');
        if (refType) refType.value = '';
        const refBy = document.getElementById('referredBy');
        if (refBy) refBy.value = '';
        const spon = document.getElementById('sponsorName');
        if (spon) spon.value = '';
        this.handleReferralTypeChange();

        this.recalculate();
        // Reset payment mode
        document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.mode-btn[data-mode="Cash"]')?.classList.add('active');
        this.paymentMode = 'Cash';
    }

    // ─── Recent Bills ─────────────────────────────────────────
    async loadRecentBills() {
        try {
            this.allBills = await this.api('GET', '/api/billing/opd?exclude_purpose=Registration/Appointment');
            this.filterBills(); // This will trigger initial render
        } catch (e) {
            document.getElementById('recentBillsTbody').innerHTML =
                `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>${e.message}</p></div></td></tr>`;
        }
    }

    filterBills() {
        const status = document.getElementById('billStatusFilter').value;
        const query = document.getElementById('billSearchInput')?.value.trim().toLowerCase() || '';

        this.filteredBills = this.allBills.filter(b => {
            const matchesStatus = status ? b.payment_status === status : true;
            
            if (!query) return matchesStatus;

            const billId = (b.bill_id || '').toLowerCase();
            const name = (b.name || b.patient_name || '').toLowerCase();
            const phone = (b.mobile || b.patient_phone || '').toLowerCase();
            const receipt = (b.receipt_no || '').toLowerCase();

            const matchesQuery = billId.includes(query) || 
                                 name.includes(query) || 
                                 phone.includes(query) || 
                                 receipt.includes(query);

            return matchesStatus && matchesQuery;
        });

        this.currentPage = 1; // Reset to page 1 on filter
        this.renderBills();
    }

    attachBillSearchListener() {
        const input = document.getElementById('billSearchInput');
        if (input) {
            input.addEventListener('input', () => {
                this.filterBills();
            });
        }
    }

    renderBills() {
        const bills = this.filteredBills || [];
        const tbody = document.getElementById('recentBillsTbody');
        const paginationContainer = document.getElementById('billsPagination');

        if (!bills || bills.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-receipt"></i><p>No bills found</p></div></td></tr>`;
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        // Calculate pagination slice
        const totalItems = bills.length;
        const totalPages = Math.ceil(totalItems / this.pageSize);
        
        // Ensure current page is valid
        if (this.currentPage > totalPages) this.currentPage = totalPages;
        if (this.currentPage < 1) this.currentPage = 1;

        const startIdx = (this.currentPage - 1) * this.pageSize;
        const endIdx = startIdx + this.pageSize;
        const pageItems = bills.slice(startIdx, endIdx);

        tbody.innerHTML = pageItems.map(b => {
            const date = b.bill_date ? new Date(b.bill_date).toLocaleDateString('en-IN') : '—';
            const status = (b.payment_status || 'Pending').toLowerCase();
            const badgeClass = status === 'paid' ? 'badge-paid' : 'badge-pending';
            const displayStatus = status === 'paid' ? 'PAID' : 'PENDING';
            
            return `<tr>
                <td><a href="javascript:void(0)" onclick="opdBilling.showBillDetails('${b.bill_id}')" style="color:var(--teal); text-decoration:none; font-weight:700; border-bottom:1px dashed var(--teal);">${b.bill_id}</a></td>
                <td><span style="color:var(--teal);font-weight:600;">${b.receipt_no || '—'}</span></td>
                <td>
                    <div style="font-weight:600; color:#1e293b;">${this._escape(b.name || b.patient_name || '—')}</div>
                    <div style="font-size:.72rem; color:#64748b;">ID: ${b.patient_id}</div>
                </td>
                <td>${date}</td>
                <td style="font-weight:700; color:#334155;">₹${this._fmt(b.grand_total)}</td>
                <td style="color:#22c55e;">₹${this._fmt(b.amount_paid)}</td>
                <td style="color:#ef4444; font-weight:600;">₹${this._fmt(b.balance_due)}</td>
                <td><span class="status-badge ${badgeClass}">${displayStatus}</span></td>
                <td>
                    <button onclick="opdBilling.triggerPrint('${b.bill_id}', '${b.primary_receipt_id || ''}')"
                       title="Print Bill"
                       style="display:inline-flex;align-items:center;justify-content:center;
                                width:28px;height:28px;border-radius:6px;
                                background:rgba(31, 107, 74,.1);color:var(--teal);
                                border:none;cursor:pointer;font-size:.8rem;transition:background .15s;"
                       onmouseover="this.style.background='rgba(31, 107, 74,.22)'"
                       onmouseout="this.style.background='rgba(31, 107, 74,.1)'">
                        <i class="fas fa-print"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');

        this.renderPagination(totalPages, totalItems, startIdx, Math.min(endIdx, totalItems));
    }

    renderPagination(totalPages, totalItems, startIdx, currentEndIdx) {
        const container = document.getElementById('billsPagination');
        if (!container) return;

        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = `
            <div class="page-info">
                Showing <b>${startIdx + 1}</b> to <b>${currentEndIdx}</b> of <b>${totalItems}</b> entries
            </div>
            <div class="pagination-controls">
                <button class="page-btn" ${this.currentPage === 1 ? 'disabled' : ''} onclick="opdBilling.goToPage(${this.currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>
        `;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                html += `
                    <button class="page-btn ${i === this.currentPage ? 'active' : ''}" onclick="opdBilling.goToPage(${i})">
                        ${i}
                    </button>
                `;
            } else if (i === this.currentPage - 2 || i === this.currentPage + 2) {
                html += `<span style="padding:0 .25rem;color:var(--gray-400);">...</span>`;
            }
        }

        html += `
                <button class="page-btn" ${this.currentPage === totalPages ? 'disabled' : ''} onclick="opdBilling.goToPage(${this.currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        `;

        container.innerHTML = html;
    }

    goToPage(page) {
        this.currentPage = page;
        this.renderBills();
        // Scroll to top of table if possible
        const cardBody = document.querySelector('.billing-right .billing-card-body .table-wrap');
        if (cardBody) cardBody.scrollTop = 0;
    }
    
    // ─── Bill Details Modal ───────────────────────────────────
    async showBillDetails(billId) {
        const overlay = document.getElementById('billDetailModalOverlay');
        const content = document.getElementById('billDetailContent');
        const billIdSpan = document.getElementById('detailBillId');
        const printBtn = document.getElementById('btnPrintDetail');

        if (!overlay || !content) return;

        billIdSpan.textContent = billId;
        overlay.classList.add('active');
        
        // Show loading state
        content.innerHTML = `
            <div class="loading-state" style="padding:4rem; text-align:center;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; color:var(--teal);"></i>
                <p style="margin-top:1rem; color:#64748b;">Fetching details for ${billId}...</p>
            </div>
        `;

        try {
            const data = await this.api('GET', `/api/billing/opd/${billId}`);
            this.renderBillDetailContent(data);
            
            // Set up print button
            if (printBtn) {
                printBtn.onclick = () => this.triggerPrint(billId, data.primary_receipt_id || '');
            }
        } catch (e) {
            content.innerHTML = `
                <div style="padding:4rem; text-align:center; color:#ef4444;">
                    <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
                    <p style="margin-top:1rem;">Error: ${e.message}</p>
                </div>
            `;
        }
    }

    renderBillDetailContent(data) {
        const content = document.getElementById('billDetailContent');
        const date = data.bill_date ? new Date(data.bill_date).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—';
        const time = data.bill_time || '—';
        const status = (data.payment_status || 'Pending').toLowerCase();
        const displayStatus = status === 'paid' ? 'PAID' : 'PENDING';

        let itemsHtml = (data.items || []).map((item, index) => `
            <tr>
                <td style="color:#1f6b4a; font-weight:600;">${index + 1}</td>
                <td style="color:#1e293b;">${this._escape(item.item_name)}</td>
                <td>${this._fmt(item.quantity)}</td>
                <td>₹${this._fmt(item.unit_price)}</td>
                <td>₹${this._fmt(item.discount_amount)}</td>
                <td style="text-align:right;">₹${this._fmt(item.quantity * item.unit_price - item.discount_amount)}</td>
            </tr>
        `).join('');

        if (!itemsHtml) itemsHtml = '<tr><td colspan="5" style="text-align:center; padding:3rem; color:#94a3b8;"><i class="fas fa-receipt" style="font-size:2rem; display:block; margin-bottom:1rem; opacity:0.3;"></i>No items found for this bill.</td></tr>';

        const hasBalance = data.balance_due > 0;
        const payments = data.payments || [];
        let paymentsHtml = payments.map(p => `
            <tr>
                <td>${new Date(p.payment_date).toLocaleDateString('en-IN')}</td>
                <td><span style="font-weight:600; color:#1e293b;">₹${this._fmt(p.amount)}</span></td>
                <td><span class="badge" style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:4px; font-size:0.75rem; border:1px solid #e2e8f0;">${p.payment_method}</span></td>
                <td style="font-size:0.8rem; color:#64748b;">${p.receipt_id}</td>
            </tr>
        `).join('');

        const historySection = payments.length > 0 ? `
            <div style="margin-top:2rem; padding-top:1.5rem; border-top:2px dashed #e2e8f0;">
                <label style="display:block; margin-bottom:1rem; font-weight:700; color:#475569; font-size:0.9rem;">
                    <i class="fas fa-history" style="color:var(--teal);"></i> Transaction & Payment History
                </label>
                <div class="detail-table-container" style="margin-top:0; border:1px solid #f1f5f9; border-radius:8px;">
                    <table class="detail-table" style="font-size:0.85rem;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Mode</th>
                                <th>Receipt No</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${paymentsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        ` : '';

        content.innerHTML = `
            <div class="receipt-container ${status === 'paid' ? 'is-paid' : ''}">
                ${status === 'paid' ? '<div class="watermark-paid">PAID</div>' : ''}
                
                <!-- Header -->
                <div class="receipt-header">
                    <div class="header-left">
                        <h2>GM Hospital</h2>
                        <p>612, Nagarabhavi Main Rd, Vinayaka Layout,<br>
                        Papreddy Palya, 2nd Stage, Nagarabhaavi,<br>
                        Bengaluru, Karnataka 560072<br>
                        OPD Billing Department</p>
                    </div>
                    <div class="header-right">
                        <h3>PAYMENT RECEIPT</h3>
                        <div class="bill-id">${data.bill_id}</div>
                        <div class="bill-date">${date} - ${time}</div>
                    </div>
                </div>

                <!-- Patient Info -->
                <div class="receipt-info-box">
                    <div class="info-row">
                        <span class="info-label">PATIENT</span>
                        <span class="info-value" style="font-weight:600; color:#1e293b; text-transform:uppercase;">${this._escape(data.patient_name || data.name)}</span>
                        <span class="info-label">PATIENT ID</span>
                        <span class="info-value">${data.patient_id}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">PHONE</span>
                        <span class="info-value">${data.mobile || '—'}</span>
                        <span class="info-label">DOCTOR</span>
                        <span class="info-value">Dr. ${this._escape(data.doctor_name || 'Walking')}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">APPOINTMENT</span>
                        <span class="info-value">${data.appointment_id || '—'}</span>
                        <span class="info-label">CREATED BY</span>
                        <span class="info-value">${this._escape(data.created_by || 'System')}</span>
                    </div>
                </div>

                <!-- Billing Items -->
                <div class="receipt-section-title">BILLING ITEMS</div>
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>DESCRIPTION</th>
                            <th>QTY</th>
                            <th>RATE (₹)</th>
                            <th>DISCOUNT (₹)</th>
                            <th style="text-align:right;">AMOUNT (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>

                <!-- Summary Section -->
                <div class="receipt-summary-container">
                    <div class="summary-table">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₹${this._fmt(data.subtotal)}</span>
                        </div>
                        <div class="summary-row">
                            <span>Discount</span>
                            <span>₹${this._fmt(data.discount_amount)}</span>
                        </div>
                        <div class="summary-row grand-total">
                            <span>Receipt Amount</span>
                            <span>₹${this._fmt(data.amount_paid > 0 ? data.amount_paid : data.grand_total)}</span>
                        </div>
                        ${data.payments && data.payments.length > 0 ? `
                        <div class="summary-row payment-mode">
                            <span style="color:#16a34a;">Payment Mode</span>
                            <span style="color:#16a34a;">${data.payments[data.payments.length-1].payment_method}</span>
                        </div>` : ''}
                    </div>
                </div>

                <!-- Amount in words -->
                <div class="amount-in-words">
                    <span style="color:#64748b; font-weight:600; font-size:0.8rem;">AMOUNT IN WORDS:</span>
                    <span style="font-weight:600; margin-left:5px; color:#1e293b; font-size:0.85rem;">${this.numberToWords(data.amount_paid > 0 ? data.amount_paid : data.grand_total)}</span>
                </div>

                <!-- History -->
                ${payments.length > 0 ? `
                <div class="receipt-section-title" style="margin-top:2rem;">RECEIPT DETAILS</div>
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>RECEIPT NO.</th>
                            <th>DATE</th>
                            <th>MODE OF PAYMENT</th>
                            <th style="text-align:right;">AMOUNT (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${payments.map((p, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${p.receipt_id}</td>
                            <td>${new Date(p.payment_date).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' })}</td>
                            <td>${p.payment_method}</td>
                            <td style="text-align:right;">₹${this._fmt(p.amount)}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>` : ''}

                <!-- Footer -->
                <div class="receipt-footer">
                    <div class="footer-left">
                        <p style="margin:0; font-size:0.75rem; color:#64748b;">Printed on: <strong style="color:#1e293b;">${new Date().toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric'})}, ${new Date().toLocaleTimeString('en-IN', {hour: '2-digit', minute:'2-digit', hour12:true})}</strong></p>
                        <p style="margin:0; margin-bottom:1rem; font-size:0.75rem; color:#64748b;">Printed by: <strong style="color:#1e293b;">${this._escape(data.created_by || 'System')}</strong></p>
                        <p style="margin:0; font-size:0.8rem; color:#94a3b8;">Thank you for choosing GM Hospital.</p>
                        <p style="margin:0; font-size:0.75rem; color:#94a3b8;">This is a computer-generated bill and does not require a signature.</p>
                    </div>
                    <div class="footer-right">
                        <div class="sign-line"></div>
                        <p style="margin:0; font-size:0.8rem; color:#64748b; text-align:center;">Authorised Signatory</p>
                    </div>
                </div>
                
                ${hasBalance ? `
                <div style="margin-top:2rem;">
                    <button onclick="opdBilling.settleBillBalance('${data.bill_id}', ${data.balance_due})" 
                            class="btn btn-primary" 
                            style="background:linear-gradient(135deg, #22c55e, #16a34a); border:none; width:100%; justify-content:center; padding:0.75rem; font-weight:700; border-radius:8px;">
                        <i class="fas fa-money-bill-wave"></i> Pay Outstanding Balance (₹${this._fmt(data.balance_due)})
                    </button>
                </div>
                ` : ''}
            </div>
        `;
    }

    settleBillBalance(billId, balance) {
        this.currentSettleBillId = billId;
        const overlay = document.getElementById('settlementModalOverlay');
        const amtInput = document.getElementById('settleAmount');
        const balDisp = document.getElementById('settleBalanceDisplay');

        if (balDisp) balDisp.textContent = '₹' + this._fmt(balance);
        if (amtInput) {
            amtInput.value = balance;
            amtInput.max = balance;
        }

        if (overlay) overlay.classList.add('active');
    }

    hideSettlementModal() {
        const overlay = document.getElementById('settlementModalOverlay');
        if (overlay) overlay.classList.remove('active');
    }

    async submitSettlement() {
        const billId = this.currentSettleBillId;
        const amount = parseFloat(document.getElementById('settleAmount').value || 0);
        const mode = document.getElementById('settleMode').value;
        const refNo = document.getElementById('settleRefNo').value;

        if (amount <= 0) {
            this.toast('Please enter a valid amount', 'error');
            return;
        }

        const btn = document.querySelector('#settlementModalOverlay .btn-primary');
        const originalText = btn.innerHTML;

        try {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            await this.api('POST', '/api/billing/opd/payment', {
                bill_id: billId,
                amount: amount,
                payment_mode: mode,
                notes: refNo ? `Ref: ${refNo}` : 'Balance payment from Bill Details'
            });

            this.toast('Payment processed successfully!', 'success');
            this.hideSettlementModal();
            
            // Refresh details
            const data = await this.api('GET', `/api/billing/opd/${billId}`);
            this.renderBillDetailContent(data);
            
            // Refresh lists
            this.loadStats();
            this.loadRecentBills();
        } catch (e) {
            this.toast('Payment failed: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    hideBillDetails() {
        const overlay = document.getElementById('billDetailModalOverlay');
        if (overlay) overlay.classList.remove('active');
    }

    // ─── Referral Search ─────────────────────────────────────
    attachReferralListener() {
        const input = document.getElementById('referredBy');
        if (!input) return;

        input.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            clearTimeout(this.referralDebounce);
            if (query.length < 1) {
                this.hideReferralSuggestions();
                return;
            }

            this.referralDebounce = setTimeout(() => {
                const type = document.getElementById('referralType').value;
                if (type === 'Internal') {
                    this.fetchInternalDoctorSuggestions(query);
                } else {
                    this.fetchReferralSuggestions(query);
                }
            }, 300);
        });

        // Show suggestions on focus if query exists
        input.addEventListener('focus', () => {
            const query = input.value.trim();
            if (query.length >= 1) {
                const list = document.getElementById('referralSuggestions');
                if (list && list.children.length > 0) list.classList.add('active');
            }
        });
    }

    fetchInternalDoctorSuggestions(query) {
        const q = query.toLowerCase();
        const list = this.doctors.filter(d => 
            (d.full_name || '').toLowerCase().includes(q) || 
            (d.specialization || '').toLowerCase().includes(q)
        ).slice(0, 10);
        
        // Map to format
        const mapped = list.map(d => ({
            name: d.full_name,
            mobile: d.specialization || 'Internal Doctor'
        }));
        
        this.showReferralSuggestions(mapped);
    }

    attachSponsorListener() {
        const input = document.getElementById('sponsorName');
        const list = document.getElementById('sponsorSuggestions');
        if (!input || !list) return;

        input.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            clearTimeout(this.sponsorDebounce);

            if (query.length < 1) {
                this.hideSponsorSuggestions();
                return;
            }

            this.sponsorDebounce = setTimeout(() => {
                this.fetchSponsorSuggestions(query);
            }, 300);
        });

        // Show suggestions on focus if query exists
        input.addEventListener('focus', () => {
            const query = input.value.trim();
            if (query.length >= 1) {
                const list = document.getElementById('sponsorSuggestions');
                if (list && list.children.length > 0) list.classList.add('active');
            }
        });
    }

    async fetchSponsorSuggestions(query) {
        try {
            const response = await fetch(`/GM_HMS/api/billing/opd/sponsor/search?q=${encodeURIComponent(query)}`);
            const result = await response.json();

            if (result.success) {
                this.showSponsorSuggestions(result.data);
            }
        } catch (error) {
            console.error('Error fetching sponsor suggestions:', error);
        }
    }

    showSponsorSuggestions(suggestions) {
        const list = document.getElementById('sponsorSuggestions');
        if (!list) return;

        list.innerHTML = '';
        
        if (suggestions.length === 0) {
            list.innerHTML = `
                <div class="suggestion-empty">
                    <i class="fas fa-search"></i>
                    <p>No matches found</p>
                </div>
            `;
        } else {
            suggestions.forEach(item => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `
                    <div style="display:flex; align-items:center; gap:10px; font-weight:600;">
                        <i class="fas fa-building" style="font-size:0.8rem; color:#64748b;"></i> 
                        <span>${item.name}</span>
                    </div>
                `;
                div.onclick = () => this.selectSponsor(item.name);
                list.appendChild(div);
            });
        }

        list.classList.add('active');
    }

    selectSponsor(name) {
        const input = document.getElementById('sponsorName');
        if (input) input.value = name;
        this.hideSponsorSuggestions();
    }

    hideSponsorSuggestions() {
        const list = document.getElementById('sponsorSuggestions');
        if (list) list.classList.remove('active');
    }

    async fetchReferralSuggestions(query) {
        try {
            const response = await fetch(`/GM_HMS/api/billing/opd/referral/search?q=${encodeURIComponent(query)}`);
            const result = await response.json();

            if (result.success) {
                this.showReferralSuggestions(result.data);
            }
        } catch (error) {
            console.error('Error fetching referral suggestions:', error);
        }
    }

    showReferralSuggestions(suggestions) {
        const list = document.getElementById('referralSuggestions');
        if (!list) return;

        list.innerHTML = '';
        
        if (suggestions.length === 0) {
            list.innerHTML = `
                <div class="suggestion-empty">
                    <i class="fas fa-search"></i>
                    <p>No record found, please add</p>
                </div>
            `;
        } else {
            suggestions.forEach(item => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.style.flexDirection = 'column';
                div.style.alignItems = 'flex-start';
                div.style.gap = '2px';
                div.innerHTML = `
                    <div style="display:flex; align-items:center; gap:10px; font-weight:600;">
                        <i class="fas fa-user-md" style="font-size:0.8rem;"></i> 
                        <span>${item.name}</span>
                    </div>
                    <div style="font-size:0.8rem; color:#636e72; padding-left:22px;">
                        <i class="fas fa-mobile-alt" style="font-size:0.7rem;"></i> ${item.mobile}
                    </div>
                `;
                div.onclick = () => this.selectReferral(item.name);
                list.appendChild(div);
            });
        }

        list.classList.add('active');
    }

    selectReferral(name) {
        const input = document.getElementById('referredBy');
        if (input) input.value = name;
        this.hideReferralSuggestions();
    }

    hideReferralSuggestions() {
        const list = document.getElementById('referralSuggestions');
        if (list) list.classList.remove('active');
    }

    // ─── Referrals ───────────────────────────────────────────
    showReferralModal() {
        const overlay = document.getElementById('referralModalOverlay');
        if (overlay) overlay.classList.add('active');
        // Clear previous values
        const nameInp = document.getElementById('newReferralName');
        const phoneInp = document.getElementById('newReferralPhone');
        if (nameInp) nameInp.value = '';
        if (phoneInp) phoneInp.value = '';
    }

    hideReferralModal() {
        const overlay = document.getElementById('referralModalOverlay');
        if (overlay) overlay.classList.remove('active');
    }

    async saveNewReferral() {
        const nameInput = document.getElementById('newReferralName');
        const phoneInput = document.getElementById('newReferralPhone');
        const name = nameInput?.value.trim();
        const phone = phoneInput?.value.trim();

        if (!name) {
            this.toast('Please enter the referral name', 'error');
            return;
        }

        const btn = document.querySelector('#referralModalOverlay .btn-primary');
        const originalText = btn ? btn.innerHTML : '';

        try {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }

            // Save to permanent referral_data table
            await this.api('POST', '/api/billing/opd/referral', { 
                name: name, 
                mobile: phone 
            });

            // Update main form field
            const displayName = phone ? `${name} (${phone})` : name;
            const mainReferredBy = document.getElementById('referredBy');
            if (mainReferredBy) {
                mainReferredBy.value = displayName;
            }
            
            this.hideReferralModal();
            this.showSuccessOverlay('Referral saved to database successfully');
        } catch (e) {
            this.toast('Failed to save referral: ' + e.message, 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    }

    /**
     * Show a professional centered success overlay
     */
    showSuccessOverlay(message) {
        let overlay = document.querySelector('.success-overlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'success-overlay';
            overlay.innerHTML = `
                <div class="success-card">
                    <div class="success-icon-wrapper">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3>Success!</h3>
                    <p id="successMessageText"></p>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        const msgText = overlay.querySelector('#successMessageText');
        if (msgText) msgText.textContent = message;

        overlay.classList.add('active');

        // Auto hide after 2 seconds
        setTimeout(() => {
            overlay.classList.remove('active');
        }, 2000);
    }

    // ─── Utility ──────────────────────────────────────────────
    toast(msg, type = 'info') {
        const icons = { success: 'check-circle', error: 'times-circle', info: 'info-circle' };
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i> <span>${msg}</span>`;
        document.getElementById('toastContainer').appendChild(el);
        setTimeout(() => el.remove(), 4500);
    }

    _fmt(n) { return parseFloat(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    _escape(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    _setText(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
    _renderLoading() { document.getElementById('patientResults').innerHTML = `<div class="no-result"><div class="spinner" style="border-color:rgba(31, 107, 74,.2);border-top-color:var(--teal);margin:auto;"></div></div>`; }
    _renderNoResult(msg) { document.getElementById('patientResults').innerHTML = `<div class="no-result"><i class="fas fa-user-search"></i><p>${msg}</p></div>`; }

    numberToWords(num) {
        if (!num || num === 0) return 'Zero Rupees Only';
        const a = ['', 'One ', 'Two ', 'Three ', 'Four ', 'Five ', 'Six ', 'Seven ', 'Eight ', 'Nine ', 'Ten ', 'Eleven ', 'Twelve ', 'Thirteen ', 'Fourteen ', 'Fifteen ', 'Sixteen ', 'Seventeen ', 'Eighteen ', 'Nineteen '];
        const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        const numToWords = (n) => {
            if (n < 20) return a[n];
            if (n < 100) return b[Math.floor(n / 10)] + (n % 10 !== 0 ? ' ' + a[n % 10] : '');
            if (n < 1000) return a[Math.floor(n / 100)] + 'Hundred ' + (n % 100 !== 0 ? 'and ' + numToWords(n % 100) : '');
            if (n < 100000) return numToWords(Math.floor(n / 1000)) + 'Thousand ' + (n % 1000 !== 0 ? numToWords(n % 1000) : '');
            if (n < 10000000) return numToWords(Math.floor(n / 100000)) + 'Lakh ' + (n % 100000 !== 0 ? numToWords(n % 100000) : '');
            return numToWords(Math.floor(n / 10000000)) + 'Crore ' + (n % 10000000 !== 0 ? numToWords(n % 10000000) : '');
        };
        const intPart = Math.floor(num);
        return numToWords(intPart) + 'Rupees Only';
    }
}

// ─── Bootstrap ────────────────────────────────────────────────
const opdBilling = new OpdBillingManager();
document.addEventListener('DOMContentLoaded', () => opdBilling.init());

/**
 * Patient Prescriptions Logic - Reception View
 * Handles searching, fetching, and professional A4 printing
 */

let allPrescriptions = [];

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('patient-id-input');
    if (input) {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') searchPrescription();
        });
    }
    loadAllPrescriptions();
});

async function loadAllPrescriptions() {
    try {
        const response = await API.get('prescriptions');
        if (response.success) {
            allPrescriptions = response.data;
            renderGlobalList(allPrescriptions);
        }
    } catch (error) {
        console.error('Failed to load global prescriptions:', error);
    }
}

function renderGlobalList(prescriptions) {
    const listContainer = document.getElementById('all-prescriptions-list');
    if (prescriptions.length === 0) {
        listContainer.innerHTML = '<div class="no-records">No prescriptions found in system.</div>';
        return;
    }

    listContainer.innerHTML = prescriptions.map(p => renderPrescriptionItem(p)).join('');
}

function renderPrescriptionItem(p) {
    const dateStr = DateUtils.formatDateReadable(p.prescription_date);
    // Split and remove any trailing commas from day part (e.g. "Jan 23," -> "23")
    const dateParts = dateStr.replace(',', '').split(' ');
    return `
        <div class="prescription-item">
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <div style="text-align: center; border-right: 1px solid #e2e8f0; padding-right: 1.5rem; min-width: 60px;">
                    <div style="font-weight: 800; color: #0FA4AF; font-size: 1.1rem;">${dateParts[1] || ''}</div>
                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase;">${dateParts[0] || ''}</div>
                </div>
                <div>
                    <div style="font-weight: 700; color: #1e293b;">${p.diagnosis || 'General Consultation'}</div>
                    <div style="font-size: 0.85rem; color: #64748b;">
                        <span style="color: #0FA4AF; font-weight: 600;">${p.first_name} ${p.last_name}</span>
                        <span style="margin: 0 8px; opacity: 0.3;">|</span>
                        <i class="fas fa-phone-alt" style="margin-right: 4px;"></i> ${p.patient_phone || 'No Phone'}
                        <span style="margin: 0 8px; opacity: 0.3;">|</span>
                        <i class="fas fa-user-md" style="margin-right: 4px;"></i> ${p.doctor_name} 
                        <span style="margin: 0 8px; opacity: 0.3;">|</span>
                        <i class="fas fa-hashtag" style="margin-right: 4px;"></i> ${p.prescription_id}
                    </div>
                </div>
            </div>
            <button onclick="viewProfessionalPrescription('${p.prescription_id}')" class="btn btn-sm btn-outline">
                <i class="fas fa-eye"></i> View & Print
            </button>
        </div>
    `;
}

async function searchPrescription() {
    let searchValue = document.getElementById('patient-id-input').value.trim();
    if (!searchValue) {
        loadAllPrescriptions();
        document.getElementById('results-section').style.display = 'none';
        document.getElementById('empty-state').style.display = 'block';
        return;
    }

    try {
        showLoading('Searching for records...');
        let patientId = searchValue;

        // If it looks like a phone number (only digits, 10+ characters)
        if (/^\d{10,}$/.test(searchValue)) {
            const patientRes = await API.get(`patients?search=${searchValue}`);
            if (patientRes.success && patientRes.data.data && patientRes.data.data.length > 0) {
                patientId = patientRes.data.data[0].patient_id;
            } else {
                hideLoading();
                showEmptyState('No patient found with this mobile number.');
                return;
            }
        }

        const response = await API.get(`prescriptions/receptionist/view/${patientId}`);
        hideLoading();

        if (response.success && response.data && response.data.prescriptions) {
            allPrescriptions = response.data.prescriptions;

            if (allPrescriptions.length > 0) {
                renderResults(response.data);
            } else {
                showEmptyState('No prescriptions found for this patient.');
            }
        } else {
            showEmptyState(response.error || 'Patient not found or no prescription records.');
        }
    } catch (error) {
        hideLoading();
        console.error('Search error:', error);
        showEmptyState('Failed to fetch prescription data. Please try again.');
    }
}

function renderResults(data) {
    const resultsSection = document.getElementById('results-section');
    const allSection = document.getElementById('all-prescriptions-section');
    const emptyState = document.getElementById('empty-state');
    const listContainer = document.getElementById('prescription-history-list');

    // Update Patient Summary
    const firstPresc = data.prescriptions[0];
    document.getElementById('pat-name').textContent = `${firstPresc.first_name} ${firstPresc.last_name}`;
    document.getElementById('pat-details').textContent = `Age: ${firstPresc.age} | Gender: ${firstPresc.gender} | ID: ${data.patient_id}`;
    document.getElementById('pat-initials').textContent = `${firstPresc.first_name[0]}${firstPresc.last_name[0]}`;

    // Render History
    listContainer.innerHTML = data.prescriptions.map(p => renderPrescriptionItem(p)).join('');

    resultsSection.style.display = 'block';
    allSection.style.display = 'none'; // Hide global list when showing specific patient results
    emptyState.style.display = 'none';
}

function showEmptyState(message) {
    const resultsSection = document.getElementById('results-section');
    const allSection = document.getElementById('all-prescriptions-section');
    const emptyState = document.getElementById('empty-state');

    resultsSection.style.display = 'none';
    allSection.style.display = 'block'; // Show global list when search fails/clears
    emptyState.style.display = 'block';

    emptyState.querySelector('h3').textContent = message;
    emptyState.querySelector('p').textContent = 'Please double check the Patient ID and try again.';
}

async function viewProfessionalPrescription(prescriptionId) {
    const presc = allPrescriptions.find(p => p.prescription_id === prescriptionId);
    if (!presc) {
        console.error('Prescription not found:', prescriptionId);
        return;
    }

    console.log('=== PRESCRIPTION DATA ===', presc);
    console.log('Medicines field type:', typeof presc.medicines);
    console.log('Medicines field value:', presc.medicines);

    const modal = document.getElementById('prescription-modal');
    const container = document.getElementById('professional-prescription-a4');

    // Parse medicines if it's a string, otherwise use as-is
    let medicines = [];
    if (typeof presc.medicines === 'string') {
        try {
            medicines = JSON.parse(presc.medicines);
        } catch (e) {
            console.error('Failed to parse medicines JSON:', e);
            medicines = [];
        }
    } else if (Array.isArray(presc.medicines)) {
        medicines = presc.medicines;
    }

    console.log('Parsed medicines array:', medicines);

    // Build the medicines table HTML
    const medicinesHTML = medicines.length > 0 ? medicines.map(m => `
        <tr>
            <td>
                <div class="med-pro-name">${m.name || 'N/A'}</div>
            </td>
            <td style="font-weight: 600;">${m.dosage || '-'}</td>
            <td>${m.timing || 'After Food'}</td>
            <td style="font-weight: 700; color: #0D9488;">${m.frequency || '-'}</td>
            <td>${m.duration || '-'}</td>
            <td class="med-pro-qty">
                ${m.qty || '0'}
                <span style="display: block; font-size: 8px; color: #64748B; text-transform: uppercase;">${m.unit || 'Tabs'}</span>
            </td>
        </tr>
    `).join('') : '<tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 3rem;">No medications prescribed</td></tr>';

    // Construct Elite A4 Layout
    container.innerHTML = `
        <div class="presc-inner-frame">
            <div class="watermark-pro">GM<br>HOSPITAL</div>
            
            <header class="presc-header">
                <div class="hospital-brand">
                    <h1>${presc.hospital_name || 'GM - Hospital'}</h1>
                    <p><i class="fas fa-map-marker-alt"></i> ${presc.hospital_address || 'Health City Circle, West Wing'}</p>
                    <p><i class="fas fa-phone"></i> ${presc.hospital_phone || '+91 98765 43210'} | <i class="fas fa-globe"></i> www.gm-hospital.com</p>
                </div>
                <div class="doc-info-block">
                    <h2>Dr. ${presc.doctor_name}</h2>
                    <div class="spec">${presc.specialization || 'General Medicine'}</div>
                    <div class="reg">REGISTRATION: ${presc.registration_no || 'MED-2026-X77'}</div>
                </div>
            </header>

            <section class="patient-cli-banner">
                <div class="cli-item">
                    <label>Patient Name</label>
                    <span>${presc.first_name} ${presc.last_name}</span>
                </div>
                <div class="cli-item">
                    <label>Patient ID</label>
                    <span>${presc.patient_id}</span>
                </div>
                <div class="cli-item">
                    <label>Age</label>
                    <span>${presc.age}Y</span>
                </div>
                <div class="cli-item">
                    <label>Sex</label>
                    <span>${presc.gender}</span>
                </div>
                <div class="cli-item" style="text-align: right; border-right: none;">
                    <label>Date</label>
                    <span>${DateUtils.formatDateReadable(presc.prescription_date)}</span>
                </div>
            </section>

            <section>
                <div class="presc-section-header">Prescribed Plan (Medicines)</div>
                <table class="med-table-pro">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Medicine & Instructions</th>
                            <th>Dosage</th>
                            <th>Timing</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th style="text-align: center;">Total Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${medicinesHTML}
                    </tbody>
                </table>
            </section>

            <footer class="presc-footer-pro">
                <div style="font-size: 12px; color: #94A3B8;">
                    <p style="margin-bottom: 4px;"><strong>Note:</strong> This is a digitally signed electronic prescription.</p>
                    <p>Printed on: ${new Date().toLocaleDateString('en-GB')} at ${new Date().toLocaleTimeString('en-GB')}</p>
                </div>
                <div class="signature-box" style="visibility: hidden;">
                    <div class="sig-line"></div>
                    <div class="sig-name"></div>
                    <div class="sig-title"></div>
                </div>
            </footer>
        </div>
    `;

    modal.style.display = 'flex';

    // Log the print action
    logPrint(prescriptionId);
}

function closePrescriptionModal() {
    document.getElementById('prescription-modal').style.display = 'none';
}

async function logPrint(prescriptionId) {
    try {
        await API.post('prescriptions/log-print', { prescription_id: prescriptionId });
    } catch (e) {
        console.warn('Print logging failed:', e);
    }
}

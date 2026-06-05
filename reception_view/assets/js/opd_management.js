/**
 * OPD Management Logic
 * Handles Queue Loading, Modal Interactions, and API calls
 */

document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadQueue('all');

    // Filter Buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            loadQueue(e.target.dataset.filter);
        });
    });

    // Vitals Form Submit
    document.getElementById('vitals-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveVitals(new FormData(e.target));
    });



    // Lab Form Submit
    document.getElementById('lab-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveLabRequest(new FormData(e.target));
    });

    // Follow-up Form Submit
    document.getElementById('followup-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveFollowUp(new FormData(e.target));
    });
});

// --- API Calls ---

const API_BASE = '/GM_HMS';

async function loadStats() {
    try {
        const res = await fetch(`${API_BASE}/api/opd/stats`);
        const data = await res.json();

        if (data.success) {
            document.getElementById('stat-opd-total').textContent = data.data.total_opd || 0;
            document.getElementById('stat-doctors-active').textContent = data.data.active_doctors || 0;
            document.getElementById('stat-revenue').textContent = formatCurrency(data.data.revenue_today || 0);
        }
    } catch (error) {
        console.error('Failed to load stats', error);
    }
}

async function loadQueue(filter) {
    const loader = document.getElementById('queue-loading');
    const list = document.getElementById('queue-list');
    const empty = document.getElementById('queue-empty');

    loader.style.display = 'block';
    list.style.display = 'none';
    empty.style.display = 'none';

    try {
        const res = await fetch(`${API_BASE}/api/opd/queue`);
        const json = await res.json();

        loader.style.display = 'none';

        if (!json.success || !json.data || json.data.length === 0) {
            empty.style.display = 'block';
            return;
        }

        const counts = {
            all: json.data.length,
            pending: json.data.filter(p => p.appointment_status === 'Pending').length,
            done: json.data.filter(p => p.appointment_status === 'Completed').length
        };
        updateTabCounts(counts);

        let patients = json.data;
        if (filter !== 'all') {
            patients = patients.filter(p => p.appointment_status === filter);
        }

        if (patients.length === 0) {
            empty.style.display = 'block';
            return;
        }

        list.innerHTML = patients.map(p => createPatientCard(p)).join('');
        list.style.display = 'grid';

    } catch (error) {
        console.error('Error loading queue', error);
        loader.style.display = 'none';
        empty.style.display = 'block'; // Show empty or error state
    }
}

function createPatientCard(p) {
    const statusLower = (p.appointment_status || 'waiting').toLowerCase().replace(' ', '-');
    const statusClass = `status-${statusLower}`; // e.g., status-waiting, status-in-progress

    // Determine badge color
    let badgeClass = 'secondary';
    if (statusLower === 'waiting' || statusLower === 'pending') badgeClass = 'warning';
    if (statusLower === 'in-progress') badgeClass = 'primary';
    if (statusLower === 'completed') badgeClass = 'success';

    return `
        <div class="patient-card ${statusClass}" onclick="openEncounter('${p.appointment_id}')">
            <div class="card-status-bar"></div>
            
            <div class="patient-header">
                <span class="token-badge">Token #${p.token_number || '---'}</span>
                <span class="badge ${badgeClass}">${p.appointment_status}</span>
            </div>
            
            <div class="patient-info">
                <h4>${p.first_name} ${p.last_name}</h4>
                <div class="patient-detail">
                    <i class="fas fa-id-card-alt w-5"></i> ${p.patient_id}
                </div>
                <div class="patient-detail">
                    <i class="fas fa-user-clock w-5"></i> ${p.age} Y / ${p.sex}
                </div>
                <div class="patient-detail">
                    <i class="fas fa-user-md w-5"></i> ${p.doctor_name || 'Not Assigned'}
                </div>
            </div>

            <div class="card-actions">
                <button class="action-btn" onclick="event.stopPropagation(); openEncounter('${p.appointment_id}', 'clinical')">
                    <i class="fas fa-notes-medical"></i> Vitals
                </button>
            </div>
        </div>
    `;
}

// --- Encounter Modal ---

async function openEncounter(appointmentId, tab = 'clinical') {
    const modal = document.getElementById('encounterModal');

    // Reset and Load Data
    document.getElementById('modal-patient-name').textContent = 'Loading...';
    document.getElementById('vitals-form').reset();
    document.getElementById('rx-list').innerHTML = '<div class="spinner mx-auto"></div>';

    modal.classList.remove('hidden');
    switchTab(tab);

    try {
        const res = await fetch(`${API_BASE}/api/opd/encounter/${appointmentId}`);
        const json = await res.json();

        if (json.success) {
            populateEncounterData(json.data);
        }
    } catch (error) {
        console.error('Error loading encounter', error);
        showToast('Failed to load patient details', 'error');
    }
}

function updateTabCounts(counts) {
    const pendingTab = document.querySelector('[data-filter="Pending"]');
    const completedTab = document.querySelector('[data-filter="Completed"]');

    if (pendingTab) pendingTab.textContent = `Pending (${counts.pending})`;
    if (completedTab) completedTab.textContent = `Completed (${counts.done})`;
}
function populateEncounterData(data) {
    const pt = data.appointment;

    // Header
    document.getElementById('modal-patient-name').textContent = `${pt.first_name} ${pt.last_name}`;
    document.getElementById('modal-patient-id').textContent = pt.patient_id;
    document.getElementById('modal-patient-details').textContent = `${pt.age} Y / ${pt.sex} / ${pt.blood_group || '-'}`;
    document.getElementById('modal-doctor-name').textContent = pt.doctor_name || 'Not assigned';

    // Vitals Form Hidden Fields
    document.getElementById('vitals-appt-id').value = pt.appointment_id;
    document.getElementById('vitals-patient-id').value = pt.patient_id;
    document.getElementById('vitals-doctor-id').value = pt.doctor_id;

    // Fill Vitals if exist
    if (data.consultation && data.consultation.vital_signs) {
        try {
            const vitals = JSON.parse(data.consultation.vital_signs);
            const form = document.getElementById('vitals-form');
            form.querySelector('[name="bp"]').value = vitals.bp || '';
            form.querySelector('[name="pulse"]').value = vitals.pulse || '';
            form.querySelector('[name="temp"]').value = vitals.temp || '';
            form.querySelector('[name="weight"]').value = vitals.weight || '';
            form.querySelector('[name="spo2"]').value = vitals.spo2 || '';

            form.querySelector('[name="chief_complaint"]').value = data.consultation.soap_subjective || '';
        } catch (e) {
            console.error('Error parsing vitals', e);
        }
    }

    // Prescriptions
    const rxList = document.getElementById('rx-list');
    let rxHtml = '';



    // 2. Prescription Logic (Split Complex vs Standard)
    if (data.prescriptions && data.prescriptions.length > 0) {
        let standardRx = [];
        let complexHtml = '';

        data.prescriptions.forEach(rx => {
            // Check if this is a complex text block (contains DIAGNOSIS or MEDICATIONS keyword)
            if (rx.name && (rx.name.includes('DIAGNOSIS:') || rx.name.includes('MEDICATIONS:'))) {
                // Parse Complex Text
                let diagnosis = '';
                let medications = '';

                // Simple text parsing
                const parts = rx.name.split('MEDICATIONS:');
                if (parts.length > 0) {
                    diagnosis = parts[0].replace('DIAGNOSIS:', '').trim();
                }
                if (parts.length > 1) {
                    medications = parts[1].trim();
                } else if (rx.name.includes('MEDICATIONS:')) {
                    // Case where only medications might be present without diagnosis keyword
                    medications = rx.name.replace('MEDICATIONS:', '').trim();
                }

                if (diagnosis) {
                    complexHtml += `
                        <div class="diagnosis-section">
                            <div class="section-title"><i class="fas fa-stethoscope"></i> Diagnosis & Findings</div>
                            <div class="section-content">${diagnosis}</div>
                        </div>
                     `;
                }

                if (medications) {
                    complexHtml += `
                        <div class="medication-section">
                            <div class="section-title"><i class="fas fa-pills"></i> Prescribed Protocol</div>
                            <div class="section-content">${medications}</div>
                        </div>
                     `;
                }

            } else {
                standardRx.push(rx);
            }
        });

        // Add Complex Blocks first
        rxHtml += complexHtml;

        // Add Standard Table if items exist
        if (standardRx.length > 0) {
            rxHtml += `
                <div class="rx-table-container">
                    <table class="rx-table">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th>Instructions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            rxHtml += standardRx.map(rx => `
                <tr>
                    <td class="med-name">${rx.name}</td>
                    <td>${rx.dosage || '-'}</td>
                    <td><span class="badge secondary">${rx.frequency || '-'}</span></td>
                    <td>${rx.duration || '-'}</td>
                    <td class="med-instructions">${rx.instructions || '-'}</td>
                </tr>
            `).join('');

            rxHtml += `
                        </tbody>
                    </table>
                </div>
            `;
        }

        // If we processed items but produced no HTML (shouldn't happen), show empty
        if (!complexHtml && standardRx.length === 0) {
            rxHtml = '<p class="text-center text-gray-500 py-4">No active prescriptions.</p>';
        }

    } else {
        rxHtml = '<p class="text-center text-gray-500 py-4">No active prescriptions.</p>';
    }

    rxList.innerHTML = rxHtml;



    // Labs (New Section)
    const labList = document.getElementById('lab-list');
    if (data.lab_orders && data.lab_orders.length > 0) {
        labList.innerHTML = data.lab_orders.map(lab => `
            <div class="d-flex justify-content-between border-bottom py-2">
                <div>
                    <div class="font-weight-bold">${lab.test_name}</div>
                    <div class="text-xs text-secondary">${lab.order_date} | ${lab.status}</div>
                </div>
                <div class="badge badge-${lab.priority === 'Urgent' ? 'danger' : 'info'} h-auto my-auto">${lab.priority}</div>
            </div>
        `).join('');
    } else {
        labList.innerHTML = '<p class="text-center text-gray-500 py-4">No lab orders found.</p>';
    }

    // Follow-up Display
    const followupAlert = document.getElementById('current-followup');
    if (data.consultation && data.consultation.follow_up_date) {
        followupAlert.style.display = 'block';
        document.getElementById('followup-display').textContent = data.consultation.follow_up_date;
        // Pre-fill form
        document.querySelector('[name="follow_up_date"]').value = data.consultation.follow_up_date;
    } else {
        followupAlert.style.display = 'none';
        document.querySelector('[name="follow_up_date"]').value = '';
    }
}

async function saveVitals(formData) {
    const data = Object.fromEntries(formData.entries());

    try {
        const res = await fetch(`${API_BASE}/api/opd/vitals`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();

        if (json.success) {
            showToast('Vitals saved successfully', 'success');
        } else {
            showToast('Failed to save vitals', 'error');
        }
    } catch (error) {
        console.error('Error saving vitals', error);
        showToast('Network error', 'error');
    }
}

async function saveLabRequest(formData) {
    const data = Object.fromEntries(formData.entries());
    data.patient_id = document.getElementById('vitals-patient-id').value;
    data.doctor_id = document.getElementById('vitals-doctor-id').value; // Assuming doctor ID is available

    try {
        const res = await fetch(`${API_BASE}/api/opd/lab-request`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();

        if (json.success) {
            showToast('Lab request sent', 'success');
            document.getElementById('lab-form').reset();
            openEncounter(document.getElementById('vitals-appt-id').value, 'labs');
        } else {
            showToast('Failed to send lab request', 'error');
        }
    } catch (error) {
        console.error('Error saving lab request', error);
    }
}

async function saveFollowUp(formData) {
    const data = Object.fromEntries(formData.entries());
    data.appointment_id = document.getElementById('vitals-appt-id').value;

    try {
        const res = await fetch(`${API_BASE}/api/opd/follow-up`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();

        if (json.success) {
            showToast('Follow-up scheduled', 'success');
            openEncounter(data.appointment_id, 'followup');
        } else {
            showToast('Failed to schedule follow-up', 'error');
        }
    } catch (error) {
        console.error('Error saving follow-up', error);
    }
}

// --- Printing ---

function printPrescription() {
    const patientName = document.getElementById('modal-patient-name').textContent;
    const doctorName = document.getElementById('modal-doctor-name').textContent;
    const items = document.getElementById('rx-list').innerHTML;

    // Create a print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Prescription - ${patientName}</title>
            <style>
                body { font-family: 'Inter', sans-serif; padding: 2rem; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 1rem; margin-bottom: 2rem; }
                .meta { display: flex; justify-content: space-between; margin-bottom: 2rem; }
                .rx-header { font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem; }
                .rx-item { margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px dashed #ccc; }
                .rx-name { font-weight: bold; font-size: 1.1rem; }
                .footer { margin-top: 4rem; text-align: right; border-top: 1px solid #ccc; padding-top: 1rem; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>GM HOSPITAL</h1>
                <p>Excellence in Healthcare</p>
            </div>
            <div class="meta">
                <div>
                    <strong>Patient:</strong> ${patientName}<br>
                    <strong>Date:</strong> ${new Date().toLocaleDateString()}
                </div>
                <div>
                    <strong>Doctor:</strong> ${doctorName}
                </div>
            </div>
            <div class="rx-header">Rx</div>
            <div class="content">
                ${items}
            </div>
            <div class="footer">
                <p>Doctor's Signature</p>
            </div>
            <script>window.print();</script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// --- Reports ---

async function openReportsModal() {
    const modal = document.getElementById('reportsModal');
    modal.classList.remove('hidden');
    await loadReports();
}

function closeReportsModal() {
    document.getElementById('reportsModal').classList.add('hidden');
}

async function loadReports() {
    // Clear previous
    document.getElementById('report-daily-trend').innerHTML = '<div class="spinner mx-auto"></div>';
    document.getElementById('report-revenue').innerHTML = '<div class="spinner mx-auto"></div>';
    document.getElementById('report-doctor-wise').innerHTML = '<tr><td colspan="2" class="text-center">Loading...</td></tr>';

    try {
        console.log('Fetching reports from:', `${API_BASE}/api/opd/reports`);
        const res = await fetch(`${API_BASE}/api/opd/reports`);
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers);

        const text = await res.text();
        console.log('Raw response:', text);

        let json;
        try {
            json = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text was:', text);
            showToast('Server returned invalid JSON: ' + text.substring(0, 100), 'error');
            return;
        }

        if (json.success) {
            const data = json.data;

            // 1. Daily Trend
            if (data.daily_trend && data.daily_trend.length > 0) {
                const list = data.daily_trend.map(d => `<div class="d-flex justify-content-between border-bottom py-2">
                    <span>${d.date}</span>
                    <span class="font-weight-bold">${d.count}</span>
                </div>`).join('');
                document.getElementById('report-daily-trend').innerHTML = list;
            } else {
                document.getElementById('report-daily-trend').innerHTML = '<p class="text-secondary text-center">No data available</p>';
            }

            // 2. Revenue
            if (data.revenue) {
                document.getElementById('report-revenue').innerHTML = `
                    <div class="text-center py-4">
                        <h3 class="text-success font-weight-bold display-4">${formatCurrency(data.revenue.total || 0)}</h3>
                        <p class="text-secondary">${data.revenue.count || 0} Invoices generated this month</p>
                    </div>
                `;
            }

            // 3. Doctor Wise
            if (data.doctor_wise && data.doctor_wise.length > 0) {
                const rows = data.doctor_wise.map(d => `
                    <tr>
                        <td>${d.full_name}</td>
                        <td class="font-weight-bold">${d.count}</td>
                    </tr>
                `).join('');
                document.getElementById('report-doctor-wise').innerHTML = rows;
            } else {
                document.getElementById('report-doctor-wise').innerHTML = '<tr><td colspan="2" class="text-center text-secondary">No consults today</td></tr>';
            }

        } else {
            console.error('API returned error:', json);
            showToast('API Error - Loading sample data', 'warning');
            loadMockReports(); // Fallback to mock data
        }
    } catch (error) {
        console.error('Error loading reports:', error);
        showToast('Network error - Loading sample data', 'warning');
        loadMockReports(); // Fallback to mock data
    }
}

// Mock data fallback for testing/demo
function loadMockReports() {
    console.log('Loading mock reports data...');

    // 1. Daily Trend - Last 7 days
    const dailyTrend = [
        { date: '2025-12-24', count: 45 },
        { date: '2025-12-25', count: 38 },
        { date: '2025-12-26', count: 52 },
        { date: '2025-12-27', count: 61 },
        { date: '2025-12-28', count: 48 },
        { date: '2025-12-29', count: 55 },
        { date: '2025-12-30', count: 42 }
    ];

    const dailyList = dailyTrend.map(d => `<div class="d-flex justify-content-between border-bottom py-2">
        <span>${d.date}</span>
        <span class="font-weight-bold">${d.count}</span>
    </div>`).join('');
    document.getElementById('report-daily-trend').innerHTML = dailyList;

    // 2. Revenue
    document.getElementById('report-revenue').innerHTML = `
        <div class="text-center py-4">
            <h3 class="text-success font-weight-bold display-4">₹45,250.00</h3>
            <p class="text-secondary">127 Invoices generated this month</p>
        </div>
    `;

    // 3. Doctor Wise
    const doctorWise = [
        { full_name: 'Dr. Ravi Kumar', count: 18 },
        { full_name: 'Dr. Priya Sharma', count: 15 },
        { full_name: 'Dr. Amit Patel', count: 12 },
        { full_name: 'Dr. Sneha Reddy', count: 9 }
    ];

    const doctorRows = doctorWise.map(d => `
        <tr>
            <td>${d.full_name}</td>
            <td class="font-weight-bold">${d.count}</td>
        </tr>
    `).join('');
    document.getElementById('report-doctor-wise').innerHTML = doctorRows;
}

// --- Utils ---

function closeModal() {
    document.getElementById('encounterModal').classList.add('hidden');
}

function switchTab(tabId) {
    // Update Buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active'); // Warning: this relies on the click event

    // Update Content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(`tab-${tabId}`).classList.add('active');
}

// Global click to close modal
window.onclick = function (event) {
    const modal = document.getElementById('encounterModal');
    const reportModal = document.getElementById('reportsModal');
    if (event.target == modal) {
        closeModal();
    }
    if (event.target == reportModal) {
        closeReportsModal();
    }
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2);
}

// Toast helper from reception_utils.js is expected to be available

<!-- Nurse Sidebar Navigation -->
<aside
    class="nurse-sidebar fixed lg:relative z-50 h-full transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out"
    id="nurseSidebar">
    <div style="padding: 1.5rem; height: 100%; display: flex; flex-direction: column;">
        <!-- Logo & Branding -->
        <div
            style="display: flex; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <i class="fas fa-user-nurse" style="font-size: 2.5rem; color: #4A90E2; margin-right: 1rem;"></i>
            <div>
                <h1 style="color: white; font-weight: 700; font-size: 1.25rem; margin: 0;">GM Hospital</h1>
                <p style="color: #94a3b8; font-size: 0.75rem; margin: 0;">Nursing Portal</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav style="flex: 1; display: flex; flex-direction: column;">
            <div style="flex: 1;">
                <!-- Dashboard -->
                <a href="dashboard.php" class="sidebar-link" data-page="dashboard">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Patient Care Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p
                        style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem;">
                        <i class="fas fa-user-injured" style="margin-right: 0.5rem;"></i>Patient Care
                    </p>
                </div>

                <a href="patient_care.php" class="sidebar-link" data-page="patient_care">
                    <i class="fas fa-users"></i>
                    <span>My Patients</span>
                    <span class="badge badge-info" id="patient-count"
                        style="margin-left: auto; font-size: 0.7rem;">0</span>
                </a>

                <a href="vitals.php" class="sidebar-link" data-page="vitals">
                    <i class="fas fa-heartbeat"></i>
                    <span>Vital Signs</span>
                </a>

                <a href="medication.php" class="sidebar-link" data-page="medication">
                    <i class="fas fa-pills"></i>
                    <span>Medications (MAR)</span>
                    <span class="badge badge-warning" id="pending-meds"
                        style="margin-left: auto; font-size: 0.7rem;">0</span>
                </a>

                <a href="nurse_notes.php" class="sidebar-link" data-page="nurse_notes">
                    <i class="fas fa-notes-medical"></i>
                    <span>Nurse Notes</span>
                </a>

                <a href="ipd_summary.php" class="sidebar-link" data-page="ipd_summary">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>IPD Summary</span>
                </a>

                <!-- Tasks & Schedule Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p
                        style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem;">
                        <i class="fas fa-calendar-check" style="margin-right: 0.5rem;"></i>Tasks & Schedule
                    </p>
                </div>

                <a href="tasks.php" class="sidebar-link" data-page="tasks">
                    <i class="fas fa-tasks"></i>
                    <span>My Tasks</span>
                    <span class="badge badge-danger" id="pending-tasks"
                        style="margin-left: auto; font-size: 0.7rem;">0</span>
                </a>

                <a href="my_shift.php" class="sidebar-link" data-page="my_shift">
                    <i class="fas fa-clock"></i>
                    <span>My Shift</span>
                </a>

                <!-- Ward Management Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p
                        style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem;">
                        <i class="fas fa-hospital" style="margin-right: 0.5rem;"></i>Ward Management
                    </p>
                </div>

                <a href="ward_management.php" class="sidebar-link" data-page="ward_management">
                    <i class="fas fa-bed"></i>
                    <span>Ward Overview</span>
                </a>



                <a href="reports.php" class="sidebar-link" data-page="reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </div>

            <!-- Quick Actions -->
            <div style="margin-top: auto; padding: 1rem; background: rgba(74, 144, 226, 0.1); border-radius: 0.5rem;">
                <button onclick="quickRecordVitals()" class="btn btn-primary"
                    style="width: 100%; justify-content: center;">
                    <i class="fas fa-heartbeat"></i>
                    <span>Quick Vitals</span>
                </button>
            </div>
        </nav>
    </div>
</aside>

<style>
    .nurse-sidebar {
        width: 280px;
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 0.875rem 1.125rem;
        color: #cbd5e1;
        text-decoration: none;
        border-radius: 12px;
        margin-bottom: 0.375rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.9375rem;
        gap: 1rem;
    }

    .sidebar-link i {
        width: 1.5rem;
        text-align: center;
        font-size: 1.125rem;
    }

    .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.08);
        color: white;
        transform: translateX(6px);
    }

    .sidebar-link.active {
        background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
    }

    .badge {
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .badge-info {
        background: #17A2B8;
        color: white;
    }

    .badge-warning {
        background: #FFC107;
        color: #000;
    }

    .badge-danger {
        background: #DC3545;
        color: white;
    }

    @media (max-width: 1024px) {
        .nurse-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
        }

        .nurse-sidebar.open {
            transform: translateX(0);
        }
    }
</style>

<script>
    // Set active link based on current page
    document.addEventListener('DOMContentLoaded', function () {
        const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
        const links = document.querySelectorAll('.sidebar-link');

        links.forEach(link => {
            if (link.dataset.page === currentPage) {
                link.classList.add('active');
            }
        });

        // Load counts
        loadNurseCounts();
    });

    // Load nurse dashboard counts
    async function loadNurseCounts() {
        try {
            const response = await fetch('api/dashboard.php');
            const result = await response.json();

            if (result.success) {
                const stats = result.data.statistics;

                // Update patient count
                const patientBadge = document.getElementById('patient-count');
                if (patientBadge) {
                    patientBadge.textContent = stats.shift.total_patients || 0;
                }

                // Update pending medications
                const medsBadge = document.getElementById('pending-meds');
                if (medsBadge) {
                    medsBadge.textContent = stats.medications.pending || 0;
                    medsBadge.style.display = (stats.medications.pending > 0) ? 'inline-block' : 'none';
                }

                // Update pending tasks
                const tasksBadge = document.getElementById('pending-tasks');
                if (tasksBadge) {
                    tasksBadge.textContent = stats.tasks.pending || 0;
                    tasksBadge.style.display = (stats.tasks.pending > 0) ? 'inline-block' : 'none';
                }
            }
        } catch (error) {
            console.error('Failed to load nurse counts:', error);
        }
    }

    // Quick vitals function
    function quickRecordVitals() {
        window.location.href = 'vitals.php';
    }

    // Toggle sidebar for mobile
    function toggleSidebar() {
        const sidebar = document.getElementById('nurseSidebar');
        sidebar.classList.toggle('open');
    }
</script>
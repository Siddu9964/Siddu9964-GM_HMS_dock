<!-- Doctor Sidebar Navigation -->
<aside class="doctor-sidebar fixed lg:relative z-50 h-full transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out" id="doctorSidebar">
    <div style="padding: 1.5rem; height: 100%; display: flex; flex-direction: column;">
        <!-- Logo & Branding -->
        <div style="display: flex; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <i class="fas fa-hospital-user" style="font-size: 2.5rem; color: #0FA4AF; margin-right: 1rem;"></i>
            <div>
                <h1 style="color: white; font-weight: 700; font-size: 1.25rem; margin: 0;">GM hospital</h1>
                <p style="color: #94a3b8; font-size: 0.75rem; margin: 0;">Physician Portal</p>
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
            
            <!-- Patient Management Section -->
            <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                <p style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem;">
                    <i class="fas fa-users" style="margin-right: 0.5rem;"></i>Patient Management
                </p>
            </div>
            
            <a href="mypatient.php" class="sidebar-link" data-page="mypatient">
                <i class="fas fa-user-injured"></i>
                <span>My Patients</span>
            </a>
            
            <!-- <a href="patients.php" class="sidebar-link" data-page="patients">
                <i class="fas fa-hospital-user"></i>
                <span>All Patients</span>
            </a> -->
            
            <a href="opd_patients.php" class="sidebar-link" data-page="opd">
                <i class="fas fa-stethoscope"></i>
                <span>OPD Queue</span>
                <span class="badge badge-info" id="opd-count" style="margin-left: auto; font-size: 0.7rem;">0</span>
            </a>
            
            <a href="ipd_patients.php" class="sidebar-link" data-page="ipd">
                <i class="fas fa-bed"></i>
                <span>IPD Patients</span>
                <span class="badge badge-warning" id="ipd-count" style="margin-left: auto; font-size: 0.7rem;">0</span>
            </a>
            
            <!-- Clinical Tools Section -->
            <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                <p style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem;">
                    <i class="fas fa-briefcase-medical" style="margin-right: 0.5rem;"></i>Clinical Tools
                </p>
            </div>
            
            <a href="consultation.php" class="sidebar-link" data-page="consultation">
                <i class="fas fa-notes-medical"></i>
                <span>Consultation</span>
            </a>
            
            <a href="ai_symptom_analysis.php" class="sidebar-link" data-page="ai-analysis">
                <i class="fas fa-brain"></i>
                <span>AI Symptom Analysis</span>
                <span class="badge badge-primary" style="margin-left: auto; font-size: 0.6rem;">AI</span>
            </a>
            
            <a href="prescription.php" class="sidebar-link" data-page="prescription">
                <i class="fas fa-prescription"></i>
                <span>Prescriptions</span>
            </a>
            
            <a href="lab_reports.php" class="sidebar-link" data-page="lab-reports">
                <i class="fas fa-flask"></i>
                <span>Lab Reports</span>
                <span class="badge badge-danger" id="pending-labs" style="margin-left: auto; font-size: 0.7rem; display: none;">0</span>
            </a>
            
            <!-- Reports & Analytics Section -->
            <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                <p style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem;">
                    <i class="fas fa-chart-line" style="margin-right: 0.5rem;"></i>Reports & Analytics
                </p>
            </div>
            
            <a href="analytics.php" class="sidebar-link" data-page="analytics">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
            
            <a href="notifications.php" class="sidebar-link" data-page="notifications">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <span class="badge badge-danger" id="notification-count" style="margin-left: auto; font-size: 0.7rem; display: none;">0</span>
            </a>
            </div>
            
        </nav>
    </div>
</aside>

<style>
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
    background: linear-gradient(135deg, #0FA4AF 0%, #056674 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(15, 164, 175, 0.2);
}

.badge {
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
}
</style>

<script>
// Set active link based on current page
document.addEventListener('DOMContentLoaded', function() {
    // Get current page name more reliably
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop().replace('.php', '');
    
    // Handle special cases and ensure consistent active state
    const pageMapping = {
        'dashboard': 'dashboard',
        'mypatient': 'mypatient',
        'opd_patients': 'opd',
        'ipd_patients': 'ipd',
        'consultation': 'consultation',
        'ai_symptom_analysis': 'ai-analysis',
        'prescription': 'prescription',
        'lab_reports': 'lab-reports',
        'analytics': 'analytics',
        'notifications': 'notifications'
    };
    
    const activePage = pageMapping[currentPage] || currentPage;
    const links = document.querySelectorAll('.sidebar-link');
    
    // Remove all active classes first
    links.forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to current page link
    links.forEach(link => {
        if (link.dataset.page === activePage) {
            link.classList.add('active');
        }
    });
    
    // Load notification count
    if (typeof loadNotificationCount === 'function') loadNotificationCount();
    
    // Load patient counts
    if (typeof loadPatientCounts === 'function') loadPatientCounts();
});

// Load notification count
async function loadNotificationCount() {
    try {
        if (typeof API !== 'undefined') {
            const response = await API.get('notifications/unread-count');
            if (response.success && response.data.count > 0) {
                const badge = document.getElementById('notification-count');
                if (badge) {
                    badge.textContent = response.data.count;
                    badge.style.display = 'inline-block';
                }
            }
        }
    } catch (error) {
        console.error('Failed to load notification count:', error);
    }
}

// Load patient counts
async function loadPatientCounts() {
    try {
        if (typeof API !== 'undefined') {
            // Use global ID if available, otherwise check storage (for other pages)
            // But prefer PHP injection for reliability
            const doctorId = "<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>";
            
            if (!doctorId) {
                console.warn('No doctor ID found for sidebar counts');
                return;
            }

            // Using the new OpdController endpoint or similar? 
            // The original error showed: api/doctors/DOC001/opd-patients?status=Scheduled
            // We should use the endpoint that works for the logged in doctor.
            // If the restrictions are in place, /api/doctors/{id}/... works IF id matches session.
            
            // OPD count (Scheduled)
            const opdResponse = await API.get(`doctors/${doctorId}/opd-patients?status=Scheduled`);
            if (opdResponse.success) {
                const count = opdResponse.data.appointments.length;
                const badge = document.getElementById('opd-count');
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-block' : 'none';
                }
            }
            
            // IPD count (Admitted)
            const ipdResponse = await API.get(`doctors/${doctorId}/ipd-patients?status=Admitted`);
            if (ipdResponse.success) {
                const count = ipdResponse.data.admissions.length;
                const badge = document.getElementById('ipd-count');
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-block' : 'none';
                }
            }
        }
    } catch (error) {
        console.error('Failed to load patient counts:', error);
    }
}

// Quick consultation function
function startQuickConsultation() {
    window.location.href = 'consultation.php';
}
</script>

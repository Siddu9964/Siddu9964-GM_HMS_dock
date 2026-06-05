<?php

// Get current page and directory for proper active state detection

$current_file = basename($_SERVER['PHP_SELF']);

$current_path = dirname($_SERVER['PHP_SELF']);

$request_uri = $_SERVER['REQUEST_URI'];



// Function to check if current page matches the menu item

function isActive($page_file, $current_file, $current_path, $request_uri) {

    // Direct file match

    if ($current_file === $page_file) {

        return true;

    }

    

    // Check if page name appears in the request URI

    if (strpos($request_uri, $page_file) !== false) {

        return true;

    }

    

    return false;

}

?>

<!-- Sidebar -->

<aside id="adminSidebar"

    class="sidebar fixed lg:relative w-52 h-full flex-shrink-0 overflow-y-auto transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-50">

    <div class="p-6">

        <div class="flex items-center mb-8">

            <i class="fas fa-hospital text-4xl text-white mr-3"></i>

            <div>

                <h1 class="text-white font-bold text-xl">GM hospital</h1>

                <p class="text-gray-400 text-xs">Admin Panel</p>

            </div>

        </div>



        <!-- Navigation Menu -->

        <nav class="space-y-2">

            <a href="admin_dashboard.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('admin_dashboard.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-th-large w-6"></i>

                <span class="ml-3">Dashboard</span>

            </a>



            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">OPD & Appointments</p>

            </div>



            <a href="opd_info.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('opd_info.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-stethoscope w-6"></i>

                <span class="ml-3">OPD</span>

            </a>

            <a href="ipd_info.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('ipd_info.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-bed w-6"></i>

                <span class="ml-3">IPD</span>

            </a>

            <a href="../reception_view/appointment_management.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('appointment_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-calendar-alt w-6"></i>

                <span class="ml-3">Appointments</span>

            </a>



            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">Staff Management</p>

            </div>

            <a href="doctor_management.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('doctor_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-user-md w-6"></i>

                <span class="ml-3">Doctors</span>

            </a>

            <a href="staff_management.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('staff_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-user-nurse w-6"></i>

                <span class="ml-3">Nurses & Staff</span>

            </a>

            <a href="nurse_assignment.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('nurse_assignment.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-calendar-alt w-6"></i>

                <span class="ml-3">Nurse Assignments</span>

            </a>

            <a href="department_management.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('department_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-building w-6"></i>

                <span class="ml-3">Departments</span>

            </a>

            <a href="patient_registration.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('patient_registration.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-user-injured w-6"></i>

                <span class="ml-3">Patients</span>

            </a>

            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">Hospital Services</p>

            </div>

            <a href="#pharmacy" class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg">

                <i class="fas fa-pills w-6"></i>

                <span class="ml-3">Pharmacy</span>

            </a>

            <a href="laboratory.php"
                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('laboratory.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">
                <i class="fas fa-flask w-6"></i>
                <span class="ml-3">Laboratory</span>
            </a>

            <a href="#blood-bank" class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg">

                <i class="fas fa-tint w-6"></i>

                <span class="ml-3">Blood Bank</span>

            </a>

            <a href="#ambulance" class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg">

                <i class="fas fa-ambulance w-6"></i>

                <span class="ml-3">Ambulance</span>

            </a>

            <a href="#operations" class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg">

                <i class="fas fa-procedures w-6"></i>

                <span class="ml-3">Operations</span>

            </a>



            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">Finance</p>

            </div>

            <a href="billing_management.php"

                class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg <?php echo isActive('billing_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-file-invoice-dollar w-6"></i>

                <span class="ml-3">Billing</span>

            </a>

            <a href="#insurance" class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg">

                <i class="fas fa-shield-alt w-6"></i>

                <span class="ml-3">Insurance</span>

            </a>



            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">System</p>

            </div>

            <a href="#reports" class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg">

                <i class="fas fa-chart-bar w-6"></i>

                <span class="ml-3">Reports</span>

            </a>

            <a href="#noticeboard" class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg">

                <i class="fas fa-bullhorn w-6"></i>

                <span class="ml-3">Noticeboard</span>

            </a>

            <a href="#users" class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg">

                <i class="fas fa-users-cog w-6"></i>

                <span class="ml-3">User Management</span>

            </a>

            <a href="#settings" class="sidebar-item flex items-center px-4 py-3 text-white rounded-lg">

                <i class="fas fa-cog w-6"></i>

                <span class="ml-3">Settings</span>

            </a>

        </nav>

    </div>

</aside>



<style>

    .sidebar {

        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);

        transition: all 0.3s ease;

    }



    .sidebar-item {

        transition: all 0.3s ease;

        font-size: 15px;

    }



    .sidebar-item:hover {

        background: rgba(255, 255, 255, 0.1);

        transform: translateX(5px);

    }



    .sidebar-item.active {

        background: linear-gradient(135deg, #0FA4AF 0%, #056674 100%);

    }

</style>
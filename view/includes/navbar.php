<?php
// Determine base path relative to view folder
if (!isset($basePath)) {
    $basePath = '../';
}
?>
<!-- Top Navbar -->
<header class="bg-white shadow-sm sticky top-0 z-30">
    <div class="flex items-center justify-between px-8 py-4">
        <div class="flex items-center flex-1">
            <button id="sidebarToggle" class="text-gray-600 hover:text-gray-800 mr-4 lg:hidden" onclick="toggleSidebar()">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="relative flex-1 max-w-md">
                <input type="text" placeholder="Search patients, doctors, appointments..." 
                       class="w-full px-4 py-2 pl-10 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
        </div>
        
        <div class="flex items-center space-x-6">
            <!-- Notifications -->
            <div class="relative">
                <button class="text-gray-600 hover:text-gray-800 relative">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="notification-badge">5</span>
                </button>
            </div>
            
            <!-- Messages -->
            <div class="relative">
                <button class="text-gray-600 hover:text-gray-800 relative">
                    <i class="fas fa-envelope text-xl"></i>
                    <span class="notification-badge">3</span>
                </button>
            </div>
            
            <!-- User Profile -->
            <div class="relative" id="admin-profile-wrapper">
                <button onclick="toggleDropdown()" class="flex items-center space-x-3" id="admin-profile-btn">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'Admin'); ?>&background=667eea&color=fff" 
                         class="w-10 h-10 rounded-full">
                    <div class="text-left hidden md:block">
                        <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin User'); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['designation'] ?? 'Administrator'); ?></p>
                    </div>
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </button>
                <div id="userDropdown" class="dropdown-menu">
                    <a href="javascript:void(0)" onclick="typeof toggleProfileModal === 'function' && toggleProfileModal()" class="block px-4 py-3 hover:bg-gray-50">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <a href="javascript:void(0)" onclick="toggleChangePasswordModal()" class="block px-4 py-3 hover:bg-gray-50">
                        <i class="fas fa-key mr-2"></i> Change Password
                    </a>
                    <hr>
                    <a href="<?php echo $basePath; ?>logout.php" class="block px-4 py-3 hover:bg-gray-50 text-red-600">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Change Password Modal -->
<div id="adminChangePasswordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px); z-index:10002; align-items:center; justify-content:center;">
    <div style="background:white; width:100%; max-width:400px; border-radius:20px; overflow:hidden; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="height:100px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); position:relative;">
            <button onclick="toggleChangePasswordModal()" style="position:absolute; top:15px; right:15px; background:rgba(255,255,255,0.2); border:none; color:white; font-size:24px; width:32px; height:32px; border-radius:50%; cursor:pointer; line-height:1;">&times;</button>
            <h3 style="color:white; margin:0; position:absolute; bottom:15px; left:30px; font-size:1.25rem; font-weight:700;">Change Password</h3>
        </div>
        <div style="padding:30px;">
            <form id="admin-change-password-form">
                <div style="margin-bottom:15px; text-align:left;">
                    <label style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:5px;">Current Password</label>
                    <div style="position:relative;">
                        <input type="password" name="current_password" id="admin-pw-current" style="width:100%; padding:10px 2.5rem 10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:0.875rem; outline:none; box-sizing:border-box;" required>
                        <button type="button" onclick="togglePwVis('admin-pw-current','admin-eye-cur')" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i id="admin-eye-cur" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div style="margin-bottom:15px; text-align:left;">
                    <label style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:5px;">New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="new_password" id="admin-pw-new" style="width:100%; padding:10px 2.5rem 10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:0.875rem; outline:none; box-sizing:border-box;" minlength="8" required>
                        <button type="button" onclick="togglePwVis('admin-pw-new','admin-eye-new')" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i id="admin-eye-new" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small style="color:#64748b; font-size:0.75rem;">Minimum 8 characters</small>
                </div>
                <div id="admin-pw-message" style="display:none; margin-bottom:10px; padding:10px; border-radius:8px; font-size:0.875rem;"></div>
                <div style="display:flex; gap:15px; margin-top:20px;">
                    <button type="button" onclick="toggleChangePasswordModal()" style="flex:1; padding:12px; border-radius:12px; font-weight:600; font-size:14px; border:none; cursor:pointer; background:#e9ecef; color:#495057;">Cancel</button>
                    <button type="submit" id="admin-pw-submit-btn" style="flex:1; padding:12px; border-radius:12px; font-weight:600; font-size:14px; border:none; cursor:pointer; background:#667eea; color:white;">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    min-width: 200px;
    z-index: 1000;
    margin-top: 8px;
}

.dropdown-menu.show {
    display: block;
}

@media (max-width: 1023px) {
    #sidebarToggle { display: block !important; }
}
@media (min-width: 1024px) {
    #sidebarToggle { display: none !important; }
}
</style>

<script>
<?php
// Dynamically calculate the project root URL relative to the web root
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$fullPath = str_replace('\\', '/', dirname(__DIR__, 2));
$projectRoot = str_ireplace($docRoot, '', $fullPath);
$apiBase = rtrim($projectRoot, '/') . '/api/';
?>
const API_BASE = '<?php echo $apiBase; ?>';

function togglePwVis(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
function toggleDropdown() {
    document.getElementById('userDropdown').classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('#admin-profile-wrapper')) {
        var dd = document.getElementById('userDropdown');
        if (dd) dd.classList.remove('show');
    }
});

function toggleChangePasswordModal() {
    var modal = document.getElementById('adminChangePasswordModal');
    var isVisible = modal.style.display === 'flex';
    modal.style.display = isVisible ? 'none' : 'flex';
    if (!isVisible) {
        document.getElementById('admin-change-password-form').reset();
        var msg = document.getElementById('admin-pw-message');
        msg.style.display = 'none';
        document.getElementById('userDropdown').classList.remove('show');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('admin-change-password-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        var btn = document.getElementById('admin-pw-submit-btn');
        var msgDiv = document.getElementById('admin-pw-message');
        var originalText = btn.textContent;
        btn.textContent = 'Updating...';
        btn.disabled = true;
        msgDiv.style.display = 'none';

        try {
            var data = {
                current_password: document.getElementById('admin-pw-current').value,
                new_password: document.getElementById('admin-pw-new').value
            };

            // Use the dynamically calculated API base
            var response = await fetch(API_BASE + 'auth/change-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            var contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Invalid server response');
            }

            var json = await response.json();

            if (json.success) {
                msgDiv.style.display = 'block';
                msgDiv.style.background = '#d1fae5';
                msgDiv.style.color = '#065f46';
                msgDiv.textContent = 'Password updated! Redirecting to login...';
                setTimeout(function() { window.location.href = '<?php echo $basePath; ?>logout.php'; }, 1500);
            } else {
                msgDiv.style.display = 'block';
                msgDiv.style.background = '#fee2e2';
                msgDiv.style.color = '#991b1b';
                msgDiv.textContent = json.message || json.error || 'Failed to update password';
            }
        } catch (error) {
            msgDiv.style.display = 'block';
            msgDiv.style.background = '#fee2e2';
            msgDiv.style.color = '#991b1b';
            msgDiv.textContent = 'An error occurred. Please try again.';
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });
});
</script>

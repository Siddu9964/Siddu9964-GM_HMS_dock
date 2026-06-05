<!-- Doctor Top Navbar -->
<nav class="doctor-navbar">
    <!-- Left Section: Mobile Menu + Page Title -->
    <div style="display: flex; align-items: center; gap: 1rem;">
        <!-- Mobile Menu Toggle -->
        <button onclick="toggleSidebar()" class="btn btn-outline" style="display: none;" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Page Title -->
        <div>
            <h2 id="page-title" style="font-size: 1.5rem; font-weight: 700; color: var(--gray-900); margin: 0;">
                Dashboard
            </h2>
            <p id="page-subtitle" style="font-size: 0.875rem; color: var(--gray-500); margin: 0;">
                Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </p>
        </div>
    </div>
    
    <!-- Right Section: Quick Actions + Notifications + Profile -->
    <div style="display: flex; align-items: center; gap: 1rem;">
        <!-- Emergency Alert Button - Working Fix -->
        <button onclick="triggerEmergencyAlert()" style="background: #dc2626 !important; color: white !important; padding: 0.5rem 1rem !important; border: none !important; border-radius: 0.375rem !important; cursor: pointer !important; display: flex !important; align-items: center !important; gap: 0.5rem !important; position: relative !important; z-index: 99999 !important;" title="Emergency Alert">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="hide-mobile">Emergency</span>
        </button>
        
        <!-- Quick Consultation Button -->
        <button onclick="window.location.href='consultation.php'" class="btn" style="background: #10b981; color: white;" title="Start Consultation">
            <i class="fas fa-notes-medical"></i>
            <span class="hide-mobile">New Consultation</span>
        </button>
        
        <!-- Notifications -->
        <div style="position: relative;">
            <button onclick="toggleNotifications()" class="btn btn-outline" style="position: relative;" title="Notifications">
                <i class="fas fa-bell"></i>
                <span id="navbar-notification-badge" class="notification-badge" style="display: none;">0</span>
            </button>
            
            <!-- Notifications Dropdown -->
            <div id="notifications-dropdown" class="dropdown-menu" style="display: none;">
                <div style="padding: 1rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Notifications</h3>
                    <a href="notifications.php" style="font-size: 0.75rem; color: var(--primary-blue);">View All</a>
                </div>
                <div id="notifications-list" style="max-height: 400px; overflow-y: auto;">
                    <div style="padding: 2rem; text-align: center; color: var(--gray-400);">
                        <i class="fas fa-bell-slash" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                        <p>No new notifications</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Doctor Profile -->
        <div style="position: relative;">
            <button onclick="toggleProfileMenu()" class="profile-button">
                <img id="doctor-photo" src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=0FA4AF&color=fff&size=128" alt="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" 
                     style="width: 42px; height: 42px; border-radius: 12px; object-fit: cover; border: 2px solid #0FA4AF; box-shadow: 0 4px 10px rgba(15, 164, 175, 0.2);">
                <div class="hide-mobile" style="text-align: left; margin-left: 0.75rem;">
                    <div id="doctor-name" style="font-weight: 700; font-size: 0.9375rem; color: #1e293b;">
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </div>
                    <div id="doctor-specialization" style="font-size: 0.75rem; color: #64748b; font-weight: 500;">
                        <?php echo htmlspecialchars($_SESSION['designation']); ?>
                    </div>
                </div>
                <i class="fas fa-chevron-down hide-mobile" style="margin-left: 0.75rem; color: #94a3b8; font-size: 0.8rem;"></i>
            </button>
            
            <!-- Profile Dropdown -->
            <div id="profile-dropdown" class="dropdown-menu" style="display: none; right: 0;">
                <a href="javascript:void(0)" onclick="toggleProfileModal()" class="dropdown-item">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
                <a href="javascript:void(0)" onclick="toggleSettingsModal()" class="dropdown-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="schedule.php" class="dropdown-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Schedule</span>
                </a>
                <hr style="margin: 0.5rem 0; border: none; border-top: 1px solid var(--gray-200);">
                <a href="../logout.php" class="dropdown-item" style="color: var(--status-danger);">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Profile Info Modal -->
<div id="profileModal" class="modal-overlay" style="display: none;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleProfileModal()">&times;</button>
        </div>
        <div class="profile-card-content">
            <div class="profile-card-avatar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'Doctor'); ?>&background=0FA4AF&color=fff&size=128" alt="Avatar">
            </div>
            <h3 class="profile-card-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Doctor'); ?></h3>
            <p class="profile-card-role"><?php echo htmlspecialchars($_SESSION['designation'] ?? 'Medical Professional'); ?></p>
            
            <div class="profile-card-details" style="max-height: 400px; overflow-y: auto;">
                <div class="detail-item">
                    <i class="fas fa-envelope"></i>
                    <div class="detail-text">
                        <span>Email Address</span>
                        <strong><?php echo htmlspecialchars($_SESSION['email'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-phone"></i>
                    <div class="detail-text">
                        <span>Mobile Number</span>
                        <strong><?php echo htmlspecialchars($_SESSION['mobile_number'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-id-card"></i>
                    <div class="detail-text">
                        <span>Doctor Identifier</span>
                        <strong><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'N/A'); ?></strong>
                    </div>
                </div>

                <!-- Personal Info -->
                <div class="detail-item">
                    <i class="fas fa-venus-mars"></i>
                    <div class="detail-text">
                        <span>Gender</span>
                        <strong><?php echo htmlspecialchars($_SESSION['gender'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-birthday-cake"></i>
                    <div class="detail-text">
                        <span>Date of Birth</span>
                         <strong><?php echo htmlspecialchars($_SESSION['date_of_birth'] ?? 'Not Set'); ?> (Age: <?php echo htmlspecialchars($_SESSION['age'] ?? 'N/A'); ?>)</strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-tint"></i>
                    <div class="detail-text">
                        <span>Blood Group</span>
                        <strong><?php echo htmlspecialchars($_SESSION['blood_group'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-ring"></i>
                    <div class="detail-text">
                        <span>Marital Status</span>
                        <strong><?php echo htmlspecialchars($_SESSION['marital_status'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>

                <!-- Professional & Address -->
                 <div class="detail-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="detail-text">
                        <span>Address</span>
                        <strong>
                            <?php 
                                $addrElements = array_filter([
                                    $_SESSION['address'] ?? '',
                                    $_SESSION['city'] ?? '',
                                    $_SESSION['state'] ?? '',
                                    $_SESSION['country'] ?? '',
                                    $_SESSION['pincode'] ?? ''
                                ]);
                                echo !empty($addrElements) ? implode(', ', $addrElements) : 'Not Set';
                            ?>
                        </strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-graduation-cap"></i>
                    <div class="detail-text">
                        <span>Qualification</span>
                        <strong><?php echo htmlspecialchars($_SESSION['qualification'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
                
                <div class="detail-item">
                    <i class="fas fa-user-shield"></i>
                    <div class="detail-text">
                        <span>Access Level</span>
                        <strong><?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'Doctor')); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="detail-text">
                        <span>Account Status</span>
                        <strong style="color: #28a745;"><?php echo htmlspecialchars($_SESSION['status'] ?? 'Active'); ?></strong>
                    </div>
                </div>
            </div>

            <div class="profile-card-actions">
                <button onclick="toggleProfileModal()" class="btn-secondary">Close</button>
                <a href="../logout.php" class="btn-primary">Log Out</a>
            </div>
        </div>
    </div>
</div>



<!-- Settings Modal -->
<div id="settingsModal" class="modal-overlay" style="display: none; z-index: 10001;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleSettingsModal()">&times;</button>
            <h3 style="color: white; margin: 0; position: absolute; bottom: 15px; left: 30px; font-size: 1.25rem;">Settings</h3>
        </div>
        <div class="profile-card-content" style="padding-top: 30px; text-align: left;">

            
            <div style="margin-bottom: 15px;">
                <h4 style="color: #4a5568; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #edf2f7; padding-bottom: 5px;">Preferences</h4>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-moon" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Dark Mode</span>
                    </div>
                    <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 24px;">
                        <input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode()">
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
                        <span class="slider-knob" style="position: absolute; content: ''; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%;"></span>
                    </label>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-bell" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Notifications</span>
                    </div>
                    <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 24px;">
                        <input type="checkbox" id="notificationsToggle" checked onchange="toggleNotificationPref()">
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
                        <span class="slider-knob" style="position: absolute; content: ''; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%;"></span>
                    </label>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; margin-top: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-compress-alt" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Compact Mode</span>
                    </div>
                    <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 24px;">
                        <input type="checkbox" id="compactModeToggle" onchange="toggleCompactMode()">
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
                        <span class="slider-knob" style="position: absolute; content: ''; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%;"></span>
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <h4 style="color: #4a5568; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #edf2f7; padding-bottom: 5px;">Account</h4>
                <div onclick="toggleEditProfileModal(); toggleSettingsModal();" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; cursor: pointer; transition: background 0.2s; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-edit" style="color: #0FA4AF;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Edit Profile</span>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #cbd5e0; font-size: 0.8rem;"></i>
                </div>
                <div onclick="toggleChangePasswordModal(); toggleSettingsModal();" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; cursor: pointer; transition: background 0.2s;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-key" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Change Password</span>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #cbd5e0; font-size: 0.8rem;"></i>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <h4 style="color: #4a5568; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #edf2f7; padding-bottom: 5px;">Support</h4>
                <a href="mailto:support@hospital.com" style="text-decoration: none; color: inherit;">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; cursor: pointer; transition: background 0.2s; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-question-circle" style="color: #6c757d;"></i>
                            <span style="font-weight: 500; font-size: 0.95rem;">Help Center</span>
                        </div>
                        <i class="fas fa-external-link-alt" style="color: #cbd5e0; font-size: 0.8rem;"></i>
                    </div>
                </a>
            </div>
    </div>
</div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="modal-overlay" style="display: none; z-index: 10002;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleEditProfileModal()">&times;</button>
            <h3 style="color: white; margin: 0; position: absolute; bottom: 15px; left: 30px; font-size: 1.25rem;">Edit Profile</h3>
        </div>
        <div class="profile-card-content" style="padding-top: 30px;">
            <form id="edit-profile-form" onsubmit="handleProfileUpdate(event)">
                <div style="text-align: center; margin-bottom: 20px; position: relative; width: 100px; margin-left: auto; margin-right: auto;">
                    <img id="edit-profile-preview" src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'Doctor'); ?>&background=0FA4AF&color=fff&size=128" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #0FA4AF;">
                    <label for="profile-photo-input" style="position: absolute; bottom: 0; right: 0; background: #0FA4AF; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                        <i class="fas fa-camera" style="font-size: 14px;"></i>
                    </label>
                    <input type="file" id="profile-photo-input" name="photo" accept="image/*" style="display: none;" onchange="previewProfilePhoto(this)">
                </div>
                <p style="text-align: center; font-size: 0.75rem; color: #6c757d; margin-bottom: 20px;">Click the camera icon to upload a profile photo (optional)</p>
                
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">Full Name <span style="color: #6c757d; font-size: 0.75rem;">(optional)</span></label>
                    <input type="text" id="edit-profile-name" name="full_name" class="form-control" placeholder="Enter your full name">
                </div>
                
                <div class="profile-card-actions">
                    <button type="button" onclick="toggleEditProfileModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="changePasswordModal" class="modal-overlay" style="display: none; z-index: 10002;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleChangePasswordModal()">&times;</button>
            <h3 style="color: white; margin: 0; position: absolute; bottom: 15px; left: 30px; font-size: 1.25rem;">Change Password</h3>
        </div>
        <div class="profile-card-content" style="padding-top: 30px;">
            <form id="change-password-form">
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">Current Password</label>
                    <div style="position:relative;">
                        <input type="password" name="current_password" id="doc-pw-current" class="form-control" required style="padding-right:2.5rem;">
                        <button type="button" onclick="togglePwVis('doc-pw-current','doc-eye-cur')" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i id="doc-eye-cur" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="new_password" id="doc-pw-new" class="form-control" minlength="8" required style="padding-right:2.5rem;">
                        <button type="button" onclick="togglePwVis('doc-pw-new','doc-eye-new')" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i id="doc-eye-new" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small style="color: #64748b; font-size: 0.75rem;">Minimum 8 characters</small>
                </div>
                <div id="change-pw-msg" style="display:none; padding:10px; border-radius:8px; font-size:0.875rem; margin-bottom:10px;"></div>
                <div class="profile-card-actions">
                    <button type="button" onclick="toggleChangePasswordModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Update Password</button>
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
    background: var(--status-danger);
    color: white;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0.15rem 0.4rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.profile-button {
    display: flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    padding: 0.4rem 0.75rem;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.profile-button:hover {
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border-color: #0FA4AF;
}

.dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: white;
    border-radius: 0.5rem;
    box-shadow: var(--shadow-xl);
    min-width: 200px;
    z-index: 1000;
    animation: slideUp 0.2s ease-out;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--gray-700);
    text-decoration: none;
    font-size: 0.875rem;
    transition: background 0.2s ease;
}

.dropdown-item:hover {
    background: var(--gray-50);
}

.dropdown-item i {
    width: 1.25rem;
    text-align: center;
}

@media (max-width: 1024px) {
    #mobile-menu-btn {
        display: inline-flex !important;
    }
    
    .hide-mobile {
        display: none !important;
    }
}

/* Ensure Emergency button is always visible - Force visibility */
nav.doctor-navbar .btn-danger[onclick*="triggerEmergencyAlert"] {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: relative !important;
    z-index: 1001 !important;
    transform: none !important;
    clip: none !important;
    clip-path: none !important;
}

/* Additional override for any potential hiding */
.btn-danger[onclick*="triggerEmergencyAlert"] {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: relative !important;
    z-index: 1001 !important;
    transform: none !important;
    clip: none !important;
    clip-path: none !important;
}

/* Profile Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-card-modal {
    background: white;
    width: 100%;
    max-width: 400px;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.profile-card-header {
    height: 100px;
    background: linear-gradient(135deg, #0FA4AF 0%, #056674 100%);
    position: relative;
}

.close-modal {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 24px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    line-height: 1;
}

.profile-card-content {
    padding: 0 30px 30px;
    text-align: center;
}

.profile-card-avatar {
    margin-top: -50px;
    margin-bottom: 15px;
    position: relative;
    z-index: 10;
}

.profile-card-avatar img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 5px solid white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.profile-card-name {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 5px;
}

.profile-card-role {
    color: #0FA4AF;
    font-weight: 700;
    font-size: 15px;
    margin-bottom: 25px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.profile-card-details {
    text-align: left;
    margin-bottom: 30px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 10px;
}

.detail-item i {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    color: #6c757d;
    border-radius: 10px;
    font-size: 16px;
}

.detail-text span {
    display: block;
    font-size: 11px;
    color: #adb5bd;
    text-transform: uppercase;
    font-weight: 700;
}

.detail-text strong {
    display: block;
    font-size: 14px;
    color: #495057;
}

.profile-card-actions {
    display: flex;
    gap: 15px;
}

.profile-card-actions button, .profile-card-actions a {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.btn-secondary {
    background: #e9ecef;
    color: #495057;
}

.btn-primary {
    background: #007bff;
    color: white;
    text-align: center;
}

.btn-primary:hover {
    background: #0056b3;
}
/* Dark Mode Styles */
body.dark-mode {
    background-color: #1a202c !important;
    color: #e2e8f0;
}

body.dark-mode .sidebar,
body.dark-mode .doctor-navbar, 
body.dark-mode .reception-navbar,
body.dark-mode .card, 
body.dark-mode .profile-card-modal,
body.dark-mode .dropdown-menu,
body.dark-mode .detail-item {
    background-color: #2d3748 !important;
    border-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, 
body.dark-mode h4, body.dark-mode h5, body.dark-mode h6,
body.dark-mode strong, body.dark-mode .profile-card-name {
    color: #f7fafc !important;
}

body.dark-mode .text-muted, 
body.dark-mode .text-secondary,
body.dark-mode .detail-text span {
    color: #a0aec0 !important;
}

body.dark-mode .detail-item {
    background-color: #2d3748 !important;
}

body.dark-mode .detail-item i {
    background-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

body.dark-mode .dropdown-item {
    color: #e2e8f0 !important;
}

body.dark-mode .dropdown-item:hover {
    background-color: #4a5568 !important;
}

body.dark-mode .form-control {
    background-color: #1a202c !important;
    border-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

/* Compact Mode Styles */
body.compact-mode .container,
body.compact-mode .container-fluid {
    padding-left: 10px !important;
    padding-right: 10px !important;
}

body.compact-mode .card {
    margin-bottom: 10px !important;
}

body.compact-mode .table td, 
body.compact-mode .table th {
    padding: 0.25rem 0.5rem !important;
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

// Toggle password visibility
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
// Load doctor profile on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDoctorProfile();
    loadNavbarNotifications();
    
    // Set dynamic page title based on current page
    setDynamicPageTitle();
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.profile-button') && !event.target.closest('#profile-dropdown')) {
            document.getElementById('profile-dropdown').style.display = 'none';
        }
        if (!event.target.closest('[onclick="toggleNotifications()"]') && !event.target.closest('#notifications-dropdown')) {
            document.getElementById('notifications-dropdown').style.display = 'none';
        }
    });
});

// Load doctor profile
async function loadDoctorProfile() {
    try {
        const doctorId = "<?php echo $_SESSION['user_id']; ?>";
        const response = await API.get(`doctors/${doctorId}`);
        if (response.success) {
            const doctor = response.data;
            let fullName = doctor.full_name;
            
            // Fix Dr. Dr. issue
            let displayName = fullName;
            if (!fullName.toLowerCase().startsWith('dr.')) {
                displayName = `Dr. ${fullName}`;
            }
            
            document.getElementById('doctor-name').textContent = displayName;
            document.getElementById('doctor-specialization').textContent = doctor.specialization || 'Specialist';
            
            // Only update photo if it exists and is a valid URL
            if (doctor.photo && typeof doctor.photo === 'string' && doctor.photo.trim() !== '') {
                const photoUrl = doctor.photo;
                // Test if image loads before setting it
                const testImg = new Image();
                testImg.onload = function() {
                    document.getElementById('doctor-photo').src = photoUrl;
                };
                testImg.onerror = function() {
                    // Fail silently and keep the default avatar
                    if (!sessionStorage.getItem('photo_prompt_shown')) {
                        setTimeout(() => {
                            showPhotoUploadPrompt();
                            sessionStorage.setItem('photo_prompt_shown', 'true');
                        }, 2000);
                    }
                };
                testImg.src = photoUrl;
            } else {
                // No photo in database, show prompt
                if (!sessionStorage.getItem('photo_prompt_shown')) {
                    setTimeout(() => {
                        showPhotoUploadPrompt();
                        sessionStorage.setItem('photo_prompt_shown', 'true');
                    }, 2000);
                }
            }
            
            // Update page subtitle greeting securely
            const firstName = fullName.toLowerCase().startsWith('dr.') ? fullName.split(' ')[1] : fullName.split(' ')[0];
            const greetingElement = document.getElementById('page-subtitle');
            if (greetingElement) {
                greetingElement.textContent = `Welcome back, Dr. ${firstName}`;
            }
        }
    } catch (error) {
        console.error('Failed to load doctor profile:', error);
    }
}

// Load navbar notifications
async function loadNavbarNotifications() {
    try {
        const response = await API.get('notifications?limit=5');
        if (response.success && response.data.length > 0) {
            const badge = document.getElementById('navbar-notification-badge');
            badge.textContent = response.data.length;
            badge.style.display = 'inline-block';
            
            // Populate notifications list
            const listHtml = response.data.map(notif => `
                <a href="notifications.php" class="dropdown-item">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">
                            ${notif.title}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--gray-500);">
                            ${notif.message}
                        </div>
                        <div style="font-size: 0.7rem; color: var(--gray-400); margin-top: 0.25rem;">
                            ${DateUtils.getRelativeTime(notif.created_at)}
                        </div>
                    </div>
                </a>
            `).join('');
            
            document.getElementById('notifications-list').innerHTML = listHtml;
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
    }
}

// Toggle profile modal
function toggleProfileModal() {
    const modal = document.getElementById('profileModal');
    const isVisible = modal.style.display === 'flex';
    modal.style.display = isVisible ? 'none' : 'flex';
    document.body.style.overflow = isVisible ? 'auto' : 'hidden';
    
    // Close dropdown
    document.getElementById('profile-dropdown').style.display = 'none';
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('profileModal');
    if (event.target == modal) {
        toggleProfileModal();
    }
}

// Toggle notifications dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notifications-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    
    // Close profile dropdown
    document.getElementById('profile-dropdown').style.display = 'none';
}

// Toggle profile menu
function toggleProfileMenu() {
    const dropdown = document.getElementById('profile-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    
    // Close notifications dropdown
    document.getElementById('notifications-dropdown').style.display = 'none';
}

// Emergency alert function
function triggerEmergencyAlert() {
    Modal.confirm(
        'Are you sure you want to trigger an emergency alert? This will notify all available staff.',
        async function() {
            try {
                showLoading('Sending emergency alert...');
                // Call emergency API
                // await API.post('EmergencyController.php/api/emergency/alert', {
                //     doctor_id: Storage.get('doctor_id'),
                //     message: 'Emergency assistance required'
                // });
                hideLoading();
                showToast('Emergency alert sent successfully!', 'success');
            } catch (error) {
                hideLoading();
                showToast('Failed to send emergency alert', 'error');
            }
        }
    );
}

// Dark Mode Toggle
function toggleDarkMode() {
    const isDark = document.getElementById('darkModeToggle').checked;
    
    // Toggle style for input
    const slider = document.querySelector('#darkModeToggle + .slider');
    if (isDark) {
        document.body.classList.add('dark-mode');
        slider.style.backgroundColor = '#0FA4AF';
        // Persist
        localStorage.setItem('theme', 'dark');
    } else {
        document.body.classList.remove('dark-mode');
        slider.style.backgroundColor = '#ccc';
        localStorage.setItem('theme', 'light');
    }
}

// Compact Mode Toggle
function toggleCompactMode() {
    const isCompact = document.getElementById('compactModeToggle').checked;
    const slider = document.querySelector('#compactModeToggle + .slider');
    
    if (isCompact) {
        document.body.classList.add('compact-mode');
        slider.style.backgroundColor = '#0FA4AF';
        localStorage.setItem('compactMode', 'enabled');
    } else {
        document.body.classList.remove('compact-mode');
        slider.style.backgroundColor = '#ccc';
        localStorage.setItem('compactMode', 'disabled');
    }
}

// Toggle Edit Profile Modal
function toggleEditProfileModal() {
    const modal = document.getElementById('editProfileModal');
    const isVisible = modal.style.display === 'flex';
    modal.style.display = isVisible ? 'none' : 'flex';
    
    // Close settings modal if opening this one
    if (!isVisible) {
        document.getElementById('settingsModal').style.display = 'none';
        // Pre-fill data if needed (mocked/fetched from DOM for now)
        document.getElementById('edit-profile-name').value = document.getElementById('doctor-name').textContent.replace('Dr. ', '');
    } else {
        document.getElementById('settingsModal').style.display = 'flex';
    }
}

// Preview Profile Photo
function previewProfilePhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('edit-profile-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Handle Profile Update
async function handleProfileUpdate(event) {
    event.preventDefault();
    const btn = event.target.querySelector('button[type="submit"]');
    const originalText = btn.textContent;
    btn.textContent = 'Saving...';
    btn.disabled = true;

    try {
        const form = document.getElementById('edit-profile-form');
        const formData = new FormData(form);
        const doctorId = "<?php echo $_SESSION['user_id']; ?>"; // Get ID from session

        // Use absolute path to ensure it works from any subfolder
        const response = await fetch(`/GM_HMS/api/doctors/${doctorId}/update-profile`, {
            method: 'POST',
            body: formData // allow browser to set Content-Type header with boundary
        });

        const result = await response.json();

        if (result.success) {
            toggleEditProfileModal();
            showToast('Profile updated successfully!', 'success');
            
            // Update UI with returned data
            const doctor = result.data;
            const displayName = `Dr. ${doctor.full_name}`;
            
            document.getElementById('doctor-name').textContent = displayName;
            // Update page subtitle via existing logic or manually
            const firstName = doctor.full_name.split(' ')[0];
            const subtitle = document.getElementById('page-subtitle');
            if(subtitle) subtitle.textContent = `Welcome back, Dr. ${firstName}`;
            
            // Update photo with validation
            if (doctor.photo && typeof doctor.photo === 'string' && doctor.photo.trim() !== '') {
                const photoUrl = doctor.photo;
                const testImg = new Image();
                testImg.onload = function() {
                    document.getElementById('doctor-photo').src = photoUrl;
                };
                testImg.onerror = function() {
                    console.log('Doctor photo not found after update, keeping avatar');
                };
                testImg.src = photoUrl;
            }
            
            // Reload page after 1 second to refresh session and all UI elements
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(result.message || 'Update failed');
        }
    } catch (error) {
        console.error('Profile update failed:', error);
        showToast(error.message, 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// Notification Pref Toggle
function toggleNotificationPref() {
    const isEnabled = document.getElementById('notificationsToggle').checked;
    const slider = document.querySelector('#notificationsToggle + .slider');
    
    if (isEnabled) {
        slider.style.backgroundColor = '#0FA4AF';
        localStorage.setItem('notifications', 'enabled');
        showToast('Notifications enabled', 'success');
    } else {
        slider.style.backgroundColor = '#ccc';
        localStorage.setItem('notifications', 'disabled');
        showToast('Notifications disabled', 'info');
    }
}

// Initialize Preferences on Load
document.addEventListener('DOMContentLoaded', () => {
    // Initialize mobile menu button visibility
    const mobileBtn = document.getElementById('mobile-menu-btn');
    if (mobileBtn) {
        // Show on mobile/tablet, hide on desktop (match reception breakpoint)
        if (window.innerWidth <= 1024) {
            mobileBtn.style.display = 'block';
            console.log('Doctor mobile menu button visible on mobile/tablet');
        } else {
            mobileBtn.style.display = 'none';
        }
    }
    
    // Window resize handler for mobile menu button
    window.addEventListener('resize', function() {
        const mobileBtn = document.getElementById('mobile-menu-btn');
        if (mobileBtn) {
            if (window.innerWidth <= 1024) {
                mobileBtn.style.display = 'block';
            } else {
                mobileBtn.style.display = 'none';
            }
        }
    });
    
    // Check Dark Mode
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        const toggle = document.getElementById('darkModeToggle');
        if(toggle) {
            toggle.checked = true;
            toggle.nextElementSibling.style.backgroundColor = '#0FA4AF';
        }
    }
    
    // Check Notifications
    const savedNotif = localStorage.getItem('notifications');
    const notifToggle = document.getElementById('notificationsToggle');
    if(notifToggle) {
        if (savedNotif === 'disabled') {
            notifToggle.checked = false;
            notifToggle.nextElementSibling.style.backgroundColor = '#ccc';
        } else {
            notifToggle.checked = true; // Default enabled
            notifToggle.nextElementSibling.style.backgroundColor = '#0FA4AF';
        }
    }
});

// Update page title dynamically
function setPageTitle(title, subtitle = '') {
    document.getElementById('page-title').textContent = title;
    if (subtitle) {
        document.getElementById('page-subtitle').textContent = subtitle;
    }
}
// Toggle Change Password Modal
function toggleChangePasswordModal() {
    const modal = document.getElementById('changePasswordModal');
    const isVisible = modal.style.display === 'flex';
    modal.style.display = isVisible ? 'none' : 'flex';
    
    // Close profile modal if opening this one
    if (!isVisible) {
        document.getElementById('profileModal').style.display = 'none';
        document.getElementById('change-password-form').reset();
    } else {
        // Re-open profile modal when closing this one
        document.getElementById('profileModal').style.display = 'flex';
    }
}

// Toggle Settings Modal
function toggleSettingsModal() {
    const modal = document.getElementById('settingsModal');
    
    if (modal.style.display === 'none' || modal.style.display === '') {
        modal.style.display = 'flex';
        // Hide profile dropdown if open
        document.getElementById('profile-dropdown').style.display = 'none';
        
        // Ensure strictly one modal is open
        document.getElementById('profileModal').style.display = 'none';
    } else {
        modal.style.display = 'none';
    }
}

// Handle Password Change Submit
document.getElementById('change-password-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="spinner-sm"></div> Updating...';
    btn.disabled = true;
    
    const msgDiv = document.getElementById('change-pw-msg');
    msgDiv.style.display = 'none';
    
    try {
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        
        const apiPath = '../api/auth/change-password';
        
        const response = await fetch(API_BASE + 'auth/change-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + '<?php echo $_SESSION['auth_token'] ?? ''; ?>'
            },
            body: JSON.stringify(data)
        });
        
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Invalid server response");
        }
        
        const json = await response.json();
        
        msgDiv.style.display = 'block';
        if (json.success) {
            msgDiv.style.background = '#d1fae5';
            msgDiv.style.color = '#065f46';
            msgDiv.textContent = 'Password updated! Redirecting to login...';
            setTimeout(function() { window.location.href = '../logout.php'; }, 1500);
        } else {
            msgDiv.style.background = '#fee2e2';
            msgDiv.style.color = '#991b1b';
            msgDiv.textContent = json.message || json.error || 'Failed to update password';
        }
        
    } catch (error) {
        console.error('Password reset error', error);
        msgDiv.style.display = 'block';
        msgDiv.style.background = '#fee2e2';
        msgDiv.style.color = '#991b1b';
        msgDiv.textContent = 'An error occurred. Please try again.';
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

// Show photo upload prompt modal
function showPhotoUploadPrompt() {
    // Create modal HTML
    const modalHTML = `
        <div id="photoUploadPromptModal" class="modal-overlay" style="display: flex; z-index: 10003;">
            <div class="profile-card-modal" style="max-width: 450px;">
                <div class="profile-card-header">
                    <button class="close-modal" onclick="closePhotoPrompt()">&times;</button>
                    <h3 style="color: white; margin: 0; position: absolute; bottom: 15px; left: 30px; font-size: 1.25rem;">
                        <i class="fas fa-camera"></i> Profile Photo
                    </h3>
                </div>
                <div class="profile-card-content" style="padding-top: 30px;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <i class="fas fa-user-circle" style="font-size: 80px; color: #cbd5e0;"></i>
                    </div>
                    <h4 style="text-align: center; color: #1a202c; margin-bottom: 10px;">No Profile Photo Found</h4>
                    <p style="text-align: center; color: #64748b; font-size: 0.9rem; margin-bottom: 25px;">
                        Upload a professional photo to personalize your profile and help patients recognize you.
                    </p>
                    
                    <div class="profile-card-actions">
                        <button onclick="closePhotoPrompt()" class="btn-secondary">Maybe Later</button>
                        <button onclick="closePhotoPrompt(); toggleEditProfileModal();" class="btn-primary" style="background: #0FA4AF;">
                            <i class="fas fa-upload"></i> Upload Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.body.style.overflow = 'hidden';
}

function closePhotoPrompt() {
    const modal = document.getElementById('photoUploadPromptModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Set dynamic page title based on current page
function setDynamicPageTitle() {
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop().replace('.php', '');
    
    // Page title mapping
    const pageTitles = {
        'dashboard': { title: 'Dashboard', subtitle: 'Welcome back, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>' },
        'mypatient': { title: 'My Patients', subtitle: 'Manage your patient records' },
        'opd_patients': { title: 'OPD Queue', subtitle: 'Outpatient Department Queue' },
        'ipd_patients': { title: 'IPD Patients', subtitle: 'Inpatient Department Management' },
        'consultation': { title: 'Clinical Command Center', subtitle: 'Active Consultation Session' },
        'ai_symptom_analysis': { title: 'AI Symptom Analysis', subtitle: 'AI-powered symptom checker' },
        'prescription': { title: 'Prescriptions', subtitle: 'Manage patient prescriptions' },
        'lab_reports': { title: 'Lab Reports', subtitle: 'View laboratory results' },
        'analytics': { title: 'Analytics', subtitle: 'Medical analytics and insights' },
        'notifications': { title: 'Notifications', subtitle: 'Stay updated with latest alerts' }
    };
    
    // Get title info for current page
    const titleInfo = pageTitles[currentPage] || { 
        title: 'Dashboard', 
        subtitle: 'Welcome back, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>' 
    };
    
    // Update navbar title and subtitle
    const titleElement = document.getElementById('page-title');
    const subtitleElement = document.getElementById('page-subtitle');
    
    if (titleElement) {
        titleElement.textContent = titleInfo.title;
    }
    
    if (subtitleElement) {
        subtitleElement.textContent = titleInfo.subtitle;
    }
}
</script>

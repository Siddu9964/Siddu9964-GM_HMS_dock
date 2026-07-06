/**
 * Doctor Management - Frontend JavaScript
 * 
 * Handles all AJAX calls and DOM manipulation for doctor management
 * Pure vanilla JavaScript with AJAX for API communication
 */

class DoctorManager {
    constructor() {
        this.apiUrl = '/GM_HMS/api';
        this.doctors = [];
        this.filteredDoctors = [];
    }

    /**
     * Initialize the doctor management page
     */
    async init() {
        await this.loadDoctors();
        this.setupEventListeners();
        this.applyFilters(); // Apply default filters (like 'Available') on load
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Search input
        const searchInput = document.getElementById('doctorSearch');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.applyFilters());
        }

        // Filter dropdowns
        const departmentFilter = document.getElementById('departmentFilter');
        if (departmentFilter) {
            departmentFilter.addEventListener('change', () => this.applyFilters());
        }

        const specializationFilter = document.getElementById('specializationFilter');
        if (specializationFilter) {
            specializationFilter.addEventListener('change', () => this.applyFilters());
        }

        const availabilityFilter = document.getElementById('availabilityFilter');
        if (availabilityFilter) {
            availabilityFilter.addEventListener('change', () => this.applyFilters());
        }
    }

    /**
     * Load all doctors from API
     */
    async loadDoctors() {
        try {
            this.showLoading(true);

            const response = await this.apiCall('GET', '/doctors');

            if (response.success) {
                this.doctors = response.data;
                this.filteredDoctors = response.data;
                this.updateStatistics();
                this.populateFilters();
                this.renderDoctors();
            } else {
                this.showError('Failed to load doctors: ' + response.message);
            }
        } catch (error) {
            this.showError('Error loading doctors: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Get single doctor by ID
     */
    async getDoctorById(doctorId) {
        try {
            const response = await this.apiCall('GET', `/doctors/${doctorId}`);

            if (response.success) {
                return response.data;
            } else {
                this.showError('Failed to load doctor: ' + response.message);
                return null;
            }
        } catch (error) {
            this.showError('Error loading doctor: ' + error.message);
            return null;
        }
    }

    /**
     * Create new doctor
     */
    async createDoctor(data) {
        try {
            this.showLoading(true);

            const response = await this.apiCall('POST', '/doctors', data);

            if (response.success) {
                this.showSuccess('Doctor created successfully');
                await this.loadDoctors();
                return response.data;
            } else {
                this.showError('Failed to create doctor: ' + response.message);
                return null;
            }
        } catch (error) {
            this.showError('Error creating doctor: ' + error.message);
            return null;
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Update existing doctor
     */
    async updateDoctor(doctorId, data) {
        try {
            this.showLoading(true);

            const response = await this.apiCall('PUT', `/doctors/${doctorId}`, data);

            if (response.success) {
                this.showSuccess('Doctor updated successfully');
                await this.loadDoctors();
                return response.data;
            } else {
                this.showError('Failed to update doctor: ' + response.message);
                return null;
            }
        } catch (error) {
            this.showError('Error updating doctor: ' + error.message);
            return null;
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Delete doctor
     */
    async deleteDoctor(doctorId) {
        if (!confirm('Are you sure you want to delete this doctor?')) {
            return false;
        }

        try {
            this.showLoading(true);

            const response = await this.apiCall('DELETE', `/doctors/${doctorId}`);

            if (response.success) {
                this.showSuccess('Doctor deleted successfully');
                await this.loadDoctors();
                return true;
            } else {
                this.showError('Failed to delete doctor: ' + response.message);
                return false;
            }
        } catch (error) {
            this.showError('Error deleting doctor: ' + error.message);
            return false;
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Make API call using AJAX
     */
    apiCall(method, endpoint, data = null) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const url = this.apiUrl + endpoint;

            xhr.open(method, url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        reject(new Error('Invalid JSON response'));
                    }
                } else {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        reject(new Error(response.message || 'Request failed'));
                    } catch (e) {
                        reject(new Error('Request failed with status: ' + xhr.status));
                    }
                }
            };

            xhr.onerror = function () {
                reject(new Error('Network error'));
            };

            if (data) {
                xhr.send(JSON.stringify(data));
            } else {
                xhr.send();
            }
        });
    }

    /**
     * Update statistics dashboard
     */
    updateStatistics(data = this.doctors) {
        if (!data) return;

        const total = data.length;
        const available = data.filter(d => d.availability === 'Available').length;
        const offDuty = total - available;
        const departments = new Set(data.map(d => d.department).filter(Boolean)).size;

        this.animateValue('totalDoctors', 0, total, 1000);
        this.animateValue('availableDoctors', 0, available, 1000);
        this.animateValue('offDutyDoctors', 0, offDuty, 1000);
        this.animateValue('departmentCount', 0, departments, 1000);
    }

    /**
     * Animate number counting
     */
    animateValue(id, start, end, duration) {
        const element = document.getElementById(id);
        if (!element) return;

        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = Math.round(current);
        }, 16);
    }

    /**
     * Populate filter dropdowns
     */
    populateFilters() {
        // Populate departments
        const departments = [...new Set(this.doctors.map(d => d.department).filter(Boolean))].sort();
        const deptFilter = document.getElementById('departmentFilter');
        if (deptFilter) {
            deptFilter.innerHTML = '<option value="">All Departments</option>';
            departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept;
                option.textContent = dept;
                deptFilter.appendChild(option);
            });
        }

        // Populate specializations
        const specializations = [...new Set(this.doctors.map(d => d.specialization).filter(Boolean))].sort();
        const specFilter = document.getElementById('specializationFilter');
        if (specFilter) {
            specFilter.innerHTML = '<option value="">All Specializations</option>';
            specializations.forEach(spec => {
                const option = document.createElement('option');
                option.value = spec;
                option.textContent = spec;
                specFilter.appendChild(option);
            });
        }
    }

    /**
     * Apply filters
     */
    applyFilters() {
        const searchTerm = document.getElementById('doctorSearch')?.value.toLowerCase() || '';
        const department = document.getElementById('departmentFilter')?.value || '';
        const specialization = document.getElementById('specializationFilter')?.value || '';
        const availability = document.getElementById('availabilityFilter')?.value || '';

        let filtered = this.doctors;

        if (searchTerm) {
            filtered = filtered.filter(d =>
                d.full_name.toLowerCase().includes(searchTerm) ||
                (d.specialization && d.specialization.toLowerCase().includes(searchTerm))
            );
        }

        if (department) {
            filtered = filtered.filter(d => d.department === department);
        }

        if (specialization) {
            filtered = filtered.filter(d => d.specialization === specialization);
        }

        if (availability) {
            filtered = filtered.filter(d => d.availability === availability);
        }

        this.filteredDoctors = filtered;
        this.updateStatistics(this.filteredDoctors); // Update cards based on current view
        this.renderDoctors();
    }

    /**
     * Clear all filters
     */
    clearFilters() {
        document.getElementById('doctorSearch').value = '';
        document.getElementById('departmentFilter').value = '';
        document.getElementById('specializationFilter').value = '';
        document.getElementById('availabilityFilter').value = '';
        this.applyFilters();
    }

    /**
     * Render doctors grid
     */
    renderDoctors() {
        const grid = document.getElementById('doctorsGrid');
        const emptyState = document.getElementById('emptyState');

        if (!grid) return;

        if (this.filteredDoctors.length === 0) {
            grid.innerHTML = '';
            if (emptyState) emptyState.classList.remove('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');

        grid.innerHTML = this.filteredDoctors.map(doctor => this.createDoctorCard(doctor)).join('');
    }

    /**
     * Create doctor card HTML
     */
    createDoctorCard(doctor) {
        const availabilityClass = doctor.availability === 'Available' ? 'success' : 'danger';
        const availabilityIcon = doctor.availability === 'Available' ? 'fa-check-circle' : 'fa-times-circle';
        const statusDot = doctor.availability === 'Available' ? 'status-dot-online' : 'status-dot-offline';

        const initials = doctor.full_name
            .split(' ')
            .map(n => n[0])
            .join('')
            .substring(0, 2)
            .toUpperCase();

        return `
            <div class="doctor-card-advanced" data-doctor-id="${doctor.doctor_id}">
                <div class="doctor-card-header">
                    <div class="doctor-avatar-wrapper">
                        <div class="doctor-avatar ${availabilityClass}">
                            ${doctor.photo ?
                `<img src="${doctor.photo}" alt="${doctor.full_name}">` :
                `<span class="avatar-initials">${initials}</span>`
            }
                        </div>
                        <span class="${statusDot}"></span>
                    </div>
                    <div class="doctor-status-badge">
                        <span class="status-badge ${availabilityClass}">
                            <i class="fas ${availabilityIcon}"></i>
                            ${doctor.availability}
                        </span>
                    </div>
                </div>
                
                <div class="doctor-card-body">
                    <h4 class="doctor-name">${doctor.full_name}</h4>
                    <p class="doctor-specialization">
                        <i class="fas fa-stethoscope"></i>
                        ${doctor.specialization || 'General Physician'}
                    </p>
                    
                    <div class="doctor-info-grid">
                        <div class="info-item">
                            <i class="fas fa-building"></i>
                            <span>${doctor.department || 'General'}</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-door-open"></i>
                            <span>Room ${doctor.room_number || 'N/A'}</span>
                        </div>
                        <div class="info-item" style="grid-column: 1 / -1; justify-content: center;">
                            <i class="fas fa-clock"></i>
                            <span>${doctor.in_time ? doctor.in_time.substring(0, 5) : '--:--'} to ${doctor.out_time ? doctor.out_time.substring(0, 5) : '--:--'}</span>
                        </div>
                        <div class="info-item highlight">
                            <i class="fas fa-rupee-sign"></i>
                            <span class="fee-amount">₹${doctor.consultation_fee || '0'}</span>
                        </div>
                        <div class="info-item highlight" style="background: rgba(31, 107, 74, 0.05);">
                            <i class="fas fa-calendar-alt"></i>
                            <span>${(doctor.available_days || 'All Days').replace('Mon,Tue,Wed,Thu,Fri,Sat,Sun', 'All Days').replace('Mon,Tue,Wed,Thu,Fri,Sat', 'Mon-Sat')}</span>
                        </div>
                    </div>
                </div>
                
                <div class="doctor-card-footer">
                    <button class="btn-card-action secondary" onclick="doctorManager.viewDetails('${doctor.doctor_id}')">
                        <i class="fas fa-info-circle"></i>
                        View Details
                    </button>
                    <button class="btn-card-action primary" onclick="doctorManager.bookAppointment('${doctor.doctor_id}')" ${doctor.availability !== 'Available' ? 'disabled' : ''}>
                        <i class="fas fa-calendar-plus"></i>
                        Book Appointment
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * View doctor details in modal
     */
    async viewDetails(doctorId) {
        const doctor = await this.getDoctorById(doctorId);
        if (!doctor) return;

        const modal = document.getElementById('doctorModal');
        const modalBody = document.getElementById('doctorModalBody');

        if (!modal || !modalBody) return;

        const initials = doctor.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        const availabilityClass = doctor.availability === 'Available' ? 'success' : 'danger';

        modalBody.innerHTML = `
            <div class="doctor-profile-modal">
                <div class="profile-header">
                    <div class="profile-avatar ${availabilityClass}">
                        ${doctor.photo ?
                `<img src="${doctor.photo}" alt="${doctor.full_name}">` :
                `<span class="avatar-initials-large">${initials}</span>`
            }
                    </div>
                    <div class="profile-info">
                        <h2>${doctor.full_name}</h2>
                        <p class="profile-specialization">${doctor.specialization || 'General Physician'}</p>
                        <span class="status-badge ${availabilityClass}">
                            <i class="fas ${doctor.availability === 'Available' ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                            ${doctor.availability}
                        </span>
                    </div>
                </div>
                
                <div class="profile-details-grid">
                    <div class="detail-section">
                        <h4><i class="fas fa-building"></i> Department</h4>
                        <p>${doctor.department || 'General'}</p>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-door-open"></i> Room Number</h4>
                        <p>${doctor.room_number || 'N/A'}</p>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-clock"></i> Timing</h4>
                        <p>${doctor.in_time || '--:--'} - ${doctor.out_time || '--:--'}</p>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-calendar-day"></i> Working Days</h4>
                        <p>${doctor.available_days || 'Mon, Tue, Wed, Thu, Fri, Sat, Sun'}</p>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-rupee-sign"></i> Consultation Fee</h4>
                        <p class="fee-highlight">₹${doctor.consultation_fee || '0'}</p>
                    </div>
                </div>
                
                ${doctor.qualification ? `
                    <div class="detail-section-full">
                        <h4><i class="fas fa-graduation-cap"></i> Qualifications</h4>
                        <p>${doctor.qualification}</p>
                    </div>
                ` : ''}
                
                ${doctor.experience_years ? `
                    <div class="detail-section-full">
                        <h4><i class="fas fa-briefcase"></i> Experience</h4>
                        <p>${doctor.experience_years} years</p>
                    </div>
                ` : ''}
                
                <div class="modal-actions">
                    <button class="btn btn-secondary" onclick="doctorManager.closeModal()">
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                    <button class="btn btn-primary" onclick="doctorManager.bookAppointment('${doctor.doctor_id}')" ${doctor.availability !== 'Available' ? 'disabled' : ''}>
                        <i class="fas fa-calendar-plus"></i>
                        Book Appointment
                    </button>
                </div>
            </div>
        `;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close modal
     */
    closeModal() {
        const modal = document.getElementById('doctorModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    /**
     * Book appointment with doctor
     */
    bookAppointment(doctorId) {
        this.closeModal();
        window.location.href = `appointment_management.php?doctor_id=${doctorId}`;
    }

    /**
     * Show loading overlay
     */
    showLoading(show) {
        const loading = document.getElementById('loadingOverlay');
        if (loading) {
            if (show) {
                loading.classList.remove('hidden');
            } else {
                loading.classList.add('hidden');
            }
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        this.showToast(message, 'success');
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showToast(message, 'error');
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                container.removeChild(toast);
            }, 300);
        }, 3000);
    }
}

// Initialize doctor manager when DOM is ready
let doctorManager;
document.addEventListener('DOMContentLoaded', () => {
    doctorManager = new DoctorManager();
    doctorManager.init();
});

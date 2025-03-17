/**
 * Food Craft Club - Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    initializeBootstrapComponents();
    
    // Initialize alert auto-dismiss
    initializeAlertDismiss();
    
    // Initialize admin dashboard functionality
    initializeAdminDashboard();
});

/**
 * Initialize Bootstrap components like dropdowns, tooltips, etc.
 */
function initializeBootstrapComponents() {
    // Initialize dropdowns
    const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    const dropdownList = [...dropdownElementList].map(dropdownToggleEl => {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize modals
    const modalTriggerList = document.querySelectorAll('[data-bs-toggle="modal"]');
    modalTriggerList.forEach(modalTrigger => {
        modalTrigger.addEventListener('click', function() {
            const targetModal = this.getAttribute('data-bs-target');
            const modal = new bootstrap.Modal(document.querySelector(targetModal));
            modal.show();
        });
    });
}

/**
 * Auto-dismiss alerts after 5 seconds
 */
function initializeAlertDismiss() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Initialize admin dashboard functionality
 */
function initializeAdminDashboard() {
    // Handle delete user confirmation
    const deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data attributes
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            // Update the modal's content
            const modalTitle = deleteUserModal.querySelector('.modal-title');
            const modalBody = deleteUserModal.querySelector('.modal-body p');
            const deleteForm = deleteUserModal.querySelector('form');
            
            if (modalTitle) modalTitle.textContent = `Delete User: ${userName}`;
            if (modalBody) modalBody.textContent = `Are you sure you want to delete ${userName}? This action cannot be undone.`;
            if (deleteForm) deleteForm.action = `/admin/users/delete/${userId}`;
        });
    }
    
    // Add active class to current nav item
    const currentPath = window.location.pathname;
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (currentPath === href || (href !== '/' && currentPath.startsWith(href))) {
            link.classList.add('active');
        }
    });
}

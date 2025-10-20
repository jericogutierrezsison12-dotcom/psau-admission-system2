/**
 * PSAU Admission System - Common JavaScript Functions
 * Contains utility functions and shared functionality
 */

document.addEventListener('DOMContentLoaded', function () {
    // Set active state for navigation based on current page
    setActiveNavigation();
});

/**
 * Sets the active state on navigation elements based on the current page
 */
function setActiveNavigation() {
    // Get current page URL
    const currentPage = window.location.pathname.split('/').pop();

    // Sidebar links
    const sidebarLinks = document.querySelectorAll(`.nav-link[href='${currentPage}']`);
    sidebarLinks.forEach(link => link.classList.add('active'));

    // Mobile list items (if any)
    const mobileItems = document.querySelectorAll(`.list-group-item[href='${currentPage}']`);
    mobileItems.forEach(item => item.classList.add('active'));
}

/**
 * Updates the user information in the navbar
 * This assumes that the userData global variable is already set by PHP
 */
function loadUserInfo() {
    if (typeof userData !== 'undefined' && userData) {
        const dropdown = document.getElementById('navbarDropdown');
        if (dropdown) {
            dropdown.textContent = `${userData.first_name} ${userData.last_name}`;
        }
    }
}

/**
 * Formats a date string
 * @param {string} dateString - The date string to format
 * @param {boolean} includeTime - Whether to include time in the formatted date
 * @returns {string} Formatted date string
 */
function formatDate(dateString, includeTime = true) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    
    if (includeTime) {
        options.hour = 'numeric';
        options.minute = 'numeric';
        options.hour12 = true;
    }
    
    return date.toLocaleString('en-US', options);
}

/**
 * Formats a time string
 * @param {string} timeString - The time string to format
 * @returns {string} Formatted time string
 */
function formatTime(timeString) {
    if (!timeString) return '';
    
    const date = new Date(`2000-01-01T${timeString}`);
    return date.toLocaleString('en-US', {
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    });
}

/**
 * Shows an alert message
 * @param {string} message - The message to display
 * @param {string} type - The type of alert (success, danger, warning, info)
 * @param {string} container - The selector for the container to add the alert to
 */
function showAlert(message, type = 'info', container = '#alert-container') {
    const wrapper = document.querySelector(container);
    if (!wrapper) return;
    const div = document.createElement('div');
    div.className = `alert alert-${type} alert-dismissible fade show`;
    div.setAttribute('role', 'alert');
    div.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
    wrapper.innerHTML = '';
    wrapper.appendChild(div);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        try {
            div.classList.remove('show');
            div.remove();
        } catch (e) {}
    }, 5000);
}
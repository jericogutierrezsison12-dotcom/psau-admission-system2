/**
 * PSAU Admission System - Dashboard JavaScript
 * Handles AJAX functionality for dashboard interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded');
    
    // Initialize dashboard functionality
    initializeDashboard();
    
    // Set up AJAX for quick action buttons
    setupAjaxNavigation();
    
    // Set up auto-refresh for status updates
    setupAutoRefresh();
});

/**
 * Initialize dashboard functionality
 */
function initializeDashboard() {
    // Add loading states to buttons
    const quickActionButtons = document.querySelectorAll('.dashboard-card .btn');
    quickActionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Don't prevent default for now, just add loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...';
            this.disabled = true;
            
            // Re-enable after a short delay (in case of navigation)
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 2000);
        });
    });
    
    // Add smooth transitions
    addSmoothTransitions();
}

/**
 * Set up AJAX navigation for dashboard elements
 */
function setupAjaxNavigation() {
    // Handle application progress link with AJAX
    const progressLink = document.querySelector('a[href="application_progress.php"]');
    if (progressLink) {
        progressLink.addEventListener('click', function(e) {
            e.preventDefault();
            loadApplicationProgress();
        });
    }
    
    // Handle application form link with AJAX
    const formLink = document.querySelector('a[href="application_form.php"]');
    if (formLink) {
        formLink.addEventListener('click', function(e) {
            e.preventDefault();
            loadApplicationForm();
        });
    }
    
    // Handle course selection link with AJAX
    const courseLink = document.querySelector('a[href="course_selection.php"]');
    if (courseLink) {
        courseLink.addEventListener('click', function(e) {
            e.preventDefault();
            loadCourseSelection();
        });
    }
}

/**
 * Load application progress via AJAX
 */
function loadApplicationProgress() {
    showLoadingOverlay('Loading Application Progress...');
    
    fetch('application_progress.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        hideLoadingOverlay();
        // Replace main content with progress page
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.innerHTML = html;
            // Re-initialize any JavaScript for the new content
            initializeProgressPage();
        }
    })
    .catch(error => {
        hideLoadingOverlay();
        console.error('Error loading application progress:', error);
        showError('Failed to load application progress. Please try again.');
        // Fallback to normal navigation
        window.location.href = 'application_progress.php';
    });
}

/**
 * Load application form via AJAX
 */
function loadApplicationForm() {
    showLoadingOverlay('Loading Application Form...');
    
    fetch('application_form.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        hideLoadingOverlay();
        // Replace main content with form page
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.innerHTML = html;
            // Re-initialize any JavaScript for the new content
            initializeFormPage();
        }
    })
    .catch(error => {
        hideLoadingOverlay();
        console.error('Error loading application form:', error);
        showError('Failed to load application form. Please try again.');
        // Fallback to normal navigation
        window.location.href = 'application_form.php';
    });
}

/**
 * Load course selection via AJAX
 */
function loadCourseSelection() {
    showLoadingOverlay('Loading Course Selection...');
    
    fetch('course_selection.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        hideLoadingOverlay();
        // Replace main content with course selection page
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.innerHTML = html;
            // Re-initialize any JavaScript for the new content
            initializeCourseSelectionPage();
        }
    })
    .catch(error => {
        hideLoadingOverlay();
        console.error('Error loading course selection:', error);
        showError('Failed to load course selection. Please try again.');
        // Fallback to normal navigation
        window.location.href = 'course_selection.php';
    });
}

/**
 * Set up auto-refresh for status updates
 */
function setupAutoRefresh() {
    // Refresh dashboard data every 30 seconds
    setInterval(() => {
        refreshDashboardData();
    }, 30000);
}

/**
 * Refresh dashboard data via AJAX
 */
function refreshDashboardData() {
    fetch('dashboard.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        // Parse the response and update only the status sections
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Update status badge
        const newStatusBadge = doc.querySelector('.status-badge');
        const currentStatusBadge = document.querySelector('.status-badge');
        if (newStatusBadge && currentStatusBadge) {
            currentStatusBadge.innerHTML = newStatusBadge.innerHTML;
        }
        
        // Update timeline
        const newTimeline = doc.querySelector('.timeline');
        const currentTimeline = document.querySelector('.timeline');
        if (newTimeline && currentTimeline) {
            currentTimeline.innerHTML = newTimeline.innerHTML;
        }
        
        console.log('Dashboard data refreshed');
    })
    .catch(error => {
        console.error('Error refreshing dashboard data:', error);
    });
}

/**
 * Show loading overlay
 */
function showLoadingOverlay(message = 'Loading...') {
    // Remove existing overlay if any
    hideLoadingOverlay();
    
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-content">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>${message}</p>
        </div>
    `;
    
    // Add styles
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;
    
    const loadingContent = overlay.querySelector('.loading-content');
    loadingContent.style.cssText = `
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    `;
    
    document.body.appendChild(overlay);
}

/**
 * Hide loading overlay
 */
function hideLoadingOverlay() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Show error message
 */
function showError(message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-danger');
    existingAlerts.forEach(alert => alert.remove());
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        <i class="bi bi-exclamation-triangle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insert at the top of main content
    const mainContent = document.querySelector('.main-content .container-fluid');
    if (mainContent) {
        mainContent.insertBefore(alert, mainContent.firstChild);
    }
}

/**
 * Add smooth transitions to dashboard elements
 */
function addSmoothTransitions() {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.style.transition = 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out';
        
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });
}

/**
 * Initialize progress page after AJAX load
 */
function initializeProgressPage() {
    // Load the application progress JavaScript
    if (typeof renderApplicationContent === 'function') {
        renderApplicationContent();
    }
}

/**
 * Initialize form page after AJAX load
 */
function initializeFormPage() {
    // Load the application form JavaScript
    console.log('Form page loaded via AJAX');
}

/**
 * Initialize course selection page after AJAX load
 */
function initializeCourseSelectionPage() {
    // Load the course selection JavaScript
    console.log('Course selection page loaded via AJAX');
}

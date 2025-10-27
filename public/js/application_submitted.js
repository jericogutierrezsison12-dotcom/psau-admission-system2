/**
 * PSAU Admission System - Application Submitted JavaScript
 * Handles template loading and UI interactions
 */

$(document).ready(function() {
    // Load common components
    $("#navbar-placeholder").load("templates/navbar.html", function() {
        // Update username in navbar after loading
        updateUserInfo();
    });
    
    // Use application data passed from PHP
    if (typeof applicationData !== 'undefined' && applicationData) {
        updateApplicationDetails(applicationData.application);
    } else {
        console.error('Application data not available');
    }
    
    // Update application details in the UI
    function updateApplicationDetails(application) {
        if (application) {
            // Format date
            const submittedDate = new Date(application.created_at);
            const formattedDate = submittedDate.toLocaleString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
            
            // Update submitted date
            $('[data-submitted-date]').text(formattedDate);
            
            // Update status
            $('[data-status]').text(application.status);
        }
    }
});

/**
 * Updates user information in the navbar
 */
function updateUserInfo() {
    // Use userData passed from PHP
    if (typeof userData !== 'undefined' && userData) {
        $('#navbarDropdown').text(userData.first_name + ' ' + userData.last_name);
    }
} 
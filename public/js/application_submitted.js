/**
 * PSAU Admission System - Application Submitted JavaScript
 * Handles template loading and UI interactions
 */

// Update application details in the UI
function updateApplicationDetails(application) {
    console.log('updateApplicationDetails called with:', application);
    if (application) {
        // Format date
        const submittedDate = new Date(application.created_at);
        console.log('Raw date:', application.created_at);
        console.log('Parsed date:', submittedDate);
        
        const formattedDate = submittedDate.toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        });
        
        console.log('Formatted date:', formattedDate);
        
        // Update submitted date
        $('[data-submitted-date]').text(formattedDate);
        console.log('Updated date element:', $('[data-submitted-date]').length);
        
        // Update status
        $('[data-status]').text(application.status);
        console.log('Updated status element:', $('[data-status]').length);
    } else {
        console.error('Application data is null or undefined');
    }
}

$(document).ready(function() {
    // Load common components
    $("#navbar-placeholder").load("templates/navbar.html", function() {
        // Update username in navbar after loading
        updateUserInfo();
    });
    
    // Use application data passed from PHP
    console.log('Checking applicationData:', typeof applicationData, applicationData);
    if (typeof applicationData !== 'undefined' && applicationData) {
        updateApplicationDetails(applicationData.application);
    } else {
        console.error('Application data not available');
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
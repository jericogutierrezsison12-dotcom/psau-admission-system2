// Store cooldown timers
const cooldownTimers = {};
const maxRetries = 2; // Maximum number of retries for failed requests

function updateCooldownButton(button, timeRemaining) {
    const hours = Math.floor(timeRemaining / 3600);
    const minutes = Math.floor((timeRemaining % 3600) / 60);
    const seconds = timeRemaining % 60;
    
    button.disabled = true;
    button.innerHTML = `<i class="fas fa-clock"></i> ${hours}h ${minutes}m ${seconds}s`;
}

function startCooldownTimer(userId, reminderType, timeRemaining) {
    const buttonId = `reminder-${userId}-${reminderType}`;
    const button = document.getElementById(buttonId);
    
    if (!button) return;
    
    // Clear existing timer if any
    if (cooldownTimers[buttonId]) {
        clearInterval(cooldownTimers[buttonId]);
    }
    
    // Set initial state
    updateCooldownButton(button, timeRemaining);
    
    // Start countdown
    cooldownTimers[buttonId] = setInterval(() => {
        timeRemaining--;
        
        if (timeRemaining <= 0) {
            // Reset button when cooldown is complete
            clearInterval(cooldownTimers[buttonId]);
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-bell"></i> Send Reminder';
            delete cooldownTimers[buttonId];
        } else {
            // Update button text with remaining time
            updateCooldownButton(button, timeRemaining);
        }
    }, 1000);
}

function sendReminder(button, userId, reminderType, retryCount = 0) {
    // Disable button and show loading state
    button.prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin"></i> Sending...');

    // Send reminder
    $.ajax({
        url: 'send_reminder.php',
        method: 'POST',
        data: {
            user_id: userId,
            reminder_type: reminderType
        },
        dataType: 'json',
        timeout: 30000 // 30 seconds timeout
    })
    .done(function(response) {
        console.log('Server response:', response);

        if (response.success) {
            // Show success message with message ID if available
            const messageId = response.messageId ? ` (ID: ${response.messageId})` : '';
            showAlert('success', `${response.message}${messageId}`);
            
            // If there's a warning, show it as well
            if (response.warning) {
                setTimeout(() => {
                    showAlert('warning', response.warning);
                }, 500); // Show warning after a short delay
            }
            
            // Start cooldown timer (24 hours = 86400 seconds)
            startCooldownTimer(userId, reminderType, 86400);
            
            // Clear retry count on success
            button.data('retryCount', 0);
        } else if (response.cooldown) {
            // Show cooldown message
            showAlert('info', response.message);
            
            // Add cooldown timer if time remaining is provided
            if (response.time_remaining) {
                startCooldownTimer(userId, reminderType, response.time_remaining);
            }
        } else {
            // Show error message with details if available
            const errorDetails = response.error ? `: ${response.error}` : '';
            showAlert('danger', `${response.message || 'Failed to send reminder'}${errorDetails}`);
            
            // Check if we should retry
            if (retryCount < maxRetries) {
                showAlert('warning', `Retrying... Attempt ${retryCount + 1} of ${maxRetries}`);
                setTimeout(() => {
                    sendReminder(button, userId, reminderType, retryCount + 1);
                }, 2000); // Wait 2 seconds before retrying
            } else {
                // Reset button after a short delay
                setTimeout(() => resetButton(button), 2000);
            }
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.error('AJAX Error:', {
            status: jqXHR.status,
            statusText: jqXHR.statusText,
            responseText: jqXHR.responseText,
            textStatus: textStatus,
            errorThrown: errorThrown
        });
        
        // Try to parse response for more details
        let errorMessage = 'Failed to communicate with the server. Please try again.';
        try {
            const response = JSON.parse(jqXHR.responseText);
            if (response.message) {
                errorMessage = response.message;
            }
        } catch (e) {
            // Use default message if response is not JSON
            if (textStatus === 'timeout') {
                errorMessage = 'The request timed out. Please try again.';
            } else if (jqXHR.status === 0) {
                errorMessage = 'Unable to connect to the server. Please check your internet connection.';
            } else if (jqXHR.status === 404) {
                errorMessage = 'The requested service is not available.';
            } else if (jqXHR.status === 500) {
                errorMessage = 'Internal server error. Please try again later.';
            }
        }
        
        // Show error message
        showAlert('danger', errorMessage);
        
        // Check if we should retry
        if (retryCount < maxRetries && textStatus !== 'abort') {
            showAlert('warning', `Retrying... Attempt ${retryCount + 1} of ${maxRetries}`);
            setTimeout(() => {
                sendReminder(button, userId, reminderType, retryCount + 1);
            }, 2000); // Wait 2 seconds before retrying
        } else {
            // Reset button after a short delay
            setTimeout(() => resetButton(button), 2000);
        }
    })
    .always(function() {
        // Clear processing flag
        button.data('processing', false);
        
        // Store retry count
        button.data('retryCount', retryCount);
    });
}

// Check cooldown status on page load
function checkInitialCooldowns() {
    const reminderButtons = document.querySelectorAll('[id^="reminder-"]');
    
    reminderButtons.forEach(button => {
        const [, userId, reminderType] = button.id.split('-');
        
        $.ajax({
            url: 'check_cooldown.php',
            type: 'POST',
            data: {
                user_id: userId,
                reminder_type: reminderType
            },
            dataType: 'json',
            success: function(response) {
                if (response.cooldown) {
                    startCooldownTimer(userId, reminderType, response.time_remaining);
                }
            }
        });
    });
}

// Initialize tooltips and check cooldowns
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    checkInitialCooldowns();
});

$(document).ready(function() {
    // Initialize DataTable
    const table = $('#applicantsTable').DataTable({
        responsive: true,
        order: [[4, 'desc']], // Sort by waiting time by default
        pageLength: 25,
        language: {
            search: "Search applicants:",
            lengthMenu: "Show _MENU_ applicants per page",
            info: "Showing _START_ to _END_ of _TOTAL_ applicants",
            infoEmpty: "No applicants found",
            emptyTable: "No applicants available"
        }
    });


    // Add click event listeners to reminder buttons
    $(document).on('click', '.reminder-btn:not([disabled])', function(e) {
        e.preventDefault();
        const button = $(this);
        
        // Prevent double-clicking
        if (button.data('processing')) {
            return;
        }
        
        const userId = button.data('user-id');
        const reminderType = button.data('reminder-type');
        
        // Mark button as processing
        button.data('processing', true);
        
        // Pass the button element as the first parameter
        sendReminder(button, userId, reminderType);
    });
});

// Function to show alert messages
function showAlert(type, message) {
    // Remove any existing alerts
    $('.alert').remove();
    
    // Create new alert with icon
    let icon = '';
    switch (type) {
        case 'success':
            icon = '<i class="fas fa-check-circle"></i> ';
            break;
        case 'danger':
            icon = '<i class="fas fa-exclamation-circle"></i> ';
            break;
        case 'info':
            icon = '<i class="fas fa-info-circle"></i> ';
            break;
        case 'warning':
            icon = '<i class="fas fa-exclamation-triangle"></i> ';
            break;
    }
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${icon}${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Insert alert before the table
    $('#applicantsTable').before(alertHtml);
    
    // Auto-dismiss after 5 seconds for success messages, 10 seconds for others
    const dismissTime = type === 'success' ? 5000 : 10000;
    setTimeout(() => {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, dismissTime);
}

// Function to start cooldown timer
function startCooldownTimer(button, initialSeconds) {
    let secondsLeft = initialSeconds;
    
    // Create or update cooldown timer display
    let cooldownDiv = button.next('.cooldown-timer');
    if (cooldownDiv.length === 0) {
        cooldownDiv = $('<div class="cooldown-timer"></div>');
        button.after(cooldownDiv);
    }
    
    // Format time display
    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `Wait ${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            return `Wait ${minutes}m ${secs}s`;
        } else {
            return `Wait ${secs}s`;
        }
    }
    
    // Update timer immediately
    cooldownDiv.text(formatTime(secondsLeft));
    button.prop('disabled', true)
        .html('<i class="bi bi-clock"></i> Cooldown');
    
    // Clear any existing interval
    if (button.data('timerInterval')) {
        clearInterval(button.data('timerInterval'));
    }
    
    // Update timer every second
    const timerInterval = setInterval(() => {
        secondsLeft--;
        cooldownDiv.text(formatTime(secondsLeft));
        
        if (secondsLeft <= 0) {
            clearInterval(timerInterval);
            resetButton(button);
            cooldownDiv.remove();
        }
    }, 1000);
    
    // Store interval ID on button element
    button.data('timerInterval', timerInterval);
}

// Function to reset button state
function resetButton(button) {
    button.prop('disabled', false)
        .html('<i class="bi bi-bell"></i> Send Reminder')
        .data('processing', false);
    
    // Clear any existing interval
    if (button.data('timerInterval')) {
        clearInterval(button.data('timerInterval'));
        button.removeData('timerInterval');
    }
    
    // Remove cooldown timer if exists
    button.next('.cooldown-timer').remove();
} 
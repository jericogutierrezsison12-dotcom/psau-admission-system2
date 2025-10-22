/**
 * PSAU Admission System - Login Page JavaScript
 */

// Initialize the FingerprintJS agent
const fpPromise = FingerprintJS.load();

// Get the visitor identifier
async function getFingerprint() {
    try {
        const fp = await fpPromise;
        const result = await fp.get();
        
        // Set the fingerprint in the hidden field
        document.getElementById('deviceFingerprint').value = result.visitorId;
        return result.visitorId;
    } catch (error) {
        console.error('Error generating fingerprint:', error);
        return null;
    }
}

// Countdown timer for blocked devices
function startCountdown() {
    const countdownEl = document.getElementById('countdown');
    if (countdownEl) {
        let minutes = parseInt(countdownEl.textContent);
        if (isNaN(minutes)) return;
        
        const totalSeconds = minutes * 60;
        let secondsRemaining = totalSeconds;
        
        const timer = setInterval(() => {
            secondsRemaining--;
            
            if (secondsRemaining <= 0) {
                clearInterval(timer);
                window.location.reload();
                return;
            }
            
            const minutesLeft = Math.floor(secondsRemaining / 60);
            const secondsLeft = secondsRemaining % 60;
            
            countdownEl.textContent = `${minutesLeft}:${secondsLeft < 10 ? '0' : ''}${secondsLeft}`;
        }, 1000);
    }
}

// reCAPTCHA callback handler - called when user submits the form
function onLoginSubmit(token) {
    // Store the token in the hidden field
    document.getElementById('recaptchaToken').value = token;
    
    // Display the token value for debugging on localhost
    const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
    if (isLocalhost) {
        console.log('reCAPTCHA token generated:', token ? token.substring(0, 15) + '...' : 'null');
    }
    
    // Show loading state
    const loginBtn = document.querySelector('button[type="submit"]');
    const originalText = loginBtn.innerHTML;
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';
    
    // Submit the form with a slight delay to prevent brute force automation
    setTimeout(() => {
        document.getElementById('loginForm').submit();
    }, 800);
}

// Handle reCAPTCHA errors
function recaptchaError() {
    // Create or update error message
    let errorDiv = document.getElementById('recaptchaErrorMessage');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'recaptchaErrorMessage';
        errorDiv.className = 'alert alert-warning mt-2';
        
        // Insert before the form
        const form = document.getElementById('loginForm');
        form.parentNode.insertBefore(errorDiv, form);
    }
    
    errorDiv.innerHTML = '<strong>reCAPTCHA Error:</strong> Failed to load reCAPTCHA. Please check your internet connection and refresh the page.';
}

// Document ready handler
document.addEventListener('DOMContentLoaded', function() {
    // Generate device fingerprint
    getFingerprint();
    
    // Start countdown if blocked
    startCountdown();
    
    // Make grecaptcha callback available globally
    window.onLoginSubmit = onLoginSubmit;
    
    // Handle reCAPTCHA errors
    window.recaptchaError = recaptchaError;
    
    // Check if reCAPTCHA is loaded properly
    setTimeout(function() {
        if (typeof grecaptcha === 'undefined' || typeof grecaptcha.execute === 'undefined') {
            window.recaptchaError();
        }
    }, 1000);

    // Add a manual submit handler for cases where reCAPTCHA might fail
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // If token is empty, try to manually execute reCAPTCHA
            const token = document.getElementById('recaptchaToken').value;
            if (!token) {
                // Don't prevent form submission on localhost
                const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
                if (!isLocalhost && typeof grecaptcha !== 'undefined' && typeof grecaptcha.execute === 'function') {
                    e.preventDefault();
                    try {
                        grecaptcha.execute('6LezOyYrAAAAAJRRTgIcrXDqa5_gOrkJNjNvoTFA', {action: 'login'})
                            .then(function(token) {
                                document.getElementById('recaptchaToken').value = token;
                                document.getElementById('loginForm').submit();
                            });
                    } catch (error) {
                        console.error('reCAPTCHA execution error:', error);
                        // Allow form to submit anyway as a fallback
                        document.getElementById('loginForm').submit();
                    }
                }
            }
        });
    }
}); 
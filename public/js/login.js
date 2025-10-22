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

// reCAPTCHA callback function
function onLoginSubmit(token) {
    console.log('reCAPTCHA token received:', token);
    
    // Set the reCAPTCHA token in the hidden field
    document.getElementById('recaptchaToken').value = token;
    
    // Submit the form
    document.getElementById('loginForm').submit();
}

// Global function for reCAPTCHA error handling
function onRecaptchaError(error) {
    console.error('reCAPTCHA error:', error);
    alert('reCAPTCHA verification failed. Please try again.');
}

// Document ready handler
document.addEventListener('DOMContentLoaded', function() {
    // Generate device fingerprint
    getFingerprint();
    
    // Start countdown if blocked
    startCountdown();
    
    // Add form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Check if reCAPTCHA token exists
            const recaptchaToken = document.getElementById('recaptchaToken').value;
            if (!recaptchaToken) {
                e.preventDefault();
                alert('Please complete the reCAPTCHA verification.');
                return false;
            }
        });
    }
}); 
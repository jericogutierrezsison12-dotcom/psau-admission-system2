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
    
    // Initialize reCAPTCHA v3
    const loginButton = document.getElementById('loginButton');
    const recaptchaSiteKey = loginButton?.getAttribute('data-sitekey');
    if (recaptchaSiteKey && typeof grecaptcha !== 'undefined') {
        grecaptcha.ready(function() {
            // Execute reCAPTCHA v3 on form submission
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent default submission
                    
                    // Execute reCAPTCHA v3
                    grecaptcha.execute(recaptchaSiteKey, {action: 'login'})
                        .then(function(token) {
                            // Set the token
                            document.getElementById('recaptchaToken').value = token;
                            
                            // Submit the form
                            loginForm.submit();
                        })
                        .catch(function(error) {
                            console.error('reCAPTCHA error:', error);
                            // On error, try to submit anyway (for localhost/development)
                            const isLocalhost = window.location.hostname === 'localhost' || 
                                              window.location.hostname === '127.0.0.1';
                            if (isLocalhost) {
                                // Allow submission on localhost even without token
                                loginForm.submit();
                            } else {
                                alert('reCAPTCHA verification failed. Please refresh the page and try again.');
                            }
                        });
                });
            }
        });
    } else {
        // Fallback if reCAPTCHA is not loaded - allow submission for localhost
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            const isLocalhost = window.location.hostname === 'localhost' || 
                              window.location.hostname === '127.0.0.1';
            if (isLocalhost) {
                console.log('reCAPTCHA not loaded, allowing form submission on localhost');
                // Don't block form submission on localhost
            } else {
                loginForm.addEventListener('submit', function(e) {
                    const recaptchaToken = document.getElementById('recaptchaToken').value;
                    if (!recaptchaToken) {
                        console.warn('reCAPTCHA token missing');
                        // Still allow submission but warn
                    }
                });
            }
        }
    }
}); 
/**
 * PSAU Admission System - Admin Login Page JavaScript
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

// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const icon = toggleBtn.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.className = 'bi bi-eye-slash';
        toggleBtn.setAttribute('title', 'Hide password');
    } else {
        passwordInput.type = 'password';
        icon.className = 'bi bi-eye';
        toggleBtn.setAttribute('title', 'Show password');
    }
}

// Execute reCAPTCHA and get token
async function executeRecaptcha() {
    try {
        console.log('Executing reCAPTCHA...');
        const token = await grecaptcha.execute('6LezOyYrAAAAAJRRTgIcrXDqa5_gOrkJNjNvoTFA', {action: 'admin_login'});
        console.log('reCAPTCHA executed successfully, token:', token ? token.substring(0, 15) + '...' : 'null');
        return token;
    } catch (error) {
        console.error('reCAPTCHA execution failed:', error);
        return null;
    }
}

// Handle login submission
async function handleLogin() {
    console.log('Handling login submission...');
    
    // Show loading state
    const loginBtn = document.getElementById('loginBtn');
    const originalText = loginBtn.innerHTML;
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';
    
    try {
        // Try to execute reCAPTCHA
        const token = await executeRecaptcha();
        
        if (token) {
            // Store the token
            document.getElementById('recaptchaToken').value = token;
            console.log('reCAPTCHA token stored, submitting form...');
            
            // Submit the form
            setTimeout(() => {
                document.getElementById('adminLoginForm').submit();
            }, 500);
        } else {
            // reCAPTCHA failed, check if we're on localhost
            const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
            if (isLocalhost) {
                console.log('Localhost detected, proceeding without reCAPTCHA token');
                // Submit form without token on localhost
                setTimeout(() => {
                    document.getElementById('adminLoginForm').submit();
                }, 500);
            } else {
                // Show error and reset button
                console.error('reCAPTCHA failed and not on localhost');
                alert('reCAPTCHA verification failed. Please refresh the page and try again.');
                loginBtn.disabled = false;
                loginBtn.innerHTML = originalText;
            }
        }
    } catch (error) {
        console.error('Login handling error:', error);
        
        // Check if we're on localhost
        const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
        if (isLocalhost) {
            console.log('Localhost detected, proceeding without reCAPTCHA');
            setTimeout(() => {
                document.getElementById('adminLoginForm').submit();
            }, 500);
        } else {
            alert('An error occurred during login. Please try again.');
            loginBtn.disabled = false;
            loginBtn.innerHTML = originalText;
        }
    }
}

// Document ready handler
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin login page loaded');
    
    // Generate device fingerprint
    getFingerprint();
    
    // Start countdown if blocked
    startCountdown();
    
    // Setup password toggle
    const toggleBtn = document.getElementById('togglePassword');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', togglePassword);
    }
    
    // Setup login button
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Login button clicked');
            handleLogin();
        });
    }
    
    // Check if reCAPTCHA is loaded properly
    setTimeout(function() {
        if (typeof grecaptcha === 'undefined' || typeof grecaptcha.execute === 'undefined') {
            console.warn('reCAPTCHA not loaded properly');
            
            // Show warning for non-localhost
            const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
            if (!isLocalhost) {
                const warningDiv = document.createElement('div');
                warningDiv.className = 'alert alert-warning mt-2';
                warningDiv.innerHTML = '<strong>Warning:</strong> reCAPTCHA failed to load. Please refresh the page.';
                
                const form = document.getElementById('adminLoginForm');
                if (form) {
                    form.parentNode.insertBefore(warningDiv, form);
                }
            }
        } else {
            console.log('reCAPTCHA loaded successfully');
        }
    }, 3000);
    
    // Add form validation
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // Auto-focus username field
    if (usernameInput) {
        usernameInput.focus();
    }
    
    // Add form submit handler as backup
    const loginForm = document.getElementById('adminLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            
            // Check if we have a token or are on localhost
            const token = document.getElementById('recaptchaToken').value;
            const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
            
            if (!token && !isLocalhost) {
                console.log('No reCAPTCHA token and not on localhost, preventing submission');
                e.preventDefault();
                alert('Please wait for reCAPTCHA verification to complete.');
                return false;
            }
            
            console.log('Form submission proceeding...');
        });
    }
    
    // Log page load completion
    console.log('Admin login page setup complete');
});

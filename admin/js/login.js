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

// Handle login submission
function handleLogin() {
    console.log('Handling login submission...');
    
    // Show loading state
    const loginBtn = document.getElementById('loginBtn');
    const originalText = loginBtn.innerHTML;
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';
    
    // Submit the form with a slight delay
    setTimeout(() => {
        document.getElementById('adminLoginForm').submit();
    }, 500);
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
    
    // Log page load completion
    console.log('Admin login page setup complete');
});

// Import Firebase modules
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getAuth, RecaptchaVerifier } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-auth.js";

/**
 * PSAU Admission System - Forgot Password Page JavaScript
 */

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8",
    authDomain: "psau-admission-system.firebaseapp.com",
    projectId: "psau-admission-system",
    storageBucket: "psau-admission-system.appspot.com",
    messagingSenderId: "522448258958",
    appId: "1:522448258958:web:994b133a4f7b7f4c1b06df"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

// Track reCAPTCHA verification state
let isRecaptchaVerified = false;
let recaptchaResponse = null;

// Function to show messages with proper styling
function showMessage(message, type = 'info') {
    // Remove any existing message
    const existingMessage = document.querySelector('.otp-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `otp-message alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'}`;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        font-size: 14px;
        line-height: 1.4;
    `;
    messageDiv.innerHTML = message;
    
    // Add to page
    document.body.appendChild(messageDiv);
    
    // Auto-remove after 5 seconds for success messages, 10 seconds for errors
    const timeout = type === 'error' ? 10000 : 5000;
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, timeout);
}

document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on step 2 (OTP verification)
    if (document.getElementById('otpForm')) {
        // Setup verification
        setupOTPVerification();
    }

    // Check if we're on step 3 (New Password)
    if (document.getElementById('password') && document.getElementById('length')) {
        setupPasswordValidation();
    }
});

/**
 * Setup OTP verification functionality
 */
function setupOTPVerification() {
    // Create RecaptchaVerifier
    window.recaptchaVerifier = new RecaptchaVerifier(auth, 'recaptcha-container', {
        'size': 'normal',
        'callback': (response) => {
            // reCAPTCHA solved
            isRecaptchaVerified = true;
            recaptchaResponse = response;
            
            // Keep the check mark visible
            const recaptchaIframe = document.querySelector('iframe[title="reCAPTCHA"]');
            if (recaptchaIframe) {
                const recaptchaElement = recaptchaIframe.parentElement;
                recaptchaElement.setAttribute('data-verified', 'true');
            }
            
            // Enable verify button
            document.getElementById('verify-otp').disabled = false;
        }
    });
    
    window.recaptchaVerifier.render();
    
    // Initially disable verify button
    document.getElementById('verify-otp').disabled = true;
    
    // Send OTP function (email-based)
    window.sendOTP = function() {
        if (!isRecaptchaVerified) {
            showMessage("Please complete the reCAPTCHA verification first", 'error');
            return;
        }

        const email = document.getElementById('passwordResetEmail').value;
        const token = recaptchaResponse;

        fetch('send_forgot_password_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, recaptcha_token: token })
        })
        .then(async (res) => {
            if (!res.ok) {
                const txt = await res.text();
                throw new Error(txt || 'Failed to send OTP');
            }
            return res.json();
        })
        .then((data) => {
            document.getElementById('resend-otp').disabled = true;
            let seconds = 60;
            const countdown = setInterval(() => {
                document.getElementById('resend-otp').innerText = `Resend OTP (${seconds}s)`;
                seconds--;
                if (seconds < 0) {
                    clearInterval(countdown);
                    document.getElementById('resend-otp').innerText = 'Resend OTP';
                    document.getElementById('resend-otp').disabled = false;
                }
            }, 1000);
            
            // Show success message with remaining requests
            if (data.message) {
                showMessage(data.message, 'success');
            }
        })
        .catch((error) => {
            console.error('Error sending OTP:', error);
            // Try to parse error message for better display
            try {
                const errorData = JSON.parse(error.message);
                if (errorData.error) {
                    showMessage(errorData.error, 'error');
                } else {
                    showMessage('Error sending OTP: ' + error.message, 'error');
                }
            } catch (e) {
                showMessage('Error sending OTP: ' + error.message, 'error');
            }
        });
    };
    
    // Verify OTP button click (server-side validation)
    document.getElementById('verify-otp').addEventListener('click', function() {
        if (!isRecaptchaVerified) {
            showMessage("Please complete the reCAPTCHA verification first", 'error');
            return;
        }

        const otpCode = document.getElementById('otp_code').value;
        if (!otpCode || otpCode.length !== 6) {
            showMessage("Please enter a valid 6-digit OTP code", 'error');
            return;
        }

        // Set reCAPTCHA verified flag
        document.getElementById('recaptcha_verified').value = 'true';
        
        // Submit the form
        document.getElementById('otpForm').submit();
    });
    
    // Resend OTP button click
    document.getElementById('resend-otp').addEventListener('click', function() {
        // Reset reCAPTCHA
        window.recaptchaVerifier.clear();
        isRecaptchaVerified = false;
        recaptchaResponse = null;
        
        // Re-render reCAPTCHA
        window.recaptchaVerifier = new RecaptchaVerifier(auth, 'recaptcha-container', {
            'size': 'normal',
            'callback': (response) => {
                isRecaptchaVerified = true;
                recaptchaResponse = response;
                sendOTP();
            }
        });
        window.recaptchaVerifier.render();
        
        // Disable verify button until reCAPTCHA is solved
        document.getElementById('verify-otp').disabled = true;
    });
}

/**
 * Setup password validation functionality
 */
function setupPasswordValidation() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const lengthIndicator = document.getElementById('length');
    const uppercaseIndicator = document.getElementById('uppercase');
    const lowercaseIndicator = document.getElementById('lowercase');
    const numberIndicator = document.getElementById('number');
    const specialIndicator = document.getElementById('special');
    const matchIndicator = document.getElementById('match');

    function validatePassword() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Length check
        const hasLength = password.length >= 8;
        lengthIndicator.className = hasLength ? 'text-success' : 'text-danger';

        // Uppercase check
        const hasUppercase = /[A-Z]/.test(password);
        uppercaseIndicator.className = hasUppercase ? 'text-success' : 'text-danger';

        // Lowercase check
        const hasLowercase = /[a-z]/.test(password);
        lowercaseIndicator.className = hasLowercase ? 'text-success' : 'text-danger';

        // Number check
        const hasNumber = /\d/.test(password);
        numberIndicator.className = hasNumber ? 'text-success' : 'text-danger';

        // Special character check
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        specialIndicator.className = hasSpecial ? 'text-success' : 'text-danger';

        // Match check
        const passwordsMatch = password === confirmPassword && password.length > 0;
        matchIndicator.className = passwordsMatch ? 'text-success' : 'text-danger';

        // Enable/disable submit button
        const isValid = hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial && passwordsMatch;
        const submitButton = document.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = !isValid;
        }
    }

    // Add event listeners
    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', validatePassword);

    // Initial validation
    validatePassword();
}
// Firebase-related functionality 
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getAuth, RecaptchaVerifier } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-auth.js";

const firebaseConfig = {
    apiKey: "AIzaSyBQ5jLQX2JggHQU0ikymEEjywxEos5Lr3c",
    authDomain: "psau-admission-system-f55f8.firebaseapp.com",
    projectId: "psau-admission-system-f55f8",
    storageBucket: "psau-admission-system-f55f8.firebasestorage.app",
    messagingSenderId: "615441800587",
    appId: "1:615441800587:web:8b0df9b012e24c147da38e",
    measurementId: "G-4WWB01B974"
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
    const currentStep = document.getElementById('currentStep')?.value;
    
    // OTP verification functionality
    if (currentStep === '2') {
        setupOtpVerification();
    }
    
    // Password validation functionality
    if (currentStep === '1') {
        setupPasswordValidation();
    }
});

// Setup OTP verification
function setupOtpVerification() {
    // Create RecaptchaVerifier with enterprise mode
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
            
            // Enable the verify button
            document.getElementById('verify-otp').disabled = false;
            
            // Send OTP only if not already sent
            if (!window.otpSent) {
                sendOTP();
            }
        },
        'expired-callback': () => {
            // reCAPTCHA expired
            isRecaptchaVerified = false;
            recaptchaResponse = null;
            document.getElementById('verify-otp').disabled = true;
            
            // Remove verified state
            const recaptchaIframe = document.querySelector('iframe[title="reCAPTCHA"]');
            if (recaptchaIframe) {
                const recaptchaElement = recaptchaIframe.parentElement;
                recaptchaElement.removeAttribute('data-verified');
            }
        }
    });
    
    window.recaptchaVerifier.render().then(widgetId => {
        window.recaptchaWidgetId = widgetId;
        
        // Add CSS to maintain check mark
        const style = document.createElement('style');
        style.textContent = `
            #recaptcha-container[data-verified="true"] iframe {
                pointer-events: none;
            }
            #recaptcha-container[data-verified="true"] .recaptcha-checkbox-checked {
                display: block !important;
            }
        `;
        document.head.appendChild(style);
    });
    
    // Initially disable verify button
    document.getElementById('verify-otp').disabled = true;
    
    // Send OTP function (email-based)
    window.sendOTP = function() {
        if (!isRecaptchaVerified) {
            alert("Please complete the reCAPTCHA verification first");
            return;
        }

        const email = document.getElementById('registrationEmail').value;
        const token = recaptchaResponse;

        fetch('send_email_otp.php', {
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
            window.otpSent = true;
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
            alert("Please complete the reCAPTCHA verification first");
            return;
        }

        const code = document.getElementById('otp_code').value;
        if (!code || code.length !== 6) {
            alert("Please enter a valid 6-digit OTP code");
            return;
        }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';

        // For email OTP, just submit the form; PHP will validate session OTP
        document.getElementById('recaptcha_verified').value = 'true';
        document.getElementById('otpForm').submit();
    });
    
    // Resend OTP button click
    document.getElementById('resend-otp').addEventListener('click', function() {
        if (!isRecaptchaVerified || !recaptchaResponse) {
            // Only reset if reCAPTCHA is expired or invalid
            if (window.recaptchaVerifier) {
                window.recaptchaVerifier.clear();
                
                // Remove verified state
                const recaptchaIframe = document.querySelector('iframe[title="reCAPTCHA"]');
                if (recaptchaIframe) {
                    const recaptchaElement = recaptchaIframe.parentElement;
                    recaptchaElement.removeAttribute('data-verified');
                }
            }
            window.otpSent = false;
            
            window.recaptchaVerifier = new RecaptchaVerifier(auth, 'recaptcha-container', {
                'size': 'normal',
                'callback': (response) => {
                    isRecaptchaVerified = true;
                    recaptchaResponse = response;
                    
                    // Keep the check mark visible
                    const recaptchaIframe = document.querySelector('iframe[title="reCAPTCHA"]');
                    if (recaptchaIframe) {
                        const recaptchaElement = recaptchaIframe.parentElement;
                        recaptchaElement.setAttribute('data-verified', 'true');
                    }
                    
                    document.getElementById('verify-otp').disabled = false;
                    sendOTP();
                },
                'expired-callback': () => {
                    isRecaptchaVerified = false;
                    recaptchaResponse = null;
                    document.getElementById('verify-otp').disabled = true;
                    
                    // Remove verified state
                    const recaptchaIframe = document.querySelector('iframe[title="reCAPTCHA"]');
                    if (recaptchaIframe) {
                        const recaptchaElement = recaptchaIframe.parentElement;
                        recaptchaElement.removeAttribute('data-verified');
                    }
                }
            });
            window.recaptchaVerifier.render().then(widgetId => {
                window.recaptchaWidgetId = widgetId;
            });
        } else {
            // If reCAPTCHA is still valid, just resend OTP using existing verification
            sendOTP();
        }
    });
}

// Setup password validation
function setupPasswordValidation() {
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        
        // Check length
        document.getElementById('length').style.color = 
            password.length >= 8 ? 'green' : 'inherit';
        
        // Check uppercase
        document.getElementById('uppercase').style.color = 
            /[A-Z]/.test(password) ? 'green' : 'inherit';
        
        // Check lowercase
        document.getElementById('lowercase').style.color = 
            /[a-z]/.test(password) ? 'green' : 'inherit';
        
        // Check number
        document.getElementById('number').style.color = 
            /[0-9]/.test(password) ? 'green' : 'inherit';
        
        // Check special character
        document.getElementById('special').style.color = 
            /[^A-Za-z0-9]/.test(password) ? 'green' : 'inherit';
    });
}

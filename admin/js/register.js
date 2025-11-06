// Firebase-related functionality for Admin Registration
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

document.addEventListener('DOMContentLoaded', function() {
    const currentStep = document.getElementById('currentStep')?.value;
    
    // Reset OTP sent state when entering a new step
    window.otpSent = false;
    
    // OTP verification functionality
    if (currentStep === '1' || currentStep === '3') {
        setupOtpVerification();
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

        const currentStep = document.getElementById('currentStep')?.value;
        const token = recaptchaResponse;
        let endpoint = '';

        // Determine which OTP endpoint to use based on step
        if (currentStep === '1') {
            // Step 1: Send to restricted email
            endpoint = 'send_restricted_email_otp.php';
        } else if (currentStep === '3') {
            // Step 3: Send to user's email
            endpoint = 'send_admin_otp.php';
            const email = document.getElementById('emailAddress').value;
            if (!email) {
                alert('Email address not found');
                return;
            }
        }

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                recaptcha_token: token,
                email: currentStep === '3' ? document.getElementById('emailAddress').value : undefined
            })
        })
        .then(async (res) => {
            if (!res.ok) {
                const txt = await res.text();
                throw new Error(txt || 'Failed to send OTP');
            }
            return res.json();
        })
        .then(() => {
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
        })
        .catch((error) => {
            console.error('Error sending OTP:', error);
            alert('Error sending OTP: ' + error.message);
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
        document.getElementById('restrictedOtpForm')?.submit();
        document.getElementById('otpForm')?.submit();
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
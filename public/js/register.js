// Firebase-related functionality 
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-auth.js";

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
    // Create RecaptchaVerifier with correct site key
    window.recaptchaVerifier = new RecaptchaVerifier(auth, 'recaptcha-container', {
        'size': 'normal',
        'sitekey': '6LezOyYrAAAAAJRRTgIcrXDqa5_gOrkJNjNvoTFA',
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
    
    // Send OTP function
    window.sendOTP = function() {
        if (!isRecaptchaVerified) {
            alert("Please complete the reCAPTCHA verification first");
            return;
        }

        const phoneNumber = "+63" + document.getElementById('mobileNumber').value;
        
        signInWithPhoneNumber(auth, phoneNumber, window.recaptchaVerifier)
            .then((confirmationResult) => {
                // SMS sent. Store confirmationResult for later use
                window.confirmationResult = confirmationResult;
                window.otpSent = true;
                document.getElementById('resend-otp').disabled = true;
                
                // Start countdown for resend button
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
            }).catch((error) => {
                // Error; SMS not sent
                console.error("Error sending OTP:", error);
                alert("Error sending OTP: " + error.message);
            });
    };
    
    // Verify OTP button click
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
        
        // Show verification in progress
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';
        
        // Confirm the OTP code
        window.confirmationResult.confirm(code)
            .then((result) => {
                // User signed in successfully
                document.getElementById('firebase_verified').value = 'true';
                document.getElementById('otpForm').submit();
            }).catch((error) => {
                // Invalid code
                alert("Invalid OTP code. Please try again.");
                console.error("Error verifying OTP:", error);
                
                // Reset button
                this.disabled = false;
                this.innerHTML = 'Verify OTP';
            });
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
                'sitekey': '6LezOyYrAAAAAJRRTgIcrXDqa5_gOrkJNjNvoTFA',
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

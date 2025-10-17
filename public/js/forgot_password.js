// Import Firebase modules
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-auth.js";

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
            // reCAPTCHA solved, send OTP
            sendOTP();
        }
    });
    
    window.recaptchaVerifier.render();
    
    // Send OTP function
    window.sendOTP = function() {
        let phoneNumber = document.querySelector('p strong').textContent; // Get number from the displayed text
        
        console.log("Sending OTP to:", phoneNumber);
        
        // Show sending indicator
        document.getElementById('verify-otp').disabled = true;
        document.getElementById('verify-otp').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending OTP...';
        
        signInWithPhoneNumber(auth, phoneNumber, window.recaptchaVerifier)
            .then((confirmationResult) => {
                // SMS sent. Store confirmationResult for later use
                window.confirmationResult = confirmationResult;
                
                // Reset button text
                document.getElementById('verify-otp').disabled = false;
                document.getElementById('verify-otp').innerHTML = 'Verify OTP';
                
                // Disable resend button and start countdown
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
                
                // Reset button
                document.getElementById('verify-otp').disabled = false;
                document.getElementById('verify-otp').innerHTML = 'Verify OTP';
            });
    };
    
    // Automatically send OTP on page load
    sendOTP();
    
    // Verify OTP button click
    document.getElementById('verify-otp').addEventListener('click', function() {
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
                document.getElementById('verify-otp').disabled = false;
                document.getElementById('verify-otp').innerHTML = 'Verify OTP';
            });
    });
    
    // Resend OTP button click
    document.getElementById('resend-otp').addEventListener('click', function() {
        // Reset reCAPTCHA
        window.recaptchaVerifier.clear();
        window.recaptchaVerifier = new RecaptchaVerifier(auth, 'recaptcha-container', {
            'size': 'normal',
            'callback': (response) => {
                sendOTP();
            }
        });
        window.recaptchaVerifier.render();
    });
}

/**
 * Setup password validation functionality
 */
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
// Registration functionality - Firebase Email OTP Version
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js';
import { getAuth, sendSignInLinkToEmail, signInWithEmailLink, isSignInWithEmailLink } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js';

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

// Global variables
let isRecaptchaVerified = false;
let recaptchaResponse = null;

document.addEventListener('DOMContentLoaded', function() {
    const currentStep = document.getElementById('currentStep')?.value;
    
    // Password validation functionality for Step 1
    if (currentStep === '1') {
        setupPasswordValidation();
        setupStep1Recaptcha();
    }
    
    // Setup email OTP for Step 2
    if (currentStep === '2') {
        setupEmailOtpVerification();
    }
});

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

// Setup reCAPTCHA for Step 1
function setupStep1Recaptcha() {
    // Check if reCAPTCHA container exists
    const recaptchaContainer = document.getElementById('recaptcha-container-step1');
    if (!recaptchaContainer) return;
    
    // Load reCAPTCHA script if not already loaded
    if (typeof grecaptcha === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://www.google.com/recaptcha/api.js';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
        
        script.onload = function() {
            renderRecaptcha();
        };
    } else {
        renderRecaptcha();
    }
}

// Render reCAPTCHA widget
function renderRecaptcha() {
    const recaptchaContainer = document.getElementById('recaptcha-container-step1');
    if (!recaptchaContainer || typeof grecaptcha === 'undefined') return;
    
    grecaptcha.render(recaptchaContainer, {
        'sitekey': '6LezOyYrAAAAAJRRTgIcrXDqa5_gOrkJNjNvoTFA',
        'callback': function(response) {
            // reCAPTCHA solved
            document.getElementById('recaptcha_token').value = response;
            document.getElementById('continue-btn').disabled = false;
        },
        'expired-callback': function() {
            // reCAPTCHA expired
            document.getElementById('recaptcha_token').value = '';
            document.getElementById('continue-btn').disabled = true;
        }
    });
}

// Setup Firebase Email OTP verification
function setupEmailOtpVerification() {
    // Send email OTP when page loads
    sendEmailOtp();
    
    // Check if user clicked email link
    if (isSignInWithEmailLink(auth, window.location.href)) {
        // User clicked the email link, verify it
        verifyEmailLink();
    }
}

// Send email OTP using Firebase
function sendEmailOtp() {
    const email = document.getElementById('userEmail').value;
    if (!email) {
        alert('Email address not found. Please go back and try again.');
        return;
    }
    
    const actionCodeSettings = {
        // URL you want to redirect back to after clicking the email link
        url: window.location.origin + '/public/register.php?step=2&email=' + encodeURIComponent(email),
        // This must be true for email link sign-in
        handleCodeInApp: true,
    };
    
    // Send the email OTP
    sendSignInLinkToEmail(auth, email, actionCodeSettings)
        .then(() => {
            // Email sent successfully
            alert('Verification email sent! Please check your email and click the link to verify your account.');
            // Store email for verification
            localStorage.setItem('emailForSignIn', email);
        })
        .catch((error) => {
            console.error('Error sending email OTP:', error);
            let errorMessage = 'Error sending verification email: ' + error.message;
            
            // Provide user-friendly error messages
            switch (error.code) {
                case 'auth/invalid-email':
                    errorMessage = 'Invalid email address. Please check your email and try again.';
                    break;
                case 'auth/too-many-requests':
                    errorMessage = 'Too many requests. Please try again later.';
                    break;
                case 'auth/user-disabled':
                    errorMessage = 'This account has been disabled. Please contact support.';
                    break;
                default:
                    errorMessage = 'Failed to send verification email. Please try again.';
            }
            
            alert(errorMessage);
        });
}

// Verify email link when user clicks it
function verifyEmailLink() {
    const email = localStorage.getItem('emailForSignIn');
    if (!email) {
        alert('Email verification failed. Please try again.');
        return;
    }
    
    // Verify the email link
    signInWithEmailLink(auth, email, window.location.href)
        .then((result) => {
            // Email verified successfully
            localStorage.removeItem('emailForSignIn');
            
            // Set verification flag and submit form
            document.getElementById('firebase_verified').value = 'true';
            document.getElementById('otpForm').submit();
        })
        .catch((error) => {
            console.error('Error verifying email link:', error);
            let errorMessage = 'Email verification failed: ' + error.message;
            
            switch (error.code) {
                case 'auth/invalid-action-code':
                    errorMessage = 'Invalid verification link. Please request a new one.';
                    break;
                case 'auth/expired-action-code':
                    errorMessage = 'Verification link has expired. Please request a new one.';
                    break;
                case 'auth/invalid-email':
                    errorMessage = 'Invalid email address. Please try again.';
                    break;
                default:
                    errorMessage = 'Email verification failed. Please try again.';
            }
            
            alert(errorMessage);
        });
}

// Resend email OTP
document.addEventListener('DOMContentLoaded', function() {
    const resendBtn = document.getElementById('resend-otp');
    if (resendBtn) {
        resendBtn.addEventListener('click', function() {
            sendEmailOtp();
        });
    }
});
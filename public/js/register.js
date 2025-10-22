// Registration functionality - Email OTP Version
document.addEventListener('DOMContentLoaded', function() {
    const currentStep = document.getElementById('currentStep')?.value;
    
    // Password validation functionality for Step 1
    if (currentStep === '1') {
        setupPasswordValidation();
        setupStep1Recaptcha();
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

// Auto-format OTP input (Step 2)
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.getElementById('otp_code');
    if (otpInput) {
        otpInput.addEventListener('input', function() {
            // Remove non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 6 digits
            if (this.value.length > 6) {
                this.value = this.value.substring(0, 6);
            }
        });
        
        // Auto-submit when 6 digits are entered
        otpInput.addEventListener('input', function() {
            if (this.value.length === 6) {
                // Small delay to show the complete code
                setTimeout(() => {
                    document.getElementById('otpForm').submit();
                }, 500);
            }
        });
    }
});
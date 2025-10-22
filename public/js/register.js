// Registration JS for Email OTP + reCAPTCHA (no Firebase)
document.addEventListener('DOMContentLoaded', function() {
    const currentStep = document.getElementById('currentStep')?.value;
    if (currentStep === '1') {
        setupPasswordValidation();
        setupStep1Recaptcha();
    }
    if (currentStep === '2') {
        setupOtpInput();
    }
});

function setupStep1Recaptcha() {
    const container = document.getElementById('recaptcha-container-step1');
    if (!container) return;
    if (typeof grecaptcha === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://www.google.com/recaptcha/api.js';
        s.async = true; s.defer = true;
        s.onload = renderRecaptcha;
        document.head.appendChild(s);
    } else {
        renderRecaptcha();
    }
}

function renderRecaptcha() {
    const container = document.getElementById('recaptcha-container-step1');
    if (!container || typeof grecaptcha === 'undefined') return;
    grecaptcha.render(container, {
        sitekey: '6LezOyYrAAAAAJRRTgIcrXDqa5_gOrkJNjNvoTFA',
        callback: function(token) {
            const field = document.getElementById('recaptcha_token');
            if (field) field.value = token;
            const btn = document.querySelector('#registrationForm button[type="submit"]');
            if (btn) btn.disabled = false;
        },
        'expired-callback': function() {
            const field = document.getElementById('recaptcha_token');
            if (field) field.value = '';
            const btn = document.querySelector('#registrationForm button[type="submit"]');
            if (btn) btn.disabled = true;
        }
    });
}

function setupOtpInput() {
    const otp = document.getElementById('otp_code');
    if (!otp) return;
    otp.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    });
}

function setupPasswordValidation() {
    const pwd = document.getElementById('password');
    if (!pwd) return;
    pwd.addEventListener('input', function() {
        const password = this.value;
        const set = (id, ok) => { const el = document.getElementById(id); if (el) el.style.color = ok ? 'green' : 'inherit'; };
        set('length', password.length >= 8);
        set('uppercase', /[A-Z]/.test(password));
        set('lowercase', /[a-z]/.test(password));
        set('number', /[0-9]/.test(password));
        set('special', /[^A-Za-z0-9]/.test(password));
    });
}

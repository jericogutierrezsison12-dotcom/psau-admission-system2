// Firebase-related functionality for Admin Registration OTP
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

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

let isRecaptchaVerified = false;
let recaptchaResponse = null;

document.addEventListener('DOMContentLoaded', function() {
	const currentStep = document.getElementById('currentStep')?.value;
	if (currentStep === '2') {
		setupOtpVerification();
	}
});

function setupOtpVerification() {
	window.recaptchaVerifier = new RecaptchaVerifier(auth, 'recaptcha-container', {
		'size': 'normal',
		'callback': (response) => {
			isRecaptchaVerified = true;
			recaptchaResponse = response;
			document.getElementById('verify-otp').disabled = false;
			if (!window.otpSent) {
				sendOTP();
			}
		},
		'expired-callback': () => {
			isRecaptchaVerified = false;
			recaptchaResponse = null;
			document.getElementById('verify-otp').disabled = true;
		}
	});

	window.recaptchaVerifier.render().then(widgetId => {
		window.recaptchaWidgetId = widgetId;
	});

	document.getElementById('verify-otp').disabled = true;

	window.sendOTP = function() {
		if (!isRecaptchaVerified) {
			alert("Please complete the reCAPTCHA verification first");
			return;
		}
	// Convert local 09XXXXXXXXX to E.164 +63 format for OTP sending
	let local = (document.getElementById('mobileNumber').value || '').replace(/[^0-9]/g, '');
	if (local.startsWith('0')) {
		local = local.substring(1);
	}
	const phoneNumber = "+63" + local;
		signInWithPhoneNumber(auth, phoneNumber, window.recaptchaVerifier)
			.then((confirmationResult) => {
				window.confirmationResult = confirmationResult;
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
			}).catch((error) => {
				console.error("Error sending OTP:", error);
				alert("Error sending OTP: " + error.message);
			});
	};

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
		window.confirmationResult.confirm(code)
			.then(() => {
				document.getElementById('firebase_verified').value = 'true';
				document.getElementById('otpForm').submit();
			}).catch((error) => {
				alert("Invalid OTP code. Please try again.");
				console.error("Error verifying OTP:", error);
				this.disabled = false;
				this.innerHTML = 'Verify OTP';
			});
	});

	document.getElementById('resend-otp').addEventListener('click', function() {
		if (!isRecaptchaVerified) {
			if (window.recaptchaVerifier) {
				window.recaptchaVerifier.clear();
			}
			window.otpSent = false;
			window.recaptchaVerifier = new RecaptchaVerifier(auth, 'recaptcha-container', {
				'size': 'normal',
				'callback': (response) => {
					isRecaptchaVerified = true;
					recaptchaResponse = response;
					document.getElementById('verify-otp').disabled = false;
					sendOTP();
				},
				'expired-callback': () => {
					isRecaptchaVerified = false;
					recaptchaResponse = null;
					document.getElementById('verify-otp').disabled = true;
				}
			});
			window.recaptchaVerifier.render().then(widgetId => {
				window.recaptchaWidgetId = widgetId;
			});
		} else {
			sendOTP();
		}
	});
}



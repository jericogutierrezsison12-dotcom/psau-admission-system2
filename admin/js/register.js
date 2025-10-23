// Admin Registration Email OTP functionality
let isOtpSent = false;

document.addEventListener('DOMContentLoaded', function() {
	const currentStep = document.getElementById('currentStep')?.value;
	if (currentStep === '2') {
		setupOtpVerification();
		// Automatically send OTP when step 2 loads
		sendInitialOtp();
	}
});

function sendInitialOtp() {
	const email = document.getElementById('emailAddress')?.value;
	if (!email) {
		console.error('Email address not found');
		return;
	}

	// Get reCAPTCHA token
	if (typeof grecaptcha !== 'undefined') {
		grecaptcha.ready(function() {
			grecaptcha.execute('6LfJzKkpAAAAAKQjJQjJQjJQjJQjJQjJQjJQjJQjJ', {action: 'admin_register'}).then(function(token) {
				sendOtpRequest(email, token);
			}).catch(function(error) {
				console.error('reCAPTCHA error:', error);
				// Try without reCAPTCHA for admin registration
				sendOtpRequest(email, '');
			});
		});
	} else {
		// Try without reCAPTCHA for admin registration
		sendOtpRequest(email, '');
	}
}

function sendOtpRequest(email, recaptchaToken) {
	fetch('send_admin_otp.php', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({
			email: email,
			recaptcha_token: recaptchaToken
		})
	})
	.then(response => {
		if (!response.ok) {
			throw new Error('Network response was not ok');
		}
		return response.json();
	})
	.then(data => {
		if (data.ok) {
			console.log('OTP sent successfully');
			isOtpSent = true;
		} else {
			console.error('Failed to send OTP:', data.error);
			alert('Failed to send OTP: ' + (data.error || 'Unknown error'));
		}
	})
	.catch(error => {
		console.error('Error sending OTP:', error);
		alert('Failed to send OTP. Please try again.');
	});
}

function setupOtpVerification() {
	// Email OTP is already sent by the server, just setup verification
	document.getElementById('verify-otp').addEventListener('click', function() {
		const code = document.getElementById('otp_code').value;
		if (!code || code.length !== 6) {
			alert("Please enter a valid 6-digit OTP code");
			return;
		}
		this.disabled = true;
		this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';
		
		// Submit the form for server-side verification
		document.getElementById('otpForm').submit();
	});

	document.getElementById('resend-otp').addEventListener('click', function() {
		if (isOtpSent) {
			alert("Please wait before requesting another OTP");
			return;
		}
		
		this.disabled = true;
		this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
		
		const email = document.getElementById('emailAddress').value;
		
		// Get reCAPTCHA token
		if (typeof grecaptcha !== 'undefined') {
			grecaptcha.ready(function() {
				grecaptcha.execute('6LfJzKkpAAAAAKQjJQjJQjJQjJQjJQjJQjJQjJQjJ', {action: 'admin_register'}).then(function(token) {
					sendOtpRequest(email, token);
					handleResendResponse();
				}).catch(function(error) {
					console.error('reCAPTCHA error:', error);
					sendOtpRequest(email, '');
					handleResendResponse();
				});
			});
		} else {
			sendOtpRequest(email, '');
			handleResendResponse();
		}
	});
}

function handleResendResponse() {
	setTimeout(() => {
		if (isOtpSent) {
			alert('OTP resent successfully. Please check your email.');
			let seconds = 60;
			const countdown = setInterval(() => {
				document.getElementById('resend-otp').innerText = `Resend OTP (${seconds}s)`;
				seconds--;
				if (seconds < 0) {
					clearInterval(countdown);
					document.getElementById('resend-otp').innerText = 'Resend OTP';
					document.getElementById('resend-otp').disabled = false;
					isOtpSent = false;
				}
			}, 1000);
		} else {
			alert('Failed to resend OTP. Please try again.');
			document.getElementById('resend-otp').disabled = false;
			document.getElementById('resend-otp').innerHTML = 'Resend OTP';
		}
	}, 1000);
}



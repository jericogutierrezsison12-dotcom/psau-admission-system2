// Admin Registration Email OTP functionality
let isOtpSent = false;

document.addEventListener('DOMContentLoaded', function() {
	const currentStep = document.getElementById('currentStep')?.value;
	if (currentStep === '2') {
		setupOtpVerification();
	}
});

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
		
		// Send resend request to server
		fetch('resend_admin_otp.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				email: document.getElementById('emailAddress').value
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				alert('OTP resent successfully. Please check your email.');
				isOtpSent = true;
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
				alert('Failed to resend OTP: ' + (data.message || 'Unknown error'));
				this.disabled = false;
				this.innerHTML = 'Resend OTP';
			}
		})
		.catch(error => {
			console.error('Error resending OTP:', error);
			alert('Failed to resend OTP. Please try again.');
			this.disabled = false;
			this.innerHTML = 'Resend OTP';
		});
	});
}



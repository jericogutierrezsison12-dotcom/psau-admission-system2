/**
 * PSAU Admission System - Login Page JavaScript
 */

// Initialize the FingerprintJS agent
const fpPromise = FingerprintJS.load();

// Get the visitor identifier
async function getFingerprint() {
    try {
        const fp = await fpPromise;
        const result = await fp.get();
        
        // Set the fingerprint in the hidden field
        document.getElementById('deviceFingerprint').value = result.visitorId;
        return result.visitorId;
    } catch (error) {
        console.error('Error generating fingerprint:', error);
        return null;
    }
}

// Countdown timer for blocked devices
function startCountdown() {
    const countdownEl = document.getElementById('countdown');
    if (countdownEl) {
        let minutes = parseInt(countdownEl.textContent);
        if (isNaN(minutes)) return;
        
        const totalSeconds = minutes * 60;
        let secondsRemaining = totalSeconds;
        
        const timer = setInterval(() => {
            secondsRemaining--;
            
            if (secondsRemaining <= 0) {
                clearInterval(timer);
                window.location.reload();
                return;
            }
            
            const minutesLeft = Math.floor(secondsRemaining / 60);
            const secondsLeft = secondsRemaining % 60;
            
            countdownEl.textContent = `${minutesLeft}:${secondsLeft < 10 ? '0' : ''}${secondsLeft}`;
        }, 1000);
    }
}

// Document ready handler
document.addEventListener('DOMContentLoaded', function() {
    // Generate device fingerprint
    getFingerprint();
    
    // Start countdown if blocked
    startCountdown();
}); 
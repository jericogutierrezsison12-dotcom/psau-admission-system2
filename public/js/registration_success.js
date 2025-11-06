// Import the functions you need from the SDKs you need
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getFunctions, httpsCallable } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-functions.js";

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
const functions = getFunctions(app);

// Send welcome email
const sendWelcomeEmail = httpsCallable(functions, 'sendWelcomeEmail');

// Send welcome email when page loads
window.onload = function() {
    // Prepare data for welcome email
    const emailData = {
        email: document.getElementById('user-email').value,
        firstName: document.getElementById('user-firstname').value,
        lastName: document.getElementById('user-lastname').value,
        controlNumber: document.getElementById('control-number-value').textContent
    };
    
    // Call the Firebase function to send welcome email
    sendWelcomeEmail(emailData)
        .then((result) => {
            console.log("Welcome email sent successfully");
        })
        .catch((error) => {
            console.error("Error sending welcome email:", error);
        });
};

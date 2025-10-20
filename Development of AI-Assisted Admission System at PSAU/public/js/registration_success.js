// Import the functions you need from the SDKs you need
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getFunctions, httpsCallable } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-functions.js";

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

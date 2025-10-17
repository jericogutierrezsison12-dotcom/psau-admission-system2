/**
 * PSAU Admission System - Profile Page JavaScript
 * Handles form validation and UI interactions for user profile
 */

document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            // Toggle password visibility
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('bi-eye-slash');
                this.classList.add('bi-eye');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('bi-eye');
                this.classList.add('bi-eye-slash');
            }
        });
    });
    
    // Form validation
    const profileForm = document.getElementById('profileForm');
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            let valid = true;
            const firstNameInput = document.getElementById('first_name');
            const lastNameInput = document.getElementById('last_name');
            const currentPassword = document.getElementById('current_password');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            // Clear previous validation messages
            clearValidationMessages();
            
            // Basic validation for required fields
            if (!firstNameInput.value.trim()) {
                showError(firstNameInput, 'First name is required');
                valid = false;
            }
            
            if (!lastNameInput.value.trim()) {
                showError(lastNameInput, 'Last name is required');
                valid = false;
            }
            
            // Password validation
            if (currentPassword.value || newPassword.value || confirmPassword.value) {
                // If any password field is filled, all are required
                if (!currentPassword.value) {
                    showError(currentPassword, 'Current password is required');
                    valid = false;
                }
                
                if (!newPassword.value) {
                    showError(newPassword, 'New password is required');
                    valid = false;
                } else {
                    // Check password requirements
                    let hasError = false;
                    let errorMessage = '';

                    if (newPassword.value.length < 8) {
                        errorMessage = 'Password must be at least 8 characters long';
                        hasError = true;
                    } else if (!newPassword.value.match(/[A-Z]/)) {
                        errorMessage = 'Password must contain at least one uppercase letter';
                        hasError = true;
                    } else if (!newPassword.value.match(/[a-z]/)) {
                        errorMessage = 'Password must contain at least one lowercase letter';
                        hasError = true;
                    } else if (!newPassword.value.match(/[0-9]/)) {
                        errorMessage = 'Password must contain at least one number';
                        hasError = true;
                    } else if (!newPassword.value.match(/[^A-Za-z0-9]/)) {
                        errorMessage = 'Password must contain at least one special character';
                        hasError = true;
                    }

                    if (hasError) {
                        showError(newPassword, errorMessage);
                        valid = false;
                    }
                }
                
                if (!confirmPassword.value) {
                    showError(confirmPassword, 'Please confirm your new password');
                    valid = false;
                } else if (newPassword.value !== confirmPassword.value) {
                    showError(confirmPassword, 'Passwords do not match');
                    valid = false;
                }
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
    }
    
    // Helper functions for form validation
    function showError(input, message) {
        input.classList.add('is-invalid');
        
        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        // Find parent form-group and append error message
        const formGroup = input.closest('.mb-3') || input.parentElement;
        formGroup.appendChild(errorDiv);
    }
    
    function clearValidationMessages() {
        // Remove all error messages and validation classes
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.remove();
        });
    }
}); 
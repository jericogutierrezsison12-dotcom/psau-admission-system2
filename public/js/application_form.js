/**
 * PSAU Admission System - Application Form JavaScript
 * Handles the form validation and file upload interactions
 */

$(document).ready(function() {
    // Load common components
    $("#navbar-placeholder").load("templates/navbar.html", function() {
        // Update username in navbar after loading
        updateUserInfo();
    });
    
    $("#sidebar-placeholder").load("templates/sidebar.html", function() {
        // Highlight active sidebar item
        $(".nav-link").removeClass("active");
        $(".nav-link[href='application_form.php']").addClass("active");
    });
    
    $("#mobile-menu-placeholder").load("templates/mobile_menu.html", function() {
        // Highlight active mobile menu item
        $(".list-group-item").removeClass("active");
        $(".list-group-item[href='application_form.php']").addClass("active");
    });
    
    initFormFunctionality();
});

/**
 * Initialize all form functionality
 */
function initFormFunctionality() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('pdf_file');
    const browseBtn = document.getElementById('browseBtn');
    const fileName = document.getElementById('file-name');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('uploadForm');
    
    // Browse button click
    if (browseBtn) {
        browseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (fileInput) fileInput.click();
        });
    }
    
    // File input change
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            handleFileSelect(this.files);
        });
    }
    
    // Drag and drop events
    if (dropZone) {
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('border-primary');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('border-primary');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('border-primary');
            
            const dt = e.dataTransfer;
            const files = dt.files;
            
            handleFileSelect(files);
        });
    }
    
    // Handle file selection
    function handleFileSelect(files) {
        if (files.length > 0) {
            const file = files[0];
            
            // Check if file is PDF
            if (file.type === 'application/pdf') {
                // Check file size (max 5MB)
                if (file.size <= 5 * 1024 * 1024) {
                    if (fileName) fileName.innerHTML = `<i class="bi bi-file-earmark-pdf"></i> ${file.name}`;
                    checkFormValidity();
                } else {
                    alert('File size must be less than 5MB.');
                    if (fileInput) fileInput.value = '';
                    if (fileName) fileName.textContent = '';
                    if (submitBtn) submitBtn.disabled = true;
                }
            } else {
                alert('Only PDF files are allowed.');
                if (fileInput) fileInput.value = '';
                if (fileName) fileName.textContent = '';
                if (submitBtn) submitBtn.disabled = true;
            }
        }
    }
    
    // Handle 2x2 image preview
    const imageInput = document.getElementById('image_2x2');
    const imagePreview = document.getElementById('image-preview');
    
    if (imageInput && imagePreview) {
        const previewImg = imagePreview.querySelector('img');
        
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Check if file is an image
                if (file.type.match('image/jpeg') || file.type.match('image/png')) {
                    // Check file size (max 2MB)
                    if (file.size <= 2 * 1024 * 1024) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            previewImg.src = e.target.result;
                            imagePreview.classList.remove('d-none');
                        }
                        
                        reader.readAsDataURL(file);
                        checkFormValidity();
                    } else {
                        alert('Image size must be less than 2MB.');
                        imageInput.value = '';
                        imagePreview.classList.add('d-none');
                        checkFormValidity();
                    }
                } else {
                    alert('Only JPG, JPEG, or PNG images are allowed.');
                    imageInput.value = '';
                    imagePreview.classList.add('d-none');
                    checkFormValidity();
                }
            } else {
                imagePreview.classList.add('d-none');
                checkFormValidity();
            }
        });
    }
    
    // Form field validation
    if (form) {
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(function(field) {
            field.addEventListener('input', checkFormValidity);
        });
        
        // Form submit validation
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Validate GPA/grade range
            const gpaField = document.getElementById('gpa');
            if (gpaField && gpaField.value) {
                const gpaValue = parseFloat(gpaField.value);
                if (gpaValue < 75 || gpaValue > 100) {
                    isValid = false;
                    gpaField.classList.add('is-invalid');
                    alert('GPA/Average Grade must be between 75 and 100.');
                }
            }
            
            // Validate age
            const ageField = document.getElementById('age');
            if (ageField && ageField.value) {
                const ageValue = parseInt(ageField.value);
                if (ageValue < 16 || ageValue > 100) {
                    isValid = false;
                    ageField.classList.add('is-invalid');
                    alert('Age must be between 16 and 100.');
                }
            }
            
            // Prevent form submission if validation fails
            if (!isValid) {
                event.preventDefault();
                window.scrollTo(0, 0);
            }
        });
    }
    
    // Handle "Others" option for previous_school
    const previousSchoolSelect = document.getElementById('previous_school');
    const previousSchoolOther = document.getElementById('previous_school_other');
    if (previousSchoolSelect && previousSchoolOther) {
        previousSchoolSelect.addEventListener('change', function() {
            if (this.value === 'others') {
                previousSchoolOther.classList.remove('d-none');
                previousSchoolOther.required = true;
            } else {
                previousSchoolOther.classList.add('d-none');
                previousSchoolOther.required = false;
                previousSchoolOther.value = '';
            }
            checkFormValidity();
        });
    }
    
    // Handle "Others" option for GPA
    const gpaSelect = document.getElementById('gpa');
    const gpaOther = document.getElementById('gpa_other');
    if (gpaSelect && gpaOther) {
        gpaSelect.addEventListener('change', function() {
            if (this.value === 'others') {
                gpaOther.classList.remove('d-none');
                gpaOther.required = true;
            } else {
                gpaOther.classList.add('d-none');
                gpaOther.required = false;
                gpaOther.value = '';
            }
            checkFormValidity();
        });
    }
    
    // Handle "Others" option for address
    const addressSelect = document.getElementById('address');
    const addressOther = document.getElementById('address_other');
    if (addressSelect && addressOther) {
        addressSelect.addEventListener('change', function() {
            if (this.value === 'others') {
                addressOther.classList.remove('d-none');
                addressOther.required = true;
            } else {
                addressOther.classList.add('d-none');
                addressOther.required = false;
                addressOther.value = '';
            }
            checkFormValidity();
        });
    }
    
    // Check if all required fields are filled
    function checkFormValidity() {
        if (!submitBtn || !form) return;
        
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                isValid = false;
            }
        });
        
        // Special validation for GPA
        const gpaField = document.getElementById('gpa');
        if (gpaField && gpaField.value && gpaField.value !== 'others') {
            // If it's a range, it's valid
        } else if (gpaField && gpaField.value === 'others') {
            const gpaOtherField = document.getElementById('gpa_other');
            if (gpaOtherField && gpaOtherField.value) {
                const gpaValue = parseFloat(gpaOtherField.value);
                if (gpaValue < 75 || gpaValue > 100) {
                    isValid = false;
                }
            }
        }
        
        // Special validation for age
        const ageField = document.getElementById('age');
        if (ageField && ageField.value) {
            const ageValue = parseInt(ageField.value);
            if (ageValue < 16 || ageValue > 100) {
                isValid = false;
            }
        }
        
        // PDF file must be selected
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            isValid = false;
        }
        
        // 2x2 image must be selected
        const imageInput = document.getElementById('image_2x2');
        if (!imageInput || !imageInput.files || imageInput.files.length === 0) {
            isValid = false;
        }
        
        submitBtn.disabled = !isValid;
    }
}

/**
 * Updates user information in the navbar
 */
function updateUserInfo() {
    // Use userData passed from PHP
    if (typeof userData !== 'undefined' && userData) {
        $('#navbarDropdown').text(userData.first_name + ' ' + userData.last_name);
    }
} 
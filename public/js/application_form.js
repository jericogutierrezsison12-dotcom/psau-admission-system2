/**
 * PSAU Admission System - Application Form JavaScript
 * Handles the form validation and file upload interactions
 */

$(document).ready(function() {
    console.log('Application form JavaScript loaded');
    
    // Load common components (only if placeholders exist)
    if ($("#navbar-placeholder").length) {
        $("#navbar-placeholder").load("templates/navbar.html", function() {
            // Update username in navbar after loading
            updateUserInfo();
        }).fail(function() {
            console.log('Navbar template not found, continuing without it');
        });
    }
    
    if ($("#sidebar-placeholder").length) {
        $("#sidebar-placeholder").load("templates/sidebar.html", function() {
            // Highlight active sidebar item
            $(".nav-link").removeClass("active");
            $(".nav-link[href='application_form.php']").addClass("active");
        }).fail(function() {
            console.log('Sidebar template not found, continuing without it');
        });
    }
    
    if ($("#mobile-menu-placeholder").length) {
        $("#mobile-menu-placeholder").load("templates/mobile_menu.html", function() {
            // Highlight active mobile menu item
            $(".list-group-item").removeClass("active");
            $(".list-group-item[href='application_form.php']").addClass("active");
        }).fail(function() {
            console.log('Mobile menu template not found, continuing without it');
        });
    }
    
    // Initialize form functionality
    console.log('Initializing form functionality');
    initFormFunctionality();
});

/**
 * Initialize all form functionality
 */
function initFormFunctionality() {
    console.log('initFormFunctionality called');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('pdf_file');
    const browseBtn = document.getElementById('browseBtn');
    const fileName = document.getElementById('file-name');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('uploadForm');
    
    console.log('Form elements found:', {
        dropZone: !!dropZone,
        fileInput: !!fileInput,
        browseBtn: !!browseBtn,
        fileName: !!fileName,
        submitBtn: !!submitBtn,
        form: !!form
    });
    
    // Browse button click
    if (browseBtn) {
        browseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (fileInput) fileInput.click();
        });
    }
    
    // File input change
    if (fileInput) {
        console.log('Adding file input change listener');
        fileInput.addEventListener('change', function() {
            console.log('File input changed, files:', this.files);
            handleFileSelect(this.files);
        });
    } else {
        console.log('File input not found!');
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
                    
                    // Start ML classification scan
                    console.log('Starting ML scan for file:', file.name);
                    scanDocumentWithML(file);
                    
                    checkFormValidity();
                } else {
                    alert('File size must be less than 5MB.');
                    if (fileInput) fileInput.value = '';
                    if (fileName) fileName.textContent = '';
                    if (submitBtn) submitBtn.disabled = true;
                    hideMLScanResults();
                }
            } else {
                alert('Only PDF files are allowed.');
                if (fileInput) fileInput.value = '';
                if (fileName) fileName.textContent = '';
                if (submitBtn) submitBtn.disabled = true;
                hideMLScanResults();
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
        if (gpaField && gpaField.value) {
            const gpaValue = parseFloat(gpaField.value);
            if (gpaValue < 75 || gpaValue > 100) {
                isValid = false;
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

/**
 * ML Classifier Functions
 */

/**
 * Scan document with ML classifier
 */
function scanDocumentWithML(file) {
    console.log('scanDocumentWithML called with file:', file);
    // Show loading state
    showMLScanLoading();
    
    // Create FormData for file upload
    const formData = new FormData();
    formData.append('file', file);
    
    // Make API call to ML classifier
    fetch('http://localhost:5000/ocr_service', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            displayMLScanResults(data);
        } else {
            showMLScanError(data.error || 'Failed to analyze document');
        }
    })
    .catch(error => {
        console.error('ML Scan Error:', error);
        showMLScanError('Unable to connect to AI service. Please check your internet connection and try again.');
    });
}

/**
 * Show ML scan loading state
 */
function showMLScanLoading() {
    console.log('showMLScanLoading called');
    const scanResults = document.getElementById('ml-scan-results');
    const scanLoading = document.getElementById('scan-loading');
    const scanResultsDiv = document.getElementById('scan-results');
    const scanError = document.getElementById('scan-error');
    
    console.log('Elements found:', {
        scanResults: !!scanResults,
        scanLoading: !!scanLoading,
        scanResultsDiv: !!scanResultsDiv,
        scanError: !!scanError
    });
    
    if (scanResults) {
        scanResults.style.display = 'block';
    }
    if (scanLoading) {
        scanLoading.style.display = 'block';
    }
    if (scanResultsDiv) {
        scanResultsDiv.style.display = 'none';
    }
    if (scanError) {
        scanError.style.display = 'none';
    }
}

/**
 * Display ML scan results
 */
function displayMLScanResults(data) {
    const scanLoading = document.getElementById('scan-loading');
    const scanResults = document.getElementById('scan-results');
    const scanError = document.getElementById('scan-error');
    const documentType = document.getElementById('document-type');
    const documentStatus = document.getElementById('document-status');
    const analysisDetails = document.getElementById('analysis-details');
    const extractedText = document.getElementById('extracted-text');
    
    // Hide loading and error states
    if (scanLoading) scanLoading.style.display = 'none';
    if (scanError) scanError.style.display = 'none';
    
    // Show results
    if (scanResults) scanResults.style.display = 'block';
    
    // Set document type
    if (documentType) {
        const isReportCard = data.prediction === 'Report Card';
        documentType.textContent = data.prediction || 'Unknown';
        documentType.className = `badge fs-6 ${isReportCard ? 'bg-success' : 'bg-warning'}`;
    }
    
    // Set document status
    if (documentStatus && data.status_info) {
        const status = data.status_info.status;
        const statusText = status === 'passed' ? 'Passed' : 
                          status === 'failed' ? 'Failed' : 
                          status === 'unknown' ? 'Unknown' : 'Error';
        
        documentStatus.textContent = statusText;
        documentStatus.className = `badge fs-6 ${
            status === 'passed' ? 'bg-success' : 
            status === 'failed' ? 'bg-danger' : 
            status === 'unknown' ? 'bg-warning' : 'bg-secondary'
        }`;
    }
    
    // Set analysis details
    if (analysisDetails && data.status_info) {
        analysisDetails.innerHTML = `
            <strong>Analysis:</strong> ${data.status_info.message || 'No additional details available'}<br>
            <strong>Confidence:</strong> ${data.total_texts || 0} text elements detected<br>
            <strong>Processing:</strong> AI-powered document analysis completed
        `;
    }
    
    // Set extracted text
    if (extractedText && data.texts && data.texts.length > 0) {
        const textList = data.texts.slice(0, 10).map((text, index) => 
            `<div class="mb-1"><small class="text-muted">${index + 1}.</small> ${text.text} <span class="badge bg-light text-dark">${text.confidence}%</span></div>`
        ).join('');
        
        extractedText.innerHTML = textList + (data.texts.length > 10 ? 
            `<div class="text-muted mt-2"><small>... and ${data.texts.length - 10} more items</small></div>` : '');
    } else if (extractedText) {
        extractedText.innerHTML = '<div class="text-muted">No text could be extracted from this document.</div>';
    }
}

/**
 * Show ML scan error
 */
function showMLScanError(message) {
    const scanLoading = document.getElementById('scan-loading');
    const scanResults = document.getElementById('scan-results');
    const scanError = document.getElementById('scan-error');
    const errorMessage = document.getElementById('error-message');
    
    // Hide loading and results
    if (scanLoading) scanLoading.style.display = 'none';
    if (scanResults) scanResults.style.display = 'none';
    
    // Show error
    if (scanError) scanError.style.display = 'block';
    if (errorMessage) errorMessage.textContent = message;
}

/**
 * Hide ML scan results
 */
function hideMLScanResults() {
    const scanResults = document.getElementById('ml-scan-results');
    if (scanResults) {
        scanResults.style.display = 'none';
    }
} 
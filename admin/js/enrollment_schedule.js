// Tab persistence
const url = new URL(window.location);
const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
tabLinks.forEach(link => {
    link.addEventListener('shown.bs.tab', function (e) {
        url.searchParams.set('tab', e.target.getAttribute('href').substring(1));
        window.history.replaceState({}, '', url);
    });
});

// Bootstrap form validation
(function () {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
})();

// Manual assignment form validation
const manualAssignForm = document.getElementById('manualAssignForm');
if (manualAssignForm) {
    manualAssignForm.addEventListener('submit', function (e) {
        const checkboxes = manualAssignForm.querySelectorAll('input[name="applicant_ids[]"]:checked');
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one applicant to assign.');
        }
    });
}

$(document).ready(function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Auto-fill instructions and requirements if data is available
    const instructionsField = document.getElementById('instructions');
    const requirementsField = document.getElementById('requirements');
    const defaultInstructions = document.getElementById('default_instructions')?.value;
    const defaultRequirements = document.getElementById('default_requirements')?.value;
    
    if (instructionsField && !instructionsField.value.trim() && defaultInstructions) {
        instructionsField.value = defaultInstructions;
    }
    if (requirementsField && !requirementsField.value.trim() && defaultRequirements) {
        requirementsField.value = defaultRequirements;
    }

    // Function to update capacity limit based on selected venue
    function updateCapacityLimit() {
        const venueSelect = document.getElementById('venue_id');
        const capacityInput = document.getElementById('capacity');
        const capacityFeedback = document.getElementById('capacity-feedback');
        const capacityHelp = document.getElementById('capacity-help');
        
        const selectedOption = venueSelect.options[venueSelect.selectedIndex];
        const maxCapacity = parseInt(selectedOption.getAttribute('data-capacity')) || 0;
        
        if (maxCapacity > 0) {
            capacityInput.max = maxCapacity;
            const currentValue = parseInt(capacityInput.value) || 0;
            
            if (currentValue > maxCapacity) {
                capacityInput.value = maxCapacity;
            }
            
            capacityHelp.textContent = `Enter the number of students (maximum ${maxCapacity} for this venue)`;
            capacityFeedback.textContent = `Please enter a number between 1 and ${maxCapacity}`;
        } else {
            capacityInput.removeAttribute('max');
            capacityHelp.textContent = 'Enter the number of students that can enroll in this schedule.';
            capacityFeedback.textContent = 'Please enter the number of slots (minimum 1).';
        }
    }

    // Add event listener for venue selection
    document.getElementById('venue_id')?.addEventListener('change', updateCapacityLimit);

    // Add event listener for capacity input to validate in real-time
    document.getElementById('capacity')?.addEventListener('input', function() {
        const venueSelect = document.getElementById('venue_id');
        const selectedOption = venueSelect.options[venueSelect.selectedIndex];
        const maxCapacity = parseInt(selectedOption.getAttribute('data-capacity')) || 0;
        
        if (maxCapacity > 0) {
            const currentValue = parseInt(this.value) || 0;
            if (currentValue > maxCapacity) {
                this.value = maxCapacity;
            }
        }
    });

    // Handle select all checkbox
    $('#selectAll').change(function() {
        $('.applicant-checkbox').prop('checked', $(this).prop('checked'));
        updateAssignButton();
    });

    // Handle individual checkboxes
    $('.applicant-checkbox').change(function() {
        updateAssignButton();
        
        // Update select all checkbox
        const allChecked = $('.applicant-checkbox:checked').length === $('.applicant-checkbox').length;
        $('#selectAll').prop('checked', allChecked);
    });

    // Enable/disable assign button based on selections
    function updateAssignButton() {
        const hasSchedule = $('#schedule_id').val() !== '';
        const hasApplicants = $('.applicant-checkbox:checked').length > 0;
        $('#assignButton').prop('disabled', !(hasSchedule && hasApplicants));
    }

    // Update assign button when schedule is selected
    $('#schedule_id').change(updateAssignButton);

    // Refresh schedules button
    $('#refreshSchedules').click(function() {
        location.reload();
    });

    // Auto-close alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);

    // Initialize on page load
    updateCapacityLimit();
});

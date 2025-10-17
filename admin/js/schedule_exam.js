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

    // Handle venue selection and capacity
    $('#venue_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        const capacity = selectedOption.data('capacity');
        if (capacity) {
            $('#capacity').val(capacity);
            $('#capacity').attr('max', capacity);
        } else {
            $('#capacity').val('');
            $('#capacity').removeAttr('max');
        }
    });

    // Validate capacity against venue maximum
    $('#capacity').on('input', function() {
        const maxCapacity = parseInt($('#venue_id option:selected').data('capacity'));
        const enteredCapacity = parseInt($(this).val());
        
        if (maxCapacity && enteredCapacity > maxCapacity) {
            $(this).val(maxCapacity);
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

    // Prevent form submission if no applicants are selected
    $('form').submit(function(e) {
        if ($(this).find('input[type="checkbox"]:checked').length === 0 && 
            $(this).find('input[name="action"]').val() === 'assign_applicants') {
            e.preventDefault();
            alert('Please select at least one applicant to assign.');
        }
    });

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
            capacityHelp.textContent = 'Enter the number of students that can take the exam in this schedule.';
            capacityFeedback.textContent = 'Please enter the number of slots (minimum 1).';
        }
    }

    // Add event listener for venue selection
    document.getElementById('venue_id')?.addEventListener('change', updateCapacityLimit);

    // Add event listener for capacity input to validate in real-time
    document.getElementById('capacity').addEventListener('input', function() {
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

    // Handle select all checkbox in manual assignment
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.applicant-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateAssignButton();
    });

    // Handle individual checkboxes in manual assignment
    document.querySelectorAll('.applicant-checkbox')?.forEach(checkbox => {
        checkbox.addEventListener('change', updateAssignButton);
    });

    // Update assign button state
    function updateAssignButton() {
        const assignButton = document.getElementById('assignButton');
        const checkedBoxes = document.querySelectorAll('.applicant-checkbox:checked');
        
        if (assignButton) {
            assignButton.disabled = checkedBoxes.length === 0;
        }
    }

    // Handle refresh button click
    document.getElementById('refreshSchedules')?.addEventListener('click', function() {
        location.reload();
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize assign button state
        updateAssignButton();
        
        // Initialize capacity if venue is pre-selected
        if (document.getElementById('venue_id')) {
            updateCapacityLimit();
        }
    });

    // Table sorting functionality
    let currentSortColumn = '';
    let currentSortOrder = 'asc';

    $('.sortable').on('click', function() {
        const column = $(this).data('sort');
        
        // Remove sort indicators from all columns
        $('.sortable').removeClass('asc desc');
        
        // Toggle sort order if clicking the same column
        if (column === currentSortColumn) {
            currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortColumn = column;
            currentSortOrder = 'asc';
        }
        
        // Add sort indicator to current column
        $(this).addClass(currentSortOrder);
        
        const table = $(this).closest('table');
        const tbody = table.find('tbody');
        const rows = tbody.find('tr').toArray();
        
        rows.sort(function(a, b) {
            let aValue = $(a).find('.' + column).text().trim();
            let bValue = $(b).find('.' + column).text().trim();
            
            // Handle date sorting for verified-at column
            if (column === 'verified-at') {
                aValue = new Date(aValue).getTime();
                bValue = new Date(bValue).getTime();
                return currentSortOrder === 'asc' ? aValue - bValue : bValue - aValue;
            }
            
            // Handle numeric sorting for control numbers
            if (column === 'control-number') {
                aValue = aValue.replace(/[^0-9]/g, '');
                bValue = bValue.replace(/[^0-9]/g, '');
                return currentSortOrder === 'asc' ? 
                    aValue.localeCompare(bValue, undefined, {numeric: true}) :
                    bValue.localeCompare(aValue, undefined, {numeric: true});
            }
            
            // Regular string comparison for other columns
            return currentSortOrder === 'asc' ? 
                aValue.localeCompare(bValue) :
                bValue.localeCompare(aValue);
        });
        
        // Reattach sorted rows to tbody
        tbody.empty();
        rows.forEach(row => tbody.append(row));
    });
}); 
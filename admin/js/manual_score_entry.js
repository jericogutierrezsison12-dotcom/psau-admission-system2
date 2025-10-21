$(document).ready(function() {
    // Initialize DataTable
    $('#recentUploadsTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [[4, 'desc']], // Sort by upload date by default
        language: {
            search: "Search uploads:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ uploads",
            infoEmpty: "Showing 0 to 0 of 0 uploads",
            emptyTable: "No score uploads available"
        }
    });

    // Form validation
    $('#scoreForm').on('submit', function(e) {
        let isValid = true;
        const requiredFields = $(this).find('[required]');

        // Clear previous error states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Check each required field
        requiredFields.each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                $(this).after('<div class="invalid-feedback">This field is required</div>');
                isValid = false;
            }
        });

        // Validate control number format (PSAU followed by numbers)
        const controlNumber = $('#control_number').val().trim();
        if (!controlNumber.startsWith('PSAU') || !/^PSAU\d+$/.test(controlNumber)) {
            $('#control_number').addClass('is-invalid');
            $('#control_number').after('<div class="invalid-feedback">Invalid control number format. Must start with PSAU followed by numbers.</div>');
            isValid = false;
        }

        // Validate stanine score
        const stanineScore = parseInt($('#stanine_score').val());
        if (isNaN(stanineScore) || stanineScore < 1 || stanineScore > 9) {
            $('#stanine_score').addClass('is-invalid');
            $('#stanine_score').after('<div class="invalid-feedback">Please select a valid stanine score (1-9)</div>');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.is-invalid:first').offset().top - 100
            }, 200);
        }
    });

    // Format control number input
    $('#control_number').on('input', function() {
        let value = $(this).val().toUpperCase().trim();
        
        // If PSAU is not at the start, add it
        if (!value.startsWith('PSAU')) {
            value = 'PSAU' + value.replace(/[^0-9]/g, '');
        } else {
            // Keep PSAU and only numbers after it
            value = 'PSAU' + value.substring(4).replace(/[^0-9]/g, '');
        }
        
        $(this).val(value);
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}); 
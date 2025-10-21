$(document).ready(function() {
    // Initialize DataTable
    $('#venuesTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [[0, 'asc']],
        language: {
            search: "Search venues:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ venues",
            infoEmpty: "Showing 0 to 0 of 0 venues",
            emptyTable: "No venues available"
        },
        columnDefs: [
            { orderable: false, targets: -1 } // Disable sorting for actions column
        ]
    });

    // Form validation
    $('#venueForm').on('submit', function(e) {
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

        // Validate capacity
        const capacity = parseInt($('#capacity').val());
        if (isNaN(capacity) || capacity < 1) {
            $('#capacity').addClass('is-invalid');
            $('#capacity').after('<div class="invalid-feedback">Capacity must be a positive number</div>');
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

    // Handle edit venue
    $('.edit-venue').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const capacity = $(this).data('capacity');
        const description = $(this).data('description');
        const isActive = $(this).data('active');

        // Update form
        $('#formTitle').text('Edit Venue');
        $('#formAction').val('edit_venue');
        $('#venueId').val(id);
        $('#venue_name').val(name);
        $('#capacity').val(capacity);
        $('#description').val(description);
        $('#is_active').prop('checked', isActive === 1);
        $('#isActiveContainer').show();
        $('#submitButtonText').text('Update Venue');
        $('#cancelEdit').show();

        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#venueForm').offset().top - 100
        }, 200);
    });

    // Handle delete venue
    $('.delete-venue').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        $('#deleteVenueId').val(id);
        $('#deleteVenueName').text(name);
        $('#deleteModal').modal('show');
    });

    // Handle cancel edit
    $('#cancelEdit').on('click', function() {
        // Reset form
        $('#venueForm')[0].reset();
        $('#formTitle').text('Add New Venue');
        $('#formAction').val('add_venue');
        $('#venueId').val('');
        $('#isActiveContainer').hide();
        $('#submitButtonText').text('Add Venue');
        $(this).hide();
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}); 
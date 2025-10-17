$(document).ready(function() {
    // Initialize DataTable
    const table = $('#contentTable').DataTable({
        order: [[0, 'asc']], // Sort by ID by default
        pageLength: 10,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries available",
            infoFiltered: "(filtered from _MAX_ total entries)"
        }
    });

    // Handle Delete Item
    $('.delete-item').on('click', function() {
        const id = $(this).data('id');
        $('#deleteItemId').val(id);
    });

    // Form Validation
    $('#contentForm').on('submit', function(e) {
        let isValid = true;
        
        // Check all fields as they are now required
        $(this).find('input, textarea, select').each(function() {
            const $field = $(this);
            const fieldType = $field.attr('type');
            
            // Skip submit buttons and hidden fields
            if (fieldType === 'submit' || fieldType === 'hidden') {
                return true;
            }
            
            // For checkboxes, they should have a value (checked or unchecked is fine)
            if (fieldType === 'checkbox') {
                return true;
            }
            
            // For all other fields, they must have a value
            if (!$field.val()) {
                isValid = false;
                $field.addClass('is-invalid');
                
                // Show validation message
                const fieldName = $field.closest('.mb-3').find('label').text();
                $field.next('.invalid-feedback').text(`${fieldName} is required.`);
            } else {
                $field.removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        return true;
    });

    // Clear validation on input
    $('#contentForm').find('input, textarea, select').on('input change', function() {
        $(this).removeClass('is-invalid');
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Handle textarea auto-resize
    $('textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}); 
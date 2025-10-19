$(document).ready(function() {
    // Initialize DataTable
    const table = $('#faqsTable').DataTable({
        order: [[0, 'asc']], // Sort by the index column by default
        columnDefs: [
            { orderable: false, targets: [3, 4] }, // Disable sorting for status and actions columns
            { 
                targets: 2,
                render: function(data, type, row) {
                    if (type === 'display') {
                        // Truncate answer text if it's too long
                        return data.length > 100 ? data.substr(0, 97) + '...' : data;
                    }
                    return data;
                }
            }
        ],
        pageLength: 10,
        language: {
            search: "Search FAQs:",
            lengthMenu: "Show _MENU_ FAQs per page",
            info: "Showing _START_ to _END_ of _TOTAL_ FAQs",
            infoEmpty: "No FAQs available",
            infoFiltered: "(filtered from _MAX_ total FAQs)"
        }
    });

    // Handle Edit FAQ
    $('.edit-faq').on('click', function() {
        const id = $(this).data('id');
        const question = $(this).data('question');
        const answer = $(this).data('answer');
        const isActive = $(this).data('active');

        $('#faqId').val(id);
        $('#question').val(question);
        $('#answer').val(answer);
        $('#is_active').prop('checked', isActive === 1);
        
        $('#formAction').val('edit');
        $('#formTitle').text('Edit FAQ');
        $('#submitButtonText').text('Update FAQ');
        $('#isActiveContainer').show();
        $('#cancelEdit').show();
    });

    // Handle Cancel Edit
    $('#cancelEdit').on('click', function() {
        resetForm();
    });

    // Handle Delete FAQ
    $('.delete-faq').on('click', function() {
        const id = $(this).data('id');
        const question = $(this).data('question');
        
        $('#deleteFaqId').val(id);
        $('#deleteFaqQuestion').text(question);
        $('#deleteModal').modal('show');
    });

    // Handle Toggle Status
    $('.toggle-status').on('change', function() {
        const id = $(this).data('id');
        const isActive = $(this).prop('checked') ? 1 : 0;
        
        $.post('manage_faqs.php', {
            action: 'toggle',
            id: id,
            is_active: isActive
        })
        .done(function(response) {
            try {
                const result = JSON.parse(response);
                if (!result.success) {
                    // Revert the toggle if update failed
                    $(this).prop('checked', !isActive);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                // Revert the toggle on error
                $(this).prop('checked', !isActive);
            }
        })
        .fail(function() {
            // Revert the toggle on failure
            $(this).prop('checked', !isActive);
            alert('Failed to update FAQ status. Please try again.');
        });
    });

    // Form Validation
    $('#faqForm').on('submit', function(e) {
        const question = $('#question').val().trim();
        const answer = $('#answer').val().trim();
        
        if (!question || !answer) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        return true;
    });

    // Reset form to add mode
    function resetForm() {
        $('#faqForm')[0].reset();
        $('#faqId').val('');
        $('#formAction').val('add');
        $('#formTitle').text('Add New FAQ');
        $('#submitButtonText').text('Add FAQ');
        $('#isActiveContainer').hide();
        $('#cancelEdit').hide();
    }
}); 
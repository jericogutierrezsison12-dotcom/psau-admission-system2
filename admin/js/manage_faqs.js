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

    // Initialize Unanswered table (if present)
    if ($('#unansweredTable').length) {
        $('#unansweredTable').DataTable({
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [3] }
            ],
            pageLength: 10,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ per page",
                info: "Showing _START_ to _END_ of _TOTAL_",
                infoEmpty: "No items available",
                infoFiltered: "(filtered from _MAX_)"
            }
        });
    }

    // Handle Edit FAQ
    $(document).on('click', '.edit-faq', function() {
        console.log('Edit FAQ clicked'); // Debug log
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
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#faqForm').offset().top - 100
        }, 500);
    });

    // Handle Cancel Edit
    $('#cancelEdit').on('click', function() {
        resetForm();
    });

    // Handle Delete FAQ
    $(document).on('click', '.delete-faq', function() {
        console.log('Delete FAQ clicked'); // Debug log
        const id = $(this).data('id');
        const question = $(this).data('question');
        
        if (confirm('Are you sure you want to delete this FAQ?\n\n' + question)) {
            $.post('manage_faqs.php', {
                action: 'delete',
                id: id
            })
            .done(function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        location.reload(); // Reload page to show updated list
                    } else {
                        alert('Error: ' + (result.message || 'Failed to delete FAQ'));
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    alert('Error deleting FAQ. Please try again.');
                }
            })
            .fail(function() {
                alert('Failed to delete FAQ. Please try again.');
            });
        }
    });

    // Handle Toggle Status
    $(document).on('change', '.toggle-status', function() {
        console.log('Toggle status changed'); // Debug log
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

    // Resolve unanswered -> add as FAQ
    $(document).on('click', '.resolve-unanswered', function() {
        const uaId = $(this).data('id');
        const q = $(this).data('question');
        const answer = prompt('Provide an answer for this question:\n\n' + q);
        if (answer && answer.trim()) {
            $('<form method="POST">')
                .append('<input type="hidden" name="action" value="resolve_unanswered">')
                .append('<input type="hidden" name="ua_id" value="' + uaId + '">')
                .append('<input type="hidden" name="question" value="' + $('<div>').text(q).html() + '">')
                .append('<input type="hidden" name="answer" value="' + $('<div>').text(answer).html() + '">')
                .appendTo('body')
                .submit();
        }
    });

    // Delete unanswered
    $(document).on('click', '.delete-unanswered', function() {
        const uaId = $(this).data('id');
        const q = $(this).data('question');
        if (confirm('Delete this unanswered question?\n\n' + q)) {
            $('<form method="POST">')
                .append('<input type="hidden" name="action" value="delete_unanswered">')
                .append('<input type="hidden" name="ua_id" value="' + uaId + '">')
                .appendTo('body')
                .submit();
        }
    });

    // Form Validation and Submission
    $('#faqForm').on('submit', function(e) {
        e.preventDefault();
        
        const question = $('#question').val().trim();
        const answer = $('#answer').val().trim();
        const action = $('#formAction').val();
        const id = $('#faqId').val();
        const isActive = $('#is_active').is(':checked') ? 1 : 0;
        
        if (!question || !answer) {
            alert('Please fill in all required fields.');
            return false;
        }
        
        const formData = {
            action: action,
            question: question,
            answer: answer,
            is_active: isActive
        };
        
        if (action === 'edit' && id) {
            formData.id = id;
        }
        
        $.post('manage_faqs.php', formData)
        .done(function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    location.reload(); // Reload page to show updated list
                } else {
                    alert('Error: ' + (result.message || 'Failed to save FAQ'));
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                alert('Error saving FAQ. Please try again.');
            }
        })
        .fail(function() {
            alert('Failed to save FAQ. Please try again.');
        });
        
        return false;
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
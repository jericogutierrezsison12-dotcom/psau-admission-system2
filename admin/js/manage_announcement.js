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

    // Edit announcement
    $('.edit-announcement').click(function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        const content = $(this).data('content');

        $('#edit_id').val(id);
        $('#edit_title').val(title);
        $('#edit_content').val(content);
        $('#editAnnouncementModal').modal('show');
    });

    // Save edited announcement
    $('#saveEdit').click(function() {
        const form = $('#editAnnouncementForm')[0];
        if (form.checkValidity()) {
            form.submit();
        } else {
            form.classList.add('was-validated');
        }
    });

    // Delete announcement
    $('.delete-announcement').click(function() {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to delete this announcement?')) {
            const form = $('<form method="post">')
                .append($('<input type="hidden" name="action" value="delete">'))
                .append($('<input type="hidden" name="id">').val(id));
            $('body').append(form);
            form.submit();
        }
    });

    // Clear form on modal close
    $('#editAnnouncementModal').on('hidden.bs.modal', function() {
        $('#editAnnouncementForm')[0].reset();
        $('#editAnnouncementForm').removeClass('was-validated');
    });
});

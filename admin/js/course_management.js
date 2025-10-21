$(document).ready(function() {
    // Initialize DataTables
    $('#courses-table').DataTable({
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50],
        "order": [[0, 'asc']], // Sort by course code by default
        "language": {
            "search": "Search courses:",
            "emptyTable": "No courses available"
        }
    });

    // Form validation
    $('#courseForm').on('submit', function(e) {
        const courseCode = $('#course_code').val().trim();
        const courseName = $('#course_name').val().trim();
        const slots = parseInt($('#slots').val());
        
        if (!courseCode || !courseName) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        if (slots < 1) {
            e.preventDefault();
            alert('Available slots must be at least 1.');
            return false;
        }
        
        return true;
    });

    // Delete confirmation
    $('.btn-danger[data-bs-toggle="modal"]').on('click', function(e) {
        const courseCode = $(this).closest('tr').find('td:first').text();
        const courseName = $(this).closest('tr').find('td:eq(1)').text();
        const modal = $($(this).data('bs-target'));
        
        modal.find('.modal-body strong').text(courseCode + ' - ' + courseName);
    });
}); 
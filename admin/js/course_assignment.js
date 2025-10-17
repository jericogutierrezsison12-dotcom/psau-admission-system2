$(document).ready(function() {
    // Initialize DataTables
    $('#applicantsTable').DataTable({
        order: [[2, 'desc']], // Sort by stanine score by default
        pageLength: 10,
        language: {
            emptyTable: "No eligible applicants found"
        }
    });

    $('#coursesTable').DataTable({
        order: [[0, 'asc']], // Sort by course code
        pageLength: 5,
        language: {
            emptyTable: "No courses with available slots"
        }
    });

    // Handle modal data population
    $('#assignModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const modal = $(this);
        
        // Set hidden fields
        modal.find('#application_id').val(button.data('application-id'));
        modal.find('#user_id').val(button.data('user-id'));
        
        // Set student name
        modal.find('#student_name').text(button.data('name'));
        
        // Clear previous form data
        modal.find('#course_id').val('');
        modal.find('#notes').val('');
        
        // Display course preferences
        const preferences = button.data('preferences');
        const preferencesList = modal.find('#preferences_list');
        preferencesList.empty();
        
        if (preferences && preferences.length > 0) {
            preferences.forEach(function(pref) {
                const badge = pref.slots > 0 ? 
                    `<span class="badge bg-success float-end">${pref.slots} slots</span>` : 
                    '<span class="badge bg-danger float-end">Full</span>';
                
                preferencesList.append(`
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Choice ${pref.preference_order}: ${pref.course_code}</h6>
                            ${badge}
                        </div>
                        <small class="text-muted">${pref.course_name}</small>
                    </div>
                `);
            });
        } else {
            preferencesList.append('<div class="list-group-item text-muted">No course preferences found</div>');
        }
    });

    // Form validation
    $('#assignForm').on('submit', function(e) {
        const form = $(this);
        const courseSelect = form.find('#course_id');
        
        if (!courseSelect.val()) {
            e.preventDefault();
            courseSelect.addClass('is-invalid');
            return false;
        }
        
        courseSelect.removeClass('is-invalid');
        return true;
    });

    // Course select change handler
    $('#course_id').on('change', function() {
        $(this).removeClass('is-invalid');
    });

    // Handle refresh button
    $('#refreshSchedules').on('click', function() {
        location.reload();
    });
}); 
$(document).ready(function() {
    // Initialize DataTable for enrolled students
    if ($('.enrolled-students-section table').length) {
        $('.enrolled-students-section table').DataTable({
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            order: [[1, 'asc']], // Sort by name by default
            language: {
                search: "Search students:",
                emptyTable: "No students found"
            },
            columnDefs: [
                { orderable: false, targets: 2 } // Disable sorting for contact column
            ]
        });
    }

    // Add tooltips to icons
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Handle copy to clipboard for contact info
    $('.contact-info div').click(function() {
        const text = $(this).text().trim();
        navigator.clipboard.writeText(text).then(() => {
            const originalText = $(this).html();
            const icon = $(this).find('i');
            const originalClass = icon.attr('class');
            
            icon.removeClass().addClass('bi bi-check-circle text-success me-1');
            
            setTimeout(() => {
                icon.removeClass().addClass(originalClass);
            }, 1500);
        });
    }).css('cursor', 'pointer');
}); 
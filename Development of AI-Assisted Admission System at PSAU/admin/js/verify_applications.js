// Initialize DataTables
$(document).ready(function() {
    $('#applicationTable').DataTable({
        "order": [[3, "desc"]], // Sort by submission date column by default
        "language": {
            "emptyTable": "No applications pending verification"
        }
    });
    
    // Auto-close alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);
    
    // Debug click events on review buttons
    $('a.btn-primary').on('click', function(e) {
        console.log('Review button clicked');
        // Check if the href is working
        console.log('Link: ' + $(this).attr('href'));
    });
});
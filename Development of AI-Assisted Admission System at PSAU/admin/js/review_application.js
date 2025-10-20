// Document ready handler
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle document preview
    $('.view-document').on('click', function(e) {
        e.preventDefault();
        const documentUrl = $(this).data('document-url');
        const documentId = $(this).data('document-id');
        
        // Clear previous preview
        $('#documentPreview').empty();
        
        // Show loading indicator
        $('#documentPreview').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
        
        // Show modal
        $('#documentPreviewModal').modal('show');
        
        // Determine file type and display accordingly
        if (documentUrl.match(/\.(jpg|jpeg|png|gif)$/i)) {
            // Image file
            const img = new Image();
            img.onload = function() {
                $('#documentPreview').html(img);
            };
            img.onerror = function() {
                $('#documentPreview').html('<div class="alert alert-danger">Error loading image</div>');
            };
            img.src = documentUrl;
        } else if (documentUrl.match(/\.(pdf)$/i)) {
            // PDF file
            const iframe = $('<iframe>')
                .attr('src', documentUrl)
                .attr('width', '100%')
                .attr('height', '600px')
                .attr('frameborder', '0');
            $('#documentPreview').html(iframe);
        } else {
            // Other file types
            $('#documentPreview').html('<div class="alert alert-info">Preview not available for this file type</div>');
        }
    });

    // Form submission handler
    $('#reviewForm').on('submit', function(e) {
        const action = $(document.activeElement).val();
        
        if (action === 'reject' && !$('#rejection_reason').val().trim()) {
            e.preventDefault();
            alert('Please provide a reason for rejection');
            $('#rejection_reason').focus();
            return false;
        }
        
        // Confirm action
        if (!confirm('Are you sure you want to ' + action + ' this application?')) {
            e.preventDefault();
            return false;
        }
    });

    // Status color helper function
    function getStatusColor(status) {
        status = status.toLowerCase();
        switch(status) {
            case 'pending':
                return 'warning';
            case 'verified':
                return 'success';
            case 'rejected':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    // Update status badges
    function updateStatusBadges() {
        $('.status-badge').each(function() {
            const status = $(this).text().trim();
            $(this).removeClass('bg-secondary bg-success bg-danger bg-warning')
                  .addClass('bg-' + getStatusColor(status));
        });
    }

    // Call initial status badge update
    updateStatusBadges();

    // Handle rejection reason textarea
    $('#rejection_reason').on('input', function() {
        const maxLength = 500;
        const currentLength = $(this).val().length;
        
        if (currentLength > maxLength) {
            $(this).val($(this).val().substring(0, maxLength));
        }
    });

    // Document preview modal cleanup
    $('#documentPreviewModal').on('hidden.bs.modal', function() {
        $('#documentPreview').empty();
    });

    // Error handling for failed document loads
    window.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG' || e.target.tagName === 'IFRAME') {
            const container = e.target.closest('#documentPreview');
            if (container) {
                container.innerHTML = '<div class="alert alert-danger">Error loading document</div>';
            }
        }
    }, true);
}); 
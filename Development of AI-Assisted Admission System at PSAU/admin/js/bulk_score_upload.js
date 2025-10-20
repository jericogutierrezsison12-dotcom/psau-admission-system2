document.addEventListener('DOMContentLoaded', function() {
    const scoreUploadForm = document.getElementById('score-upload-form');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');
    const recentUploadsContainer = document.getElementById('recent-uploads');

    console.log('DOM Content Loaded');
    console.log('Recent uploads container:', recentUploadsContainer);

    // Load recent uploads on page load
    loadRecentUploads();

    // Handle form submission
    scoreUploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('upload_scores', '1');

        fetch('bulk_score_upload.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showSuccessMessage(data.message);
                scoreUploadForm.reset();
                loadRecentUploads(); // Refresh the recent uploads first
                // Redirect to dashboard after showing success message
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000); // Wait 2 seconds before redirecting
            } else {
                showErrorMessage(data.message);
            }
        })
        .catch(error => {
            showErrorMessage('An error occurred while uploading the file. Please try again.');
            console.error('Error:', error);
        });
    });

    // Function to load recent uploads
    function loadRecentUploads() {
        // Show loading spinner
        recentUploadsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
        
        // Fetch recent uploads
        fetch('get_recent_uploads.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                if (data.length === 0) {
                    recentUploadsContainer.innerHTML = '<p class="text-center text-muted">No recent uploads found.</p>';
                    return;
                }
                
                // Create table
                const table = `
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Control #</th>
                                <th>Score</th>
                                <th>Date</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(item => `
                                <tr>
                                    <td>${item.control_number}</td>
                                    <td>${item.stanine_score}</td>
                                    <td>${new Date(item.upload_date).toLocaleDateString()}</td>
                                    <td><span class="badge bg-${item.upload_method === 'bulk' ? 'primary' : 'info'}">${item.upload_method}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                
                recentUploadsContainer.innerHTML = table;
            })
            .catch(error => {
                recentUploadsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        Error loading recent uploads: ${error.message}
                    </div>
                `;
                console.error('Error:', error);
            });
    }

    // Helper function to show success message
    function showSuccessMessage(message) {
        successMessage.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        errorMessage.innerHTML = '';
    }

    // Helper function to show error message
    function showErrorMessage(message) {
        errorMessage.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        successMessage.innerHTML = '';
        setTimeout(() => {
            errorMessage.innerHTML = '';
        }, 5000);
    }
});

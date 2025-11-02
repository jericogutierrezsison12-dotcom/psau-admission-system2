/**
 * PSAU Admission System - Application Progress JavaScript
 * Handles template loading and UI interactions
 */

$(document).ready(function() {
    
    // Populate application progress content
    renderApplicationContent();
    
    // Initialize progress bar animation
    setTimeout(function() {
        const progressBar = document.querySelector('.progress-bar-fill');
        if (progressBar) {
            const progress = progressBar.getAttribute('data-progress');
            progressBar.style.width = progress + '%';
        }
    }, 300);
    
    // Set up AJAX functionality
    setupAjaxNavigation();
    
    // Set up auto-refresh
    setupAutoRefresh();
});

/**
 * Renders the application progress content based on the application data
 */
function renderApplicationContent() {
    const contentDiv = $("#application-progress-content");
    
    if (!applicationData.hasApplication) {
        // No application yet
        renderNoApplicationContent(contentDiv);
    } else {
        // Application exists
        renderApplicationOverview(contentDiv);
        renderProgressTracker(contentDiv);
        renderCurrentStageDetails(contentDiv);
        
        // If there's status history, render the timeline
        if (applicationData.statusHistory && applicationData.statusHistory.length > 0) {
            renderStatusTimeline(contentDiv);
        }
    }
}

/**
 * Renders content when no application has been submitted
 */
function renderNoApplicationContent(container) {
    const template = document.querySelector('#no-application-template');
    container.html(template.innerHTML);
}

/**
 * Renders the application overview section
 */
function renderApplicationOverview(container) {
    const app = applicationData.application;
    const user = applicationData.user;
    const status = applicationData.status;
    const statusClass = applicationData.statusClass;
    
    const template = document.querySelector('#application-overview-template');
    const html = template.innerHTML
        .replace('${statusClass}', statusClass)
        .replace('${status}', status)
        .replace('${app.id}', app.id)
        .replace('${formatDate(app.created_at)}', formatDate(app.created_at))
        .replace('${formatDate(app.updated_at)}', formatDate(app.updated_at))
        .replace('${user.control_number}', user.control_number)
        .replace('${user.first_name}', user.first_name)
        .replace('${user.last_name}', user.last_name)
        .replace('${user.email}', user.email);
    
    container.append(html);
    
    // Add rejection alert if applicable
    if (status === 'Rejected' && app.rejection_reason) {
        const rejectionTemplate = document.querySelector('#rejection-alert-template');
        const rejectionHtml = rejectionTemplate.innerHTML
            .replace('${app.rejection_reason}', app.rejection_reason);
        $('#rejection-alert').html(rejectionHtml);
    }
}

/**
 * Renders the visual progress tracker
 */
function renderProgressTracker(container) {
    const status = applicationData.status;
    const statusClass = applicationData.statusClass;
    
    // Define all possible application statuses in order
    const applicationStages = [
        'Submitted',
        'Verified',
        'Exam Scheduled',
        'Score Posted',
        'Course Assigned',
        'Enrollment Scheduled',
        'Enrolled'
    ];
    
    // Calculate progress percentage
    const totalStages = applicationStages.length;
    const currentStageIndex = applicationStages.indexOf(status);
    let progressPercent = currentStageIndex !== -1 ? ((currentStageIndex + 1) / totalStages) * 100 : 0;
    
    if (status === 'Rejected') {
        progressPercent = 0;
    }
    
    let stepsHtml = '';
    let passedCurrentStage = false;
    
    // Generate the progress steps
    applicationStages.forEach(stageName => {
        let stageClass = '';
        
        if (status === 'Rejected' && stageName === 'Submitted') {
            stageClass = 'step-rejected';
        } else if (status === stageName) {
            stageClass = 'step-active';
            passedCurrentStage = true;
        } else if (!passedCurrentStage) {
            stageClass = 'step-completed';
        }
        
        // Get the template for this stage
        const stageTemplate = document.querySelector(`#stage-${stageName.toLowerCase().replace(' ', '-')}-template`);
        const stageHtml = stageTemplate.innerHTML;
        
        // Wrap the template content with a div that has the appropriate class
        stepsHtml += stageHtml.replace('class="progress-step"', `class="progress-step ${stageClass}"`);
    });
    
    const template = document.querySelector('#progress-tracker-template');
    const html = template.innerHTML
        .replace('${stepsHtml}', stepsHtml)
        .replace('${progressPercent}', progressPercent)
        .replace('${statusClass}', statusClass)
        .replace('${status === \'Rejected\' ? \'Application Rejected\' : \'Current Stage: \' + status}', 
                 status === 'Rejected' ? 'Application Rejected' : 'Current Stage: ' + status);
    
    container.append(html);
}

/**
 * Renders details for the current application stage
 */
function renderCurrentStageDetails(container) {
    const status = applicationData.status;
    const app = applicationData.application;
    const examSchedule = applicationData.examSchedule;
    const examScore = applicationData.examScore;
    const courseAssignment = applicationData.courseAssignment;
    const enrollmentSchedule = applicationData.enrollmentSchedule;
    
    let template;
    let html = '';
    
    if (status === 'Rejected') {
        template = document.querySelector('#rejected-stage-template');
        html = template.innerHTML
            .replace('${formatDate}', formatDate(app.updated_at, false))
            .replace('${rejectionReason}', app.rejection_reason);
    } else if (status === 'Submitted') {
        template = document.querySelector('#submitted-stage-template');
        html = template.innerHTML
            .replace('${submittedDate}', formatDate(app.created_at));
    } else if (status === 'Verified') {
        template = document.querySelector('#verified-stage-template');
        html = template.innerHTML;
    } else if (status === 'Exam Scheduled' && examSchedule) {
        template = document.querySelector('#exam-scheduled-stage-template');
        html = template.innerHTML
            .replace('${examDate}', formatDate(examSchedule.exam_date, false))
            .replace('${examTime}', formatTime(examSchedule.exam_time))
            .replace('${examVenue}', examSchedule.venue_name || examSchedule.venue);
        
        // Add instructions if available
        if (examSchedule.instructions) {
            const instructionsTemplate = document.querySelector('#exam-instructions-template');
            const instructionsHtml = instructionsTemplate.innerHTML
                .replace('${instructions}', examSchedule.instructions.replace(/\n/g, '<br>'));
            $('#exam-instructions').html(instructionsHtml);
        }
        
        // Add requirements if available
        if (examSchedule.requirements) {
            const requirementsTemplate = document.querySelector('#exam-requirements-template');
            const requirementsHtml = requirementsTemplate.innerHTML
                .replace('${requirements}', examSchedule.requirements.replace(/\n/g, '<br>'));
            $('#exam-requirements').html(requirementsHtml);
        }
    } else if (status === 'Score Posted' && examScore) {
        template = document.querySelector('#score-posted-stage-template');
        html = template.innerHTML
            .replace('${stanineScore}', examScore.stanine_score)
            .replace('${scorePostedDate}', formatDate(examScore.created_at, false));
    } else if (status === 'Course Assigned' && courseAssignment) {
        template = document.querySelector('#course-assigned-stage-template');
        html = template.innerHTML
            .replace('${courseName}', courseAssignment.course_name)
            .replace('${courseCode}', courseAssignment.course_code)
            .replace('${assignedDate}', formatDate(courseAssignment.created_at, false));
    } else if (status === 'Enrollment Scheduled' && enrollmentSchedule) {
        template = document.querySelector('#enrollment-scheduled-stage-template');
        html = template.innerHTML
            .replace('${enrollmentDate}', formatDate(enrollmentSchedule.enrollment_date, false))
            .replace('${enrollmentTime}', formatTime(enrollmentSchedule.start_time))
            .replace('${venueName}', enrollmentSchedule.venue_name);
        
        // Add instructions if available
        if (enrollmentSchedule.instructions) {
            const instructionsTemplate = document.querySelector('#enrollment-instructions-template');
            const instructionsHtml = instructionsTemplate.innerHTML
                .replace('${instructions}', enrollmentSchedule.instructions.replace(/\n/g, '<br>'));
            $('#enrollment-instructions').html(instructionsHtml);
        }
    } else if (status === 'Enrolled') {
        template = document.querySelector('#enrolled-stage-template');
        html = template.innerHTML;
    }
    
    const stageDetailsTemplate = document.querySelector('#stage-details-card-template');
    const finalHtml = stageDetailsTemplate.innerHTML.replace('${detailsHtml}', html);
    container.append(finalHtml);
}

/**
 * Renders the application status timeline
 */
function renderStatusTimeline(container) {
    const statusHistory = applicationData.statusHistory;
    
    let timelineItems = statusHistory.map(history => {
        const template = document.querySelector('#timeline-item-template');
        return template.innerHTML
            .replace('${date}', formatDate(history.created_at, true, true))
            .replace('${status}', history.status)
            .replace('${description}', history.description);
    }).join('');
    
    const template = document.querySelector('#timeline-template');
    const html = template.innerHTML.replace('${timelineItems}', timelineItems);
    
    container.append(html);
}

/**
 * Helper function to format dates
 * @param {string} dateString - The date string to format
 * @param {boolean} includeTime - Whether to include time in the formatted date
 * @param {boolean} shortFormat - Whether to use short month format
 * @returns {string} Formatted date string
 */
function formatDate(dateString, includeTime = true, shortFormat = false) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: shortFormat ? 'short' : 'long',
        day: 'numeric'
    };
    
    if (includeTime) {
        options.hour = 'numeric';
        options.minute = 'numeric';
        options.hour12 = true;
    }
    
    return date.toLocaleString('en-US', options);
}

/**
 * Helper function to format time
 * @param {string} timeString - The time string to format
 * @returns {string} Formatted time string
 */
function formatTime(timeString) {
    if (!timeString) return '';
    
    const date = new Date(`2000-01-01T${timeString}`);
    return date.toLocaleString('en-US', {
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    });
}

/**
 * Set up AJAX navigation for application progress
 */
function setupAjaxNavigation() {
    // Handle navigation links with AJAX
    const navLinks = document.querySelectorAll('a[href*="dashboard.php"], a[href*="application_form.php"], a[href*="course_selection.php"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            loadPageViaAjax(href);
        });
    });
    
    // Handle back to dashboard link
    const backLink = document.querySelector('a[href="dashboard.php"]');
    if (backLink) {
        backLink.addEventListener('click', function(e) {
            e.preventDefault();
            loadDashboardViaAjax();
        });
    }
}

/**
 * Load page via AJAX
 */
function loadPageViaAjax(url) {
    showLoadingOverlay('Loading page...');
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        hideLoadingOverlay();
        // Replace main content
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.innerHTML = html;
            // Re-initialize JavaScript for the new content
            if (url.includes('dashboard.php')) {
                // Load dashboard JavaScript
                loadScript('js/dashboard.js');
            } else if (url.includes('application_form.php')) {
                // Load form JavaScript
                loadScript('js/application_form.js');
            } else if (url.includes('course_selection.php')) {
                // Load course selection JavaScript
                loadScript('js/course_selection.js');
            }
        }
    })
    .catch(error => {
        hideLoadingOverlay();
        console.error('Error loading page:', error);
        showError('Failed to load page. Please try again.');
        // Fallback to normal navigation
        window.location.href = url;
    });
}

/**
 * Load dashboard via AJAX
 */
function loadDashboardViaAjax() {
    loadPageViaAjax('dashboard.php');
}

/**
 * Set up auto-refresh for application progress
 */
function setupAutoRefresh() {
    // Refresh application progress data every 60 seconds
    setInterval(() => {
        refreshApplicationProgress();
    }, 60000);
}

/**
 * Refresh application progress data via AJAX
 */
function refreshApplicationProgress() {
    fetch('application_progress.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        // Parse the response and update only the progress content
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Update progress content
        const newProgressContent = doc.querySelector('#application-progress-content');
        const currentProgressContent = document.querySelector('#application-progress-content');
        if (newProgressContent && currentProgressContent) {
            currentProgressContent.innerHTML = newProgressContent.innerHTML;
            // Re-render the content
            renderApplicationContent();
        }
        
        console.log('Application progress data refreshed');
    })
    .catch(error => {
        console.error('Error refreshing application progress:', error);
    });
}

/**
 * Show loading overlay
 */
function showLoadingOverlay(message = 'Loading...') {
    // Remove existing overlay if any
    hideLoadingOverlay();
    
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-content">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>${message}</p>
        </div>
    `;
    
    // Add styles
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;
    
    const loadingContent = overlay.querySelector('.loading-content');
    loadingContent.style.cssText = `
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    `;
    
    document.body.appendChild(overlay);
}

/**
 * Hide loading overlay
 */
function hideLoadingOverlay() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Show error message
 */
function showError(message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-danger');
    existingAlerts.forEach(alert => alert.remove());
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        <i class="bi bi-exclamation-triangle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insert at the top of main content
    const mainContent = document.querySelector('.main-content .container-fluid');
    if (mainContent) {
        mainContent.insertBefore(alert, mainContent.firstChild);
    }
}

/**
 * Load script dynamically
 */
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
} 
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
            // Special-case: when the final stage 'Enrolled' is current, show it as green (completed)
            if (stageName === 'Enrolled') {
                stageClass = 'step-completed';
            } else {
                stageClass = 'step-active';
            }
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
            .replace('${enrollmentTime}', formatTime(enrollmentSchedule.enrollment_time))
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
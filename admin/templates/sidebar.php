<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
$role = $_SESSION['admin_role'] ?? 'admin';
// Mobile navbar
include_once __DIR__ . '/mobile_navbar.php';
?>
<!-- Sidebar (desktop) -->
<div class="sidebar d-none d-lg-flex flex-column flex-shrink-0 text-white">
    <div class="d-flex align-items-center mb-2 mb-md-0 me-md-auto text-white text-decoration-none p-3">
        <img src="../logo/PSAU_logo.png" alt="PSAU" class="me-2" style="height:28px;width:auto;object-fit:contain;" />
        <div class="fs-6 fw-semibold sidebar-brand-text">PSAU Admin</div>
    </div>
	<hr class="mx-3">
	<ul class="nav nav-pills flex-column mb-auto">
		<?php if ($role === 'admin'): ?>
			<!-- Admin users: See all menu items including "View Logs" -->
			<li class="nav-item">
				<a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
					<i class="bi bi-speedometer2"></i>
					<span>Dashboard</span>
				</a>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" href="#" id="contentManagementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
					<i class="bi bi-gear"></i>
					<span>Content Management</span>
				</a>
				<ul class="dropdown-menu" aria-labelledby="contentManagementDropdown">
					<li><a class="dropdown-item" href="course_management.php"><i class="bi bi-book me-2"></i>Manage Course</a></li>
					<li><a class="dropdown-item" href="manage_content.php"><i class="bi bi-file-text me-2"></i>Manage Content</a></li>
					<li><a class="dropdown-item" href="manage_announcement.php"><i class="bi bi-megaphone me-2"></i>Manage Announcements</a></li>
					<li><a class="dropdown-item" href="manage_faqs.php"><i class="bi bi-question-circle me-2"></i>Manage FAQs</a></li>
				</ul>
			</li>
			<li>
				<a href="verify_applications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'verify_applications.php' ? 'active' : ''; ?>">
					<i class="bi bi-check-circle"></i>
					<span>Verify Applications</span>
				</a>
			</li>
			<li>
				<a href="schedule_exam.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'schedule_exam.php' ? 'active' : ''; ?>">
					<i class="bi bi-calendar-event"></i>
					<span>Schedule Exams</span>
				</a>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" href="#" id="scoreUploadDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
					<i class="bi bi-file-earmark-text"></i>
					<span>Score Uploads</span>
				</a>
				<ul class="dropdown-menu" aria-labelledby="scoreUploadDropdown">
					<li><a class="dropdown-item" href="manual_score_entry.php">Manual Score Entry</a></li>
					<li><a class="dropdown-item" href="bulk_score_upload.php">Bulk Score Upload</a></li>
				</ul>
			</li>
			<li>
				<a href="course_assignment.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'course_assignment.php' ? 'active' : ''; ?>">
					<i class="bi bi-mortarboard"></i>
					<span>Assign Courses</span>
				</a>
			</li>
			<li>
				<a href="enrollment_schedule.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'enrollment_schedule.php' ? 'active' : ''; ?>">
					<i class="bi bi-calendar-check"></i>
					<span>Set Enrollment</span>
				</a>
			</li>
			<li>
				<a href="view_all_applicants.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_all_applicants.php' ? 'active' : ''; ?>">
					<i class="bi bi-list-check"></i>
					<span>Applicants by Status</span>
				</a>
			</li>
			<li>
				<a href="courses_overview.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'courses_overview.php' ? 'active' : ''; ?>">
					<i class="bi bi-book"></i>
					<span>Courses Overview</span>
				</a>
			</li>
			<li>
				<a href="view_logs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_logs.php' ? 'active' : ''; ?>">
					<i class="bi bi-journal-text"></i>
					<span>View Logs</span>
				</a>
			</li>
			<li>
				<a href="clear_attempts.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clear_attempts.php' ? 'active' : ''; ?>">
					<i class="bi bi-eraser"></i>
					<span>Clear Application Attempts</span>
				</a>
			</li>
			<li>
				<a href="enrollment_completion.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'enrollment_completion.php' ? 'active' : ''; ?>">
					<i class="bi bi-check2-square"></i>
					<span>Enrollment Completion</span>
				</a>
			</li>
        <li>
            <a href="view_all_users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_all_users.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>View All Users</span>
            </a>
        </li>
        <li>
            <a href="view_enrolled_students.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_enrolled_students.php' ? 'active' : ''; ?>">
                <i class="bi bi-mortarboard"></i>
                <span>Enrolled Students</span>
            </a>
        </li>
			<li>
				<a href="manage_admins.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_admins.php' ? 'active' : ''; ?>">
					<i class="bi bi-people"></i>
					<span>Manage Admins</span>
				</a>
			</li>
		<?php elseif ($role === 'registrar'): ?>
			<!-- Registrar users: See Verify Applications, Courses Overview, Enrolled Students (no Dashboard, no View Logs) -->
			<li>
				<a href="verify_applications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'verify_applications.php' ? 'active' : ''; ?>">
					<i class="bi bi-check-circle"></i>
					<span>Verify Applications</span>
				</a>
			</li>
			<li>
				<a href="courses_overview.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'courses_overview.php' ? 'active' : ''; ?>">
					<i class="bi bi-book"></i>
					<span>Courses Overview</span>
				</a>
			</li>
			<li>
				<a href="view_enrolled_students.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_enrolled_students.php' ? 'active' : ''; ?>">
					<i class="bi bi-mortarboard"></i>
					<span>Enrolled Students</span>
				</a>
			</li>
		<?php else: ?>
			<!-- Department users: See Courses Overview only (no Dashboard, no View Logs) -->
			<li>
				<a href="courses_overview.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'courses_overview.php' ? 'active' : ''; ?>">
					<i class="bi bi-book"></i>
					<span>Courses Overview</span>
				</a>
			</li>
		<?php endif; ?>
	</ul>
	<hr class="mx-3">
	<div class="logout-link">
		<a href="logout.php" class="btn btn-outline-light w-100 btn-sm">
			<i class="bi bi-box-arrow-right me-1"></i>
			<span>Logout</span>
		</a>
	</div>
</div>

<div id="sidebarBackdrop" class="d-lg-none" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1029;"></div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var body = document.body;
    var sidebar = document.querySelector('.sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggleBtn = document.getElementById('sidebarToggle');
    var closeBtn = document.getElementById('sidebarClose');

    function openSidebar(){
        body.classList.add('sidebar-open');
        if (backdrop) backdrop.style.display = 'block';
        body.style.overflow = 'hidden';
    }
    function closeSidebar(){
        body.classList.remove('sidebar-open');
        if (backdrop) backdrop.style.display = 'none';
        body.style.overflow = '';
    }

    if (toggleBtn) toggleBtn.addEventListener('click', function(e){ e.preventDefault(); openSidebar(); });
    if (closeBtn) closeBtn.addEventListener('click', function(e){ e.preventDefault(); closeSidebar(); });
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeSidebar(); });
});
</script>



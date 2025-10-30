<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
$role = $_SESSION['admin_role'] ?? 'admin';
?>

<nav class="navbar navbar-dark d-lg-none" style="position:fixed;top:0;left:0;right:0;z-index:1031;background-color: var(--psau-dark);">
	<div class="container-fluid">
		<a class="navbar-brand d-flex align-items-center" href="<?php echo $role === 'department' ? 'courses_overview.php' : 'dashboard.php'; ?>">
			<img src="../logo/PSAU_logo.png" alt="PSAU" style="height:28px;width:auto;object-fit:contain;" class="me-2" />
			<span>PSAU Admin</span>
		</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="mobileNav">
			<ul class="navbar-nav me-auto mb-2 mb-lg-0">
				<?php if ($role === 'admin'): ?>
				<li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
				<li class="nav-item"><a class="nav-link" href="verify_applications.php">Verify Applications</a></li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="contentMgmtDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Content Management</a>
					<ul class="dropdown-menu" aria-labelledby="contentMgmtDropdown">
						<li><a class="dropdown-item" href="course_management.php">Manage Program</a></li>
						<li><a class="dropdown-item" href="manage_content.php">Manage Content</a></li>
						<li><a class="dropdown-item" href="manage_announcement.php">Manage Announcements</a></li>
						<li><a class="dropdown-item" href="manage_faqs.php">Manage FAQs</a></li>
					</ul>
				</li>
				<li class="nav-item"><a class="nav-link" href="schedule_exam.php">Schedule Exams</a></li>
				<li class="nav-item"><a class="nav-link" href="manual_score_entry.php">Manual Score Entry</a></li>
				<li class="nav-item"><a class="nav-link" href="bulk_score_upload.php">Bulk Score Upload</a></li>
				<li class="nav-item"><a class="nav-link" href="course_assignment.php">Assign Programs</a></li>
				<li class="nav-item"><a class="nav-link" href="enrollment_schedule.php">Set Enrollment</a></li>
				<li class="nav-item"><a class="nav-link" href="view_all_applicants.php">Applicants by Status</a></li>
				<li class="nav-item"><a class="nav-link" href="courses_overview.php">Programs Overview</a></li>
				<li class="nav-item"><a class="nav-link" href="view_logs.php">View Logs</a></li>
				<li class="nav-item"><a class="nav-link" href="clear_attempts.php">Clear Application Attempts</a></li>
				<li class="nav-item"><a class="nav-link" href="enrollment_completion.php">Enrollment Completion</a></li>
				<li class="nav-item"><a class="nav-link" href="view_all_users.php">View All Users</a></li>
				<li class="nav-item"><a class="nav-link" href="view_enrolled_students.php">Enrolled Students</a></li>
				<li class="nav-item"><a class="nav-link" href="manage_admins.php">Manage Admins</a></li>
				<?php elseif ($role === 'registrar'): ?>
				<li class="nav-item"><a class="nav-link" href="verify_applications.php">Verify Applications</a></li>
				<li class="nav-item"><a class="nav-link" href="courses_overview.php">Courses Overview</a></li>
				<li class="nav-item"><a class="nav-link" href="view_enrolled_students.php">Enrolled Students</a></li>
				<?php else: ?>
				<li class="nav-item"><a class="nav-link" href="courses_overview.php">Courses Overview</a></li>
				<?php endif; ?>
			</ul>
			<a href="logout.php" class="btn btn-outline-light">Logout</a>
		</div>
	</div>
</nav>

<style>
@media (max-width: 992px) {
	.main-content { padding-top: 64px; }
}
</style>


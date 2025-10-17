<?php
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';

is_admin_logged_in('login.php');

// Ensure user has access to courses overview
require_page_access('courses_overview');

$role = $_SESSION['admin_role'] ?? 'admin';

// Fetch courses with the new clear structure
$courses = [];
try {
	$stmt = $conn->query("SELECT id, course_code, course_name, description, total_capacity, slots FROM courses ORDER BY course_name ASC");
	$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	$courses = [];
}

// Compute enrollment assignment counts per course by status
$pendingByCourse = [];
$completedByCourse = [];
$cancelledByCourse = [];
try {
	$stmt = $conn->query("SELECT es.course_id, ea.status, COUNT(*) AS cnt FROM enrollment_assignments ea JOIN enrollment_schedules es ON ea.schedule_id = es.id GROUP BY es.course_id, ea.status");
	foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$courseId = (int)$row['course_id'];
		switch ($row['status']) {
			case 'pending':
				$pendingByCourse[$courseId] = (int)$row['cnt'];
				break;
			case 'completed':
				$completedByCourse[$courseId] = (int)$row['cnt'];
				break;
			case 'cancelled':
				$cancelledByCourse[$courseId] = (int)$row['cnt'];
				break;
		}
	}
} catch (PDOException $e) {}

// Calculate overall statistics
$totalCourses = count($courses);
$totalCapacity = array_sum(array_column($courses, 'total_capacity'));
$totalAvailable = array_sum(array_column($courses, 'slots'));
$totalScheduled = array_sum($pendingByCourse);
$totalUtilization = $totalCapacity > 0 ? round((($totalCapacity - $totalAvailable) / $totalCapacity) * 100, 1) : 0;

// Render inline simple table (read-only)
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Courses Overview - PSAU Admission System</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
	<link rel="stylesheet" href="css/style.css">
	<style>
		.stats-card {
			border-left: 4px solid;
			transition: transform 0.2s;
		}
		.stats-card:hover {
			transform: translateY(-2px);
		}
		.stats-card.primary { border-left-color: #007bff; }
		.stats-card.success { border-left-color: #28a745; }
		.stats-card.info { border-left-color: #17a2b8; }
		.stats-card.warning { border-left-color: #ffc107; }
		.search-container {
			position: relative;
		}
		.search-container .form-control {
			padding-left: 40px;
		}
		.search-container .bi-search {
			position: absolute;
			left: 12px;
			top: 50%;
			transform: translateY(-50%);
			color: #6c757d;
		}
		.course-table th {
			position: sticky;
			top: 0;
			background: white;
			z-index: 10;
		}
	</style>
</head>
<body>
	<?php include 'templates/sidebar.php'; ?>
	<div class="main-content">
		<div class="container-fluid py-4">
			<!-- Enhanced Header -->
			<div class="d-flex justify-content-between align-items-center mb-4">
				<div>
					<h2 class="h3 mb-1">
						<i class="bi bi-book me-2 text-primary"></i>Courses Overview
					</h2>
					<p class="text-muted mb-0">Monitor course capacity, enrollment, and utilization</p>
				</div>
				<div class="d-flex gap-2">
					<button class="btn btn-outline-success" onclick="exportCourses()">
						<i class="bi bi-download me-1"></i>Export
					</button>
				</div>
			</div>

			<!-- Summary Statistics Cards -->
			<div class="row mb-4">
				<div class="col-md-4">
					<div class="card stats-card primary h-100">
						<div class="card-body text-center">
							<i class="bi bi-collection text-primary fs-1"></i>
							<h4 class="mb-1"><?php echo number_format($totalCourses); ?></h4>
							<small class="text-muted">Total Courses</small>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="card stats-card success h-100">
						<div class="card-body text-center">
							<i class="bi bi-people text-success fs-1"></i>
							<h4 class="mb-1"><?php echo number_format($totalCapacity); ?></h4>
							<small class="text-muted">Total Capacity</small>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="card stats-card info h-100">
						<div class="card-body text-center">
							<i class="bi bi-person-plus text-info fs-1"></i>
							<h4 class="mb-1"><?php echo number_format($totalAvailable); ?></h4>
							<small class="text-muted">Available Slots</small>
						</div>
					</div>
				</div>
			</div>

			<!-- Search and Filter Section -->
			<div class="card mb-4">
				<div class="card-body">
					<div class="row g-3">
						<div class="col-md-6">
							<div class="search-container">
								<i class="bi bi-search"></i>
								<input type="text" class="form-control" id="courseSearch" placeholder="Search courses by code or name...">
							</div>
						</div>
						<div class="col-md-3">
							<select class="form-select" id="capacityFilter">
								<option value="">All Status</option>
								<option value="full">Full</option>
								<option value="about-to-full">About to Full</option>
								<option value="limited">Limited</option>
								<option value="available">Available</option>
							</select>
						</div>
						<div class="col-md-3">
							<button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
								<i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Enhanced Courses Table -->
			<div class="card">
				<div class="card-header">
					<div class="d-flex justify-content-between align-items-center">
						<h5 class="mb-0">
							<i class="bi bi-list-ul me-2"></i>Course Details
							<span class="badge bg-secondary ms-2" id="courseCount"><?php echo $totalCourses; ?> courses</span>
						</h5>
						<div class="text-muted">
							<small>Last updated: <?php echo date('M d, Y h:i A'); ?></small>
						</div>
					</div>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
						<table class="table table-hover mb-0 course-table" id="coursesTable">
							<thead class="table-light">
								<tr>
									<th>Code</th>
									<th>Name</th>
									<th>Max Capacity</th>
									<th>Current Available</th>
                                    <th>Scheduled Students (Pending)</th>
                                    <th>Enrolled Students</th>
                                    <th>Cancelled Students</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($courses as $c):
									$maxCapacity = (int)$c['total_capacity'];
									$currentAvailable = (int)$c['slots'];
                                    $pending = $pendingByCourse[(int)$c['id']] ?? 0;
                                    $completed = $completedByCourse[(int)$c['id']] ?? 0;
                                    $cancelled = $cancelledByCourse[(int)$c['id']] ?? 0;
									$utilization = $maxCapacity > 0 ? round((($maxCapacity - $currentAvailable) / $maxCapacity) * 100, 1) : 0;
									$utilizationClass = $utilization >= 80 ? 'danger' : ($utilization >= 60 ? 'warning' : 'success');
								?>
							<tr class="course-row" data-code="<?php echo strtolower($c['course_code']); ?>" data-name="<?php echo strtolower($c['course_name']); ?>">
									<td>
										<div class="fw-bold text-primary"><?php echo htmlspecialchars($c['course_code']); ?></div>
									</td>
									<td>
										<div class="fw-bold"><?php echo htmlspecialchars($c['course_name']); ?></div>
									</td>
							
									<td>
										<span class="badge bg-primary"><?php echo number_format($maxCapacity); ?></span>
									</td>
									<td>
										<span class="badge bg-<?php echo $currentAvailable > 0 ? 'info' : 'danger'; ?>">
											<?php echo number_format($currentAvailable); ?>
										</span>
									</td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo number_format($pending); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo number_format($completed); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo number_format($cancelled); ?></span>
                                    </td>
									<td>
										<?php
										$statusClass = 'bg-success';
										$statusText = 'Available';
										$statusIcon = 'bi-check-circle';
										
										if ($currentAvailable == 0) {
											$statusClass = 'bg-danger';
											$statusText = 'Full';
											$statusIcon = 'bi-x-circle';
										} elseif ($currentAvailable <= ($maxCapacity * 0.2)) {
											$statusClass = 'bg-warning';
											$statusText = 'About to Full';
											$statusIcon = 'bi-exclamation-triangle';
										} elseif ($currentAvailable <= ($maxCapacity * 0.5)) {
											$statusClass = 'bg-info';
											$statusText = 'Limited';
											$statusIcon = 'bi-info-circle';
										}
										?>
								<span class="badge <?php echo $statusClass; ?> fs-6 status-badge">
											<i class="<?php echo $statusIcon; ?> me-1"></i>
											<?php echo $statusText; ?>
										</span>
										<br>
										<small class="text-muted">
											<?php echo $currentAvailable; ?> of <?php echo $maxCapacity; ?> slots
										</small>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<!-- Information & Quick Actions removed as requested -->
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		// Search functionality
		document.getElementById('courseSearch').addEventListener('input', function() {
			filterCourses();
		});

		// Capacity filter functionality
		document.getElementById('capacityFilter').addEventListener('change', function() {
			filterCourses();
		});

		function filterCourses() {
			const searchTerm = document.getElementById('courseSearch').value.toLowerCase();
			const statusFilter = document.getElementById('capacityFilter').value;
			const rows = document.querySelectorAll('#coursesTable tbody tr');
			let visibleCount = 0;

			rows.forEach(row => {
				const code = row.getAttribute('data-code');
				const name = row.getAttribute('data-name');
				const statusEl = row.querySelector('.status-badge');
				const status = statusEl ? statusEl.textContent.trim().toLowerCase() : '';

				let showBySearch = true;
				let showByStatus = true;

				// Search filter
				if (searchTerm) {
					showBySearch = code.includes(searchTerm) || name.includes(searchTerm);
				}

				// Status filter
				if (statusFilter) {
					switch (statusFilter) {
						case 'full':
							showByStatus = status.includes('full') && !status.includes('about');
							break;
						case 'about-to-full':
							showByStatus = status.includes('about to full');
							break;
						case 'limited':
							showByStatus = status.includes('limited');
							break;
						case 'available':
							showByStatus = status.includes('available');
							break;
					}
				}

				if (showBySearch && showByStatus) {
					row.style.display = '';
					visibleCount++;
				} else {
					row.style.display = 'none';
				}
			});

			// Update course count
			document.getElementById('courseCount').textContent = visibleCount + ' courses';
		}

		function clearFilters() {
			document.getElementById('courseSearch').value = '';
			document.getElementById('capacityFilter').value = '';
			filterCourses();
		}

		// Quick action helpers removed

		function exportCourses() {
			// Create CSV content
			let csv = 'Course Code,Course Name,Max Capacity,Available Slots,Scheduled Students,Status\n';
			
			const rows = document.querySelectorAll('#coursesTable tbody tr:not([style*="display: none"])');
			rows.forEach(row => {
				const cells = row.querySelectorAll('td');
				const code = cells[0].textContent.trim();
				const name = cells[1].textContent.trim();
				const maxCapacity = cells[2].textContent.trim();
				const available = cells[3].textContent.trim();
				const scheduled = cells[4].textContent.trim();
				const status = cells[5].querySelector('.badge').textContent.trim();
				
				csv += `"${code}","${name}","${maxCapacity}","${available}","${scheduled}","${status}"\n`;
			});
			
			// Download CSV file
			const blob = new Blob([csv], { type: 'text/csv' });
			const url = window.URL.createObjectURL(blob);
			const a = document.createElement('a');
			a.href = url;
			a.download = 'courses_overview.csv';
			a.click();
			window.URL.revokeObjectURL(url);
		}

		// Auto-refresh every 5 minutes
		setInterval(() => {
			// You can implement auto-refresh here if needed
		}, 300000);
	</script>
</body>
</html>



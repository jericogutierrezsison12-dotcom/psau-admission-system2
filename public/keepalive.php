<?php
// Lightweight keep-alive endpoint
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo json_encode([
	'status' => 'ok',
	'timestamp' => date('c')
]);
?>



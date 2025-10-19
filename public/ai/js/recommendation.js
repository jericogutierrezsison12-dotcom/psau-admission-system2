// Placeholder client script for Course Recommendation page
(function initReco(){
	const btn = document.getElementById('get-recommendations');
	const results = document.getElementById('recommendation-results');
	if (!btn || !results) return;
	btn.addEventListener('click', () => {
		results.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split me-2"></i>Recommendation engine is not yet connected.</div>';
	});
})();



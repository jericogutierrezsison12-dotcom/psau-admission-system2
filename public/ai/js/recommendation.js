// Course Recommendation client: collects form data and calls PHP proxy -> Render API
(function initReco(){
	const btn = document.getElementById('get-recommendations');
	const results = document.getElementById('recommendation-results');
	const form = document.getElementById('recommendation-form');
	if (!btn || !results || !form) return;

	function renderLoading(){
		results.innerHTML = '<div class="alert alert-info fade-in"><i class="bi bi-hourglass-split me-2"></i>Fetching recommendations...</div>';
	}

	function renderError(message){
		results.innerHTML = '<div class="alert alert-danger fade-in"><i class="bi bi-exclamation-triangle me-2"></i>' + message + '</div>';
	}

	function renderRecommendations(list){
		if (!Array.isArray(list) || list.length === 0) {
			results.innerHTML = '<div class="alert alert-warning fade-in"><i class="bi bi-info-circle me-2"></i>No recommendations returned.</div>';
			return;
		}
		const items = list.map(function(item){
			if (typeof item === 'string') return '<li class="list-group-item">' + item + '</li>';
			if (item && item.name) return '<li class="list-group-item d-flex justify-content-between align-items-start"><div class="ms-2 me-auto"><div class="fw-semibold">' + item.name + '</div>' + (item.reason ? '<small class="text-muted">' + item.reason + '</small>' : '') + '</div></li>';
			return '<li class="list-group-item">' + JSON.stringify(item) + '</li>';
		}).join('');
		results.innerHTML = '<div class="card fade-in"><div class="card-body"><div class="d-flex align-items-center mb-2"><span class="section-icon me-2"><i class="bi bi-stars"></i></span><h5 class="card-title mb-0">Recommended Courses</h5></div><ul class="list-group">' + items + '</ul></div></div>';
	}

	function renderTextRecommendations(text){
		// Remove unwanted heading if present
		text = String(text).replace(/^\s*##\s*(?:🎯\s*)?Course Recommendations for You\s*\n?/i, '');
		// Convert markdown-style text to HTML
		let html = text
			.replace(/### (\d+\.\s*[^\n]+)/g, '<h5 class="mt-3 mb-2">$1</h5>')
			.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
			.replace(/\n\n/g, '</p><p>')
			.replace(/\n/g, '<br>');
		
		// Wrap in paragraphs
		html = '<p>' + html + '</p>';
		
		results.innerHTML = '<div class="card fade-in"><div class="card-body"><div class="d-flex align-items-center mb-2"><span class="section-icon me-2"><i class="bi bi-stars"></i></span><h5 class="card-title mb-0">Course Recommendations</h5></div><div class="recommendations-content">' + html + '</div></div></div>';
	}

	async function fetchRecommendations(payload){
		const endpoint = 'ai/recommendation_proxy.php';
		const res = await fetch(endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(payload)
		});
		if (!res.ok) {
			throw new Error('HTTP ' + res.status);
		}
		return await res.json();
	}

	btn.addEventListener('click', async function(){
		renderLoading();
		const data = new FormData(form);
		const stanine = data.get('stanine');
		const gwa = data.get('gwa');
		const strand = data.get('strand');
		const hobbies = data.get('hobbies');

		if (!stanine || !gwa || !strand) {
			renderError('Please provide stanine, GWA, and strand.');
			return;
		}

		try {
			const payload = {
				stanine: Number(stanine),
				gwa: Number(gwa),
				strand: String(strand).trim(),
				hobbies: String(hobbies || '').trim()
			};
			const data = await fetchRecommendations(payload);
			const list = data.recommendations || data.data || data.list || data.courses || data.results || data.items || [];
			
			// Check if recommendations is a string (formatted text)
			if (typeof data.recommendations === 'string' && data.recommendations.trim()) {
				renderTextRecommendations(data.recommendations);
			} else if (Array.isArray(list) && list.length > 0) {
				renderRecommendations(list);
			} else {
				const meta = data.meta ? '<pre class="mt-2 small bg-light p-2 border rounded">' + JSON.stringify(data.meta, null, 2) + '</pre>' : '';
				const raw = data.raw ? '<pre class="mt-2 small bg-light p-2 border rounded">' + JSON.stringify(data.raw, null, 2) + '</pre>' : '';
				results.innerHTML = '<div class="alert alert-warning"><i class="bi bi-info-circle me-2"></i>No recommendations returned by the API.</div>' + meta + raw;
			}
		} catch (err) {
			renderError('Failed to fetch recommendations: ' + err.message);
		}
	});
})();



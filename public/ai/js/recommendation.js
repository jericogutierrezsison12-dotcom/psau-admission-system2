// Course Recommendation client: collects form data and calls PHP proxy -> Render API
function initRecommendationSystem() {
	
	const btn = document.getElementById('get-recommendations');
	const results = document.getElementById('recommendation-results');
	const form = document.getElementById('recommendation-form');
	
	if (!btn || !results || !form) {
		return;
	}

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
		
		// Create rating interface for each recommendation
		const items = list.map(function(item, index){
			const courseNumber = index + 1;
			return '<div class="list-group-item">' +
				'<div class="d-flex justify-content-between align-items-start">' +
					'<div class="ms-2 me-auto">' +
						'<div class="fw-semibold">' + item + '</div>' +
					'</div>' +
					'<div class="rating-buttons" data-course="' + courseNumber + '">' +
						'<button type="button" class="btn btn-outline-success btn-sm me-1 rating-btn" data-rating="👍 Like" data-course="' + courseNumber + '">' +
							'👍 Like' +
						'</button>' +
						'<button type="button" class="btn btn-outline-danger btn-sm rating-btn" data-rating="👎 Dislike" data-course="' + courseNumber + '">' +
							'👎 Dislike' +
						'</button>' +
					'</div>' +
				'</div>' +
			'</div>';
		}).join('');
		
		const submitButton = '<div class="mt-3 text-center">' +
			'<button type="button" id="submit-ratings" class="btn btn-success" disabled>' +
				'<i class="bi bi-check-circle"></i> Submit Ratings' +
			'</button>' +
		'</div>';
		
		const finalHTML = '<div class="card fade-in">' +
			'<div class="card-body">' +
				'<div class="d-flex align-items-center mb-2">' +
					'<span class="section-icon me-2"><i class="bi bi-stars"></i></span>' +
					'<h5 class="card-title mb-0">Recommended Courses</h5>' +
				'</div>' +
				'<div class="list-group">' + items + '</div>' +
				submitButton +
			'</div>' +
		'</div>';
		
		results.innerHTML = finalHTML;
		
		// Add event listeners for rating buttons
		attachRatingListeners();
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

	// Rating functionality
	let selectedRatings = {};

	function attachRatingListeners(){
		// Add event listeners to rating buttons
		const ratingButtons = document.querySelectorAll('.rating-btn');
		
		ratingButtons.forEach((btn, index) => {
			btn.addEventListener('click', function(){
				const course = this.dataset.course;
				const rating = this.dataset.rating;
				
				// Update selected rating
				selectedRatings[`course${course}_rating`] = rating;
				
				// Update button states
				updateRatingButtons(course, rating);
				
				// Enable submit button if all ratings are selected
				updateSubmitButton();
			});
		});
		
		// Add event listener to submit button
		const submitBtn = document.getElementById('submit-ratings');
		if (submitBtn) {
			submitBtn.addEventListener('click', submitRatings);
		}
	}

	function updateRatingButtons(course, selectedRating){
		const courseButtons = document.querySelectorAll(`[data-course="${course}"] .rating-btn`);
		courseButtons.forEach(btn => {
			if (btn.dataset.rating === selectedRating) {
				btn.classList.remove('btn-outline-success', 'btn-outline-danger');
				btn.classList.add(selectedRating === '👍 Like' ? 'btn-success' : 'btn-danger');
			} else {
				btn.classList.remove('btn-success', 'btn-danger');
				btn.classList.add(selectedRating === '👍 Like' ? 'btn-outline-danger' : 'btn-outline-success');
			}
		});
	}

	function updateSubmitButton(){
		const submitBtn = document.getElementById('submit-ratings');
		if (submitBtn) {
			const hasAllRatings = selectedRatings.course1_rating && 
								  selectedRatings.course2_rating && 
								  selectedRatings.course3_rating;
			submitBtn.disabled = !hasAllRatings;
		}
	}

	async function submitRatings(){
		const submitBtn = document.getElementById('submit-ratings');
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';
		}

		try {
			const endpoint = 'ai/rating_proxy.php';
			const res = await fetch(endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(selectedRatings)
			});
			
			if (!res.ok) {
				throw new Error('HTTP ' + res.status);
			}
			
			const data = await res.json();
			
			if (data.success) {
				// Show success message
				results.innerHTML = `
					<div class="alert alert-success fade-in">
						<i class="bi bi-check-circle me-2"></i>
						Thank you for your feedback! Your ratings have been submitted successfully.
						${data.feedback ? '<div class="mt-2"><small>' + data.feedback + '</small></div>' : ''}
					</div>
				`;
			} else {
				throw new Error(data.error || 'Failed to submit ratings');
			}
		} catch (err) {
			renderError('Failed to submit ratings: ' + err.message);
		} finally {
			if (submitBtn) {
				submitBtn.disabled = false;
				submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Submit Ratings';
			}
		}
	}

	btn.addEventListener('click', async function(){
		renderLoading();
		
		// Reset ratings for new recommendations
		selectedRatings = {};
		
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
			
			// Debug: log the response
			// Check if we have recommendations array
			if (data.recommendations && Array.isArray(data.recommendations) && data.recommendations.length > 0) {
				renderRecommendations(data.recommendations);
			} else if (data.recommendations && typeof data.recommendations === 'string' && data.recommendations.trim()) {
				renderTextRecommendations(data.recommendations);
			} else {
				// Try to extract recommendations from raw data
				if (data.raw && data.raw.recommendations) {
					const rawRecs = data.raw.recommendations;
					if (rawRecs.course1 || rawRecs.course2 || rawRecs.course3) {
						const fallbackArray = [];
						if (rawRecs.course1) fallbackArray.push(rawRecs.course1);
						if (rawRecs.course2) fallbackArray.push(rawRecs.course2);
						if (rawRecs.course3) fallbackArray.push(rawRecs.course3);
						renderRecommendations(fallbackArray);
						return;
					}
				}
				
				// No valid recommendations found
				renderError('No recommendations available. Please try again.');
				
				const meta = data.meta ? '<pre class="mt-2 small bg-light p-2 border rounded">' + JSON.stringify(data.meta, null, 2) + '</pre>' : '';
				const raw = data.raw ? '<pre class="mt-2 small bg-light p-2 border rounded">' + JSON.stringify(data.raw, null, 2) + '</pre>' : '';
				results.innerHTML += '<div class="mt-3"><small class="text-muted">Debug Info:</small>' + meta + raw + '</div>';
			}
		} catch (err) {
			renderError('Failed to fetch recommendations: ' + err.message);
		}
	});
	
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initRecommendationSystem);
} else {
	initRecommendationSystem();
}



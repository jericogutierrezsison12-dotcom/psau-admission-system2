(function(){
	// Pings the server every 5 minutes to keep the service warm
	const PING_URL = 'keepalive.php'; // public root
	const INTERVAL_MS = 5 * 60 * 1000; // 5 minutes

	async function ping(){
		try {
			await fetch(PING_URL, { method: 'GET', cache: 'no-store', keepalive: true });
			// Optional: console.log('keepalive ping sent');
		} catch (e) {
			// Silently ignore
		}
	}

	// Initial ping and interval
	ping();
	setInterval(ping, INTERVAL_MS);
})();



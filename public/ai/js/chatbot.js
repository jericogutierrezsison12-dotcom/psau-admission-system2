// Chatbot client: simple UI + connection to Flask endpoint
(function initChatbot(){
    const root = document.getElementById('chatbot-root');
    if (!root) return;

    // Basic UI
    root.innerHTML = [
        '<div class="chat-wrapper">',
            '<div id="chat-messages" class="border rounded p-3 mb-3" style="height:520px; overflow:auto; background:#f8f9fa">',
                '<div class="d-flex mb-3">',
                    '<div class="alert alert-success w-100 m-0 text-center py-3" style="font-size:1.05rem;">Welcome! Ask anything about admissions.</div>',
                '</div>',
            '</div>',
            '<form id="chat-form" class="d-flex gap-2">',
                '<input id="chat-input" type="text" class="form-control" placeholder="Type your question..." autocomplete="off" required />',
                '<button id="chat-send" type="submit" class="btn btn-psau">Send</button>',
            '</form>',
            '<div id="chat-status" class="form-text mt-2 text-muted"></div>',
        '</div>'
    ].join('');

    const messagesEl = document.getElementById('chat-messages');
    const formEl = document.getElementById('chat-form');
    const inputEl = document.getElementById('chat-input');
    const statusEl = document.getElementById('chat-status');

    // Use local PHP proxy to avoid CORS issues
    const API_URL = 'ai/chatbot_proxy.php';

    function formatBotReply(text){
        if (!text) return '';
        const answerMatch = text.match(/\*\*Answer:\*\*\s*([\s\S]*?)(?=\*\*Confidence:\*\*|\*\*Suggested Questions:\*\*|$)/i);
        const confidenceMatch = text.match(/\*\*Confidence:\*\*\s*([0-9.]+)/i);
        const suggestionsMatch = text.match(/\*\*Suggested Questions:\*\*\s*([\s\S]*)/i);

        const answer = answerMatch ? answerMatch[1].trim() : '';
        const confidence = confidenceMatch ? confidenceMatch[1].trim() : '';
        let suggestionsRaw = suggestionsMatch ? suggestionsMatch[1].trim() : '';

        // Extract numbered suggestions like: 1. text 2. text 3. text
        let suggestions = [];
        if (suggestionsRaw) {
            const parts = suggestionsRaw
                .split(/\s*(?:\n|^)\s*\d+\.\s*/)
                .map(function(s){ return s.trim(); })
                .filter(function(s){ return s.length > 0; });
            // If split did not work (single line), try splitting by ' ? ' or ' ?\n'
            if (parts.length <= 1) {
                suggestions = suggestionsRaw.split(/\s*\d+\.\s*/).map(function(s){ return s.trim(); }).filter(Boolean);
            } else {
                suggestions = parts;
            }
        }

        function mdToHtml(str){
            return String(str)
                .replace(/\*\*([^*]+)\*\*/g, '<strong>$1<\/strong>')
                .replace(/\n\n/g, '<\/p><p>')
                .replace(/\n/g, '<br>');
        }

        // Build HTML
        var html = '';
        if (answer) {
            html += '<div class="mb-2">' + mdToHtml(answer) + '<\/div>';
        } else {
            html += '<div class="mb-2">' + mdToHtml(text) + '<\/div>';
        }
        if (confidence) {
            html += '<div class="mb-2"><span class="badge bg-success">Confidence: ' + confidence + '<\/span><\/div>';
        }
        if (suggestions && suggestions.length) {
            var items = suggestions.map(function(s){ return '<li class="list-group-item">' + s.replace(/^[-•]\s*/, '') + '<\/li>'; }).join('');
            html += '<div class="mt-2"><div class="fw-semibold mb-1">You can also ask:<\/div><ul class="list-group list-group-flush">' + items + '<\/ul><\/div>';
        }
        return html;
    }

    function appendMessage(text, role){
        const row = document.createElement('div');
        row.className = 'd-flex mb-3 ' + (role === 'user' ? 'justify-content-end' : 'justify-content-start');
        const bubble = document.createElement('div');
        bubble.className = 'p-2 rounded ' + (role === 'user' ? 'bg-success text-white' : 'bg-light border');
        bubble.style.maxWidth = '85%';
        if (role === 'bot') {
            bubble.innerHTML = formatBotReply(text);
        } else {
            bubble.textContent = text;
        }
        row.appendChild(bubble);
        messagesEl.appendChild(row);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function setLoading(isLoading){
        const btn = document.getElementById('chat-send');
        btn.disabled = isLoading;
        inputEl.disabled = isLoading;
        statusEl.textContent = isLoading ? 'Sending...' : '';
    }

    async function sendMessage(message){
        setLoading(true);
        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });

            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            const data = await res.json();
            // Accept reply/message/response keys
            const reply = data.reply || data.message || data.response || 'No reply received.';
            appendMessage(reply, 'bot');
        } catch (err) {
            appendMessage('Sorry, I could not reach the chatbot service. ' + err.message, 'bot');
        } finally {
            setLoading(false);
        }
    }

    formEl.addEventListener('submit', function(e){
        e.preventDefault();
        const text = (inputEl.value || '').trim();
        if (!text) return;
        appendMessage(text, 'user');
        inputEl.value = '';
        sendMessage(text);
    });
})();



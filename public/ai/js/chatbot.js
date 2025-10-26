// Chatbot client: simple UI + connection to Flask endpoint
(function initChatbot(){
    const root = document.getElementById('chatbot-root');
    if (!root) return;

    // Basic UI + suggestions container above input
    root.innerHTML = [
        '<div class="chat-wrapper">',
            '<div id="chat-messages" class="border rounded p-3 mb-3" style="height:520px; overflow:auto; background:#f8f9fa">',
                '<div class="d-flex mb-3">',
                    '<div class="alert alert-success w-100 m-0 text-center py-3" style="font-size:1.05rem;">Welcome! Ask anything about admissions.</div>',
                '</div>',
            '</div>',
            '<div id="faq-suggestions" class="mb-2" style="max-height:160px; overflow:auto; display:none;">',
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
    const suggestionsEl = document.getElementById('faq-suggestions');

    // Use local PHP proxy to avoid CORS issues
    const API_URL = 'ai/chatbot_proxy.php';
    const FAQ_URL = 'ai/get_faqs.php';

    // Load FAQs once
    let faqs = [];
    
    // Add fallback FAQs in case the endpoint fails
    const fallbackFAQs = [
        { id: 1, question: "What are the admission requirements?", answer: "Basic requirements include..." },
        { id: 2, question: "How do I apply for admission?", answer: "To apply for admission..." },
        { id: 3, question: "When is the entrance examination?", answer: "Entrance examinations are scheduled..." },
        { id: 4, question: "What courses are available?", answer: "PSAU offers various undergraduate programs..." }
    ];
    
    fetch(FAQ_URL)
        .then(function(r){ return r.json(); })
        .then(function(j){ 
            faqs = (j && j.faqs) ? j.faqs : fallbackFAQs; 
            renderSuggestions(''); 
        })
        .catch(function(err){ 
            faqs = fallbackFAQs;
            renderSuggestions('');
        });

    function scoreQuestion(query, question){
        if (!query) return 0;
        query = query.toLowerCase();
        question = (question || '').toLowerCase();
        if (!question) return 0;
        if (question.indexOf(query) !== -1) return 100; // substring boost
        // token overlap
        var qTokens = query.split(/\s+/).filter(Boolean);
        var text = question.replace(/[^a-z0-9\s]/g, ' ');
        var tTokens = text.split(/\s+/).filter(Boolean);
        var tSet = {};
        for (var i=0;i<tTokens.length;i++){ tSet[tTokens[i]] = true; }
        var overlap = 0;
        for (var k=0;k<qTokens.length;k++){ if (tSet[qTokens[k]]) overlap++; }
        return overlap * 10;
    }

    function renderSuggestions(query){
        if (!faqs || !faqs.length){ 
            suggestionsEl.style.display = 'none'; 
            return; 
        }
        var items = faqs
            .map(function(f){ return { f: f, s: scoreQuestion(query, f.question) }; })
            .filter(function(x){ return query ? x.s > 0 : true; })
            .sort(function(a,b){ return b.s - a.s; })
            .slice(0, 8);

        if (!items.length && query){ 
            suggestionsEl.style.display = 'none'; 
            return; 
        }
        
        // Always show suggestions if no query (default state)
        if (!query && faqs.length > 0) {
            items = faqs.slice(0, 8).map(function(f){ return { f: f, s: 0 }; });
        }

        var html = '';
        for (var i=0;i<items.length;i++){
            var q = items[i].f.question;
            html += '<button type="button" class="list-group-item list-group-item-action" data-idx="' + i + '" style="border:1px solid #e9ecef; border-radius:6px; margin-bottom:6px;">' + q + '<\/button>';
        }
        if (!html){
            // Default show top sorted by sort_order if no query
            var base = faqs.slice(0, 8);
            for (var j=0;j<base.length;j++){
                html += '<button type="button" class="list-group-item list-group-item-action" data-abs="' + j + '" style="border:1px solid #e9ecef; border-radius:6px; margin-bottom:6px;">' + base[j].question + '<\/button>';
            }
        }
        suggestionsEl.className = 'list-group mb-2';
        suggestionsEl.innerHTML = html;
        suggestionsEl.style.display = 'block';
    }

    // Live ranking while typing
    var debounceTimer = null;
    inputEl.addEventListener('input', function(){
        var q = (inputEl.value || '').trim();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function(){ renderSuggestions(q); }, 120);
    });

    suggestionsEl.addEventListener('click', function(e){
        var btn = e.target.closest('button[data-idx],button[data-abs]');
        if (!btn) return;
        var idxRel = btn.getAttribute('data-idx');
        var idxAbs = btn.getAttribute('data-abs');
        var selected;
        if (idxRel !== null){
            // rebuild the ranked list against current query to map index
            var query = (inputEl.value || '').trim();
            var ranked = faqs
                .map(function(f){ return { f: f, s: scoreQuestion(query, f.question) }; })
                .filter(function(x){ return query ? x.s > 0 : true; })
                .sort(function(a,b){ return b.s - a.s; })
                .slice(0, 8);
            selected = (ranked[idxRel|0] || {}).f;
        } else if (idxAbs !== null){
            selected = faqs[idxAbs|0];
        }
        if (!selected) return;
        inputEl.value = selected.question;
        inputEl.focus();
        // Move selected question to top visually by re-rendering with it as the query
        renderSuggestions(selected.question);
    });

    function formatBotReply(text){
        if (!text) return '';
        const answerMatch = text.match(/\*\*Answer:\*\*\s*([\s\S]*?)(?=\*\*Suggested Questions:\*\*|$)/i);
        const suggestionsMatch = text.match(/\*\*Suggested Questions:\*\*\s*([\s\S]*)/i);

        const answer = answerMatch ? answerMatch[1].trim() : '';
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
        // Confidence display removed as requested
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
            appendMessage('Your Network is Unstable, Please Try Again.');
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
/**
 * ChatController - Wellness Edition (v5 Attachments)
 */
class ChatController {
    constructor(userId, userRole, contactId) {
        this.userId = userId;
        this.userRole = userRole;
        this.contactId = contactId;

        this.chatContainer = document.getElementById('chatMessages');
        this.inputArea = document.querySelector('.chat-input');
        this.form = document.getElementById('messageForm');
        this.aiContainer = document.getElementById('aiSuggestions');
        this.fileInput = document.getElementById('fileInput');
        this.attachBtn = document.getElementById('attachBtn');

        this.messageMap = {}; // Store raw text by ID

        window.chat = this;

        this.init();
    }

    init() {
        this.startPolling();

        if (this.form) {
            this.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
            this.inputArea.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        // Attachment Handlers
        if (this.attachBtn && this.fileInput) {
            this.attachBtn.addEventListener('click', () => {
                this.fileInput.click();
            });

            this.fileInput.addEventListener('change', () => {
                if (this.fileInput.files.length > 0) {
                    // Auto-send or ask? For now, let's auto-send to fulfill "make it possible" simply
                    // Or ideally, fill the input with "[File selected]" and wait for send.
                    // Let's go with immediate send to ensure it works smoothly without UI complexness
                    this.sendMessage();
                }
            });
        }
    }

    startPolling() {
        this.fetchMessages();
        setInterval(() => this.fetchMessages(), 3000);
    }

    async fetchMessages() {
        try {
            const res = await fetch(`${BASE_URL}handlers/get_messages.php?contact_id=${this.contactId}`);
            const data = await res.json();
            if (data.success) {
                this.renderMessages(data.messages);
            }
        } catch (e) { }
    }

    renderMessages(messages) {
        if (!this.chatContainer) return;

        if (this.chatContainer.innerHTML.includes('fa-spinner')) {
            this.chatContainer.innerHTML = '';
        }

        const isBottom = (this.chatContainer.scrollHeight - this.chatContainer.scrollTop <= this.chatContainer.clientHeight + 150);
        let hasNewMessage = false;

        messages.forEach(msg => {
            // Store raw content
            this.messageMap[msg.id] = msg.message;

            if (document.getElementById(`msg-${msg.id}`)) return;

            hasNewMessage = true;

            if (msg.date_separator) {
                this.chatContainer.insertAdjacentHTML('beforeend', `<div class="date-divider">${msg.date_separator}</div>`);
            }

            const lastMsg = this.chatContainer.lastElementChild;
            let isGroupStart = true;

            // Force separate bubbles for every message as per user request "no stacking"
            if (lastMsg && lastMsg.classList.contains('message-wrapper')) {
                lastMsg.classList.add('group-end');
            }

            const typeClass = (msg.type === 'sent') ? 'sent' : 'received';
            const initials = msg.sender_name ? msg.sender_name.substring(0, 2).toUpperCase() : '??';

            // Sync with App Logo if it's Staff
            let avatarContent = initials;
            if (msg.sender_name === 'Dietician Staff') {
                avatarContent = `<img src="assets/img/logo.png" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">`;
            }

            const avatarHtml = (typeClass === 'received' && isGroupStart)
                ? `<div class="msg-avatar-spacer"><div class="contact-avatar" style="width:32px; height:32px; font-size:0.8rem; overflow:hidden;">${avatarContent}</div></div>`
                : (typeClass === 'received' ? `<div style="width:32px;"></div>` : '');

            // Content Generation
            let contentHtml = '';
            if (msg.message_type === 'image' && msg.attachment_path) {
                contentHtml = `<img src="${msg.attachment_path}" alt="Image" style="max-width:200px; border-radius:12px; display:block; margin-bottom:4px;">`;
            } else if (msg.message_type === 'file' && msg.attachment_path) {
                contentHtml = `<a href="${msg.attachment_path}" target="_blank" style="display:flex; align-items:center; gap:8px; color:inherit; text-decoration:none; background:rgba(0,0,0,0.05); padding:8px 12px; border-radius:8px;">
                                <i class="fas fa-file-alt"></i> ${msg.file_name}
                               </a>`;
            } else {
                contentHtml = this.escapeHtml(msg.message);
            }

            // Append Timestamp
            let timeHtml = '';
            if (msg.pretty_time) {
                timeHtml = `<div class="msg-time">${msg.pretty_time}</div>`;
            }

            // Reply Action HTML
            const replyAction = `<button class="reply-action-btn" onclick="chat.initiateReply(${msg.id})"><i class="fas fa-reply"></i></button>`;

            // Reply/Quote Parsing
            let replyHtml = '';
            let rawMessage = msg.message;

            // Normalize potential double escaping for detection
            let decodedMessage = this.decodeHtml(rawMessage);

            if (decodedMessage.startsWith('> ') || rawMessage.startsWith('&gt; ')) {
                // Determine split pattern
                let isEncoded = rawMessage.startsWith('&gt;');
                let splitPattern = isEncoded ? '\n\n' : '\n\n';

                // If the raw message is just text with HTML entities, we might need to handle the entities
                // Basically, if we detect > or &gt;, we try to split.

                // Let's use the decoded message for processing logic to be safe, but be careful of XSS when re-outputting
                // Match double newline (handles \r\n and \n)
                const parts = decodedMessage.split(/\r?\n\r?\n/);

                if (parts.length >= 2) {
                    // Extract the quote.
                    let quoteText = parts[0];
                    quoteText = quoteText.replace(/^(&gt;|>)+(\s*(&gt;|>)+)*/g, '').trim();

                    const replyText = parts.slice(1).join('\n\n');

                    // Update contentHtml to be JUST the reply
                    contentHtml = this.escapeHtml(replyText);

                    // Create the Reply Context Block
                    // We decode the quoteText completely so it looks like normal text, then escape it for safety
                    const senderName = (msg.type === 'sent') ? 'You' : msg.sender_name;
                    replyHtml = `
                        <div class="reply-context-block">
                            <div class="reply-context-header"><i class="fas fa-reply"></i> ${senderName} replied to:</div>
                            <div class="reply-context-text">${this.escapeHtml(quoteText)}</div>
                        </div>
                    `;
                }
            }

            const html = `
                <div class="message-wrapper ${typeClass} ${isGroupStart ? 'group-start' : ''} group-end" id="msg-${msg.id}" data-sender-type="${msg.sender_type}">
                    ${avatarHtml}
                    <div class="message-bubble-container">
                        <div class="message-bubble">
                            ${replyHtml}
                            ${contentHtml}
                            ${timeHtml}
                        </div>
                        ${replyAction}
                    </div>
                </div>
            `;

            this.chatContainer.insertAdjacentHTML('beforeend', html);
        });

        if (isBottom || hasNewMessage) this.scrollToBottom();
    }

    initiateReply(msgId) {
        let text = this.messageMap[msgId] || '';
        if (!text) return;

        // Strip existing quotes for the preview and context to prevent "stacking" previous replies
        let decoded = this.decodeHtml(text);
        if (decoded.trim().startsWith('>') || text.trim().startsWith('&gt;')) {
            // Split using regex for any sequence of 2+ newlines
            const parts = decoded.split(/\r?\n\r?\n/);
            if (parts.length >= 2) {
                // Take everything except the first part (the quote)
                text = parts.slice(1).join('\n\n').trim();
            } else {
                // Even if double newline fails, if it starts with quote, try to strip first line
                const lines = decoded.split(/\r?\n/);
                if (lines.length >= 2 && lines[0].trim().startsWith('>')) {
                    text = lines.slice(1).join('\n').trim();
                }
            }
        }

        this.currentReplyContext = text;
        this.currentReplyId = msgId;

        // Show Banner
        let banner = document.getElementById('replyBanner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'replyBanner';
            banner.className = 'reply-banner';
            this.inputArea.parentElement.parentElement.insertBefore(banner, this.inputArea.parentElement); // Place above input pill
        }

        banner.innerHTML = `
            <div class="reply-content">
                <div class="reply-title"><i class="fas fa-reply"></i> Replying to:</div>
                <div class="reply-text">${text.substring(0, 50)}${text.length > 50 ? '...' : ''}</div>
            </div>
            <button onclick="chat.cancelReply()" class="cancel-reply"><i class="fas fa-times"></i></button>
        `;
        banner.style.display = 'flex';
        this.inputArea.focus();
    }

    cancelReply() {
        this.currentReplyContext = null;
        this.currentReplyId = null;
        const banner = document.getElementById('replyBanner');
        if (banner) banner.style.display = 'none';
    }

    async sendMessage() {
        let text = this.inputArea.value.trim();
        const file = (this.fileInput && this.fileInput.files.length > 0) ? this.fileInput.files[0] : null;

        if (!text && !file) return;

        // Handle Reply
        if (this.currentReplyContext) {
            text = `> ${this.currentReplyContext}\n\n${text}`;
            this.cancelReply(); // Clear state
        }

        // Optimistic UI for text? No, wait for server for simplicty with mixed types

        // Reset Inputs
        this.inputArea.value = '';
        this.inputArea.style.height = 'auto';
        if (this.fileInput) this.fileInput.value = ''; // clear

        this.hideAISuggestions();

        try {
            const formData = new FormData();
            formData.append('recipient_id', this.contactId);
            formData.append('message', text); // Might be empty if file only
            if (file) {
                formData.append('attachment', file);
            }

            const res = await fetch(`${BASE_URL}handlers/send_message_ajax.php`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.renderMessages([data.message]);
                this.scrollToBottom();
            }
        } catch (e) { }
    }

    async toggleAISuggestions() {
        if (!this.aiContainer) return;

        if (this.aiContainer.classList.contains('visible') && this.aiContainer.innerHTML !== '') {
            this.hideAISuggestions();
            return;
        }

        try {
            // Pass context if replying
            let url = `${BASE_URL}api/ai_suggest.php`;
            if (this.currentReplyContext) {
                url += `?context=${encodeURIComponent(this.currentReplyContext)}`;
            }

            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                this.aiContainer.innerHTML = data.suggestions.map(s => `
                    <div class="ai-suggestion-card" onclick="chat.useSuggestion(this)">
                        <i class="fas fa-wand-magic-sparkles"></i> ${s}
                    </div>
                `).join('');

                this.aiContainer.style.display = 'flex'; // Force Flex
                // Trigger Reflow
                void this.aiContainer.offsetWidth;
                this.aiContainer.classList.add('visible');
            }
        } catch (e) { }
    }

    hideAISuggestions() {
        if (this.aiContainer) {
            this.aiContainer.classList.remove('visible');
        }
    }

    useSuggestion(el) {
        let text = el.innerText.trim();
        this.inputArea.value = text;
        this.inputArea.focus();
        this.hideAISuggestions();
    }

    scrollToBottom() {
        setTimeout(() => {
            this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
        }, 50);
    }

    escapeHtml(t) {
        if (!t) return '';
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML.replace(/\n/g, '<br>');
    }

    decodeHtml(html) {
        const txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    }
}

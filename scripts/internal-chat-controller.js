/**
 * InternalChatController
 * Elite Standard for Staff-Admin communications
 */
class ChatController {
    constructor(userId, userRole, threadId = null) {
        this.userId = userId;
        this.userRole = userRole;
        this.currentThreadId = threadId;
        this.pollingInterval = null;

        this.init();
    }

    init() {
        this.bindUI();
        if (this.currentThreadId) {
            this.fetchMessages();
            this.startPolling();
        }

        // Global Lightbox
        this.initLightbox();
    }

    bindUI() {
        this.chatMessages = document.getElementById('chatMessages');
        this.inputArea = document.getElementById('messageInput');
        this.attachBtn = document.getElementById('attachBtn');
        this.fileInput = document.getElementById('fileInput');

        if (this.inputArea) {
            this.inputArea.onkeypress = (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            };
        }

        if (this.attachBtn && this.fileInput) {
            this.attachBtn.onclick = () => this.fileInput.click();
            this.fileInput.onchange = () => {
                if (this.fileInput.files.length > 0) this.sendMessage();
            };
        }
    }

    startPolling() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        this.pollingInterval = setInterval(() => this.fetchMessages(), 3000);
    }

    async fetchMessages() {
        if (!this.currentThreadId) return;
        try {
            const res = await fetch(`${BASE_URL}handlers/get_internal_messages.php?thread_id=${this.currentThreadId}`);
            const data = await res.json();
            if (data.success) {
                this.renderMessages(data.messages);
            }
        } catch (e) {}
    }

    renderMessages(messages) {
        if (!this.chatMessages) this.chatMessages = document.getElementById('chatMessages');
        if (!this.chatMessages) return;

        let added = false;
        messages.forEach(msg => {
            if (!document.getElementById(`msg-${msg.id}`)) {
                const isMe = msg.type === 'sent';
                let content = `<div class="message-text">${this.escapeHtml(msg.message)}</div>`;

                if (msg.message_type === 'image' && msg.attachment_path) {
                    content = `<div class="message-text"><img src="${msg.attachment_path}" class="chat-img-zoomable" alt="Attachment" style="max-width:100%; border-radius:12px; cursor:zoom-in;"></div>`;
                } else if (msg.message_type === 'file' && msg.attachment_path) {
                    content = `<div class="message-text clinical-file-msg"><a href="${msg.attachment_path}" target="_blank" style="color:var(--primary-green); font-weight:600;"><i class="fas fa-file-pdf"></i> ${msg.file_name}</a></div>`;
                }

                const html = `
                    <div class="message-wrapper ${isMe ? 'sent' : 'received'}" id="msg-${msg.id}">
                        <div class="message-bubble" style="background:${isMe ? 'var(--primary-green)' : 'var(--bg-surface)'}; color:${isMe ? 'white' : 'inherit'};">
                            <div style="font-size:0.7rem; color:${isMe ? 'rgba(255,255,255,0.8)' : 'var(--primary-green)'}; margin-bottom:2px; font-weight:600;">${msg.sender_name}</div>
                            ${content}
                            <div class="msg-time" style="color:${isMe ? 'rgba(255,255,255,0.7)' : 'inherit'};">${msg.pretty_time}</div>
                        </div>
                    </div>
                `;
                this.chatMessages.insertAdjacentHTML('beforeend', html);
                added = true;
            }
        });

        if (added) {
            this.scrollToBottom();
            this.bindUI(); // Crucial! Re-bind listeners when content and input area exist
        }
    }

    async sendMessage() {
        if (!this.inputArea) this.bindUI();
        const text = this.inputArea ? this.inputArea.value.trim() : '';
        const file = this.fileInput ? this.fileInput.files[0] : null;
        if (!text && !file) return;

        const formData = new FormData();
        formData.append('thread_id', this.currentThreadId);
        formData.append('message', text);
        if (file) formData.append('attachment', file);

        if (this.inputArea) this.inputArea.value = '';
        if (this.fileInput) this.fileInput.value = '';

        try {
            const res = await fetch(`${BASE_URL}handlers/send_internal_message.php`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.renderMessages([data.message]);
            }
        } catch (e) { alert("Communication Error."); }
    }

    scrollToBottom() {
        if (this.chatMessages) this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }

    escapeHtml(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML.replace(/\n/g, '<br>');
    }

    initLightbox() {
        if (!document.getElementById('lightboxOverlay')) {
            const lb = document.createElement('div');
            lb.id = 'lightboxOverlay';
            lb.className = 'lightbox-overlay';
            lb.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(8px);cursor:zoom-out;";
            lb.innerHTML = '<img id="lightboxContentInternal" style="max-width:90%;max-height:90%;border-radius:12px;box-shadow:0 30px 60px rgba(0,0,0,0.5);transform:scale(0.9);transition:transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);" src="">';
            document.body.appendChild(lb);
            lb.onclick = () => { lb.style.display = 'none'; document.getElementById('lightboxContentInternal').style.transform = 'scale(0.9)'; };
        }

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('chat-img-zoomable')) {
                const lb = document.getElementById('lightboxOverlay');
                const lbImg = document.getElementById('lightboxContentInternal');
                lbImg.src = e.target.src;
                lb.style.display = 'flex';
                setTimeout(() => { lbImg.style.transform = 'scale(1)'; }, 10);
            }
        });
    }

    changeThread(threadId, threadTitle) {
        if (this.currentThreadId === threadId) return;
        this.currentThreadId = threadId;
        if (this.chatMessages) this.chatMessages.innerHTML = '<div style="text-align:center; padding:50px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        this.fetchMessages();
        this.startPolling();
        setTimeout(() => this.bindUI(), 500); // Re-bind once UI is injected
    }
}

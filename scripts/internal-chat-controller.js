/**
 * ChatController
 * Handles message sending, polling, and rendering for Staff-Admin Internal Chat
 */

class ChatController {
    constructor(userId, userRole, threadId = null) {
        this.userId = userId;
        this.userRole = userRole;
        this.currentThreadId = threadId;
        this.pollingInterval = null;
        this.isTyping = false;

        // DOM Elements
        this.chatContainer = document.getElementById('chatMessages');
        this.messageForm = document.getElementById('messageForm');
        this.messageInput = document.querySelector('.chat-input');

        this.init();
    }

    init() {
        if (this.currentThreadId) {
            this.startPolling();
            this.scrollToBottom();
        }

        this.bindForm();
    }

    changeThread(threadId, threadTitle) {
        if (this.currentThreadId === threadId) return;
        
        this.currentThreadId = threadId;
        
        // Update URL
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('thread_id', threadId);
        window.history.pushState({ path: newUrl.href }, '', newUrl.href);

        // Refresh container reference in case DOM was re-rendered
        this.chatContainer = document.getElementById('chatMessages');

        // Clear messages
        if (this.chatContainer) {
            this.chatContainer.innerHTML = `
            <div style="text-align:center; margin-top:50px; color:#9CA3AF;">
                <i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Loading conversation...
            </div>
            `;
        }

        // Update Header
        const headerTitle = document.querySelector('.chat-header h3'); // Assuming h3 is title
        if (headerTitle) headerTitle.textContent = threadTitle;
        
        // Ensure chat area is visible
        const chatArea = document.querySelector('.msg-container') || document.querySelector('.chat-area');
        if (chatArea) chatArea.style.display = 'flex';

        // Fetch new messages
        this.fetchMessages();

        // Re-bind form handlers in case the form was dynamically injected
        this.bindForm();
    }

    startPolling() {
        // Initial load
        this.fetchMessages();

        // Poll every 3 seconds
        this.pollingInterval = setInterval(() => {
            this.fetchMessages();
        }, 3000);
    }

    bindForm() {
        const form = document.getElementById('messageForm');
        const input = document.getElementById('messageInput') || document.querySelector('.chat-input');

        // If new elements differ from previously bound ones, attach listeners
        if (form && this.boundFormEl !== form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.messageInput = input;
                this.sendMessage();
            });
            this.boundFormEl = form;
        }

        if (input && this.boundInputEl !== input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.messageInput = input;
                    this.sendMessage();
                }
            });
            this.boundInputEl = input;
        }

        // Update references for use in sendMessage()
        this.messageForm = form;
        this.messageInput = input;
    }

    stopPolling() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
    }

    async fetchMessages() {
        if (!this.currentThreadId) return;

        try {
            const response = await fetch(`${BASE_URL}handlers/get_internal_messages.php?thread_id=${this.currentThreadId}`);
            const data = await response.json();

            if (data.success) {
                // Ensure latest container reference
                this.chatContainer = document.getElementById('chatMessages');
                this.renderMessages(data.messages);
                const inputArea = document.querySelector('.chat-input-area');
                if (inputArea) {
                    if (data.thread_status === 'open') {
                        inputArea.style.display = '';
                        const msgInput = document.getElementById('messageInput');
                        if (msgInput) msgInput.disabled = false;
                    } else {
                        inputArea.style.display = 'none';
                    }
                }
            } else {
                console.error('Error fetching messages:', data.error);
            }
        } catch (error) {
            console.error('Network error:', error);
        }
    }

    renderMessages(messages) {
        if (!this.chatContainer) return;

        const isAtBottom = (this.chatContainer.scrollHeight - this.chatContainer.scrollTop <= this.chatContainer.clientHeight + 100);

        messages.forEach(msg => {
            const existingMsg = document.getElementById(`msg-${msg.id}`);
            if (!existingMsg) {
                // Determine layout class
                const typeClass = (msg.type === 'sent') ? 'sent' : 'received';
                
                // Initials for avatar
                const initials = msg.sender_name 
                    ? msg.sender_name.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase() 
                    : '?';
                
                const avatar = msg.type === 'received' 
                    ? `<div class="msg-avatar" title="${msg.sender_name} (${msg.sender_role})">${initials}</div>` 
                    : '';

                // Role badge (optional)
                const roleBadge = msg.sender_role === 'admin' 
                    ? '<span style="font-size:0.7rem; color:var(--primary-green); margin-left:5px; font-weight:bold;">Admin</span>' 
                    : '';

                const html = `
                    <div class="message-wrapper ${typeClass}" id="msg-${msg.id}">
                        ${typeClass === 'received' ? avatar : ''}
                        <div class="message-bubble ${typeClass}">
                            <div class="message-text">${this.escapeHtml(msg.message)}</div>
                            <div class="message-meta">
                                <span class="message-time">${msg.pretty_time}</span>
                            </div>
                        </div>
                    </div>
                `;

                this.chatContainer.insertAdjacentHTML('beforeend', html);
            }
        });

        // Scroll logic
        if (isAtBottom) {
            this.scrollToBottom();
        }
    }

    async sendMessage() {
        const text = this.messageInput.value.trim();
        if (!text || !this.currentThreadId) return;

        // Clear input immediately for UX
        this.messageInput.value = '';
        this.messageInput.style.height = 'auto'; // Reset resize

        try {
            const formData = new FormData();
            formData.append('thread_id', this.currentThreadId);
            formData.append('message', text);

            const response = await fetch(BASE_URL + 'handlers/send_internal_message.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.renderMessages([data.message]);
                this.scrollToBottom();
            } else {
                alert('Failed to send: ' + data.error);
                this.messageInput.value = text;
            }
        } catch (e) {
            console.error(e);
            alert('Error sending message');
            this.messageInput.value = text;
        }
    }

    scrollToBottom() {
        if (this.chatContainer) {
            this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>'); // Preserve newlines
    }
}

// Auto resize textarea helper
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}

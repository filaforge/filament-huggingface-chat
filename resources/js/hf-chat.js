window.hfChat = function ($wireRef) {
    return {
        typing: false,
        autoScroll: true,
        messageSent: false,
        optimisticMessage: '',
        showOptimisticUserMessage: false,
        // two-way bound to Livewire viewMode
        viewMode: $wireRef ? $wireRef.entangle('viewMode') : 'chat',

        init() {
            this.scrollToBottom();

            // Livewire events
            this.$wire.on('messageSent', () => {
                this.messageSent = true;
                this.typing = false;
                this.showOptimisticUserMessage = false;
                this.optimisticMessage = '';
                setTimeout(() => this.scrollDownExtra(), 100);
            });

            this.$wire.on('messageReceived', () => {
                this.messageSent = false;
                this.typing = false;
                this.showOptimisticUserMessage = false;
                this.optimisticMessage = '';
                this.scrollToBottom();
            });

            // Auto-scroll observers
            this.$nextTick(() => {
                const messagesEl = this.$refs.messages;
                if (!messagesEl || typeof ResizeObserver === 'undefined') return;
                const ro = new ResizeObserver(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
                ro.observe(messagesEl);
            });
            this.$nextTick(() => {
                const messagesEl = this.$refs.messages;
                if (!messagesEl) return;
                const mo = new MutationObserver(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
                mo.observe(messagesEl, { childList: true, subtree: true });
            });

            // Global events (optional)
            window.addEventListener('set-view', (e) => {
                this.viewMode = e.detail?.mode || 'chat';
            });
            window.addEventListener('new-chat', () => {
                this.$wire.newConversation();
                this.viewMode = 'chat';
            });
        },

        sendMessage() {
            if (this.$wire.userInput?.trim()) {
                this.messageSent = true;
                this.$wire.send();
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el && this.autoScroll) el.scrollTop = el.scrollHeight;
            });
        },

        scrollDownExtra() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight + 400;
            });
        },

        handleKeydown(event) {
            if (event.key === 'Enter') {
                if (event.ctrlKey) return;
                event.preventDefault();
                this.sendMessage();
                this.scrollToBottom();
            }
        },
    };
};




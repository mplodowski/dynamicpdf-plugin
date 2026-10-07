oc.registerControl('dynamicpdf-variables', class extends oc.ControlBase {
    connect() {
        this.listen('click', '[data-snippet]', this.onCopy);
    }

    async onCopy(event) {
        const snippet = event.delegateTarget.dataset.snippet;
        const { copiedText, copyFailedText } = this.element.dataset;

        try {
            await this.copy(snippet);
            oc.flashMsg({ text: copiedText, class: 'success', interval: 2 });
        } catch {
            oc.flashMsg({ text: copyFailedText, class: 'error' });
        }
    }

    async copy(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            if (!document.execCommand('copy')) {
                throw new Error('Copy command was rejected');
            }
        } finally {
            textarea.remove();
        }
    }
});

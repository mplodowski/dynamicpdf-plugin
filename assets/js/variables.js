oc.registerControl('dynamicpdf-variables', class extends oc.ControlBase {
    connect() {
        this.listen('click', '[data-snippet]', this.onCopy);

        /** The code editor announces edits with a jQuery event on its textarea, never a change on the field, so dependsOn would not see them. */
        this.$source = $(this.element.closest('form')).find(`[data-field-name="${this.element.dataset.sourceField}"]`);
        this.$source.on('oc.codeEditorChange.dynamicpdfVariables', () => this.$source.trigger('change'));
    }

    disconnect() {
        this.$source.off('.dynamicpdfVariables');
    }

    async onCopy(event) {
        const button = event.delegateTarget;
        const snippet = button.dataset.snippet;
        const { copiedText, copyFailedText } = this.element.dataset;

        if (await this.copy(snippet)) {
            oc.flashMsg({ text: copiedText, class: 'success', interval: 2 });
            return;
        }

        this.showForManualCopy(button, snippet);
        oc.flashMsg({ text: copyFailedText, class: 'warning' });
    }

    async copy(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            return this.copyWithSelection(text);
        }
    }

    copyWithSelection(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            return document.execCommand('copy');
        } catch {
            return false;
        } finally {
            textarea.remove();
        }
    }

    showForManualCopy(button, snippet) {
        let input = button.parentElement.querySelector('[data-manual-copy]');

        if (!input) {
            input = document.createElement('input');
            input.type = 'text';
            input.readOnly = true;
            input.dataset.manualCopy = '';
            input.className = 'form-control form-control-sm font-monospace w-100 mt-1';
            input.setAttribute('aria-label', button.getAttribute('aria-label'));
            button.parentElement.appendChild(input);
        }

        input.value = snippet;
        input.focus();
        input.select();
    }
});

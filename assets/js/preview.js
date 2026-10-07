oc.registerControl('dynamicpdf-pdf-preview', class extends oc.ControlBase {
    connect() {
        const bytes = Uint8Array.from(atob(this.element.dataset.pdf), (char) => char.charCodeAt(0));

        this.url = URL.createObjectURL(new Blob([bytes], { type: 'application/pdf' }));
        this.element.src = this.url;
    }

    disconnect() {
        URL.revokeObjectURL(this.url);
    }
});

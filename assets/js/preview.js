oc.registerControl('dynamicpdf-pdf-preview', class extends oc.ControlBase {
    async connect() {
        const blob = await (await fetch('data:application/pdf;base64,' + this.element.dataset.pdf)).blob();

        this.url = URL.createObjectURL(blob);
        this.element.src = this.url;
    }

    disconnect() {
        if (this.url) {
            URL.revokeObjectURL(this.url);
        }
    }
});

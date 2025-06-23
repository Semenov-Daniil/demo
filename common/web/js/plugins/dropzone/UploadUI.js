class UploadUI {
    static addErrors(file, errors) {
        const $node = $(file.previewElement).find("[data-dz-errormessage]");
        $node.html("");
        errors.forEach((error) => $node.append(`${error}<br>`));
    }

    static addError(file, error) {
        this.addErrors(file, [error]);
    }

    static setProgress(file, progress) {
        const $progress = $(file.previewElement).find("[data-dz-uploadprogress]");
        $progress.parent().removeClass("d-none");
        $progress.css("width", `${progress}%`).prop("aria-valuenow", progress);
    }

    static toggleUploadButton(state) {
        const $button = $(".btn-upload-file");
        $button.find(".cnt-text").toggleClass("d-none", !state);
        $button.find(".cnt-load").toggleClass("d-none", state);
        $button.prop("disabled", !state);
    }

    static fadeInPreview(file) {
        const previewElement = file.previewElement;
        previewElement.style.opacity = 0;
        setTimeout(() => (previewElement.style.opacity = 1), 10);
    }

    static fadeOutPreview(file) {
        const $preview = $(file.previewElement);
        if (!$preview.hasClass("fade-out")) {
            $preview.addClass("fade-out");
            setTimeout(() => $preview.remove(), 300);
        }
    }
}
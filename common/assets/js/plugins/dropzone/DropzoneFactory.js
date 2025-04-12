class DropzoneFactory {
    constructor(form, uploadUI = UploadUI, uploadService = UploadService) {
        this.$form = $(form);
        this.uploadUI = uploadUI;
        this.uploadService = uploadService;
        this.promises = [];
    }

    create(options = {}) {
        const $previewNode = $(".dropzone-preview-list");
        $previewNode.removeClass("dropzone-preview-list d-none");
        const previewTemplate = $previewNode.parent().html();
        $previewNode.remove();

        const dropzone = new Dropzone(".dropzone", {
            url: this.$form.attr("action"),
            method: "post",
            previewTemplate: previewTemplate,
            previewsContainer: "#dropzone-preview",
            autoProcessQueue: false,
            uploadMultiple: false,
            parallelUploads: 1,
            maxFiles: 20,
            paramName: "files",
            maxFilesize: options.maxFileSize || 50,
            dictFileTooBig: "Файл слишком большой ({{filesize}}MB). Максимальный размер: {{maxFilesize}}MB.",
            cache: false,
            uploadUI: this.uploadUI,
            uploadService: this.uploadService,
            removedfile: (file) => {
                this.uploadUI.fadeOutPreview(file);
                return dropzone._updateMaxFilesReachedClass();
            },
        });

        this.attachEvents(dropzone);
        return dropzone;
    }

    attachEvents(dropzone) {
        dropzone.on("addedfile", (file) => this.uploadUI.fadeInPreview(file));
        dropzone.on("sending", (file, xhr, formData) => {
            this.uploadService.appendFormData(this.$form, formData);
            this.uploadUI.toggleUploadButton(false);
        });
        dropzone.on("success", (file, response) => {
            this.uploadService.handleSuccess(file, response, dropzone);
        });
        dropzone.on("error", (file, response, xhr) => {
            this.uploadService.handleError(file, response, xhr);
        });
        dropzone.on("queuecomplete", () => {
            dropzone.files.forEach((file) => {
                if (file.accepted) file.status = Dropzone.QUEUED;
            });
            this.uploadUI.toggleUploadButton(true);
        });
        dropzone.on("uploadprogress", (file, progress) => this.uploadUI.setProgress(file, progress));
    }
}

function dropzoneInit(form, options, uploadUI = UploadUI, uploadService = UploadService) {
    const factory = new DropzoneFactory(form, uploadUI, uploadService);
    const dropzone = factory.create(options);
    return { dropzone, uploadUI: factory.uploadUI };
}

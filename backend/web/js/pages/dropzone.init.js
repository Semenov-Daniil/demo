"use strict";

const addErrors = (file, errors) => {
    const node = $(file.previewElement).find("[data-dz-errormessage]");
    node.html('');

    for (let error of errors) {
        node.append(`${error}<br>`);
    }
}

const addError = (file, error) => {
    const node = $(file.previewElement).find("[data-dz-errormessage]");
    node.html('');
    node.append(`${error}<br>`);
}

const setProgress = (file, progress) => {
    const uploadProgress = $(file.previewElement).find("[data-dz-uploadprogress]");
    uploadProgress.parent().removeClass('d-none');
    if (uploadProgress) {
        uploadProgress.css('width',progress + '%');
        uploadProgress.prop('aria-valuenow', progress);
    }
}

function dropzoneInit(form, options) {
    const dropzonePreviewNode = $('#dropzone-preview-list');
    dropzonePreviewNode.removeAttr('id');
    dropzonePreviewNode.removeClass('d-none');
    
    const previewTemplate = dropzonePreviewNode.parent().html();
    
    dropzonePreviewNode.remove();

    const dropzone = new Dropzone('.dropzone', {
        url: $(form).attr('action'),
        method: "post",
        previewTemplate: previewTemplate,
        previewsContainer: "#dropzone-preview",
        autoProcessQueue: false,
        uploadMultiple: true,
        parallelUploads: 1,
        maxFiles: 20,
        paramName: "files",
        maxFilesize: (options?.maxFileSize ? options.maxFileSize : '50'),
        dictFileTooBig: "Файл слишком большой ({{filesize}}MB). Максимальный размер файла: {{maxFilesize}}MB.",
        cache: false,

        removedfile: function (file) {
            const previewElement = $(file.previewElement);

            if (!previewElement.hasClass('fade-out')) {
                previewElement.addClass("fade-out");
        
                setTimeout(() => {
                    this.removeFile(file); 
                }, 300);

                return false;
            }

            if (file.previewElement != null && file.previewElement.parentNode != null) {
                file.previewElement.parentNode.removeChild(file.previewElement);
            }

            return this._updateMaxFilesReachedClass();
        }
    });

    dropzone.on('addedfile', function (file) {
        const previewElement = file.previewElement;
        previewElement.style.opacity = 0;
        setTimeout(() => {
            previewElement.style.opacity = 1;
        }, 10);
    });

    dropzone.on('sending', function (file, xhr, formData) {
        (new FormData($(form)[0])).forEach((value, key) => {
            if (!formData.has(key)) {
                formData.append(key, value);
            }
        });

        $('.btn-upload-file').find('.cnt-text').addClass('d-none');
        $('.btn-upload-file').find('.cnt-load').removeClass('d-none');
        $('.btn-upload-file').prop('disabled', true);
    });

    dropzone.on("success", function(file, response) {
        // if (typeof response == "object") {
        //     if (!response.data.success) {
        //         if (!(response.data.errors === undefined || response.data.errors.length == 0)) {
        //             const fileErrors = response.data.errors.files.find(el => el.filename === file.name)?.errors;
        //             if (fileErrors) {
        //                 addErrors(file, fileErrors);
        //                 $(file.previewElement).find('[data-dz-uploadprogress]').removeClass('bg-success').addClass('bg-danger');
        //             } else {
        //                 this.removeFile(file);
        //             }
        //         }
        //     } else {
        //         this.removeFile(file);
        //     }
        // }
        if (typeof response === "object" && response.data) {
            if (!response.data.success) {
                const fileStatus = response.data.files[file.name];
                if (fileStatus && fileStatus.status === 'error') {
                    addErrors(file, fileStatus.errors);
                    $(file.previewElement).find('[data-dz-uploadprogress]').removeClass('bg-success').addClass('bg-danger');
                }
            } else {
                this.removeFile(file);
            }
        }
    });
    
    dropzone.on('error', function (file, response) {
        // if (typeof response == "object") {
        //     if (response.data.errors) {
        //         if (response.data.errors.message && typeof response.data.errors.message == 'string') {
        //             addError(file, response.data.errors.message);
        //         } else {
        //             const fileErrors = response.data.errors.files.find(el => el.filename === file.name)?.errors;
        //             if (fileErrors) {
        //                 addErrors(file, fileErrors);
        //             }
        //         }
        //     } 
        // } else {
        //     addError(file, (typeof response == "string" ? response : 'Не удалось сохранить файл.'));
        // }

        // $(file.previewElement).find('[data-dz-uploadprogress]').removeClass('bg-success').addClass('bg-danger');

        if (typeof response === "object" && response.data) {
            const fileStatus = response.data.files[file.name] || response.data.files['global'];
            if (fileStatus && fileStatus.errors) {
                addErrors(file, fileStatus.errors);
            }
        } else {
            addError(file, (typeof response === "string" ? response : 'Не удалось сохранить файл.'));
        }
        $(file.previewElement).find('[data-dz-uploadprogress]').removeClass('bg-success').addClass('bg-danger');
    });

    dropzone.on('queuecomplete', function (files) {
        for (let file of this.files) {
            if (file.accepted) {
                file.status = Dropzone.QUEUED;
            }
        }

        $('.btn-upload-file').find('.cnt-text').removeClass('d-none');
        $('.btn-upload-file').find('.cnt-load').addClass('d-none');
        $('.btn-upload-file').prop('disabled', false);
    });

    dropzone.on('uploadprogress', function (file, progress) {
        setProgress(file, progress);
    });

    return dropzone;
}

$(() => {

    const options = (typeof yiiOptions === 'undefined' ? {} : yiiOptions);

    const form = $('#upload-file-form');
    
    const dropzonePreviewNode = $('#dropzone-preview-list');
    dropzonePreviewNode.removeAttr('id');
    dropzonePreviewNode.removeClass('d-none');
    
    const previewTemplate = dropzonePreviewNode.parent().html();
    
    dropzonePreviewNode.remove();

    const dropzone = new Dropzone('.dropzone', {
        url: form.attr('action'),
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
        // forceFallback: true,

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

    if (dropzone instanceof Dropzone) {
        $('.btn-upload-file').on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
    
            for (let file of dropzone.files) {
                if (file.accepted) {
                    const previewElement = $(file.previewElement);
    
                    file.upload = {};
                    previewElement.find('[data-dz-uploadprogress]').addClass('d-none');
                    setProgress(file, 0);
     
                    previewElement.removeClass('dz-processing dz-error dz-complete');
                    previewElement.find("[data-dz-errormessage]")?.html('');
                    previewElement.find('[data-dz-uploadprogress]').removeClass('bg-danger').addClass('bg-success');
    
                    setTimeout(() => {
                        previewElement.find('[data-dz-uploadprogress]').removeClass('d-none');
                    }, 300);
                }
            }
    
            setTimeout(() => {
                dropzone.processQueue();
            }, 300);
        });

        dropzone.on('addedfile', function (file) {
            const previewElement = file.previewElement;
            previewElement.style.opacity = 0;
            setTimeout(() => {
                previewElement.style.opacity = 1;
            }, 10);
        });
    
        dropzone.on('sending', function (file, xhr, formData) {
            (new FormData(form[0])).forEach((value, key) => {
                if (!formData.has(key)) {
                    formData.append(key, value);
                }
            });
            
            if (!formData.has('dropzone')) {
                formData.append('dropzone', 1);
            }

            $('.btn-upload-file').find('.cnt-text').addClass('d-none');
            $('.btn-upload-file').find('.cnt-load').removeClass('d-none');
            $('.btn-upload-file').prop('disabled', true);
        });
    
        dropzone.on("success", function(file, response) {
            this.removeFile(file);
        });
        
        dropzone.on('error', function (file, message) {
            if (typeof message == "object" && message.files) {
                for (let responseData of message.files) {
                    if (file.name == responseData.filename) {
                        addErrors(file, responseData.errors);
                        break;
                    }
                }
            } else {
                addError(file, (typeof message == "string" ? message : 'Не удалось загрузить файл.'));
            }

            $(file.previewElement).find('[data-dz-uploadprogress]').removeClass('bg-success').addClass('bg-danger');
        });
    
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
    
        dropzone.on('complete', function (file) {
            if (file.accepted) {
                $.pjax.reload('#pjax-files');
                dropzone.processQueue();
            }
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
    
        const setProgress = (file, progress) => {
            const uploadProgress = $(file.previewElement).find("[data-dz-uploadprogress]");
            uploadProgress.parent().removeClass('d-none');
            if (uploadProgress) {
                uploadProgress.css('width',progress + '%');
                uploadProgress.prop('aria-valuenow', progress);
            }
        }
    } else {
        $('#pjax-upload-file').on('pjax:complete', function () {
            $.pjax.reload('#pjax-files');
        });
    }    
});




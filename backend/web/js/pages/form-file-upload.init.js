$(() => {
    
    const form = $('#upload-form');
    
    const dropzonePreviewNode = $('#dropzone-preview-list')[0];
    dropzonePreviewNode.id = "";
    dropzonePreviewNode.classList.remove('d-none');
    
    const previewTemplate = dropzonePreviewNode.parentNode.innerHTML;
    
    dropzonePreviewNode.parentNode.removeChild(dropzonePreviewNode);
    
    const dropzone = new Dropzone(form[0], {
        url: form.attr('action'),
        method: "post",
        previewTemplate: previewTemplate,
        previewsContainer: "#dropzone-preview",
        autoProcessQueue: false,
        uploadMultiple: true,
        parallelUploads: 1,
        maxFiles: 100,
        paramName: "files",
        // maxFilesize: 102400,
        maxFilesize: 20,
        dictFileTooBig: "Файл слишком большой ({{filesize}}MiB). Максимальный размер файла: {{maxFilesize}}MiB.",
        cache: false,

        removedfile: function (file) {
            const previewElement = file.previewElement;

            if (!previewElement.classList.contains('fade-out')) {
                previewElement.classList.add("fade-out");
        
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
    
    $('.btn-upload-file').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        for (let file of dropzone.files) {
            setProgress(file, 0);

            file.upload = {};

            file.previewElement.classList.remove('dz-processing', 'dz-error', 'dz-complete');

            const errorMessage = file.previewElement.querySelector("[data-dz-errormessage]");
            if (errorMessage) {
                errorMessage.textContent = '';
            }
        }

        setTimeout(() => {
            dropzone.processQueue();
        }, 500);
    });

    dropzone.on("successmultiple", function(files, response) {
        // this.removeAllFiles();
        // console.log(this.files);
    });

    dropzone.on("success", function(file, response) {
        this.removeFile(file);
    });
    

    dropzone.on('error', function (file, response) {
        let isError = false;

        if (typeof response !== "string") {
            for (let responseFile of response.files) {
                if (file.name == responseFile.filename) {
                    isError = true;
    
      
    
                    for (let node of file.previewElement.querySelectorAll("[data-dz-errormessage]")) {
                        for (let error of responseFile.errors) {
                            if (node.textContent) {
                                node.textContent = error;
                            } else {
                                node.textContent += error + '\n';
                            }
                        }
                    }
    
                    break;
                }
            }
        }

        // if (!isError) {
        //     this.removeFile(file);
        // }
    });

    dropzone.on('queuecomplete', function (files) {
        for (let file of this.files) {
            file.status = Dropzone.QUEUED;
        }
    });

    dropzone.on('complete', function (file) {
        $.pjax.reload('#pjax-files');
        dropzone.processQueue();
    })

    dropzone.on('uploadprogress', function (file, progress) {
        setProgress(file, progress);
    });

    dropzone.on('addedfile', function (file) {
        const previewElement = file.previewElement;
        previewElement.style.opacity = 0;
        setTimeout(() => {
            previewElement.style.opacity = 1;
        }, 10);
    });

    const setProgress = (file, progress) => {
        const uploadProgress = file.previewElement.querySelector("[data-dz-uploadprogress]");
        if (uploadProgress) {
            uploadProgress.style.width = progress + '%';
            uploadProgress.setAttribute('aria-valuenow', progress);
        }
    }
})




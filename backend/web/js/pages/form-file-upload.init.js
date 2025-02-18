$(() => {
    
    const form = $('#upload-form');
    
    const dropzonePreviewNode = $('#dropzone-preview-list')[0];
    dropzonePreviewNode.id = "";
    
    const previewTemplate = dropzonePreviewNode.parentNode.innerHTML;
    
    dropzonePreviewNode.parentNode.removeChild(dropzonePreviewNode);
    
    const dropzone = new Dropzone(form[0], {
        url: form.attr('action'),
        method: "post",
        previewTemplate: previewTemplate,
        previewsContainer: "#dropzone-preview",
        autoProcessQueue: false,
        uploadMultiple: true,
        parallelUploads: 100,
        maxFiles: 100,
        paramName: "files"
    });
    
    $('.btn-upload-file').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        dropzone.processQueue();
    });

    dropzone.on("successmultiple", function(files, response) {
        this.removeAllFiles();
    });

    dropzone.on('error', function (file, response) {
        let isError = false;

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

                file.status = Dropzone.QUEUED;

                break;
            }
        }

        if (!isError) {
            this.removeFile(file);
        }

    });

    dropzone.on('queuecomplete', function (files) {
        $.pjax.reload('#pjax-files');
    });

    dropzone.on('uploadprogress', function (file, progress) {
        console.log(file, progress);

        const uploadProgress = file.previewElement.querySelector("[data-dz-uploadprogress]");
        uploadProgress.style.width = progress + '%';
    });


})




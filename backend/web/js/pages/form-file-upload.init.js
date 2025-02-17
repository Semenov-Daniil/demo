$(() => {
    
    const form = $('#upload-form');

    console.log(form);
    
    let dropzonePreviewNode = document.querySelector("#dropzone-preview-list");
    dropzonePreviewNode.id = "";
    
    let previewTemplate = dropzonePreviewNode.parentNode.innerHTML;
    
    dropzonePreviewNode.parentNode.removeChild(dropzonePreviewNode);
    
    let dropzone = new Dropzone(form[0], {
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

    dropzone.on("sendingmultiple", function() {
        // Gets triggered when the form is actually being sent.
        // Hide the success button or the complete form.
        console.log('sendimg');
    });
    dropzone.on("successmultiple", function(files, response) {
        // Gets triggered when the files have successfully been sent.
        // Redirect user or notify of success.
        console.log('success');
        console.log(files);
    });
    dropzone.on("errormultiple", function(files, response) {
        // Gets triggered when there was an error sending the files.
        // Maybe show form again, and notify user of error
        console.log('err');
        console.log(files);

        console.log(response);
    });


})




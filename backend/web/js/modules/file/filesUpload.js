$(() => {
    const form = '#form-upload-files';
    const $form = $(form);
    const $pjaxFiles = $(pjaxFiles);

    let { dropzone, uploadUI } = dropzoneInit(form, options);

    dropzone.on("complete", async (file) => {
        if (file.accepted) {
            if (dropzone.getQueuedFiles().length > 0) {
                dropzone.processQueue();
            }

            if (dropzone.getQueuedFiles().length === 0) {
                uploadUI.toggleUploadButton(true);
            }
        }
    });

    $form
        .off('change', eventSelect)
        .on('change', eventSelect, () => {
            window.history.pushState({}, '', setEventParam(window.location.href, 'event', $(eventSelect).val()));

            $pjaxFiles
                .off('pjax:beforeSend')
                .on('pjax:beforeSend', () => CommonUtils.showLoadingPlaceholderTable(pjaxFiles, 'Файлы'));

            updateFilesList().then(() => {
                $pjaxFiles.off('pjax:beforeSend');
            });

            if (sourceSSE) sourceSSE.forEach((el, inx) => {
                el.close();
            });

            sourceSSE = [];
            
            if ($(eventSelect).val()) {
                sourceSSE.push(CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${url}/sse-data-updates`, 'event', $(eventSelect).val()), updateFilesList));
                sourceSSE.push(CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${urlModule}/sse-data-updates`, 'event', $(eventSelect).val()), updateModuleSelect));
            }

            loadChoicesDate(setEventParam(`${window.location.origin}${url}/all-modules`, 'event', $(eventSelect).val()), 'directories-select');

            $(eventSelect).removeClass('is-valid is-invalid');
        });

    $form.on('beforeSubmit', (e) => {
        e.preventDefault();
        e.stopPropagation();

        dropzone.files.forEach((file) => {
            if (file.accepted) {
                const $preview = $(file.previewElement);
                file.upload = {};
                $preview.find("[data-dz-uploadprogress]").addClass("d-none");
                uploadUI.setProgress(file, 0);
                $preview.removeClass("dz-processing dz-error dz-complete");
                $preview.find("[data-dz-errormessage]")?.html("");
                $preview.find("[data-dz-uploadprogress]").removeClass("bg-danger").addClass("bg-success");
                setTimeout(() => $preview.find("[data-dz-uploadprogress]").removeClass("d-none"), 300);
            }
        });

        setTimeout(() => dropzone.processQueue(), 300);
        return false;
    });

    const updateEventSelect = () => {
        let currentEvent = $(eventSelect).val();

        loadChoicesDate(`${url}/all-events`, 'events-select');

        if (currentEvent != $(eventSelect).val()) {
            $(eventSelect).trigger('change');
        }
        
        updateFilesList();
    };

    CommonUtils.connectDataSSE(`${urlEvent}/sse-data-updates`, updateEventSelect);
});
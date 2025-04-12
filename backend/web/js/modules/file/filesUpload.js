$(() => {
    const pjaxUploadForm = '#pjax-upload-form';
    const $pjaxUploadForm = $(pjaxUploadForm);
    const formUploadFiles = '#form-upload-files';
    const pjaxFiles = '#pjax-files';
    const eventSelect = '#events-select';
    const paramQueryEvent = () => ($(eventSelect).val() ? `?event=${$(eventSelect).val()}` : '');

    let pjaxQueue = Promise.resolve();
    const reloadPjaxDebounced = CommonUtils.debounce(async () => {
        pjaxQueue = pjaxQueue.then(async () => {
            await CommonUtils.reloadPjax(pjaxFiles, `${url}/list-files${paramQueryEvent()}`);
        }).catch(error => {
            return Promise.resolve();
        });
        return pjaxQueue;
    }, 500);

    let { dropzone, uploadUI } = dropzoneInit(formUploadFiles, options);

    dropzone.on("complete", async (file) => {
        if (file.accepted) {
            if (dropzone.getQueuedFiles().length > 0) {
                dropzone.processQueue();
            }

            try {
                await CommonUtils.getFlashMessages();
                await reloadPjaxDebounced();
            } catch (error) {

            }

            if (dropzone.getQueuedFiles().length === 0) {
                await pjaxQueue;
                uploadUI.toggleUploadButton(true);
            }
        }
    });

    $pjaxUploadForm
        .off('change', eventSelect)
        .on('change', eventSelect, async () => {
            $(pjaxFiles)
                .off('pjax:beforeSend')
                .on('pjax:beforeSend', () => CommonUtils.showLoadingPlaceholderTable(pjaxFiles, 'Файлы'))
                .off('pjax:end')
                .on('pjax:end', () => window.history.pushState({}, '', `file${paramQueryEvent()}`));

            await CommonUtils.reloadPjax(pjaxUploadForm, `${url}/upload-form${paramQueryEvent()}`);
            await CommonUtils.reloadPjax(pjaxFiles, `${url}/list-files${paramQueryEvent()}`);

            $(pjaxFiles)
                .off('pjax:beforeSend');
        });

    $pjaxUploadForm
        .off('beforeSubmit')
        .on('beforeSubmit', (e) => {
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

    $pjaxUploadForm
        .off('pjax:end')
        .on('pjax:end', () => {
            for (let select of $pjaxUploadForm.find('select[data-choices]')) {
                CommonUtils.inputChoiceInit(select);
            }
        });
});
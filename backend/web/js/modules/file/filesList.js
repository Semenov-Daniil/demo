$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-files', 'filesCheckedItems');

    const pjaxFiles = '#pjax-files';
    const $pjaxFiles = $(pjaxFiles);

    const actionButtonClasses = ['.btn-delete-selected-files'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('files_all', 'files[]', actionButtonClasses);

    if (sourceSSE) sourceSSE.forEach((el, inx) => {
        el.close();
    });

    sourceSSE = [];
    
    if ($(eventSelect).val()) {
        sourceSSE.push(CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${url}/sse-data-updates`, 'event', $(eventSelect).val()), updateFilesList));
        sourceSSE.push(CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${urlModule}/sse-data-updates`, 'event', $(eventSelect).val()), updateModuleSelect));
    }

    $pjaxFiles
        .off('click', '.btn-select-all-files')
        .on('click', '.btn-select-all-files', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $pjaxFiles
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            const $button = $(this);

            CommonUtils.performAjax({
                url: `${url}/delete-files?id=${$(this).data('id')}`,
                method: 'DELETE',
                beforeSend() {
                    CommonUtils.toggleButtonState($button, true);
                },
                success(data) {
                    if (data.success) {
                        updateFilesList();
                    }
                },
                complete() {
                    if ($button) {
                        CommonUtils.toggleButtonState($button, false);
                    }
                }
            });
        });

    $pjaxFiles
        .off('click', '.btn-delete-selected-files')
        .on('click', '.btn-delete-selected-files', function() {
            const files = CommonUtils.getSelectedCheckboxes('files[]');
            const $button = $(this);

            if (files.length) {
                CommonUtils.performAjax({
                    url: `${url}/delete-files`,
                    method: 'DELETE',
                    data: { files },
                    beforeSend() {
                        CommonUtils.toggleButtonState($button, true);
                        files.forEach(el => {
                            CommonUtils.toggleButtonState($(`.btn-delete[data-id="${el}"]`), true);
                        });
                    },
                    success(data) {
                        if (data.success) {
                            updateFilesList();
                        }
                    },
                    complete() {
                        if ($button) {
                            CommonUtils.toggleButtonState($button, false);
                        }
                        files.forEach(el => {
                            let $btnDelete = $(`.btn-delete[data-id="${el}"]`)
                            if ($btnDelete) {
                                CommonUtils.toggleButtonState($btnDelete, false);
                            }
                        });
                    }
                });
            }
        });
    
    $pjaxFiles
        .on('change', checkboxManager.checkboxSelector, () => updateCheckboxState());
    
    $pjaxFiles
        .on('change', checkboxManager.allCheckboxSelector, () => updateCheckboxState());
        
    $pjaxFiles
        .on('pjax:complete', () => updateCheckboxState());

    updateCheckboxState();
})
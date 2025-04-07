$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-files', 'filesCheckedItems');

    const pjaxFiles = '#pjax-files';
    const $pjaxFiles = $(pjaxFiles);
    const eventSelect = '#events-select';
    const paramQueryEvent = () => ($(eventSelect).val() ? `?event=${$(eventSelect).val()}` : '');

    const actionButtonClasses = ['.btn-delete-selected-files'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('files_all', 'files[]', actionButtonClasses);

    $pjaxFiles
        .off('click', '.btn-select-all-files')
        .on('click', '.btn-select-all-files', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $pjaxFiles
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            CommonUtils.performAjax({
                url: `${url}/delete-files?id=${$(this).data('id')}`,
                method: 'DELETE',
                success(data) {
                    if (data.success) {
                        CommonUtils.reloadPjax(pjaxFiles, `${url}/list-files${paramQueryEvent()}`);
                    }
                },
            });
        });

    $pjaxFiles
        .off('click', '.btn-delete-selected-files')
        .on('click', '.btn-delete-selected-files', () => {
            const files = CommonUtils.getSelectedCheckboxes('files[]');

            if (files.length) {
                CommonUtils.performAjax({
                    url: `${url}/delete-files`,
                    method: 'DELETE',
                    data: { files },
                    success(data) {
                        if (data.success) {
                            CommonUtils.reloadPjax(pjaxFiles, `${url}/list-files${paramQueryEvent()}`);
                        }
                    },
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
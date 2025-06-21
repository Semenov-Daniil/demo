$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-modules', 'modulesCheckedItems');

    const $pjaxModules = $('#pjax-modules');
    const eventSelect = '#events-select';
    const paramQueryEvent = () => ($(eventSelect).val() ? `?event=${$(eventSelect).val()}` : '');

    const actionButtonClasses = ['.btn-delete-selected-modules', '.btn-clear-selected-modules'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('modules_all', 'modules[]', actionButtonClasses);

    if ($(eventSelect).val()) {
        if (sourceSSE) sourceSSE.close();
        sourceSSE = CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${url}/sse-data-updates`, $(eventSelect).val()), updateModulesList);
    }

    function toggleCheckboxState($checkbox, status) {
        $checkbox.next('.label-badge').find('.badge')
            .toggleClass('bg-success', status)
            .toggleClass('bg-dark-subtle text-body', !status)
            .html(status ? 'Онлайн' : 'Офлайн');
    }

    $pjaxModules
        .off('change', 'input[name="status"]')
        .on('change', 'input[name="status"]', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const checkbox = $(this);
            const currentStatus = checkbox.prop('checked');
            toggleCheckboxState(checkbox, currentStatus);
            // checkbox.prop('checked', !checkbox.prop('checked'));

            CommonUtils.performAjax({
                url: `${url}/change-status-module?id=${checkbox.data('id')}`,
                method: 'PATH',
                dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify({
                    newStatus: currentStatus ? 1 : 0,
                }),
                success(data) {
                    if (data.module.status != currentStatus) {
                        checkbox.prop('checked', data.module.status);
                        toggleCheckboxState(checkbox, data.module.status);
                    }
                },
                error() {
                    checkbox.prop('checked', !currentStatus);
                    toggleCheckboxState(checkbox, !currentStatus);
                },
            });
        });

    $pjaxModules
        .off('click', '.btn-select-all-modules')
        .on('click', '.btn-select-all-modules', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $pjaxModules
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            const $button = $(this);
            CommonUtils.performAjax({
                url: `${url}/delete-modules?id=${$(this).data('id')}`,
                method: 'DELETE',
                beforeSend() {
                    CommonUtils.toggleButtonState($button, true);
                },
                success(data) {
                    if (data.success) {
                        updateModulesList()
                    }
                },
                complete() {
                    if ($button) {
                        CommonUtils.toggleButtonState($button, false);
                    }
                }
            });
        });

    $pjaxModules
        .off('click', '.btn-delete-selected-modules')
        .on('click', '.btn-delete-selected-modules', function() {
            const modules = CommonUtils.getSelectedCheckboxes('modules[]');
            const $button = $(this);

            if (modules.length) {
                CommonUtils.performAjax({
                    url: `${url}/delete-modules`,
                    method: 'DELETE',
                    data: { modules },
                    beforeSend() {
                        CommonUtils.toggleButtonState($button, true);
                        modules.forEach(el => {
                            CommonUtils.toggleButtonState($(`.btn-delete[data-id="${el}"]`), true);
                        });
                    },
                    success(data) {
                        if (data.success) {
                            updateModulesList();
                        }
                    },
                    complete() {
                        if ($button) {
                            CommonUtils.toggleButtonState($button, false);
                        }
                        modules.forEach(el => {
                            let $btnDelete = $(`.btn-delete[data-id="${el}"]`)
                            if ($btnDelete) {
                                CommonUtils.toggleButtonState($btnDelete, false);
                            }
                        });
                    }
                });
            }
        });

    $pjaxModules
        .off('click', '.btn-clear')
        .on('click', '.btn-clear', function() {
            const $button = $(this);
            CommonUtils.performAjax({
                url: `${url}/clear-modules?id=${$(this).data('id')}`,
                method: 'PATH',
                beforeSend() {
                    CommonUtils.toggleButtonState($button, true);
                },
                complete() {
                    CommonUtils.toggleButtonState($button, false);
                }
            });
        });

    $pjaxModules
        .off('click', '.btn-clear-selected-modules')
        .on('click', '.btn-clear-selected-modules', function() {
            const modules = CommonUtils.getSelectedCheckboxes('modules[]');
            const $button = $(this);

            if (modules.length) {
                CommonUtils.performAjax({
                    url: `${url}/clear-modules`,
                    method: 'PATH',
                      data: { modules },
                    beforeSend() {
                        CommonUtils.toggleButtonState($button, true);
                        modules.forEach(el => {
                            CommonUtils.toggleButtonState($(`.btn-clear[data-id="${el}"]`), true);
                        });
                    },
                    complete() {
                        if ($button) {
                            CommonUtils.toggleButtonState($button, false);
                        }
                        modules.forEach(el => {
                            let $btnDelete = $(`.btn-clear[data-id="${el}"]`)
                            if ($btnDelete) {
                                CommonUtils.toggleButtonState($btnDelete, false);
                            }
                        });
                    }
                });
            }
        });
    
    $pjaxModules
        .on('change', checkboxManager.checkboxSelector, () => updateCheckboxState());
    
    $pjaxModules
        .on('change', checkboxManager.allCheckboxSelector, () => updateCheckboxState());
        
    $pjaxModules
        .on('pjax:complete', () => updateCheckboxState());

    updateCheckboxState();
})
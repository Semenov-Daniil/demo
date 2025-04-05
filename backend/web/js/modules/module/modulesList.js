$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-modules', 'modulesCheckedItems');

    const pjaxModulesId = '#pjax-modules';
    const eventSelect = '#events-select';
    const paramQueryEvent = () => ($(eventSelect).val() ? `?event=${$(eventSelect).val()}` : '');

    const actionButtonClasses = ['.btn-delete-selected-modules', '.btn-clear-selected-modules'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('modules_all', 'modules[]', actionButtonClasses);

    function toggleCheckboxState($checkbox, status) {
        $checkbox.next('.label-badge').find('.badge')
            .toggleClass('bg-success', status)
            .toggleClass('bg-dark-subtle text-body', !status)
            .html(status ? 'Онлайн' : 'Офлайн');
    }

    $(pjaxModulesId)
        .off('change', 'input[name="status"]')
        .on('change', 'input[name="status"]', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const checkbox = $(this);
            checkbox.prop('checked', !checkbox.prop('checked'));

            CommonUtils.performAjax({
                url: `${url}/change-status-module?id=${checkbox.data('id')}`,
                method: 'PATH',
                dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify({
                    newStatus: checkbox.prop('checked') ? 0 : 1,
                }),
                success(data) {
                    if (data.success) {
                        checkbox.prop('checked', data.module.status);
                        toggleCheckboxState(checkbox, data.module.status);
                    }
                },
            });
        });

    $(pjaxModulesId)
        .off('click', '.btn-select-all-modules')
        .on('click', '.btn-select-all-modules', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $(pjaxModulesId)
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            CommonUtils.performAjax({
                url: `${url}/delete-modules?id=${$(this).data('id')}`,
                method: 'DELETE',
                success(data) {
                    if (data.success) {
                        CommonUtils.reloadPjax(pjaxModulesId, `${url}/list-modules${paramQueryEvent()}`);
                    }
                },
            });
        });

    $(pjaxModulesId)
        .off('click', '.btn-delete-selected-modules')
        .on('click', '.btn-delete-selected-modules', () => {
            const modules = CommonUtils.getSelectedCheckboxes('modules[]');

            if (modules.length) {
                CommonUtils.performAjax({
                    url: `${url}/delete-modules`,
                    method: 'DELETE',
                    data: { modules },
                    success(data) {
                        if (data.success) {
                            CommonUtils.reloadPjax(pjaxModulesId, `${url}/list-modules${paramQueryEvent()}`);
                        }
                    },
                });
            }
        });

    $(pjaxModulesId)
        .off('click', '.btn-clear')
        .on('click', '.btn-clear', function() {
            CommonUtils.performAjax({
                url: `${url}/clear-modules?id=${$(this).data('id')}`,
                method: 'PATH',
            });
        });

    $(pjaxModulesId)
        .off('click', '.btn-clear-selected-modules')
        .on('click', '.btn-clear-selected-modules', () => {
            const modules = CommonUtils.getSelectedCheckboxes('modules[]');

            if (modules.length) {
                CommonUtils.performAjax({
                    url: `${url}/clear-modules`,
                    method: 'PATH',
                    data: { modules }
                });
            }
        });
    
    $(pjaxModulesId)
        .on('change', checkboxManager.checkboxSelector, () => updateCheckboxState());
    
    $(pjaxModulesId)
        .on('change', checkboxManager.allCheckboxSelector, () => updateCheckboxState());
        
    $(pjaxModulesId)
        .on('pjax:complete', () => updateCheckboxState());

    updateCheckboxState();
})
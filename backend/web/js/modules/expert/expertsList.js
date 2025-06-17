$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-experts', 'expertsCheckedItems');

    const $pjaxExperts = $('#pjax-experts');
    const $modalUpdateExpert = $('#modal-update-expert');

    const actionButtonClasses = ['.btn-delete-selected-experts'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('experts_all', 'experts[]', actionButtonClasses);

    CommonUtils.connectDataSSE(`${url}/sse-data-updates`, reloadPjaxDebounced, pjaxExperts, updateUrl());

    $pjaxExperts
        .off('click', '.btn-select-all-experts')
        .on('click', '.btn-select-all-experts', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $pjaxExperts
        .off('click', '.btn-update')
        .on('click', '.btn-update', function() {
            $modalUpdateExpert.find('.modal-body').load(`${url}/update-expert?id=${$(this).data('id')}`, () => {
                $('#modal-update-expert').modal('show');
            });
        });

    $modalUpdateExpert
        .off('beforeSubmit', '#form-update-expert')
        .on('beforeSubmit', '#form-update-expert', function (e) {
            e.preventDefault();
            const $form = $(this);

            CommonUtils.performAjax({
                url: $form.attr('action'),
                method: 'PATCH',
                data: $form.serialize(),
                beforeSend: () => CommonUtils.toggleButtonState($('.btn-update-expert'), true),
                success(data) {
                    if (data.success) {
                        $modalUpdateExpert.modal('hide');
                        reloadPjaxDebounced(pjaxExperts, updateUrl());
                    } else if (data.errors) {
                        $form.yiiActiveForm('updateMessages', data.errors, true);
                    }
                },
                complete: () => {
                    CommonUtils.toggleButtonState($('.btn-update-expert'), false);
                },
            });

            return false;
        });

    $pjaxExperts
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            const $button = $(this);
            CommonUtils.performAjax({
                url: `${url}/delete-experts?id=${$button.data('id')}`,
                method: 'DELETE',
                beforeSend() {
                    CommonUtils.toggleButtonState($button, true);
                },
                success(data) {
                    if (data.success) {
                        reloadPjaxDebounced(pjaxExperts, updateUrl());
                    }
                },
                complete() {
                    if ($button) {
                        CommonUtils.toggleButtonState($button, false);
                    }
                }
            });
        });

    $pjaxExperts
        .off('click', '.btn-delete-selected-experts')
        .on('click', '.btn-delete-selected-experts', function() {
            const experts = CommonUtils.getSelectedCheckboxes('experts[]');
            const $button = $(this);

            experts.forEach(el => {
                CommonUtils.toggleButtonState($(`.btn-delete[data-id="${el}"]`), true);
            });

            if (experts.length) {
                CommonUtils.performAjax({
                    url: `${url}/delete-experts`,
                    method: 'DELETE',
                    data: { experts },
                    beforeSend() {
                        CommonUtils.toggleButtonState($button, true);
                    },
                    success(data) {
                        if (data.success) {
                            reloadPjaxDebounced(pjaxExperts, updateUrl());
                        }
                    },
                    complete() {
                        if ($button) {
                            CommonUtils.toggleButtonState($button, false);
                        }
                        experts.forEach(el => {
                            let $btnDelete = $(`.btn-delete[data-id="${el}"]`)
                            if ($btnDelete) {
                                CommonUtils.toggleButtonState($btnDelete, false);
                            }
                        });
                    }
                });
            }
        });
    
    $pjaxExperts
        .on('change', checkboxManager.checkboxSelector, () => updateCheckboxState());
    
    $pjaxExperts
        .on('change', checkboxManager.allCheckboxSelector, () => updateCheckboxState());
        
    $pjaxExperts
        .on('pjax:complete', () => updateCheckboxState());

    updateCheckboxState();
})
$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-experts', 'expertsCheckedItems');

    const $pjaxExperts = $('#pjax-experts');
    const $modalUpdateExpert = $('#modal-update-expert');

    const actionButtonClasses = ['.btn-delete-selected-experts'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('experts_all', 'experts[]', actionButtonClasses);

    $pjaxExperts
        .off('click', '.btn-select-all-experts')
        .on('click', '.btn-select-all-experts', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $pjaxExperts
        .off('click', '.btn-update')
        .on('click', '.btn-update', function() {
            $modalUpdateExpert.find('.modal-body').load(`/expert/update-expert?id=${$(this).data('id')}`, () => {
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
                        CommonUtils.reloadPjax('#pjax-experts', '/expert/list-experts');
                    } else if (data.errors) {
                        $form.yiiActiveForm('updateMessages', data.errors, true);
                    }
                },
                complete: () => {
                    CommonUtils.toggleButtonState($('.btn-update-expert'), false);
                    CommonUtils.getFlashMessages();
                },
            });

            return false;
        });

    $pjaxExperts
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            CommonUtils.performAjax({
                url: `/expert/delete-experts?id=${$(this).data('id')}`,
                method: 'DELETE',
                success(data) {
                    if (data.success) {
                        CommonUtils.reloadPjax('#pjax-experts', '/expert/list-experts');
                    }
                },
            });
        });

    $pjaxExperts
        .off('click', '.btn-delete-selected-experts')
        .on('click', '.btn-delete-selected-experts', () => {
            const experts = CommonUtils.getSelectedCheckboxes('experts[]');

            if (experts.length) {
                CommonUtils.performAjax({
                    url: `/expert/delete-experts`,
                    method: 'DELETE',
                    data: { experts },
                    success(data) {
                        if (data.success) {
                            CommonUtils.reloadPjax('#pjax-experts', '/expert/list-experts');
                        }
                    },
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
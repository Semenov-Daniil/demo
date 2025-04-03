$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-events', 'eventsCheckedItems');

    const $pjaxEvents = $('#pjax-events');
    const $modalUpdateEvent = $('#modal-update-event');

    const actionButtonClasses = ['.btn-delete-selected-events'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('events_all', 'events[]', actionButtonClasses);

    $pjaxEvents
        .off('click', '.btn-select-all-events')
        .on('click', '.btn-select-all-events', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $pjaxEvents
        .off('click', '.btn-update')
        .on('click', '.btn-update', function() {
            $modalUpdateEvent.find('.modal-body').load(`/expert/update-event?id=${$(this).data('id')}`, () => {
                CommonUtils.inputChoiceInit($modalUpdateEvent.find('select[data-choices]')[0]);
                $modalUpdateEvent.modal('show');
            });
        });

    $modalUpdateEvent
        .off('beforeSubmit', '#form-update-event')
        .on('beforeSubmit', '#form-update-event', function (e) {
            e.preventDefault();
            const $form = $(this);

            CommonUtils.performAjax({
                url: $form.attr('action'),
                method: 'PATCH',
                data: $form.serialize(),
                beforeSend: () => CommonUtils.toggleButtonState($('.btn-update-event'), true),
                success(data) {
                    if (data.success) {
                        $modalUpdateEvent.modal('hide');
                        CommonUtils.reloadPjax('#pjax-events', '/expert/list-events');
                    } else if (data.errors) {
                        $form.yiiActiveForm('updateMessages', data.errors, true);
                    }
                },
                complete: () => {
                    CommonUtils.toggleButtonState($('.btn-update-event'), false)
                    CommonUtils.getFlashMessages();
                },
            });

            return false;
        });

    $pjaxEvents
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            CommonUtils.performAjax({
                url: `/expert/delete-events?id=${$(this).data('id')}`,
                method: 'DELETE',
                success(data) {
                    if (data.success) {
                        CommonUtils.reloadPjax('#pjax-events', '/expert/list-events');
                    }
                },
            });
        });

    $pjaxEvents
        .off('click', '.btn-delete-selected-events')
        .on('click', '.btn-delete-selected-events', () => {
            const events = CommonUtils.getSelectedCheckboxes('events[]');

            if (experts.length) {
                CommonUtils.performAjax({
                    url: `/expert/delete-events`,
                    method: 'DELETE',
                    data: { events },
                    success(data) {
                        if (data.success) {
                            CommonUtils.reloadPjax('#pjax-events', '/expert/list-events');
                        }
                    },
                });
            }
        });
    
    $pjaxEvents
        .on('change', checkboxManager.checkboxSelector, () => updateCheckboxState());
    
    $pjaxEvents
        .on('change', checkboxManager.allCheckboxSelector, () => updateCheckboxState());
        
    $pjaxEvents
        .on('pjax:complete', () => updateCheckboxState());

    updateCheckboxState();
})
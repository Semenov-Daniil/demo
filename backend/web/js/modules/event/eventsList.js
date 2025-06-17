$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-events', 'eventsCheckedItems');

    const $pjaxEvents = $(pjaxEvents);
    const $modalUpdateEvent = $('#modal-update-event');

    const actionButtonClasses = ['.btn-delete-selected-events'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('events_all', 'events[]', actionButtonClasses);

    CommonUtils.connectDataSSE(`${url}/sse-data-updates`, reloadPjaxDebounced, pjaxEvents, updateUrl());

    $pjaxEvents
        .off('click', '.btn-select-all-events')
        .on('click', '.btn-select-all-events', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $pjaxEvents
        .off('click', '.btn-update')
        .on('click', '.btn-update', function() {
            $modalUpdateEvent.find('.modal-body').load(`${url}/update-event?id=${$(this).data('id')}`, () => {
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
                        reloadPjaxDebounced(pjaxEvents, updateUrl());
                    } else if (data.errors) {
                        $form.yiiActiveForm('updateMessages', data.errors, true);
                    }
                },
                complete: () => {
                    CommonUtils.toggleButtonState($('.btn-update-event'), false)
                },
            });

            return false;
        });

    $pjaxEvents
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            CommonUtils.performAjax({
                url: `${url}/delete-events?id=${$(this).data('id')}`,
                method: 'DELETE',
                success(data) {
                    if (data.success) {
                        reloadPjaxDebounced(pjaxEvents, updateUrl());
                    }
                },
            });
        });

    $pjaxEvents
        .off('click', '.btn-delete-selected-events')
        .on('click', '.btn-delete-selected-events', () => {
            const events = CommonUtils.getSelectedCheckboxes('events[]');

            if (events.length) {
                CommonUtils.performAjax({
                    url: `${url}/delete-events`,
                    method: 'DELETE',
                    data: { events },
                    success(data) {
                        if (data.success) {
                            reloadPjaxDebounced(pjaxEvents, updateUrl());
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
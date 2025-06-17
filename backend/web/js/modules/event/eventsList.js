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
            const $button = $('.btn-update-event');

            CommonUtils.performAjax({
                url: $form.attr('action'),
                method: 'PATCH',
                data: $form.serialize(),
                beforeSend: () => CommonUtils.toggleButtonState($button, true),
                success(data) {
                    if (data.success) {
                        $modalUpdateEvent.modal('hide');
                        reloadPjaxDebounced(pjaxEvents, updateUrl());
                    } else if (data.errors) {
                        $form.yiiActiveForm('updateMessages', data.errors, true);
                    }
                },
                complete: () => {
                    CommonUtils.toggleButtonState($button, false)
                },
            });

            return false;
        });

    $pjaxEvents
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            const $button = $(this);
            CommonUtils.performAjax({
                url: `${url}/delete-events?id=${$button.data('id')}`,
                method: 'DELETE',
                beforeSend() {
                    CommonUtils.toggleButtonState($button, true);
                },
                success(data) {
                    if (data.success) {
                        reloadPjaxDebounced(pjaxEvents, updateUrl());
                    }
                },
                complete() {
                    if ($button) {
                        CommonUtils.toggleButtonState($button, false);
                    }
                }
            });
        });

    $pjaxEvents
        .off('click', '.btn-delete-selected-events')
        .on('click', '.btn-delete-selected-events', () => {
            const events = CommonUtils.getSelectedCheckboxes('events[]');
            const $button = $(this);

            events.forEach(el => {
                CommonUtils.toggleButtonState($(`.btn-delete[data-id="${el}"]`), true);
            });

            if (events.length) {
                CommonUtils.performAjax({
                    url: `${url}/delete-events`,
                    method: 'DELETE',
                    data: { events },
                    beforeSend() {
                        CommonUtils.toggleButtonState($button, true);
                    },
                    success(data) {
                        if (data.success) {
                            reloadPjaxDebounced(pjaxEvents, updateUrl());
                        }
                    },
                    complete() {
                        if ($button) {
                            CommonUtils.toggleButtonState($button, false);
                        }
                        events.forEach(el => {
                            let $btnDelete = $(`.btn-delete[data-id="${el}"]`)
                            if ($btnDelete) {
                                CommonUtils.toggleButtonState($btnDelete, false);
                            }
                        });
                    }
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
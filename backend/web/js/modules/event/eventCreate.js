$(() => {
    const $pjaxCreateEvent = $('#pjax-create-event');
    const pjaxEvents = '#pjax-events';

    $pjaxCreateEvent
        .off('beforeSubmit')
        .on('beforeSubmit', () => {
            CommonUtils.toggleButtonState($('.btn-create-event'), true);
        })
        .off('pjax:complete')
        .on('pjax:complete', () => {
            CommonUtils.toggleButtonState($('.btn-create-event'), false);
            CommonUtils.getFlashMessages();
            CommonUtils.reloadPjax(pjaxEvents, '/expert/list-events');
        });

    $pjaxCreateEvent
        .off('pjax:end')
        .on('pjax:end', () => {
            CommonUtils.inputStepInit($pjaxCreateEvent.find('input[data-step]')[0]);
            CommonUtils.inputChoiceInit($pjaxCreateEvent.find('select[data-choices]')[0]);
        })
});
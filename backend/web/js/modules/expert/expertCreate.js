$(() => {
    const $pjaxCreateExpert = $('#pjax-create-expert');
    const pjaxExperts = '#pjax-experts';

    $pjaxCreateExpert
        .off('beforeSubmit')
        .on('beforeSubmit', () => {
            CommonUtils.toggleButtonState($('.btn-create-expert'), true);
        })
        .off('pjax:complete')
        .on('pjax:complete', () => {
            CommonUtils.toggleButtonState($('.btn-create-expert'), false);
            CommonUtils.getFlashMessages();
            CommonUtils.reloadPjax(pjaxExperts, '/expert/all-experts');
        });
});
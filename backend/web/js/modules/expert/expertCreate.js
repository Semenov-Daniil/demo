$(() => {
    const $pjaxCreateExpert = $('#pjax-create-expert');
    // const pjaxExperts = '#pjax-experts';

    // const reloadPjaxDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500, pjaxExperts);

    $pjaxCreateExpert
        .off('beforeSubmit')
        .on('beforeSubmit', () => {
            CommonUtils.toggleButtonState($('.btn-create-expert'), true);
        })
        .off('pjax:complete')
        .on('pjax:complete', () => {
            CommonUtils.toggleButtonState($('.btn-create-expert'), false);
            reloadPjaxDebounced(pjaxExperts, updateUrl());
        });
});
$(() => {
    const $pjaxCreateModule = $('#pjax-create-module');
    const pjaxModules = '#pjax-modules';
    const eventSelect = '#events-select';
    const placeholderData = `
        <div class="row">
            <div>
                <div class="card">
                    <div class="card-header align-items-center d-flex position-relative">
                        <h4 class="card-title mb-0 flex-grow-1">Модули</h4>
                    </div>
                    <div class="card-body">
                        <div class="grid-view">
                            <div class="table-responsive table-card table-responsive placeholder-glow">
                                <div class="row gx-0 gap-2">
                                    <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                                    <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                    <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                    <div class="placeholder col m-2 p-3 rounded-1"></div>
                                </div>
                                <div class="row gx-0 gap-2">
                                    <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                                    <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                    <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                    <div class="placeholder col m-2 p-3 rounded-1"></div>
                                </div>
                                <div class="row gx-0 gap-2">
                                    <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                                    <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                    <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                    <div class="placeholder col m-2 p-3 rounded-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>     
    `;
    const paramQueryEvent = () => ($(eventSelect).val() ? `?event=${$(eventSelect).val()}` : '');

    $pjaxCreateModule
        .off('change', eventSelect)
        .on('change', eventSelect, () => {
            $(pjaxModules)
                .off('pjax:beforeSend')
                .on('pjax:beforeSend', () => $(pjaxModules).html(placeholderData))
                .off('pjax:end')
                .on('pjax:end', () => window.history.pushState({}, '', `module${paramQueryEvent()}`));

            CommonUtils.reloadPjax(pjaxModules, `${url}/list-modules${paramQueryEvent()}`);

            $(pjaxModules)
                .off('pjax:beforeSend');
        });

    $pjaxCreateModule
        .off('beforeSubmit')
        .on('beforeSubmit', () => {
            CommonUtils.toggleButtonState($('.btn-create-module'), true);
        })
        .off('pjax:complete')
        .on('pjax:complete', () => {
            CommonUtils.toggleButtonState($('.btn-create-module'), false);
            CommonUtils.getFlashMessages();
            CommonUtils.reloadPjax(pjaxModules, `${url}/list-modules${paramQueryEvent()}`);
        });

    $pjaxCreateModule
        .off('pjax:end')
        .on('pjax:end', () => {
            CommonUtils.inputChoiceInit($pjaxCreateModule.find('select[data-choices]')[0]);
        })
});
$(() => {
    const $pjaxCreateStudent = $('#pjax-create-student');
    const pjaxStudents = '#pjax-students';
    const eventSelect = '#events-select';
    const placeholderData = `
        <div class="row">
            <div>
                <div class="card">
                    <div class="card-header align-items-center d-flex position-relative">
                        <h4 class="card-title mb-0 flex-grow-1">Студенты</h4>
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

    $pjaxCreateStudent
        .off('change', eventSelect)
        .on('change', eventSelect, () => {
            const $select = $(eventSelect);
            const paramQuery = $select.val() ? `?event=${$select.val()}` : '';

            $(pjaxStudents)
                .off('pjax:beforeSend')
                .on('pjax:beforeSend', () => $(pjaxStudents).html(placeholderData))
                .off('pjax:end')
                .on('pjax:end', () => window.history.pushState({}, '', `student${paramQuery}`));

            CommonUtils.reloadPjax(pjaxStudents, `${url}/list-students${paramQuery}`);

            $(pjaxStudents)
                .off('pjax:beforeSend');
        });

    $pjaxCreateStudent
        .off('beforeSubmit')
        .on('beforeSubmit', () => {
            CommonUtils.toggleButtonState($('.btn-create-student'), true);
        })
        .off('pjax:complete')
        .on('pjax:complete', () => {
            CommonUtils.toggleButtonState($('.btn-create-student'), false);
            CommonUtils.getFlashMessages();
            CommonUtils.reloadPjax(pjaxStudents, `${url}/list-students${($(eventSelect).val() ? `?event=${$(eventSelect).val()}` : '')}`);
        });

    $pjaxCreateStudent
        .off('pjax:end')
        .on('pjax:end', () => {
            CommonUtils.inputChoiceInit($pjaxCreateStudent.find('select[data-choices]')[0]);
        })
});
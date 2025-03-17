$(() => {

    $('#events-select').on('change', function (event) {
        $.ajax({
            url: `/expert/all-modules${($(this).val() ? `?event=${$(this).val()}` : '')}`,
            type: 'GET',
            success: function(data) {
                $('#pjax-modules').html(data);
                changeActiveBtn();
            },
            error: function() {
            },
            beforeSend: function() {
                $('#pjax-modules').html(`
                    <div class="row">
                        <div>
                            <div class="card students-list">
                                <div class="card-header align-items-center d-flex position-relative ">
                                    <h4 class="card-title mb-0 flex-grow-1">Модули</h4>
                                </div>
                                <div class="card-body">
                                    <div id="w0" class="grid-view">
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
                `);
            },
        });
    });

    $('#pjax-create-module').on('beforeSubmit', '#form-create-module', function (event) {
        $('.btn-add-module').find('.cnt-text').addClass('d-none');
        $('.btn-add-module').find('.cnt-load').removeClass('d-none');
        $('.btn-add-module').prop('disabled', true);
    });

    $('#pjax-create-module').on('pjax:complete', function () {
        $('.btn-add-module').find('.cnt-load').addClass('d-none');
        $('.btn-add-module').find('.cnt-text').removeClass('d-none');
        $('.btn-add-module').prop('disabled', false);

        fetchFlashMessages();

        $.pjax.reload({
            url: `/expert/all-modules?event=${$('#events-select').find('option:selected').val()}`,
            container: '#pjax-modules',
            pushState: false,
            replace: false,
            timeout: 10000
        });
    })

    $('#pjax-modules').on('click', '.btn-select-all-modules', function (event) {
        let checked = JSON.parse(localStorage.getItem($('#pjax-modules').attr('id')) || '{}');

        let allCheckboxSelected = $('#pjax-modules .grid-view .cell-selected').find('input[type="checkbox"]:not(.select-on-check-all)');
        allCheckboxSelected.each((inx, el) => {
            checked[$(el).val()] = true;
        });

        $('input[name="modules_all"]').prop('checked', true);
        $('input[name="modules[]"]').prop('checked', true);

        $('.btn-delete-selected-modules').prop('disabled', false);
        $('.btn-clear-selected-modules').prop('disabled', false);

        localStorage.setItem($('#pjax-modules').attr('id'), JSON.stringify(checked));
    });

    $('#pjax-modules').on('change', 'input[name="modules_all"]', function() {
        let isChecked = $(this).is(':checked');
        $('input[name="modules[]"]').prop('checked', isChecked);
        $('.btn-delete-selected-modules').prop('disabled', !isChecked);
        $('.btn-clear-selected-modules').prop('disabled', !isChecked);
    });

    $('#pjax-modules').on('change', 'input[name="modules[]"]', function() {
        let checkedModules = $('input[name="modules[]"]:checked'),
            allModules = $('input[name="modules[]"]');

        $('input[name="modules_all"]').prop('checked', allModules.length === checkedModules.length);

        $('.btn-delete-selected-modules').prop('disabled', ($(this).is(':checked') ? false : (checkedModules.length === 0)));
        $('.btn-clear-selected-modules').prop('disabled', ($(this).is(':checked') ? false : (checkedModules.length === 0)));
    });

    $('#pjax-modules').on('change', 'input[name="status"]', function(event) {
        event.preventDefault();
        event.stopPropagation();

        const checkbox = $(this);
        checkbox.prop('checked', !checkbox.prop('checked'));
        
        $.ajax({
            url: '/expert/change-status-module',
            method: 'PATH',
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({
                id: checkbox.data('id'),
                newStatus: checkbox.prop('checked') ? 0 : 1,
            }),
            success(data) {
                if (data.data.success) {
                    checkbox.prop('checked', data.data.module.status);

                    if (data.data.module.status) {
                        checkbox.next('.label-badge').find('.badge').removeClass('bg-dark-subtle text-body');
                        checkbox.next('.label-badge').find('.badge').addClass('bg-success');
                        checkbox.next('.label-badge').find('.badge').html('Онлайн');
                    } else {
                        checkbox.next('.label-badge').find('.badge').removeClass('bg-success');
                        checkbox.next('.label-badge').find('.badge').addClass('bg-dark-subtle text-body');
                        checkbox.next('.label-badge').find('.badge').html('Офлайн');
                    }
                }
            },
            complete() {
                fetchFlashMessages();
            }
        });
    });

    $('#pjax-modules').on('click', '.btn-delete', function (event) {
        $.ajax({
            url: `/expert/delete-modules?id=${$(this).data('id')}`,
            method: 'DELETE',
            success (data) {
                if (data.data.success) {
                    $.pjax.reload({
                        url: `/expert/all-modules?event=${$('#events-select').val()}`,
                        container: '#pjax-modules',
                        pushState: false,
                        replace: false,
                        timeout: 10000
                    });
                }
            },
            error () {
                // location.reload();
            },
            complete () {
                fetchFlashMessages();
            }
        });
    });

    $('#pjax-modules').on('click', '.btn-delete-selected-modules', function (event) {
        let modules = [];

        $('input[name="modules[]"]').each((index, element) => {
            if ($(element).is(':checked')) {
                modules.push($(element).val());
            }
        });

        $.ajax({
            url: `/expert/delete-modules`,
            method: 'DELETE',
            data: {
                modules: modules
            },
            success (data) {
                if (data.data.success) {
                    $.pjax.reload({
                        url: `/expert/all-modules?event=${$('#events-select').val()}`,
                        container: '#pjax-modules',
                        pushState: false,
                        replace: false,
                        timeout: 10000
                    });
                }
            },
            error () {
                // location.reload();
            },
            complete () {
                fetchFlashMessages();
            }
        });
    });

    $('#pjax-modules').on('click', '.btn-clear', function (event) {
        $.ajax({
            url: `/expert/clear-modules?id=${$(this).data('id')}`,
            method: 'PATH',
            error (jqXHR, textStatus, errorThrown) {
                if (jqXHR.status == 500) {
                    location.reload();
                }
            },
            complete () {
                fetchFlashMessages();
            }
        });
    });

    $('#pjax-modules').on('click', '.btn-clear-selected-modules', function (event) {
        let modules = [];

        $('input[name="modules[]"]').each((index, element) => {
            if ($(element).is(':checked')) {
                modules.push($(element).val());
            }
        });

        $.ajax({
            url: `/expert/clear-modules`,
            method: 'PATH',
            data: {
                modules: modules
            },
            error (jqXHR, textStatus, errorThrown) {
                if (jqXHR.status == 500) {
                    location.reload();
                }
            },
            complete () {
                fetchFlashMessages();
            }
        });
    });

    $('#pjax-modules').on('pjax:complete', function (event) {
        changeActiveBtn();
    });

    function changeActiveBtn() {
        let checkedModules = $('input[name="modules[]"]:checked'),
        allModules = $('input[name="modules[]"]');

        $('input[name="modules_all"]').prop('checked', allModules.length === checkedModules.length);

        $('.btn-delete-selected-modules').prop('disabled', ($(this).is(':checked') ? false : (checkedModules.length === 0)));
        $('.btn-clear-selected-modules').prop('disabled', ($(this).is(':checked') ? false : (checkedModules.length === 0)));
    }

    changeActiveBtn();
})
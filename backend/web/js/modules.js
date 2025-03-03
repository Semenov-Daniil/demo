$(() => {

    $('#pjax-modules').on('click', '.btn-add-module', function (event) {
        $('.btn-add-module').find('.cnt-text').addClass('d-none');
        $('.btn-add-module').find('.cnt-load').removeClass('d-none');
        $('.btn-add-module').prop('disabled', true);

        $.ajax({
            url: 'create-module',
            method: 'POST',
            success (data) {
                $('#pjax-modules').html(data);
            },
            error (jqXHR, textStatus, errorThrown) {
                if (jqXHR.status == 500) {
                    location.reload();
                }
            },
            complete () {
                $('.btn-add-module').find('.cnt-load').addClass('d-none');
                $('.btn-add-module').find('.cnt-text').removeClass('d-none');
                $('.btn-add-module').prop('disabled', false);

                $('#pjax-modules').trigger('pjax:complete');
            }
        });
    });

    $('#pjax-modules').on('pjax:complete', function () {
        $('.btn-add-module').find('.cnt-load').addClass('d-none');
        $('.btn-add-module').find('.cnt-text').removeClass('d-none');
        $('.btn-add-module').prop('disabled', false);
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
            url: 'change-status-module',
            method: 'PATH',
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({
                id: checkbox.data('id'),
                status: checkbox.prop('checked') ? 0 : 1,
            }),
            success(data) {
                if (data.success) {
                    checkbox.prop('checked', data.module.status);

                    if (data.module.status) {
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
                $('#pjax-modules').trigger('pjax:complete');
            }
        });
    });

    $('#pjax-modules').on('click', '.btn-delete', function (event) {
        $.ajax({
            url: `delete-modules?id=${$(this).data('id')}`,
            method: 'DELETE',
            success (data) {
                $('#pjax-modules').html(data);
            },
            error () {
                location.reload();
            },
            complete () {
                $('#pjax-modules').trigger('pjax:complete');
            }
        });
    });

    $('#pjax-modules').on('click', '.btn-clear', function (event) {
        $.ajax({
            url: `clear-modules?id=${$(this).data('id')}`,
            method: 'PATH',
            success (data) {
                $('#pjax-modules').html(data);
            },
            error (jqXHR, textStatus, errorThrown) {
                if (jqXHR.status == 500) {
                    location.reload();
                }
            },
            complete () {
                $('#pjax-modules').trigger('pjax:complete');
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
            url: `delete-modules`,
            method: 'DELETE',
            data: {
                modules: modules
            },
            success (data) {
                $('#pjax-modules').html(data);
            },
            error () {
                location.reload();
            },
            complete () {
                $('#pjax-modules').trigger('pjax:complete');
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
            url: `clear-modules`,
            method: 'PATH',
            data: {
                modules: modules
            },
            success (data) {
                $('#pjax-modules').html(data);
            },
            error (jqXHR, textStatus, errorThrown) {
                if (jqXHR.status == 500) {
                    location.reload();
                }
            },
            complete () {
                $('#pjax-modules').trigger('pjax:complete');
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
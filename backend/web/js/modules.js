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
            error () {
                location.reload();
            },
            complete () {
                $('.btn-add-module').find('.cnt-load').addClass('d-none');
                $('.btn-add-module').find('.cnt-text').removeClass('d-none');
                $('.btn-add-module').prop('disabled', false);

                fetchFlashMessages();
            }
        });
    });

    $('#pjax-modules').on('click', '.btn-select-all-modules', function (event) {
        $('input[name="modules_all"]').prop('checked', true);
        $('input[name="modules[]"]').prop('checked', true);

        $('.btn-delete-selected-modules').prop('disabled', false);
    });

    $('#pjax-modules').on('change', 'input[name="modules_all"]', function() {
        let isChecked = $(this).is(':checked');
        $('input[name="modules[]"]').prop('checked', isChecked);
        $('.btn-delete-selected-modules').prop('disabled', !isChecked);
    });

    $('#pjax-modules').on('change', 'input[name="modules[]"]', function() {
        let checkedModules = $('input[name="modules[]"]:checked'),
            allModules = $('input[name="modules[]"]');

        $('input[name="modules_all"]').prop('checked', allModules.length === checkedModules.length);

        $('.btn-delete-selected-modules').prop('disabled', ($(this).is(':checked') ? false : (checkedModules.length === 0)));
    });

    $('#pjax-modules').on('click', 'input[name="status"]', function(event) {
        event.preventDefault();
        event.stopPropagation();

        const checkbox = $(this);
        
        $.ajax({
            url: 'change-status-module',
            method: 'PATH',
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({
                id: checkbox.data('id'),
                status: checkbox.prop('checked') ? 1 : 0,
            }),
            success(data) {
                if (data.success) {
                    checkbox.prop('checked', data.module.status);

                    if (data.module.status) {
                        checkbox.next('.badge').removeClass('bg-dark-subtle text-body');
                        checkbox.next('.badge').addClass('bg-success');
                        checkbox.next('.badge').html('Онлайн');
                    } else {
                        checkbox.next('.badge').removeClass('bg-success');
                        checkbox.next('.badge').addClass('bg-dark-subtle text-body');
                        checkbox.next('.badge').html('Офлайн');
                    }
                }
            },
            complete() {
                fetchFlashMessages();
            }
        });
    });
})
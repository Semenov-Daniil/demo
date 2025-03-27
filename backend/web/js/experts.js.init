$(() => {

    $('#pjax-create-expert').on('beforeSubmit', function (event) {
        $('.btn-create-expert').find('.cnt-text').addClass('d-none');
        $('.btn-create-expert').find('.cnt-load').removeClass('d-none');
        $('.btn-create-expert').prop('disabled', true);
    });

    $('#pjax-create-expert').on('pjax:complete', function (event) {
        $('.btn-create-expert').find('.cnt-text').removeClass('d-none');
        $('.btn-create-expert').find('.cnt-load').addClass('d-none');
        $('.btn-create-expert').prop('disabled', false);

        fetchFlashMessages();
    });

    $('#pjax-create-expert').on('pjax:complete', function (event) {
        $.pjax.reload({
            url: '/expert/all-experts',
            container: '#pjax-experts',
            pushState: false,
            replace: false,
            timeout: 10000
        });
    });

    $('#pjax-experts').on('click', '.btn-select-all-experts', function (event) {
        let localName = $('#pjax-experts').attr('id'),
            checked = JSON.parse(localStorage.getItem(localName) || '{}');

        let allCheckboxSelected = $('#pjax-experts .grid-view .cell-selected').find('input[type="checkbox"]:not(.select-on-check-all, :disabled)');
        allCheckboxSelected.each((inx, el) => {
            checked[$(el).val()] = true;
        });

        $('input[name="experts_all"]').prop('checked', true);
        $('input[name="experts[]"]').prop('checked', true);

        $('.btn-delete-selected-experts').prop('disabled', !allCheckboxSelected.length);

        localStorage.setItem(localName, JSON.stringify(checked));
    });

    $('#pjax-experts').on('change', 'input[name="experts_all"]', function() {
        let isChecked = $(this).is(':checked'),
            checkbox = $('input[name="experts[]"]:not(:disabled)');

        checkbox.prop('checked', isChecked);

        $('.btn-delete-selected-experts').prop('disabled', !(isChecked && checkbox.length));
    });

    $('#pjax-experts').on('change', 'input[name="experts[]"]', function() {
        let checkedExperts = $('input[name="experts[]"]:checked:not(:disabled)'),
            allExperts = $('input[name="experts[]"]:not(:disabled)');

        $('input[name="experts_all"]').prop('checked', allExperts.length === checkedExperts.length);

        $('.btn-delete-selected-experts').prop('disabled', ($(this).is(':checked') ? false : (checkedExperts.length === 0)));
    });

    $('#pjax-experts').on('click', '.btn-update', function (event) {
        $('#modal-update-expert').find('.modal-body').load(`/expert/update-expert?id=${$(this).data('id')}`, function (event) {
            $('#modal-update-expert').modal('show');
        });
    });

    $('#modal-update-expert').on('beforeSubmit', '#form-update-expert', function (event) {
        event.preventDefault();

        const form = $(this);

        $.ajax({
            url: form.attr('action'),
            method: 'PATCH',
            data: form.serialize(),
            beforeSend () {
                $('.btn-update-expert').find('.cnt-text').addClass('d-none');
                $('.btn-update-expert').find('.cnt-load').removeClass('d-none');
                $('.btn-update-expert').prop('disabled', true);
            },
            success (data) {
                if (data.success) {
                    $('#modal-update-expert').modal('hide');
                    $.pjax.reload({
                        url: '/expert/all-experts',
                        container: '#pjax-experts',
                        pushState: false,
                        replace: false,
                        timeout: 10000
                    });
                }

                $('#pjax-update-expert').html(data);
            },
            error () {
                // location.reload();
            },
            complete () {
                $('.btn-create-expert').find('.cnt-text').removeClass('d-none');
                $('.btn-create-expert').find('.cnt-load').addClass('d-none');
                $('.btn-create-expert').prop('disabled', false);

                fetchFlashMessages();
            }
        });

        return false;
    });

    $('#pjax-experts').on('click', '.btn-delete', function (event) {
        $.ajax({
            url: `/expert/delete-experts?id=${$(this).data('id')}`,
            method: 'DELETE',
            success (data) {
                $('#pjax-experts').html(data);
            },
            error () {
                location.reload();
            },
            complete () {
                $('#pjax-experts').trigger('pjax:complete');

                fetchFlashMessages();
            }
        });
    });

    $('#pjax-experts').on('click', '.btn-delete-selected-experts', function (event) {
        let experts = [];

        $('input[name="experts[]"]:not(:disabled)').each((index, element) => {
            if ($(element).is(':checked')) {
                experts.push($(element).val());
            }
        });

        $.ajax({
            url: `/expert/delete-experts`,
            method: 'DELETE',
            data: {
                experts: experts
            },
            success (data) {
                $('#pjax-experts').html(data);
            },
            error () {
                location.reload();
            },
            complete () {
                $('#pjax-experts').trigger('pjax:complete');

                fetchFlashMessages();
            }
        });
    });

    $('#pjax-experts').on('pjax:complete', function (event) {
        changeActiveBtn();
    });

    function changeActiveBtn() {
        let checkedExperts = $('input[name="experts[]"]:checked:not(:disabled)'),
        allExperts = $('input[name="experts[]"]:not(:disabled)');

        $('input[name="experts_all"]').prop('checked', (allExperts.length === checkedExperts.length && checkedExperts.length !== 0));

        $('.btn-delete-selected-experts').prop('disabled', !checkedExperts.length);
        // $('.btn-delete-selected-experts').prop('disabled', !(allExperts.length === checkedExperts.length && checkedExperts.length !== 0));
    }

    changeActiveBtn();
})
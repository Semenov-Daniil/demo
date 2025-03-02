$(() => {

    $('#pjax-create-expert').on('beforeSubmit', function (event) {
        $('.btn-add-expert').find('.cnt-text').addClass('d-none');
        $('.btn-add-expert').find('.cnt-load').removeClass('d-none');
        $('.btn-add-expert').prop('disabled', true);
    });

    $('#pjax-create-expert').on('pjax:complete', function (event) {
        $('.btn-add-expert').find('.cnt-text').removeClass('d-none');
        $('.btn-add-expert').find('.cnt-load').addClass('d-none');
        $('.btn-add-expert').prop('disabled', false);
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

        $('.btn-delete-selected-experts').prop('disabled', false);

        localStorage.setItem(localName, JSON.stringify(checked));
    });

    $('#pjax-experts').on('change', 'input[name="experts_all"]', function() {
        let isChecked = $(this).is(':checked');
        $('input[name="experts[]"]').prop('checked', isChecked);
        $('.btn-delete-selected-experts').prop('disabled', !isChecked);
    });

    $('#pjax-experts').on('change', 'input[name="experts[]"]', function() {
        let checkedModules = $('input[name="experts[]"]:checked:not(:disabled)'),
            allModules = $('input[name="experts[]"]:not(:disabled)');

        $('input[name="experts_all"]').prop('checked', allModules.length === checkedModules.length);

        $('.btn-delete-selected-experts').prop('disabled', ($(this).is(':checked') ? false : (checkedModules.length === 0)));
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
            }
        });
    });

    $('#pjax-experts').on('pjax:complete', function (event) {
        changeActiveBtn();
    });

    function changeActiveBtn() {
        let checkedExperts = $('input[name="experts[]"]:checked:not(:disabled)'),
        allExperts = $('input[name="experts[]"]:not(:disabled)');

        $('input[name="experts_all"]').prop('checked', allExperts.length === checkedExperts.length);

        $('.btn-delete-selected-experts').prop('disabled', ($(this).is(':checked') ? false : (checkedExperts.length === 0)));
    }

    changeActiveBtn();
})
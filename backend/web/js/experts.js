$(() => {

    $('#pjax-add-expert').on('beforeSubmit', function (event) {
        $('.btn-add-expert').find('.cnt-text').addClass('d-none');
        $('.btn-add-expert').find('.cnt-load').removeClass('d-none');
        $('.btn-add-expert').prop('disabled', true);
    });

    $('#pjax-add-expert').on('pjax:complete', function (event) {
        $('.btn-add-expert').find('.cnt-text').removeClass('d-none');
        $('.btn-add-expert').find('.cnt-load').addClass('d-none');
        $('.btn-add-expert').prop('disabled', false);
    });

    $('#pjax-add-expert').on('pjax:complete', function (event) {
        $.pjax.reload({
            url: '/expert/all-experts',
            container: '#pjax-experts',
            pushState: false,
            replace: false,
            timeout: 5000
        });
    });

    $('#pjax-experts').on('change', 'input[name="selection[]"], input[name="selection_all"]', function (event) {
        let expertsChecked = JSON.parse(localStorage.getItem('expertsChecked') || '{}');

        if ($(this).prop('checked')) {

            if (!$(this).hasClass('select-on-check-all')) {
                expertsChecked[$(this).val()] = true;
            }

            $('#collapseAllActions').collapse('show');
        } else {
            if (!$('#pjax-experts').find('input[type="checkbox"]:checked').length) {
                $('#collapseAllActions').collapse('hide');
            }

            delete expertsChecked[$(this).val()];
        }

        localStorage.setItem('expertsChecked', JSON.stringify(expertsChecked));
    });

    $('#pjax-experts').on('pjax:complete', function () {
        updateExpertsChecked();
    });

    function updateExpertsChecked() {
        let expertsChecked = JSON.parse(localStorage.getItem('expertsChecked') || '{}');
    
        $('#pjax-experts input[type="checkbox"]:not([name="selection_all"])').each(function() {
            if (expertsChecked[$(this).val()]) {
                $(this).prop('checked', true);
                $('#collapseAllActions').collapse('show');
            }
        });
    }

    updateExpertsChecked();
})
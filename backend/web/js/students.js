$(() => {
    
    $('#pjax-add-student').on('beforeSubmit', function (event) {
        $('.btn-add-expert').find('.cnt-text').addClass('d-none');
        $('.btn-add-expert').find('.cnt-load').removeClass('d-none');
        $('.btn-add-expert').prop('disabled', true);
    });

    $('#pjax-add-student').on('pjax:complete', function (event) {
        $('.btn-add-expert').find('.cnt-text').removeClass('d-none');
        $('.btn-add-expert').find('.cnt-load').addClass('d-none');
        $('.btn-add-expert').prop('disabled', false);
    });

    $('#pjax-add-student').on('pjax:complete', function (event) {
        $.pjax.reload({
            url: '/expert/all-students',
            container: '#pjax-students',
            pushState: false,
            replace: false,
            timeout: 5000
        });
    });

    $('#pjax-students').on('change', 'input[name="selection[]"], input[name="selection_all"]', function (event) {
        let studentsChecked = JSON.parse(localStorage.getItem('studentsChecked') || '{}');

        if ($(this).prop('checked')) {

            if (!$(this).hasClass('select-on-check-all')) {
                studentsChecked[$(this).val()] = true;
            }

            $('#collapseAllActions').collapse('show');
        } else {
            if (!$('#pjax-students').find('input[type="checkbox"]:checked').length) {
                $('#collapseAllActions').collapse('hide');
            }

            delete studentsChecked[$(this).val()];
        }

        localStorage.setItem('studentsChecked', JSON.stringify(studentsChecked));
    });

    $('#pjax-students').on('pjax:complete', function () {
        updateStudentsChecked();
    });

    function updateStudentsChecked() {
        let studentsChecked = JSON.parse(localStorage.getItem('studentsChecked') || '{}');
    
        $('#pjax-students input[type="checkbox"]:not([name="selection_all"])').each(function() {
            if (studentsChecked[$(this).val()]) {
                $(this).prop('checked', true);
                $('#collapseAllActions').collapse('show');
            }
        });
    }

    updateStudentsChecked();

})
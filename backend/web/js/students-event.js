$(() => {

    $('#pjax-create-student').on('beforeSubmit', '#add-student-form', function (event) {
        event.preventDefault();

        $('.btn-create-student').find('.cnt-text').addClass('d-none');
        $('.btn-create-student').find('.cnt-load').removeClass('d-none');
        $('.btn-create-student').prop('disabled', true);

        const form = $(this);

        let formData = form.serializeArray();
        formData.push({name: 'event', value: $('#events-select').find('option:selected').val()});

        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: $.param(formData),
            success(data) {
                $('#pjax-create-student').html(data);
                $('#pjax-create-student').trigger('pjax:complete');

                $('.btn-create-student').find('.cnt-text').removeClass('d-none');
                $('.btn-create-student').find('.cnt-load').addClass('d-none');
                $('.btn-create-student').prop('disabled', false);
            }
        });

        return false;
    });

    $('#pjax-create-student').on('pjax:complete', function (event) {
        $('.btn-create-student').find('.cnt-text').removeClass('d-none');
        $('.btn-create-student').find('.cnt-load').addClass('d-none');
        $('.btn-create-student').prop('disabled', false);
    });

    $('#pjax-create-student').on('pjax:complete', function (event) {
        $.pjax.reload({
            url: `/expert/all-students?event=${encodeURIComponent($('#events-select').find('option:selected').val())}`,
            container: '#pjax-students',
            pushState: false,
            replace: false,
            timeout: 10000
        });
    });

    $('#pjax-students').on('click', '.btn-select-all-students', function (event) {
        let localName = $('#pjax-students').attr('id'),
            checked = JSON.parse(localStorage.getItem(localName) || '{}');

        let allCheckboxSelected = $('#pjax-students .grid-view .cell-selected').find('input[type="checkbox"]:not(.select-on-check-all, :disabled)');
        allCheckboxSelected.each((inx, el) => {
            checked[$(el).val()] = true;
        });

        $('input[name="students_all"]').prop('checked', true);
        $('input[name="students[]"]').prop('checked', true);

        $('.btn-delete-selected-students').prop('disabled', !allCheckboxSelected.length);

        localStorage.setItem(localName, JSON.stringify(checked));
    });

    $('#pjax-students').on('change', 'input[name="students_all"]', function() {
        let isChecked = $(this).is(':checked');
        $('input[name="students[]"]').prop('checked', isChecked);
        $('.btn-delete-selected-students').prop('disabled', !isChecked);
    });

    $('#pjax-students').on('change', 'input[name="students[]"]', function() {
        let checkedStudents = $('input[name="students[]"]:checked:not(:disabled)'),
            allStudents = $('input[name="students[]"]:not(:disabled)');

        $('input[name="students_all"]').prop('checked', allStudents.length === checkedStudents.length);

        $('.btn-delete-selected-students').prop('disabled', ($(this).is(':checked') ? false : (checkedStudents.length === 0)));
    });

    $('#pjax-students').on('click', '.btn-delete', function (event) {
        console.log('test');
        // $.ajax({
        //     url: `/expert/delete-students?id=${encodeURIComponent($(this).data('id'))}`,
        //     method: 'DELETE',
        //     success (data) {
        //         $('#pjax-students').html(data);
        //     },
        //     error () {
        //         // location.reload();
        //     },
        //     complete () {
        //         $('#pjax-students').trigger('pjax:complete');
        //     }
        // });
    });

    $('#pjax-students').on('click', '.btn-delete-selected-students', function (event) {
        let students = [];

        $('input[name="students[]"]:not(:disabled)').each((index, element) => {
            if ($(element).is(':checked')) {
                students.push($(element).val());
            }
        });

        $.ajax({
            url: `/expert/delete-students`,
            method: 'DELETE',
            data: {
                students: students
            },
            success (data) {
                $('#pjax-students').html(data);
            },
            error () {
                location.reload();
            },
            complete () {
                $('#pjax-students').trigger('pjax:complete');
            }
        });
    });

    $('#pjax-students').on('pjax:complete', function (event) {
        changeActiveBtn();
    });
    
})
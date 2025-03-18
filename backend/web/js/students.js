"use strict";

$(() => {

    $('#pjax-create-student').on('change', '#events-select', function (event) {
        $.ajax({
            url: `/expert/all-students${($(this).val() ? `?event=${$(this).val()}` : '')}`,
            type: 'GET',
            success: function(data) {
                $('#pjax-students').html(data);
                changeActiveBtn();
            },
            error: function() {
            },
            beforeSend: function() {
                $('#pjax-students').html(`
                    <div class="row">
                        <div>
                            <div class="card students-list">
                                <div class="card-header align-items-center d-flex position-relative ">
                                    <h4 class="card-title mb-0 flex-grow-1">Студенты</h4>
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

    $('#pjax-create-student').on('beforeSubmit', '#add-student-form', function (event) {
        $('.btn-create-student').find('.cnt-text').addClass('d-none');
        $('.btn-create-student').find('.cnt-load').removeClass('d-none');
        $('.btn-create-student').prop('disabled', true);
    });

    $('#pjax-create-student').on('pjax:complete', function (event) {
        $('.btn-create-student').find('.cnt-text').removeClass('d-none');
        $('.btn-create-student').find('.cnt-load').addClass('d-none');
        $('.btn-create-student').prop('disabled', false);

        choicesInit();
        
        fetchFlashMessages();

        $.pjax.reload({
            url: `/expert/all-students?event=${$('#events-select').find('option:selected').val()}`,
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

    $('#pjax-students').on('click', '.btn-update', function (event) {
        $('#modal-update-student').find('.modal-body').load(`/expert/update-student?id=${$(this).data('id')}`, function (event) {
            $('#modal-update-student').modal('show');
            choicesInit();
        });
    });

    $('#modal-update-student').on('beforeSubmit', '#form-update-student', function (event) {
        event.preventDefault();

        const form = $(this);

        $.ajax({
            url: form.attr('action'),
            method: 'PATCH',
            data: form.serialize(),
            beforeSend () {
                $('.btn-update-student').find('.cnt-text').addClass('d-none');
                $('.btn-update-student').find('.cnt-load').removeClass('d-none');
                $('.btn-update-student').prop('disabled', true);
            },
            success (data) {
                if (data.success) {
                    $('#modal-update-student').modal('hide');
                    $.pjax.reload({
                        url: `/expert/all-students?event=${$('#events-select').val()}`,
                        container: '#pjax-students',
                        pushState: false,
                        replace: false,
                        timeout: 10000
                    });
                }

                $('#pjax-update-student').html(data);
            },
            error () {
                // location.reload();
            },
            complete () {
                $('.btn-create-student').find('.cnt-text').removeClass('d-none');
                $('.btn-create-student').find('.cnt-load').addClass('d-none');
                $('.btn-create-student').prop('disabled', false);

                fetchFlashMessages();
            }
        });

        return false;
    });

    $('#pjax-students').on('click', '.btn-delete', function (event) {
        $.ajax({
            url: `/expert/delete-students?id=${$(this).data('id')}`,
            method: 'DELETE',
            success (data) {
                if (data.data.success) {
                    $.pjax.reload({
                        url: `/expert/all-students?event=${$('#events-select').val()}`,
                        container: '#pjax-students',
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
                if (data.data.success) {
                    $.pjax.reload({
                        url: `/expert/all-students?event=${$('#events-select').val()}`,
                        container: '#pjax-students',
                        pushState: false,
                        replace: false,
                        timeout: 10000
                    });
                }
            },
            error () {
                location.reload();
            },
            complete () {
                fetchFlashMessages();
            }
        });
    });

    function changeActiveBtn() {
        let checkedStudents = $('input[name="students[]"]:checked:not(:disabled)'),
        allStudents = $('input[name="students[]"]:not(:disabled)');
    
        $('input[name="students_all"]').prop('checked', allStudents.length === checkedStudents.length);
    
        $('.btn-delete-selected-students').prop('disabled', ($(this).is(':checked') ? false : (checkedStudents.length === 0)));
    }

    $('#pjax-students').on('pjax:complete', function (event) {
        changeActiveBtn();
    });

    // function setEvent() {
    //     let event = sessionStorage.getItem('students.event');

    //     if (event) {
    //         let option = $('#events-select').find(`option[value="${event}"]`);
    //         option.prop('selected', true);
    //         option.trigger('change');
    //     }
    // }

    // setEvent();
});

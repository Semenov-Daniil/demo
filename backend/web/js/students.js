"use strict";

function changeActiveBtn() {
    let checkedStudents = $('input[name="students[]"]:checked:not(:disabled)'),
    allStudents = $('input[name="students[]"]:not(:disabled)');

    $('input[name="students_all"]').prop('checked', allStudents.length === checkedStudents.length);

    $('.btn-delete-selected-students').prop('disabled', ($(this).is(':checked') ? false : (checkedStudents.length === 0)));
}

function initStudentsEvent() {
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
        $.ajax({
            url: `/expert/delete-students?id=${encodeURIComponent($(this).data('id'))}`,
            method: 'DELETE',
            success (data) {
                $('#pjax-students').html(data);
            },
            error () {
                // location.reload();
            },
            complete () {
                $('#pjax-students').trigger('pjax:complete');
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
}

$(() => {

    $('#events-select').on('change', function (event) {
        $.ajax({
            url: `expert/students-event${($(this).val() ? `?event=${encodeURIComponent($(this).val())}` : '')}`,
            type: 'GET',
            success: function(data) {
                $('#students-wrap').html(data);
                initStudentsEvent();
                initGridView();
                changeActiveBtn();
            },
            error: function() {
            },
            beforeSend: function() {
                $('#students-wrap').html(`
                    <div class="mb-3 placeholder-glow">
                        <div class="placeholder col-4 placeholder-lg rounded-1"></div>
                    </div>

                    <div class="row">
                        <div>
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1">Добавление студента</h4>
                                </div>

                                <div class="card-body">
                                    <div>
                                        <div class="row">
                                            <div class="d-flex flex-column justify-content-end col-lg-4 mb-3 placeholder-glow">
                                                <div class="mr-lg-3 placeholder col-4 mb-2 rounded-1"></div>
                                                <div class="form-control placeholder p-3"></div>
                                            </div>
                                            <div class="d-flex flex-column justify-content-end col-lg-4 mb-3 placeholder-glow">
                                                <div class="mr-lg-3 placeholder col-2 mb-2 rounded-1"></div>
                                                <div class="form-control placeholder p-3"></div>
                                            </div>
                                            <div class="d-flex flex-column justify-content-end col-lg-4 mb-3 placeholder-glow">
                                                <div class="mr-lg-3 placeholder col-5 mb-2 rounded-1"></div>
                                                <div class="form-control placeholder p-3"></div>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-success disabled placeholder col-1">
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

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

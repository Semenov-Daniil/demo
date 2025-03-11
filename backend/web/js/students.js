$(() => {

    $('#events-select').on('change', function (event) {
        $.ajax({
            url: `expert/students-event${($(this).val() ? `?event=${encodeURIComponent($(this).val())}` : '')}`,
            type: 'GET',
            success: function(data) {
                $('#students-wrap').html(data);
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

    function changeActiveBtn() {
        let checkedStudents = $('input[name="students[]"]:checked:not(:disabled)'),
        allStudents = $('input[name="students[]"]:not(:disabled)');

        $('input[name="students_all"]').prop('checked', allStudents.length === checkedStudents.length);

        $('.btn-delete-selected-students').prop('disabled', ($(this).is(':checked') ? false : (checkedStudents.length === 0)));
    }

    // function setEvent() {
    //     let event = sessionStorage.getItem('students.event');

    //     if (event) {
    //         let option = $('#events-select').find(`option[value="${event}"]`);
    //         option.prop('selected', true);
    //         option.trigger('change');
    //     }
    // }

    changeActiveBtn();
    // setEvent();
});

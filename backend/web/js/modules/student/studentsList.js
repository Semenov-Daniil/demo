$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-students', 'studentsCheckedItems');

    const $pjaxStudents = $(pjaxStudents);
    const $modalUpdateStudent = $('#modal-update-student');
    const eventSelect = '#events-select';

    const actionButtonClasses = ['.btn-delete-selected-students'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('students_all', 'students[]', actionButtonClasses);

    if ($(eventSelect).val()) {
        if (sourceSSE) sourceSSE.close();
        sourceSSE = CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${url}/sse-data-updates`, $(eventSelect).val()), updateStudentsList);
    }

    $pjaxStudents
        .off('click', '.btn-select-all-students')
        .on('click', '.btn-select-all-students', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $pjaxStudents
        .off('click', '.btn-update')
        .on('click', '.btn-update', function() {
            $modalUpdateStudent.find('.modal-body').load(`${url}/update-student?id=${$(this).data('id')}`, () => {
                $modalUpdateStudent.modal('show');
            });
        });

    $modalUpdateStudent
        .off('beforeSubmit', '#form-update-student')
        .on('beforeSubmit', '#form-update-student', function (e) {
            e.preventDefault();
            const $form = $(this);

            CommonUtils.performAjax({
                url: $form.attr('action'),
                method: 'PATCH',
                data: $form.serialize(),
                beforeSend: () => CommonUtils.toggleButtonState($('.btn-update-student'), true),
                success(data) {
                    if (data.success) {
                        $modalUpdateStudent.modal('hide');
                        updateStudentsList();
                    } else if (data.errors) {
                        $form.yiiActiveForm('updateMessages', data.errors, true);
                    }
                },
                complete: () => {
                    CommonUtils.toggleButtonState($('.btn-update-student'), false)
                },
            });

            return false;
        });

    $pjaxStudents
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            const $button = $(this);
            CommonUtils.performAjax({
                url: `${url}/delete-students?id=${$(this).data('id')}`,
                method: 'DELETE',
                beforeSend() {
                    CommonUtils.toggleButtonState($button, true);
                },
                success(data) {
                    if (data.success) {
                        updateStudentsList();
                    }
                },
                complete() {
                    if ($button) {
                        CommonUtils.toggleButtonState($button, false);
                    }
                }
            });
        });

    $pjaxStudents
        .off('click', '.btn-delete-selected-students')
        .on('click', '.btn-delete-selected-students', function() {
            const students = CommonUtils.getSelectedCheckboxes('students[]');
            const $button = $(this);

            if (students.length) {
                CommonUtils.performAjax({
                    url: `${url}/delete-students`,
                    method: 'DELETE',
                    data: { students },
                    beforeSend() {
                        CommonUtils.toggleButtonState($button, true);
                        students.forEach(el => {
                            CommonUtils.toggleButtonState($(`.btn-delete[data-id="${el}"]`), true);
                        });
                    },
                    success(data) {
                        if (data.success) {
                            updateStudentsList();
                        }
                    },
                    complete() {
                        if ($button) {
                            CommonUtils.toggleButtonState($button, false);
                        }
                        students.forEach(el => {
                            let $btnDelete = $(`.btn-delete[data-id="${el}"]`)
                            if ($btnDelete) {
                                CommonUtils.toggleButtonState($btnDelete, false);
                            }
                        });
                    }
                });
            }
        });
    
    $pjaxStudents
        .on('change', checkboxManager.checkboxSelector, () => updateCheckboxState());
    
    $pjaxStudents
        .on('change', checkboxManager.allCheckboxSelector, () => updateCheckboxState());
        
    $pjaxStudents
        .on('pjax:complete', () => updateCheckboxState());

    updateCheckboxState();
})
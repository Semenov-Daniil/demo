$(() => {
    const checkboxManager = new GridCheckboxManager('pjax-students', 'studentsCheckedItems');

    const $pjaxStudents = $('#pjax-students');
    const $modalUpdateStudent = $('#modal-update-student');
    const eventSelect = '#events-select';
    const paramQueryEvent = () => ($(eventSelect).val() ? `?event=${$(eventSelect).val()}` : '');

    const actionButtonClasses = ['.btn-delete-selected-students'];

    const updateCheckboxState = () => CommonUtils.updateCheckboxState('students_all', 'students[]', actionButtonClasses);

    $pjaxStudents
        .off('click', '.btn-select-all-students')
        .on('click', '.btn-select-all-students', () => {
            checkboxManager.setAllCheckboxes(true);
            updateCheckboxState();
        });

    $pjaxStudents
        .off('click', '.btn-update')
        .on('click', '.btn-update', function() {
            $modalUpdateStudent.find('.modal-body').load(`/expert/update-student?id=${$(this).data('id')}`, () => {
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
                        CommonUtils.reloadPjax('#pjax-students', `/expert/list-students${paramQueryEvent()}`);
                    } else if (data.errors) {
                        $form.yiiActiveForm('updateMessages', data.errors, true);
                    }
                },
                complete: () => {
                    CommonUtils.toggleButtonState($('.btn-update-student'), false)
                    CommonUtils.getFlashMessages();
                },
            });

            return false;
        });

    $pjaxStudents
        .off('click', '.btn-delete')
        .on('click', '.btn-delete', function() {
            CommonUtils.performAjax({
                url: `/expert/delete-students?id=${$(this).data('id')}`,
                method: 'DELETE',
                success(data) {
                    if (data.success) {
                        CommonUtils.reloadPjax('#pjax-students', `/expert/list-students${paramQueryEvent()}`);
                    }
                },
            });
        });

    $pjaxStudents
        .off('click', '.btn-delete-selected-students')
        .on('click', '.btn-delete-selected-students', () => {
            const students = CommonUtils.getSelectedCheckboxes('students[]');

            if (students.length) {
                CommonUtils.performAjax({
                    url: `/expert/delete-students`,
                    method: 'DELETE',
                    data: { students },
                    success(data) {
                        if (data.success) {
                            CommonUtils.reloadPjax('#pjax-students', `/expert/list-students${paramQueryEvent()}`);
                        }
                    },
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
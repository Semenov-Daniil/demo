$(() => {
    const $form = $('#form-create-expert');
    const $button = $('.btn-create-expert');

    $form.on('beforeSubmit', function(e) {
        e.preventDefault();

        CommonUtils.performAjax({
            url: $form.prop('action'),
            method: 'POST',
            data: $form.serialize(),
            beforeSend: () => {
                CommonUtils.toggleButtonState($button, true);
            },
            success: (data) => {
                if (data.success) {
                    $form.trigger("reset");
                    document.activeElement.blur();
                    updateExpertsList();
                } else if (data.errors) {
                    $form.yiiActiveForm('updateMessages', data.errors, true);
                }
            },
            complete: () => {
                CommonUtils.toggleButtonState($button, false);
            }
        });

        return false;
    });
});
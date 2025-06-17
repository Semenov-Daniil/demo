$(() => {
    const $form = $('#form-create-event');
    const $button = $('.btn-create-event');

    const loadChoicesDate = (url) => {
        CommonUtils.performAjax({
            url: url,
            method: 'GET',
            success(data) {
                const choices = [choicesMap.get('eventform-expert'), choicesMap.get('eventform-expertupdate')];
                choices.forEach((el) => {
                    if (el) {
                        el.clearChoices();
                        el.setChoices(data, 'value', 'label', true);
                    }
                })
            },
        });
    }

    if ($form.find('#eventform-expert').length) {
        CommonUtils.connectDataSSE(`${urlExperts}/sse-data-updates`, loadChoicesDate, `${url}/all-experts`);
    }

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
                    $form.yiiActiveForm('resetForm');
                    reloadPjaxDebounced(pjaxEvents, updateUrl());
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
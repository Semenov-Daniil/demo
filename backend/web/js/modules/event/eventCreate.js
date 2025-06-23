$(() => {
    const $form = $('#form-create-event');
    const $button = $('.btn-create-event');

    const loadChoicesDate = (url) => {
        CommonUtils.performAjax({
            url: url,
            method: 'GET',
            success(data) {
                const hasGroup = data.hasGroup;
                const experts = data.experts;
                const choices = [choicesMap.get('eventform-expert'), choicesMap.get('eventform-expertupdate')];
                choices.forEach((el) => {
                    if (el) {
                        const $select = $(el.passedElement.element);
                        const storeChoices = el._store._state.choices;
                        const currentValue = $select.val() ?? '';

                        storeChoices.forEach(option => {
                            if (option.value !== '') el.removeChoice(option.value);
                        });
                        $select.find('option').not('[value=""]').remove();

                        if (hasGroup) {
                            const groupChoices = experts.map((group, index) => ({
                                label: group.group,
                                id: index,
                                choices: group.items.map(item => ({
                                    value: item.value + '',
                                    label: item.label
                                }))
                            }));

                            el.setChoices(groupChoices, 'value', 'label', false);
                        } else {
                            const flatChoices = experts.map(item => ({
                                value: item.value + '',
                                label: item.label
                            }));

                            el.setChoices(flatChoices, 'value', 'label', false);
                        }

                        const allValues = hasGroup
                            ? experts.flatMap(g => g.items.map(i => i.value + ''))
                            : experts.map(i => i.value + '');

                        if (allValues.includes(currentValue)) {
                            el.setChoiceByValue(currentValue);
                            $select.val(currentValue);
                        } else {
                            el.setChoiceByValue('');
                            $select.val('');
                            $select.removeClass('is-valid is-invalid');
                        }
                    }
                })
            },
        });
        updateEventsList();
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
                    document.activeElement.blur();
                    updateEventsList();
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
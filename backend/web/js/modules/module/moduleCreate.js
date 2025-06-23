$(() => {
    const eventSelect = '#events-select';
    const placeholderData = `
        <div class="row">
            <div>
                <div class="card">
                    <div class="card-header align-items-center d-flex position-relative">
                        <h4 class="card-title mb-0 flex-grow-1">Модули</h4>
                    </div>
                    <div class="card-body">
                        <div class="grid-view">
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
    `;
    $pjaxModules = $(pjaxModules);

    const $form = $('#form-create-module');
    const $button = $('.btn-create-module');

    $form
        .off('change', eventSelect)
        .on('change', eventSelect, () => {
            window.history.pushState({}, '', setEventParam(window.location.href, $(eventSelect).val()));

            $pjaxModules
                .off('pjax:beforeSend')
                .on('pjax:beforeSend', () => $pjaxModules.html(placeholderData));

            updateModulesList().then(() => {
                $pjaxModules.off('pjax:beforeSend');
            });

            if (sourceSSE) sourceSSE.close();
            if ($(eventSelect).val()) sourceSSE = CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${url}/sse-data-updates`, $(eventSelect).val()), updateModulesList);

            $(eventSelect).removeClass('is-valid is-invalid');
        });

    const loadChoicesDate = (url) => {
        let currentEvent = $(eventSelect).val();
        CommonUtils.performAjax({
            url: url,
            method: 'GET',
            async: false,
            success(data) {
                const hasGroup = data.hasGroup;
                const events = data.events;
                const choices = [choicesMap.get('events-select')];
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
                            const groupChoices = events.map((group, index) => ({
                                label: group.group,
                                id: index,
                                choices: group.items.map(item => ({
                                    value: item.value + '',
                                    label: item.label
                                }))
                            }));

                            el.setChoices(groupChoices, 'value', 'label', false);
                        } else {
                            const flatChoices = events.map(item => ({
                                value: item.value + '',
                                label: item.label
                            }));

                            el.setChoices(flatChoices, 'value', 'label', false);
                        }

                        const allValues = hasGroup
                            ? events.flatMap(g => g.items.map(i => i.value + ''))
                            : events.map(i => i.value + '');

                        if (allValues.includes(currentValue)) {
                            el.setChoiceByValue(currentValue);
                            $select.val(currentValue);
                        } else {
                            el.setChoiceByValue('');
                            $select.val('');
                            $select.removeClass('is-valid is-invalid');
                        }
                        window.history.pushState({}, '', setEventParam(window.location.href, $select.val()));
                    }
                })
            },
        });

        if (currentEvent != $(eventSelect).val()) {
            $(eventSelect).trigger('change');
        }

        updateModulesList();
    }

    CommonUtils.connectDataSSE(`${urlEvent}/sse-data-updates`, loadChoicesDate, `${url}/all-events`);

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
                    // $form.trigger("reset");
                    $form.yiiActiveForm('resetForm');
                    document.activeElement.blur();
                    let currentEvent = getEventParam(window.location.href);
                    $(eventSelect).val(currentEvent);
                    choicesMap.get('events-select').setChoiceByValue(currentEvent);
                    updateModulesList();
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
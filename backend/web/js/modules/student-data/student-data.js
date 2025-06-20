$(() => {
    const url = '/expert/student-data';
    const urlStudent = '/expert/student';
    const urlEvent = '/expert/event';
    const pjaxStudents = '#pjax-students';
    const $pjaxStudents = $(pjaxStudents);
    const eventSelect = '#events-select';
    const placeholderData = `
        <div class="mb-3">
            <h4 class="card-title">Данные участников</h4>
        </div>
        <div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xxl-3">
                <div class="mt-3 item">
                    <div class="card mb-0">
                        <div class="card-body">
                            <div class="card-title placeholder col-6 my-2 p-2 rounded-1"></div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                                <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="mt-3 item">
                    <div class="card mb-0">
                        <div class="card-body">
                            <div class="card-title placeholder col-6 my-2 p-2 rounded-1"></div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                                <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="mt-3 item">
                    <div class="card mb-0">
                        <div class="card-body">
                            <div class="card-title placeholder col-6 my-2 p-2 rounded-1"></div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                                <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    let sourceSSE = null;

    const getEventParam = (url) => {
        try {
            const urlObj = new URL(url);
            const eventValue = urlObj.searchParams.get('event');
            return eventValue;
        } catch (error) {
            console.error('Invalid URL:', error);
            return null;
        }
    }

    const setEventParam = (url, eventValue = null) => {
        try {
            const urlObj = new URL(url);
            eventValue = eventValue ?? '';
            urlObj.searchParams.set('event', eventValue);
            return urlObj.toString();
        } catch (error) {
            console.error('Invalid URL:', error);
            return url;
        }
    }

    const reloadPjaxDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500);

    const updateStudentsList = () => {
        return reloadPjaxDebounced(pjaxStudents, CommonUtils.updateUrl(`${url}/list-students`));
    }

    if ($(eventSelect).val()) {
        if (sourceSSE) sourceSSE.close();
        sourceSSE = CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${urlStudent}/sse-data-updates`, $(eventSelect).val()), updateStudentsList);
    }

    $(eventSelect)
        .off('change')
        .on('change', () => {
            window.history.pushState({}, '', setEventParam(window.location.href, $(eventSelect).val()));

            $pjaxStudents
                .off('pjax:beforeSend')
                .on('pjax:beforeSend', () => $pjaxStudents.html(placeholderData));
            
            updateStudentsList().then(() => {
                $pjaxStudents.off('pjax:beforeSend');
            });

            if (sourceSSE) sourceSSE.close();
            if ($(eventSelect).val()) sourceSSE = CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${urlStudent}/sse-data-updates`, $(eventSelect).val()), updateStudentsList);

            $(eventSelect).removeClass('is-valid is-invalid');
        });

    const loadChoicesDate = (url) => {
        CommonUtils.performAjax({
            url: url,
            method: 'GET',
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
        updateStudentsList();
    }

    CommonUtils.connectDataSSE(`${urlEvent}/sse-data-updates`, loadChoicesDate, `${url}/all-events`);
});

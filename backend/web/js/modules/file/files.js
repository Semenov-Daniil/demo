"use strict";

const options = typeof yiiOptions === "undefined" ? {} : yiiOptions;

const url = '/expert/file';
const urlEvent = '/expert/event';
const urlModule = '/expert/module';

const pjaxFiles = '#pjax-files';

const eventSelect = '#events-select';

const reloadPjaxDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500);

const updateFilesList = () => {
    return reloadPjaxDebounced(pjaxFiles, CommonUtils.updateUrl(`${url}/list-files`));
}

let sourceSSE = [];

const getEventParam = (url, param) => {
    try {
        const urlObj = new URL(url);
        const value = urlObj.searchParams.get(param);
        return value;
    } catch (error) {
        console.error('Invalid URL:', error);
        return null;
    }
}

const setEventParam = (url, param, value = null) => {
    try {
        const urlObj = new URL(url);
        value = value ?? '';
        urlObj.searchParams.set(param, value);
        return urlObj.toString();
    } catch (error) {
        console.error('Invalid URL:', error);
        return url;
    }
}

const loadChoicesDate = (url, select) => {
    CommonUtils.performAjax({
        url: url,
        method: 'GET',
        async: false,
        success(data) {
            const hasGroup = data.hasGroup;
            const events = data.events;
            const choices = [choicesMap.get(select)];
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
                    // window.history.pushState({}, '', setEventParam(window.location.href, $select.val()));
                }
            })
        },
    });
}

const updateModuleSelect = () => {
    loadChoicesDate(setEventParam(`${window.location.origin}${url}/all-modules`, 'event', $(eventSelect).val()), 'directories-select');
    updateFilesList();
};
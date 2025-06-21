"use strict";

const url = '/expert/module';
const urlEvent = '/expert/event';

const pjaxModules = '#pjax-modules';

const reloadPjaxDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500);

const updateModulesList = () => {
    return reloadPjaxDebounced(pjaxModules, CommonUtils.updateUrl(`${url}/list-modules`));
}

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
"use strict";

const url = '/expert/student';
const urlEvent = '/expert/event';

const pjaxStudents = '#pjax-students';

const reloadPjaxDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500);

const updateStudentsList = () => {
    return reloadPjaxDebounced(pjaxStudents, CommonUtils.updateUrl(`${url}/list-students`));
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
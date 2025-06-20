"use strict";

const url = '/expert/event';
const urlExperts = '/expert/expert';

const pjaxEvents = '#pjax-events';

const reloadPjaxDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500);

const updateEventsList = () => {
    return reloadPjaxDebounced(pjaxEvents, CommonUtils.updateUrl(`${url}/list-events`));
}
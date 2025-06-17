"use strict";

const url = '/expert/event';
const urlExperts = '/expert/expert';

const pjaxEvents = '#pjax-events';
const pjaxEventCreate = '#pjax-create-event';

const reloadPjaxDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 1000);

const updateUrl = () => {
    let local = new URL(window.location.href);
    let pjaxUrl;
    try {
        pjaxUrl = new URL(`${url}/list-events`);
    } catch {
        pjaxUrl = new URL(`${url}/list-events`, local.origin);
    }

    let currentPage = local.searchParams.get('page');
    if (currentPage !== null) pjaxUrl.searchParams.set('page', currentPage);

    return pjaxUrl.pathname + pjaxUrl.search + pjaxUrl.hash;
}
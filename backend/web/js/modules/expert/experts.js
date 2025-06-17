"use strict";

const url = '/expert/expert';
const pjaxExperts = '#pjax-experts';

const reloadPjaxDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500);

const updateUrl = () => {
    let local = new URL(window.location.href);
    let pjaxUrl;
    try {
        pjaxUrl = new URL(`${url}/list-experts`);
    } catch {
        pjaxUrl = new URL(`${url}/list-experts`, local.origin);
    }

    let currentPage = local.searchParams.get('page');
    if (currentPage !== null) pjaxUrl.searchParams.set('page', currentPage);

    return pjaxUrl.pathname + pjaxUrl.search + pjaxUrl.hash;
}

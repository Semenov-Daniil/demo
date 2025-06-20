"use strict";

const url = '/expert/expert';
const pjaxExperts = '#pjax-experts';

const reloadPjaxDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500);

const updateExpertsList = () => {
    return reloadPjaxDebounced(pjaxExperts, CommonUtils.updateUrl(`${url}/list-experts`));
}



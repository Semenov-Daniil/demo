$(() => {

    const url = '';

    const pjaxFiles = '#pjax-files';
    const pjaxModules = '#pjax-modules';

    const reloadPjaxFilesDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500);
    const reloadPjaxModulesDebounced = CommonUtils.debounceWithPjax(CommonUtils.reloadPjax, 500);
    
    const updateFilesList = () => {
        return reloadPjaxFilesDebounced(pjaxFiles, CommonUtils.updateUrl(`${url}/files-list`));
    }

    const updateModulesList = () => {
        return reloadPjaxModulesDebounced(pjaxModules, CommonUtils.updateUrl(`${url}/modules-list`));
    }

    const updateModulesFilesList = async () => {
        await updateModulesList();
        await updateFilesList();
    }

    CommonUtils.connectDataSSE(`${url}/sse-files-updates`, updateFilesList);
    CommonUtils.connectDataSSE(`${url}/sse-modules-updates`, updateModulesFilesList);
})
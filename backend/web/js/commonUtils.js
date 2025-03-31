window.CommonUtils = {
    toggleButtonState($button, isLoading) {
        $button.find('.cnt-text').toggleClass('d-none', isLoading);
        $button.find('.cnt-load').toggleClass('d-none', !isLoading);
        $button.prop('disabled', isLoading);
    },

    performAjax(options) {
        const defauls = {
            method: 'POST',
            complete: () => this.getFlashMessages(),
            error: (jqXHR) => jqXHR.status == 500 ?? location.reload(),
        };
        return $.ajax({...defauls, ...options});
    },

    reloadPjax(container, url, options = []) {
        const pjaxOptions = {
            container: container,
            url: url,
            pushState: false,
            replace: false,
            timeout: 10000,
            ...options,
        };
        $.pjax.reload(pjaxOptions);
    },

    getFlashMessages() {
        if (typeof fetchFlashMessages === 'function') {
            fetchFlashMessages();
        }
    },

    getSelectedCheckboxes(checkboxName) {
        return $(`input[name="${checkboxName}"]:not(:disabled):checked`).map((_, el) => el.value).get();
    },

    updateCheckboxState(allCheckboxName, itemCheckboxName, actionButtonClasses) {
        const $allCheckbox = $(`input[name="${allCheckboxName}"]`);
        const $checkboxes = $(`input[name="${itemCheckboxName}"]:not(:disabled)`);
        const $checked = $checkboxes.filter(':checked');
        const isAnyChecked = $checked.length > 0;

        $allCheckbox.prop('checked', $checkboxes.length === $checked.length && isAnyChecked);

        if (Array.isArray(actionButtonClasses)) {
            actionButtonClasses.forEach((btnClass) => {
                $(btnClass).prop('disabled', !isAnyChecked);
            })
        } else {
            $(actionButtonClasses).prop('disabled', !isAnyChecked);
        }
    }
};
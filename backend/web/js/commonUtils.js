"use strict";

class CommonUtils
{
    static toggleButtonState($button, isLoading) {
        $button.find('.cnt-text').toggleClass('d-none', isLoading);
        $button.find('.cnt-load').toggleClass('d-none', !isLoading);
        $button.prop('disabled', isLoading);
    }

    static performAjax(options) {
        const defauls = {
            method: 'POST',
            complete: () => this.getFlashMessages(),
            error: (jqXHR) => jqXHR.status == 500 ?? location.reload(),
        };
        return $.ajax({...defauls, ...options});
    }

    static async reloadPjax(container, url, options = []) {
        const pjaxOptions = {
            container: container,
            url: url,
            pushState: false,
            replace: false,
            timeout: 10000,
            scrollTo: false,
            ...options,
        };
        await $.pjax.reload(pjaxOptions);
    }

    static async getFlashMessages() {
        if (typeof fetchFlashMessages === 'function') {
            await fetchFlashMessages();
        }
    }

    static inputStepInit(input) {
        if (typeof inputStepInit === 'function') {
            inputStepInit(input);
        }
    }

    static inputChoiceInit(select) {
        if (typeof choiceInit === 'function') {
            choiceInit(select);
        }
    }

    static getSelectedCheckboxes(checkboxName) {
        return $(`input[name="${checkboxName}"]:not(:disabled):checked`).map((_, el) => el.value).get();
    }

    static updateCheckboxState(allCheckboxName, itemCheckboxName, actionButtonClasses) {
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

    static showLoadingPlaceholderTable(container, title, countRows = 3) {
        $(container).html(`
            <div class="row">
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0 flex-grow-1">${title}</h4>
                        </div>
                        <div class="card-body">
                            <div class="grid-view">
                                <div class="table-responsive table-card placeholder-glow">
                                    ${this.generatePlaceholderRows(countRows)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }

    static generatePlaceholderRows(count) {
        return Array(count).fill().map(() => `
            <div class="row gx-0 gap-2">
                <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                <div class="placeholder col m-2 p-3 rounded-1"></div>
            </div>
        `).join("");
    }

    static debounce(func, delay) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            return new Promise((resolve) => {
                timeout = setTimeout(() => resolve(func(...args)), delay);
            });
        };
    }
};
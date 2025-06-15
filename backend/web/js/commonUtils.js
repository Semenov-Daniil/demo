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
            error: (jqXHR) => jqXHR.status == 500 ?? location.reload(),
        };
        return $.ajax({...defauls, ...options});
    }

    pjaxStatusMap = {};

    static setupPjaxTrackingFor(containerSelector) {
        this.pjaxStatusMap[containerSelector] = false;

        $(document)
            .on('pjax:start', function (event, xhr, options) {
                if (options.container === containerSelector) {
                    this.pjaxStatusMap[containerSelector] = true;
                }
            })
            .on('pjax:end', function (event, xhr, options) {
                if (options.container === containerSelector) {
                    this.pjaxStatusMap[containerSelector] = false;
                }
            });
    }

    static isPjaxActiveFor(containerSelector) {
        return this.pjaxStatusMap[containerSelector] === true;
    }

    static async reloadPjax(container, url, options = {}) {
        return new Promise((resolve, reject) => {
            const pjaxOptions = {
                container: container,
                url: url,
                pushState: false,
                replace: false,
                scrollTo: false,
                timeout: 5000,
                ...options,
            };
            $.pjax.reload(pjaxOptions)
                .done(resolve)
                .fail((xhr, textStatus, errorThrown) => {
                    if (textStatus !== 'abort') {
                        reject(errorThrown || xhr);
                    } else {
                        resolve();
                    }
                });
        });
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

    static debounceWithPjax(fn, delay, pjaxContainer) {
        let isProcessing = false;
        let queue = [];
        let currentXhr = null;

        const execute = async () => {
            if (isProcessing || queue.length === 0) return;

            isProcessing = true;
            const { args, resolve, reject } = queue[0];

            try {
                await fn(...args, {
                    beforeSend: (xhr) => {
                        currentXhr = xhr;
                    },
                    complete: () => {
                        currentXhr = null;
                    }
                });
                resolve();
            } catch (error) {
                console.error('Error in debounced PJAX execution:', error);
                reject(error);
            } finally {
                isProcessing = false;
                queue.shift();
                if (queue.length > 0) {
                    setTimeout(execute, delay);
                }
            }
        };

        return function (...args) {
            return new Promise((resolve, reject) => {
                if (currentXhr && currentXhr.readyState !== 4) {
                    currentXhr.abort();
                    currentXhr = null;
                }

                queue.push({ args, resolve, reject });

                if (queue.length > 1) {
                    queue = [queue[queue.length - 1]];
                }

                if (!isProcessing) {
                    setTimeout(execute, delay);
                }
            });
        };
    }

    static connectDataSSE(url, fn, pjax, urlPjax) {
        const source = new EventSource(url);
        this.closeSSE(source);
        source.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.hasUpdates) {
                fn(pjax, urlPjax);
            }
        };
        source.onerror = function() {
            source.close();
            setTimeout(CommonUtils.connectDataSSE, 5000);
        };
    }

    static closeSSE(source) {
        const sourceSSE = source;
        window.addEventListener('beforeunload', function() {
            if (sourceSSE) {
                sourceSSE.close();
            }
        });
    }
};
"use strict";

class CommonUtils
{
    static ajaxInProgress = false;
    static pjaxQueue = [];
    static pjaxDebounceTimeout = null;
    static pjaxStatusMap = {};

    static toggleButtonState($button, isLoading) {
        $button.find('.cnt-text').toggleClass('d-none', isLoading);
        $button.find('.cnt-load').toggleClass('d-none', !isLoading);
        $button.prop('disabled', isLoading);
    }

    static performAjax(options) {
        const defauls = {
            method: 'POST',
            timeout: 15000,
            beforeSend: () => {
                this.ajaxInProgress = true;
            },
            complete: () => {
                this.ajaxInProgress = false;
                this.processPjaxQueue();
            },
            error: (jqXHR) => {
                console.error('AJAX error:', jqXHR.status, jqXHR);
                // if (jqXHR.status == 500) location.reload();
            },
        };
        return $.ajax({...defauls, ...options});
    }

    static setupPjaxTrackingFor(containerSelector) {
        this.pjaxStatusMap[containerSelector] = false;

        $(document)
            .on('pjax:start', function (event, xhr, options) {
                if (options.container === containerSelector) {
                    this.pjaxStatusMap[containerSelector] = true;
                    console.log('PJAX started for', containerSelector);
                }
            }.bind(this))
            .on('pjax:end', function (event, xhr, options) {
                if (options.container === containerSelector) {
                    this.pjaxStatusMap[containerSelector] = false;
                    console.log('PJAX ended for', containerSelector);
                }
            }.bind(this));
    }

    static isPjaxActiveFor(containerSelector) {
        return this.pjaxStatusMap[containerSelector] === true;
    }

    static queuePjaxReload(container, url, options = {}, delay = 500) {
        this.pjaxQueue = [{ container, url, options }];
        clearTimeout(this.pjaxDebounceTimeout);
        this.pjaxDebounceTimeout = setTimeout(() => {
            this.processPjaxQueue();
        }, delay);
    }

    static processPjaxQueue() {
        if (this.ajaxInProgress || this.pjaxQueue.length === 0 || this.isPjaxActiveFor(this.pjaxQueue[0].container)) {
            return;
        }
        const { container, url, options } = this.pjaxQueue.shift();
        this.reloadPjax(container, url, options);
    }

    static async reloadPjax(container, url, options = {}) {
        return new Promise((resolve, reject) => {
            const pjaxOptions = {
                container: container,
                url: url,
                pushState: false,
                replace: false,
                scrollTo: false,
                timeout: 10000,
                ...options,
            };
            $.pjax.reload(pjaxOptions)
                .done(() => {
                    resolve();
                })
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

    static debounceWithPjax(fn, delay) {
        let isProcessing = false;
        let queue = [];

        const execute = async () => {
            if (isProcessing || queue.length === 0) return;
            isProcessing = true;
            const { args, resolve, reject } = queue[0];
            try {
                await fn(...args, {
                    beforeSend: (xhr) => {
                        queue[0].xhr = xhr;
                    },
                    complete: () => {
                        queue[0].xhr = null;
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

    static workerSSE() {
        return typeof EventSource !== "undefined";
    }

    static connectDataSSE(url, fn, ...args) {
        const source = new EventSource(url);
        this.closeSSE(source);
        source.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.hasUpdates) {
                fn(...args);
            }
        };
        source.onerror = function() {
            source.close();
            setTimeout(CommonUtils.connectDataSSE, 5000, url, fn, ...args);
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
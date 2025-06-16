$(() => {
    let toastSource = null;

    function renderFlashMessages(toastAlerts) {
        for (let alert of toastAlerts) {
            let newNoty = new Noty({
                text: alert.text,
                type: alert.type,
                theme: 'vz',
                progressBar: true,
                timeout: 5000,
                closeWith: ['click'],
                callbacks: {
                    onTemplate: function() {
                        this.barDom.innerHTML = `
                            <div class="toast-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 noty_body">${this.options.text}</h6>
                                    </div>
                                    <button type="button" class="btn-close" aria-label="Close"></button>
                                </div>
                                <div class="noty_progressbar progress-bar animated-progress opacity-100" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        `;

                        let cntToast = this.barDom.querySelector('.toast-body div');
                        let prbToast = this.barDom.querySelector('.toast-body .progress-bar');
                        
                        switch (alert.type) {
                            case 'primary':
                                cntToast
                                .insertBefore(Object.assign(document.createElement('div'), { className: 'flex-shrink-0 me-2' }), cntToast.firstChild)
                                .appendChild(Object.assign(document.createElement('i'), { className: 'ri-user-smile-line align-middle' }));

                                prbToast.classList.add('bg-primary');

                                break;
                            case 'success':
                                cntToast
                                .insertBefore(Object.assign(document.createElement('div'), { className: 'flex-shrink-0 me-2' }), cntToast.firstChild)
                                .appendChild(Object.assign(document.createElement('i'), { className: 'ri-checkbox-circle-fill align-middle' }));

                                prbToast.classList.add('bg-success');

                                break;
                            case 'warning':
                                cntToast
                                .insertBefore(Object.assign(document.createElement('div'), { className: 'flex-shrink-0 me-2' }), cntToast.firstChild)
                                .appendChild(Object.assign(document.createElement('i'), { className: 'ri-notification-off-line align-middle' }));

                                prbToast.classList.add('bg-warning');

                                break;
                            case 'error':
                                cntToast
                                .insertBefore(Object.assign(document.createElement('div'), { className: 'flex-shrink-0 me-2' }), cntToast.firstChild)
                                .appendChild(Object.assign(document.createElement('i'), { className: 'ri-alert-line align-middle' }));

                                prbToast.classList.add('bg-danger');

                                break;
                            case 'info':
                                cntToast
                                .insertBefore(Object.assign(document.createElement('div'), { className: 'flex-shrink-0 me-2' }), cntToast.firstChild)
                                .appendChild(Object.assign(document.createElement('i'), { className: 'ri-information-line align-middle' }));

                                prbToast.classList.add('bg-secondary');

                                break;
                        }
                    }
                }
            });

            $(`#${newNoty.id}`).on('click', '.btn-close', function (event) {
                event.stopPropagation();
                newNoty.close();
            });
            
            newNoty.show();
        }
    }

    function isExpertPath(url) {
        try {
            const parsedUrl = new URL(url);
            const path = parsedUrl.pathname;
            return path.startsWith('/expert/') || path.startsWith('/expert');
        } catch (e) {
            console.error('Invalid URL:', e);
            return false;
        }
    }

    // SSE для toast
    function connectToastSSE() {
        let toastUrl = (isExpertPath(window.location.href) ? '/expert' : '') + '/toast/notifications';

        toastSource = new EventSource(toastUrl);
        toastSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data && data.length) {
                renderFlashMessages(data);
            }
        };
        toastSource.onerror = function() {
            toastSource.close();
            toastSource = null;
            setTimeout(connectToastSSE, 5000);
        };
    }

    // Long Polling для toast (fallback)
    function fetchToastMessages() {
        $.ajax({
            url: '/expert/toast/messages',
            method: 'GET',
            dataType: 'json',
            success(response) {
                if (response.length) {
                    renderFlashMessages(response);
                }
                setTimeout(fetchToastMessages, 1000); // Проверка каждые 10 секунд
            },
            error() {
                setTimeout(fetchToastMessages, 5000);
            }
        });
    }

    // Проверка поддержки SSE
    if (typeof EventSource !== "undefined") {
        connectToastSSE();
    } else {
        console.warn('SSE not supported, falling back to Long Polling');
        // fetchToastMessages();
    }

    window.addEventListener('beforeunload', function() {
        if (toastSource) {
            toastSource.close();
            toastSource = null;
        }
    });
});



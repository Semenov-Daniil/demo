("use strict");

let toastAlerts = [];

function fetchFlashMessages() {
    let pathname = window.location.pathname.replace(/^\/|\/$/g, '');
    const parts = pathname.split('/');

    $.ajax({
        url: ((parts.length === 0 || parts[0] === '') ? '' : `/${parts[0]}`) + '/flash/get-messages',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            toastAlerts = response;
            renderFlashMessages();
        }
    });
}

function renderFlashMessages() {
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

$(document).on('pjax:complete', function () {
    fetchFlashMessages();
});

fetchFlashMessages();

$(() => {

    const options = (typeof yiiOptions === 'undefined' ? {} : yiiOptions);

    function dropzoneFormInit() {
        dropzone = dropzoneInit('#form-upload-files', options);

        $('#pjax-upload-files').on('beforeSubmit', '#form-upload-files', function (event) {
            event.preventDefault();
            event.stopPropagation();
    
            for (let file of dropzone.files) {
                if (file.accepted) {
                    const previewElement = $(file.previewElement);
    
                    file.upload = {};
                    previewElement.find('[data-dz-uploadprogress]').addClass('d-none');
                    setProgress(file, 0);
     
                    previewElement.removeClass('dz-processing dz-error dz-complete');
                    previewElement.find("[data-dz-errormessage]")?.html('');
                    previewElement.find('[data-dz-uploadprogress]').removeClass('bg-danger').addClass('bg-success');
    
                    setTimeout(() => {
                        previewElement.find('[data-dz-uploadprogress]').removeClass('d-none');
                    }, 300);
                }
            }
    
            setTimeout(() => {
                dropzone.processQueue();
            }, 300);
            
            return false;
        });

        dropzone.on('complete', function (file) {
            if (file.accepted) {
                fetchFlashMessages();
                $.pjax.reload({
                    url: `/expert/all-files?event=${$('#events-select').find('option:selected').val()}`,
                    container: '#pjax-files',
                    pushState: false,
                    replace: false,
                    timeout: 10000
                });
                dropzone.processQueue();
            }
        });
    }

    dropzoneFormInit();

    $('#pjax-upload-files').on('change', '#events-select', function (event) {
        const select = $(this);
        $.ajax({
            url: `/expert/upload-form${(select.val() ? `?event=${select.val()}` : '')}`,
            type: 'GET',
            success: function(data) {
                window.history.pushState({},
                    '',
                    `files${(select.val() ? `?event=${select.val()}` : '')}`
                );
                $('#pjax-upload-files').html(data);
                choicesInit();
            },
            error: function() {
            },
            beforeSend: function() {
                $('#pjax-files').html(`
                    <div class="row">
                        <div>
                            <div class="card students-list">
                                <div class="card-header align-items-center d-flex position-relative ">
                                    <h4 class="card-title mb-0 flex-grow-1">Файлы</h4>
                                </div>
                                <div class="card-body">
                                    <div id="w0" class="grid-view">
                                        <div class="table-responsive table-card table-responsive placeholder-glow">
                                            <div class="row gx-0 gap-2">
                                                <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col m-2 p-3 rounded-1"></div>
                                            </div>
                                            <div class="row gx-0 gap-2">
                                                <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col m-2 p-3 rounded-1"></div>
                                            </div>
                                            <div class="row gx-0 gap-2">
                                                <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col m-2 p-3 rounded-1"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>    
                `);
            },
        });

        $.ajax({
            url: `/expert/all-files${($(this).val() ? `?event=${$(this).val()}` : '')}`,
            type: 'GET',
            success: function(data) {
                $('#pjax-files').html(data);
                changeActiveBtn();
            },
            error: function() {
            },
            beforeSend: function() {
                $('#pjax-files').html(`
                    <div class="row">
                        <div>
                            <div class="card students-list">
                                <div class="card-header align-items-center d-flex position-relative ">
                                    <h4 class="card-title mb-0 flex-grow-1">Файлы</h4>
                                </div>
                                <div class="card-body">
                                    <div id="w0" class="grid-view">
                                        <div class="table-responsive table-card table-responsive placeholder-glow">
                                            <div class="row gx-0 gap-2">
                                                <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col m-2 p-3 rounded-1"></div>
                                            </div>
                                            <div class="row gx-0 gap-2">
                                                <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col m-2 p-3 rounded-1"></div>
                                            </div>
                                            <div class="row gx-0 gap-2">
                                                <div class="placeholder col-1 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col-4 m-2 p-3 rounded-1"></div>
                                                <div class="placeholder col m-2 p-3 rounded-1"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>    
                `);
            },
        });
    });

    $('#pjax-files').on('click', '.btn-select-all-files', function (event) {
        let localName = $('#pjax-files').attr('id'),
            checked = JSON.parse(localStorage.getItem(localName) || '{}');

        let allCheckboxSelected = $('#pjax-files .grid-view .cell-selected').find('input[type="checkbox"]:not(.select-on-check-all, :disabled)');
        allCheckboxSelected.each((inx, el) => {
            checked[$(el).val()] = true;
        });

        $('input[name="files_all"]').prop('checked', true);
        $('input[name="files[]"]').prop('checked', true);

        $('.btn-delete-selected-files').prop('disabled', !allCheckboxSelected.length);
        $('.btn-delete-download-files').prop('disabled', !allCheckboxSelected.length);

        localStorage.setItem(localName, JSON.stringify(checked));
    });

    $('#pjax-files').on('change', 'input[name="files_all"]', function() {
        let isChecked = $(this).is(':checked');
        $('input[name="files[]"]').prop('checked', isChecked);
        $('.btn-delete-selected-files').prop('disabled', !isChecked);
        $('.btn-delete-download-files').prop('disabled', !isChecked);
    });

    $('#pjax-files').on('change', 'input[name="files[]"]', function() {
        let checkedFiles = $('input[name="files[]"]:checked:not(:disabled)'),
            allFiles = $('input[name="files[]"]:not(:disabled)');

        $('input[name="files_all"]').prop('checked', allFiles.length === checkedFiles.length);

        $('.btn-delete-selected-files').prop('disabled', ($(this).is(':checked') ? false : (checkedFiles.length === 0)));
        $('.btn-delete-download-files').prop('disabled', ($(this).is(':checked') ? false : (checkedFiles.length === 0)));
    });

    $('#pjax-files').on('click', '.btn-delete', function (event) {
        $.ajax({
            url: `/expert/delete-files?id=${$(this).data('id')}`,
            method: 'DELETE',
            success (data) {
                if (data.data.success) {
                    $.pjax.reload({
                        url: `/expert/all-files?event=${$('#events-select').val()}`,
                        container: '#pjax-files',
                        pushState: false,
                        replace: false,
                        timeout: 10000
                    });
                }
            },
            error () {
                location.reload();
            },
            complete () {
                fetchFlashMessages();
            }
        });
    });

    // $('#pjax-files').on('click', '.btn-download', function (event) {
    //     $.ajax({
    //         url: `/expert/download?filename=${$(this).data('filename')}`, // URL для загрузки файла
    //         method: 'GET',
    //         xhrFields: {
    //             responseType: 'blob' // Указываем, что ожидаем бинарные данные (Blob)
    //         },
    //         headers: {
    //             'Accept': 'application/octet-stream' // Указываем MIME-тип
    //         },
    //         xhr: function () {
    //             var xhr = new window.XMLHttpRequest();
    //             // Отслеживаем прогресс загрузки
    //             xhr.addEventListener('progress', function (event) {
    //                 if (event.lengthComputable) {
    //                     var percentComplete = (event.loaded / event.total) * 100;
    //                     // $('#progress-bar').val(percentComplete);
    //                     // $('#progress-text').text(Math.round(percentComplete) + '%');
    //                 }
    //             }, false);
    //             return xhr;
    //         },
    //         success: function (data, status, xhr) {
    //             // Получаем имя файла из заголовка Content-Disposition (если сервер его отправляет)
    //             var filename = 'downloaded_file';
    //             var disposition = xhr.getResponseHeader('Content-Disposition');
    //             if (disposition && disposition.indexOf('attachment') !== -1) {
    //                 var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
    //                 var matches = filenameRegex.exec(disposition);
    //                 if (matches != null && matches[1]) {
    //                     filename = matches[1].replace(/['"]/g, '');
    //                 }
    //             }
        
    //             // Создаем URL для Blob
    //             var blob = new Blob([data]);
    //             var url = window.URL.createObjectURL(blob);
        
    //             // Создаем ссылку для скачивания
    //             var a = document.createElement('a');
    //             a.style.display = 'none';
    //             a.href = url;
    //             a.download = filename; // Указываем имя файла
    //             document.body.appendChild(a);
    //             a.click();
        
    //             // Очищаем URL и удаляем ссылку
    //             window.URL.revokeObjectURL(url);
    //             a.remove();
    //         },
    //         error: function (xhr, status, error) {
    //             console.error('Ошибка загрузки файла:', error);
    //             alert('Не удалось загрузить файл. Попробуйте позже.');
    //         }
    //     });
    // });

    $('#pjax-files').on('click', '.btn-delete-selected-files', function (event) {
        let files = [];

        $('input[name="files[]"]:not(:disabled)').each((index, element) => {
            if ($(element).is(':checked')) {
                files.push($(element).val());
            }
        });

        $.ajax({
            url: `/expert/delete-files`,
            method: 'DELETE',
            data: {
                files: files
            },
            success (data) {
                if (data.data.success) {
                    $.pjax.reload({
                        url: `/expert/all-files?event=${$('#events-select').val()}`,
                        container: '#pjax-files',
                        pushState: false,
                        replace: false,
                        timeout: 10000
                    });
                }
            },
            error () {
                location.reload();
            },
            complete () {
                fetchFlashMessages();
            }
        });
    });

    $('#pjax-files').on('pjax:complete', function (event) {
        changeActiveBtn();
    });

    function changeActiveBtn() {
        let checkedFiles = $('input[name="files[]"]:checked:not(:disabled)'),
        allFiles = $('input[name="files[]"]:not(:disabled)');

        $('input[name="files_all"]').prop('checked', allFiles.length === checkedFiles.length);

        $('.btn-delete-selected-files').prop('disabled', ($(this).is(':checked') ? false : (checkedFiles.length === 0)));
        $('.btn-delete-download-files').prop('disabled', ($(this).is(':checked') ? false : (checkedFiles.length === 0)));
    }

    changeActiveBtn();
})
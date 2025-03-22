"use strict";

$(() => {

    $('#events-select').on('change', function (event) {
        const select = $(this);
        $.ajax({
            url: `/expert/all-participants${($(this).val() ? `?event=${$(this).val()}` : '')}`,
            type: 'GET',
            success: function(data) {
                window.history.pushState({},
                    '',
                    `participants${(select.val() ? `?event=${select.val()}` : '')}`
                );
                $('#pjax-participants').html(data);
            },
            error: function() {
            },
            beforeSend: function() {
                $('#pjax-participants').html(`
                    <div class="mb-3">
                        <h4 class="card-title">Данные участников</h4>
                    </div>
                    <div>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-xxl-3">
                            <div class="mt-3 item">
                                <div class="card mb-0">
                                    <div class="card-body">
                                        <div class="card-title placeholder col-6 my-2 p-2 rounded-1"></div>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                                            <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 item">
                                <div class="card mb-0">
                                    <div class="card-body">
                                        <div class="card-title placeholder col-6 my-2 p-2 rounded-1"></div>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                                            <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 item">
                                <div class="card mb-0">
                                    <div class="card-body">
                                        <div class="card-title placeholder col-6 my-2 p-2 rounded-1"></div>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                                            <li class="list-group-item placeholder col-5 my-2 p-2 rounded-1"></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            },
        });
    });
});

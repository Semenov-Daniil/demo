$(() => {

    // $('#pjax-create-event').on('beforeSubmit', function (event) {
    //     $('.btn-create-event').find('.cnt-text').addClass('d-none');
    //     $('.btn-create-event').find('.cnt-load').removeClass('d-none');
    //     $('.btn-create-event').prop('disabled', true);
    // });

    // $('#pjax-create-event').on('pjax:complete', function (event) {
    //     $('.btn-create-event').find('.cnt-text').removeClass('d-none');
    //     $('.btn-create-event').find('.cnt-load').addClass('d-none');
    //     $('.btn-create-event').prop('disabled', false);

    //     inputStepInit();
    //     fetchFlashMessages();

    //     $.pjax.reload({
    //         url: '/expert/list-events',
    //         container: '#pjax-events',
    //         pushState: false,
    //         replace: false,
    //         timeout: 10000
    //     });
    // });

    $('#pjax-events').on('click', '.btn-select-all-events', function (event) {
        let localName = $('#pjax-events').attr('id'),
            checked = JSON.parse(localStorage.getItem(localName) || '{}');

        let allCheckboxSelected = $('#pjax-events .grid-view .cell-selected').find('input[type="checkbox"]:not(.select-on-check-all, :disabled)');
        allCheckboxSelected.each((inx, el) => {
            checked[$(el).val()] = true;
        });

        $('input[name="events_all"]').prop('checked', true);
        $('input[name="events[]"]').prop('checked', true);

        $('.btn-delete-selected-events').prop('disabled', !allCheckboxSelected.length);

        localStorage.setItem(localName, JSON.stringify(checked));
    });

    $('#pjax-events').on('change', 'input[name="events_all"]', function() {
        let isChecked = $(this).is(':checked');
        $('input[name="events[]"]').prop('checked', isChecked);
        $('.btn-delete-selected-events').prop('disabled', !isChecked);
    });

    $('#pjax-events').on('change', 'input[name="events[]"]', function() {
        let checkedevents = $('input[name="events[]"]:checked:not(:disabled)'),
            allevents = $('input[name="events[]"]:not(:disabled)');

        $('input[name="events_all"]').prop('checked', allevents.length === checkedevents.length);

        $('.btn-delete-selected-events').prop('disabled', ($(this).is(':checked') ? false : (checkedevents.length === 0)));
    });

    $('#pjax-events').on('click', '.btn-update', function (event) {
        $('#modal-update-event').find('.modal-body').load(`/expert/update-event?id=${$(this).data('id')}`, function (event) {
            $('#modal-update-event').modal('show');
            choicesInit();
        });
    });

    $('#modal-update-event').on('beforeSubmit', '#form-update-event', function (event) {
        event.preventDefault();

        const form = $(this);

        $.ajax({
            url: form.attr('action'),
            method: 'PATCH',
            data: form.serialize(),
            beforeSend () {
                $('.btn-update-event').find('.cnt-text').addClass('d-none');
                $('.btn-update-event').find('.cnt-load').removeClass('d-none');
                $('.btn-update-event').prop('disabled', true);
            },
            success (data) {
                if (data.success) {
                    $('#modal-update-event').modal('hide');
                    $.pjax.reload({
                        url: '/expert/all-events',
                        container: '#pjax-events',
                        pushState: false,
                        replace: false,
                        timeout: 10000
                    });
                }

                $('#pjax-update-event').html(data);
            },
            error () {
                // location.reload();
            },
            complete () {
                $('.btn-create-event').find('.cnt-text').removeClass('d-none');
                $('.btn-create-event').find('.cnt-load').addClass('d-none');
                $('.btn-create-event').prop('disabled', false);

                fetchFlashMessages();
            }
        });

        return false;
    });

    $('#pjax-events').on('click', '.btn-delete', function (event) {
        $.ajax({
            url: `/expert/delete-events?id=${$(this).data('id')}`,
            method: 'DELETE',
            success (data) {
                $('#pjax-events').html(data);
            },
            error () {
                location.reload();
            },
            complete () {
                $('#pjax-events').trigger('pjax:complete');

                fetchFlashMessages();
            }
        });
    });

    $('#pjax-events').on('click', '.btn-delete-selected-events', function (event) {
        let events = [];

        $('input[name="events[]"]:not(:disabled)').each((index, element) => {
            if ($(element).is(':checked')) {
                events.push($(element).val());
            }
        });

        $.ajax({
            url: `/expert/delete-events`,
            method: 'DELETE',
            data: {
                events: events
            },
            success (data) {
                $('#pjax-events').html(data);
            },
            error () {
                location.reload();
            },
            complete () {
                $('#pjax-events').trigger('pjax:complete');

                fetchFlashMessages();
            }
        });
    });

    $('#pjax-events').on('pjax:complete', function (event) {
        changeActiveBtn();
    });

    function changeActiveBtn() {
        let checkedevents = $('input[name="events[]"]:checked:not(:disabled)'),
        allevents = $('input[name="events[]"]:not(:disabled)');

        $('input[name="events_all"]').prop('checked', allevents.length === checkedevents.length);

        $('.btn-delete-selected-events').prop('disabled', ($(this).is(':checked') ? false : (checkedevents.length === 0)));
    }

    changeActiveBtn();

    $('#pjax-create-event').on('pjax:complete', function (event) {
        choicesInit();
    });
})
$(() => {

    $('#pjax-create-event').on('beforeSubmit', function (event) {
        $('.btn-create-event').find('.cnt-text').addClass('d-none');
        $('.btn-create-event').find('.cnt-load').removeClass('d-none');
        $('.btn-create-event').prop('disabled', true);
    });

    $('#pjax-create-event').on('pjax:complete', function (event) {
        $('.btn-create-event').find('.cnt-text').removeClass('d-none');
        $('.btn-create-event').find('.cnt-load').addClass('d-none');
        $('.btn-create-event').prop('disabled', false);
    });

    $('#pjax-create-event').on('pjax:complete', function (event) {
        $.pjax.reload({
            url: '/expert/all-events',
            container: '#pjax-events',
            pushState: false,
            replace: false,
            timeout: 10000
        });
    });

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

    function watchSelectExpert () {
        const select = $('#eventform-expert')[0];
        const config = { attributes: true, attributeFilter: ['class'] };

        $(select).closest('.choices__inner').addClass('form-select');
    
        const watchChangeClass = function(mutationsList, observer) {
            for (let mutation of mutationsList) {
                if (mutation.type === 'attributes') {
                    if (mutation.attributeName === 'class') {
                        if ($(mutation.target).hasClass('is-invalid')) {
                            $(mutation.target).closest('.choices__inner').removeClass('is-valid');
                            $(mutation.target).closest('.choices').removeClass('is-valid');

                            $(mutation.target).closest('.choices__inner').addClass('is-invalid');
                            $(mutation.target).closest('.choices').addClass('is-invalid');
                        }
                
                        if ($(mutation.target).hasClass('is-valid')) {
                            $(mutation.target).closest('.choices__inner').removeClass('is-invalid');
                            $(mutation.target).closest('.choices').removeClass('is-invalid');

                            $(mutation.target).closest('.choices__inner').addClass('is-valid');
                            $(mutation.target).closest('.choices').addClass('is-valid');
                        }
                    }
                }
            }
        };
    
        const observer = new MutationObserver(watchChangeClass);
    
        observer.observe(select, config);
    }

    $('#pjax-create-event').on('pjax:complete', function (event) {
        watchSelectExpert();
    });

    watchSelectExpert();
})
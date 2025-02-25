$(() => {

    $('#pjax-add-expert').on('beforeSubmit', function (event) {
        $('.btn-add-expert').find('.cnt-text').addClass('d-none');
        $('.btn-add-expert').find('.cnt-load').removeClass('d-none');
        $('.btn-add-expert').prop('disabled', true);
    });

    $('#pjax-add-expert').on('pjax:complete', function (event) {
        $('.btn-add-expert').find('.cnt-text').removeClass('d-none');
        $('.btn-add-expert').find('.cnt-load').addClass('d-none');
        $('.btn-add-expert').prop('disabled', false);
    });

    $('#pjax-add-expert').on('pjax:complete', function (event) {
        $.pjax.reload({
            url: '/expert',
            container: '#pjax-experts',
            timeout: 5000
        });
    });

    $("#pjax-experts").on("click", ".btn-delete", function(event) {
        event.preventDefault();
        
        $.ajax({
            type: "DELETE",
            url: $(this).attr('href'),
            success() {
                $.pjax.reload({
                    url: '/expert',
                    container: '#pjax-experts',
                    timeout: 5000
                });
            },
        });
    });

    $("#pjax-experts").on("click", ".btn-delete-check", function(event) {
        event.preventDefault();

        const form = $('.delete-experts-form');
        
        $.ajax({
            type: "DELETE",
            url: $(this).attr('href'),
            data: form.serialize(),
            success() {
                $.pjax.reload({
                    url: '/expert',
                    container: '#pjax-experts',
                    timeout: 5000
                });
            },
        });
    });

    $('#pjax-experts').on('change', 'input[name="selection[]"], input[name="selection_all"]', function (event) {
        if ($(this).prop('checked')) {
            $('.btn-delete-check').removeClass('d-none');
        } else {
            if (!$('#pjax-experts').find('input[type="checkbox"]:checked').length) {
                $('.btn-delete-check').addClass('d-none');
            }
        }
    });
    
})
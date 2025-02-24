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

    $('#pjax-add-expert').on('pjax:success', function (event) {
        $.pjax.reload('#pjax-experts');
    });

    $("#pjax-experts").on("click", ".btn-delete", function(event) {
        event.preventDefault();
        
        $.ajax({
            type: "DELETE",
            url: $(this).attr('href'),
            success() {
                $.pjax.reload("#pjax-experts");
            },
        });
    });
    
})
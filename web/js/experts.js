$(() => {
    $("#pjax-experts-form").on("pjax:complete", function(event) {
        $.pjax.reload({
            container: '#pjax-experts',
            url: '/info-experts',
            type: "GET",
            push: false,
            replace: false,
            timeout: 10000
        });
    });
    
    $("#pjax-experts").on("mousedown", ".btn-delete", function(event_mousedown) {
        event_mousedown.preventDefault();
        $(this).on("mouseup", function(event_mouseup) {
            event_mouseup.preventDefault();

            $.ajax({
                type: "DELETE",
                url: "/experts/" + $(this).data('id'),
                success() {
                    $.pjax.reload({
                        container: '#pjax-experts',
                        url: '/info-experts',
                        type: "GET",
                        push: false,
                        replace: false,
                        timeout: 10000
                    });
                },
            });
        });
    });
})
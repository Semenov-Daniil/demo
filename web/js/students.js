$(() => {
    $("#pjax-students-form").on("pjax:complete", function(event) {
        $.pjax.reload({
            container: '#pjax-students',
            url: '/info-students',
            type: "GET",
            push: false,
            replace: false,
            timeout: 10000
        });
    });
    
    $("#pjax-students").on("mousedown", ".btn-delete", function(event_mousedown) {
        event_mousedown.preventDefault();
        $(this).on("mouseup", function(event_mouseup) {
            event_mouseup.preventDefault();

            $.ajax({
                type: "DELETE",
                url: "/students/" + $(this).data('id'),
                success() {
                    $.pjax.reload({
                        container: '#pjax-students',
                        url: '/info-students',
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
$(() => {
    $("#pjax-modules").on("change", ".switch-status", function(event) {
        event.preventDefault();
        const checkbox = $(this);
        $.ajax({
            type: "PATH",
            url: "/change-status-modules",
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({
                'id': checkbox.data('id'),
                'status': checkbox.prop('checked') ? 1 : 0,
            }),
            success(data) {
                checkbox.prop('checked', data.status)
            },
            error(xhr, status, error) {
                checkbox.prop('checked', !checkbox.prop('checked'));
            }
        });
    });

    $("#pjax-modules").on("mousedown", ".btn-delete", function(event_mousedown) {
        event_mousedown.preventDefault();
        $(this).on("mouseup", function(event_mouseup) {
            event_mouseup.preventDefault();
            $.ajax({
                type: "DELETE",
                url: "/modules/" + $(this).data('id'),
                success(response) {
                    $.pjax.reload({
                        container: "#pjax-modules",
                        push: false,
                        replace: false,
                        timeout: 10000
                    });
                },
            });
        });
    });
})
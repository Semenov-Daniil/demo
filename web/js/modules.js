$(() => {
    $("#pjax-modules").on("change", ".switch-status", function(event) {
        event.preventDefault();
        const checkbox = $(this);
        $.ajax({
            type: "PATH",
            url: "change-status-modules",
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

    $("#pjax-modules").on("click", ".btn-delete", function(event) {
        event.preventDefault();

        $.ajax({
            type: "DELETE",
            url: $(this).attr('href'),
            success(response) {
                $.pjax.reload("#pjax-modules");
            },
        });
    });
})
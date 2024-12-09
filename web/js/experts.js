$(() => {

    $("#pjax-experts").on("click", ".btn-delete", function(event_mousedown) {
        event_mousedown.preventDefault();
        
        $.ajax({
            type: "DELETE",
            url: $(this).attr('href'),
            success() {
                $.pjax.reload("#pjax-experts");
            },
        });
    });
    
})
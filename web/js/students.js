$(() => {
    
    $("#pjax-students").on("click", ".btn-delete", function(event) {
        event.preventDefault();
        
        $.ajax({
            type: "DELETE",
            url: $(this).attr('href'),
            success() {
                $.pjax.reload("#pjax-students");
            },
        });
    });

})
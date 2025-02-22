$(() => {
    
    $('#pjax-experts').on('click', '.input-step .plus', function (event) {
        let input = $(this).prevAll('input'),
            max = input.prop('max');

        if (!max || (!isNaN(max) && parseInt(input.val()) < Number(max))) {
            let currentValue = input.val();
            currentValue++
            input.val(currentValue);
        }
    });

    $('#pjax-experts').on('click', '.input-step .minus', function (event) {
        let input = $(this).nextAll('input'),
            min = input.prop('min');

        if (!min || (!isNaN(min) && parseInt(input.val()) > Number(min))) {
            let currentValue = input.val();
            currentValue--
            input.val(currentValue);
        }
    });

    $('#pjax-experts').on('input', '.input-step input', function(event) {
        this.value = this.value.replace(/[^0-9\-]/g, '');
        this.value = this.value.replace(/\-+/g, function(match, offset) {
            return offset === 0 ? '-' : '';
        });
    });
});

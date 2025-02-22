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

    // $('#pjax-experts').on('change', '.input-step input', function(event) {
    //     if ($(this).hasClass('is-invalid')) {
    //         console.log($(this).parent().addClass('is-invalid border-danger'));
    //     }

    //     if ($(this).hasClass('is-valid')) {
    //         console.log($(this).parent().addClass('is-valid border-success'));
    //     }
    // });

    const inputCountModules = $('#expertsevents-countmodules')[0];
    const config = { attributes: true, attributeFilter: ['class'] };

    const watchChangeClass = function(mutationsList, observer) {
        for (let mutation of mutationsList) {
            if (mutation.type === 'attributes') {
                if (mutation.attributeName === 'class') {
                    if ($(mutation.target).hasClass('is-invalid')) {
                        $(mutation.target).parent().removeClass('is-valid border-success');
                        $(mutation.target).parent().addClass('is-invalid border-danger');
                    }
            
                    if ($(mutation.target).hasClass('is-valid')) {
                        $(mutation.target).parent().removeClass('is-invalid border-danger');
                        $(mutation.target).parent().addClass('is-valid border-success');
                    }
                }
            }
        }
    };

    const observer = new MutationObserver(watchChangeClass);

    observer.observe(inputCountModules, config);
});

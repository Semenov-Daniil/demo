$(() => {
    
    $('#pjax-create-expert, #pjax-create-module').on('click', '.input-step .plus', function (event) {
        let input = $(this).prevAll('input'),
            max = input.prop('max');

        if (!max || (!isNaN(max) && parseInt(input.val()) < Number(max))) {
            let currentValue = input.val();
            currentValue++
            input.val(currentValue);
        }

        input.removeClass('is-invalid is-valid');
        $('.input-step').removeClass('is-valid is-invalid');
    });

    $('#pjax-create-expert, #pjax-create-module').on('click', '.input-step .minus', function (event) {
        let input = $(this).nextAll('input'),
            min = input.prop('min');

        if (input.val() === '') {
            input.val(((min && !isNaN(min)) ? min : 0));
        }

        if (!min || (!isNaN(min) && parseInt(input.val()) > Number(min))) {
            let currentValue = input.val();
            currentValue--
            input.val(currentValue);
        }

        input.removeClass('is-invalid is-valid');
        $('.input-step').removeClass('is-valid is-invalid');
    });

    function watchCountModules () {
        const inputCountModules = $('.input-step input[type="number"]')[0];
        const config = { attributes: true, attributeFilter: ['class'] };
    
        const watchChangeClass = function(mutationsList, observer) {
            for (let mutation of mutationsList) {
                if (mutation.type === 'attributes') {
                    if (mutation.attributeName === 'class') {
                        if ($(mutation.target).hasClass('is-invalid')) {
                            $(mutation.target).parent().removeClass('is-valid');
                            $(mutation.target).parent().addClass('is-invalid');
                        }
                
                        if ($(mutation.target).hasClass('is-valid')) {
                            $(mutation.target).parent().removeClass('is-invalid');
                            $(mutation.target).parent().addClass('is-valid');
                        }
                    }
                }
            }
        };
    
        const observer = new MutationObserver(watchChangeClass);
    
        observer.observe(inputCountModules, config);
    }

    $('#pjax-create-expert, #pjax-create-module').on('pjax:complete', function (event) {
        watchCountModules();
    });

    watchCountModules();
});

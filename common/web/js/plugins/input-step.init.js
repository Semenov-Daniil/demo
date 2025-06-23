"use strict";

class InputStep {
    constructor(inputElement, options = {}) {
        if (inputElement.classList.contains('input-step-processed')) {
            this.input = inputElement;
            return;
        }
        
        const {
            wrapperClass = 'input-step full-width',
            minusClass = 'btn btn-light material-shadow',
            plusClass = 'btn btn-light material-shadow',
            minusValue = '–',
            plusValue = '+'
        } = options;

        this.input = inputElement;
        this.wrapperClass = wrapperClass;
        this.minusClass = minusClass;
        this.plusClass = plusClass;
        this.minusValue = minusValue;
        this.plusValue = plusValue;

        this.init();
    }

    init() {
        const wrapper = document.createElement('div');
        wrapper.className = this.wrapperClass;

        const minusButton = document.createElement('button');
        minusButton.type = 'button';
        minusButton.className = this.minusClass;
        minusButton.classList.add('minus');
        minusButton.textContent = this.minusValue;

        const plusButton = document.createElement('button');
        plusButton.type = 'button';
        plusButton.className = this.plusClass;
        plusButton.classList.add('plus');
        plusButton.textContent = this.plusValue;

        minusButton.addEventListener('click', () => this.decrement());
        plusButton.addEventListener('click', () => this.increment());

        const newInput = this.input.cloneNode(true);
        newInput.classList.add('input-step-processed');

        wrapper.appendChild(minusButton);
        wrapper.appendChild(newInput);
        wrapper.appendChild(plusButton);

        const parent = this.input.parentNode;
        parent.replaceChild(wrapper, this.input);
        
        // Обновляем ссылку на новый input
        this.input = wrapper.querySelector('input');
    }

    increment() {
        const max = this.input.getAttribute('max');
        const currentValue = parseInt(this.input.value) || 0;

        if (!max || (!isNaN(max) && currentValue < Number(max))) {
            this.input.value = currentValue + 1;
            this.input.dispatchEvent(new Event('change'));
        }

        this.input.classList.remove('is-invalid', 'is-valid');
        this.input.parentElement.classList.remove('is-valid', 'is-invalid');
    }

    decrement() {
        const min = this.input.getAttribute('min');
        let currentValue = parseInt(this.input.value);

        if (this.input.value === '') {
            this.input.value = (min && !isNaN(min)) ? Number(min) : 0;
            this.input.dispatchEvent(new Event('change'));
        } else if (!min || (!isNaN(min) && currentValue > Number(min))) {
            this.input.value = currentValue - 1;
            this.input.dispatchEvent(new Event('change'));
        }

        this.input.classList.remove('is-invalid', 'is-valid');
        this.input.parentElement.classList.remove('is-valid', 'is-invalid');
    }
}

function watchInputStep (input) {
    const config = { attributes: true, attributeFilter: ['class'] };

    const watchChangeClass = function(mutationsList, observer) {
        for (let mutation of mutationsList) {
            if (mutation.type === 'attributes') {
                if (mutation.attributeName === 'class') {
                    mutation.target.closest('.input-step').classList.remove('is-valid');
                    mutation.target.closest('.input-step').classList.remove('is-invalid');

                    if (mutation.target.classList.contains('is-invalid')) {
                        mutation.target.closest('.input-step').classList.remove('is-valid');
                        mutation.target.closest('.input-step').classList.add('is-invalid');
                    }
            
                    if ($(mutation.target).hasClass('is-valid')) {
                        mutation.target.closest('.input-step').classList.remove('is-invalid');
                        mutation.target.closest('.input-step').classList.add('is-valid');
                    }
                }
            }
        }
    };

    const observer = new MutationObserver(watchChangeClass);

    observer.observe(input, config);
}

function inputStepInit(input) {
    var inputData = {};
    let inputStep = new InputStep(input, inputData);

    new Cleave(inputStep.input, {
        numeral: true,
        delimiter: '',
        numeralPositiveOnly: false,
        signBeforePrefix: true
    });

    watchInputStep(inputStep.input);
}

function inputsStepInit() {
    var inputStepExamples = document.querySelectorAll("[data-step]");
    Array.from(inputStepExamples).forEach(function (item) {
        var inputData = {};
        let inputStep = new InputStep(item, inputData);

        new Cleave(inputStep.input, {
            numeral: true,
            delimiter: '',
            numeralPositiveOnly: false,
            signBeforePrefix: true
        });

        watchInputStep(inputStep.input);
    });
}

inputsStepInit();

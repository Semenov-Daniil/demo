"use strict";

const choicesMap = new Map();

function customSearch(term, choices, groups) {
    if (!term || term.length < 1) {
        return buildInitialStructure(choices, groups);
    }

    const fuse = new Fuse(choices, {
    includeScore: true,
    threshold: 0.3,
    distance: 100,
    keys: ['label']
    });

    const searchResults = fuse.search(term).map(result => result.item);

    return buildInitialStructure(searchResults, groups);
}

function buildInitialStructure(choices, groups) {
    const result = [];
    const seenGroups = new Map();

    choices.forEach(choice => {
        if (choice.group) {
            const groupId = choice.group.id;
            if (!seenGroups.has(groupId)) {
                const groupChoices = choices
                    .filter(c => c.group && c.group.id === groupId)
                    .map(c => ({
                        value: c.value,
                        label: c.label
                    }))
                ;

                result.push({
                    label: choice.group.label,
                    id: groupId,
                    choices: groupChoices
                });
                seenGroups.set(groupId, true);
            }
        } else {
            result.push({
                value: choice.value,
                label: choice.label
            });
        }
    });

    return result;
}

function watchSelectExpert(select) {
    const config = { attributes: true, attributeFilter: ['class'] };

    select.closest('.choices__inner').classList.add('form-select');

    const watchChangeClass = function(mutationsList, observer) {
        for (let mutation of mutationsList) {
            if (mutation.type === 'attributes') {
                if (mutation.attributeName === 'class') {
                    mutation.target.closest('.choices__inner').classList.remove('is-invalid');
                    mutation.target.closest('.choices__inner').classList.remove('is-valid');
                    mutation.target.closest('.choices').classList.remove('is-invalid');
                    mutation.target.closest('.choices').classList.remove('is-valid');

                    if (mutation.target.classList.contains('is-invalid')) {
                        mutation.target.closest('.choices__inner').classList.remove('is-valid');
                        mutation.target.closest('.choices').classList.remove('is-valid');

                        mutation.target.closest('.choices__inner').classList.add('is-invalid');
                        mutation.target.closest('.choices').classList.add('is-invalid');
                    }
            
                    if (mutation.target.classList.contains('is-valid')) {
                        mutation.target.closest('.choices__inner').classList.remove('is-invalid');
                        mutation.target.closest('.choices').classList.remove('is-invalid');

                        mutation.target.closest('.choices__inner').classList.add('is-valid');
                        mutation.target.closest('.choices').classList.add('is-valid');
                    }
                }
            }
        }
    };

    const observer = new MutationObserver(watchChangeClass);

    observer.observe(select, config);
}

const choiceInit = function (select) {
    if (typeof select === 'undefined') {
        return;
    }

    var choiceData = {};
    var isChoicesVal = select.attributes;
    if (isChoicesVal["data-choices-search-false"]) choiceData.searchEnabled = false;
    if (isChoicesVal["data-choices-search-true"]) choiceData.searchEnabled = true;
    if (isChoicesVal["data-choices-removeItem"]) choiceData.removeItemButton = true;
    if (isChoicesVal["data-choices-sorting-false"]) choiceData.shouldSort = false;
    if (isChoicesVal["data-choices-sorting-true"]) choiceData.shouldSort = true;
    if (isChoicesVal["data-choices-limit"]) choiceData.maxItemCount = isChoicesVal["data-choices-limit"].value.toString();
    if (isChoicesVal["data-choices-text-unique-true"]) choiceData.duplicateItemsAllowed = false;
    if (isChoicesVal["data-choices-text-disabled-true"]) choiceData.addItems = false;
    choiceData.searchChoices = true;
    choiceData.noResultsText = 'Результаты не найдены.';
    choiceData.noChoicesText = 'Варианты выбора не найдены.';
    choiceData.searchFields = ['label'];

    let choices = isChoicesVal["data-choices-text-disabled-true"] ? new Choices(select, choiceData).disable() : new Choices(select, choiceData);
    
    choicesMap.set(select.id, choices);

    if (isChoicesVal["data-choices-group"]) {
        const availableChoices = choices._store._state.choices;
        const allGroups = choices._store._state.groups;
        let lastSearchValue = '';

        select.addEventListener('search', function(event) {
            const searchValue = event.detail.value;

            const filteredChoices = customSearch(searchValue, availableChoices, allGroups);

            if (searchValue !== lastSearchValue || !searchValue) {
                choices.clearChoices();
                choices.setChoices(filteredChoices, 'value', 'label', true);
                lastSearchValue = searchValue;
            }
        });

        const searchInput = select.parentElement.parentElement.querySelector('input.choices__input[type="search"]');

        searchInput.addEventListener('input', function(event) {
            const searchValue = event.target.value;
            if (searchValue !== lastSearchValue) {
                const filteredChoices = customSearch(searchValue, availableChoices, allGroups);
                choices.clearChoices();
                choices.setChoices(filteredChoices, 'value', 'label', true);
                lastSearchValue = searchValue;
            }
        });
    }

    watchSelectExpert(select);
}

function choicesInit() {
    var choicesExamples = document.querySelectorAll("[data-choices]");
    Array.from(choicesExamples).forEach(function (item) {
        choiceInit(item);
    });
}

choicesInit();

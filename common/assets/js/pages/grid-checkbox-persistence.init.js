"use strict";

class GridCheckboxManager {
    constructor(containerId, storageKey) {
        this.container = $(`#${containerId}`);
        this.storageKey = storageKey || `${containerId}_checkedItems`;
        this.checkboxSelector = '.grid-view .cell-selected input[type="checkbox"]:not(.select-on-check-all, :disabled)';
        this.allCheckboxSelector = '.grid-view .cell-selected input.select-on-check-all';
        this.init();
    }

    init() {
        this.loadCheckedState();
        this.bindEvents();
    }

    loadCheckedState() {
        const checked = new Set(JSON.parse(localStorage.getItem(this.storageKey) || '[]'));
        this.container.find(this.checkboxSelector).each((_, el) => {
            $(el).prop('checked', checked.has($(el).val()));
        });
        this.updateAllCheckboxState();
    }

    bindEvents() {
        this.container.on('click', '.grid-view .cell-checkbox', (e) => {
            if (!$(e.target).is('input[type="checkbox"]') && !$(e.target).closest('label').length) {
                const checkbox = $(e.currentTarget).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            }
        });

        this.container.on('change', this.checkboxSelector, (e) => {
            this.updateStorage($(e.target));
            this.updateAllCheckboxState();
        });

        this.container.on('change', this.allCheckboxSelector, (e) => {
            this.setAllCheckboxes($(e.target).is(':checked'));
        });

        this.container.on('pjax:complete', () => this.loadCheckedState());
    }

    updateStorage($checkbox) {
        const checked = new Set(JSON.parse(localStorage.getItem(this.storageKey) || '[]'));
        if ($checkbox.prop('checked')) {
            checked.add($checkbox.val());
        } else {
            checked.delete($checkbox.val());
        }
        localStorage.setItem(this.storageKey, JSON.stringify([...checked]));
    }

    updateAllCheckboxState() {
        const allCheckboxes = this.container.find(this.checkboxSelector);
        const checkedCount = allCheckboxes.filter(':checked').length;
        this.container.find(this.allCheckboxSelector)
            .prop('checked', checkedCount === allCheckboxes.length && allCheckboxes.length > 0);
    }

    setAllCheckboxes(state) {
        const checked = new Set();
        this.container.find(this.checkboxSelector).each((_, el) => {
            $(el).prop('checked', state);
            if (state) checked.add($(el).val());
        });
        localStorage.setItem(this.storageKey, JSON.stringify([...checked]));
    }

    clearStorage() {
        localStorage.removeItem(this.storageKey);
    }
}

// $(() => {
//     const expertsGrid = new GridCheckboxManager('pjax-experts', 'expertsGridCheckedItems');
//     $('#pjax-experts').on('click', '.btn-select-all-experts', () => expertsGrid.setAllCheckboxes(true));
//     $('#logout-btn').on('click', () => expertsGrid.clearStorage());

//     // Для других страниц
//     // const studentsGrid = new GridCheckboxManager('pjax-students', 'studentsGridCheckedItems');
// });
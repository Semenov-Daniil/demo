function initGridView() {
    
    let localName = $('div[data-pjax-grid]').attr('id');

    $('div[data-pjax-grid]').on('click', '.grid-view .cell-checkbox', function (event) {
        if (!$(event.target).is('input[type="checkbox"]') && !$(event.target).closest('label').length) {
            let checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            checkbox.trigger('change');
        }
    });

    $('div[data-pjax-grid]').on('change', '.grid-view .cell-selected input[type="checkbox"]', function (event) {
        let checked = JSON.parse(localStorage.getItem(localName) || '{}');

        if ($(this).prop('checked')) {
            if ($(this).hasClass('select-on-check-all')) {
                let allCheckboxSelected = $('div[data-pjax-grid] .grid-view .cell-selected').find('input[type="checkbox"]:not(.select-on-check-all, :disabled)');

                allCheckboxSelected.each((inx, el) => {
                    checked[$(el).val()] = true;
                });
            } else {
                checked[$(this).val()] = true;
            }
        } else {
            if ($(this).hasClass('select-on-check-all')) {
                checked = {};
            } else {
                delete checked[$(this).val()];
            }
        }

        localStorage.setItem(localName, JSON.stringify(checked));
    });

    $('div[data-pjax-grid]').on('pjax:complete', function () {
        updateCheckedGrid();
    });
}

$(() => {

    let localName = $('div[data-pjax-grid]').attr('id');

    function updateCheckedGrid() {
        let checkboxSelector = 'div[data-pjax-grid] .grid-view .cell-selected input[type="checkbox"]:not(.select-on-check-all, :disabled)';
            checked = JSON.parse(localStorage.getItem(localName) || '{}');
    
        $(checkboxSelector).each(function() {
            if (checked[$(this).val()]) {
                $(this).prop('checked', true);
            }
        });

        checked = Object.fromEntries(
            Object.entries(checked).filter(([key]) => $(checkboxSelector + `[value="${key}"]`).length)
        );

        localStorage.setItem(localName, JSON.stringify(checked));
    }

    initGridView();
    updateCheckedGrid();
});
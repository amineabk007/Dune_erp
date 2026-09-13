import TomSelect from 'tom-select';

/**
 * Turns every <select> in the app into a searchable dropdown (type to
 * filter), instead of a plain scrollable option list — most were growing
 * long (77+ ingredients, dozens of tables/products) and hard to use on a
 * touch screen. Opt a select out with class="no-search" if it ever
 * shouldn't get this (e.g. a genuinely tiny, fixed list).
 */
function enhance(select) {
    if (select.tomselect || select.classList.contains('no-search')) {
        return;
    }

    new TomSelect(select, {
        create: false,
        allowEmptyOption: true,
        maxOptions: null,
        controlInput: '<input autocomplete="off">',
    });
}

function enhanceAllWithin(root) {
    root.querySelectorAll('select').forEach(enhance);
}

export function initSearchableSelects() {
    enhanceAllWithin(document);

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                if (node.matches('select')) {
                    enhance(node);
                }
                enhanceAllWithin(node);
            });
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

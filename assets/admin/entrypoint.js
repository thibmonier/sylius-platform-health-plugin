// Admin entrypoint of the plugin. A Live Component marks itself with the attribute `busy` while it renders;
// assistive technology needs aria-busy (WCAG 4.1.3 / ARIA live regions), so the attribute is mirrored on the health results.
const resultsSelector = '[data-test-health-results]';

const mirrorBusyState = (element) => {
    element.setAttribute('aria-busy', element.hasAttribute('busy') ? 'true' : 'false');
};

new MutationObserver((mutations) => {
    mutations.forEach(({ target }) => {
        if (target instanceof HTMLElement && target.matches(resultsSelector)) {
            mirrorBusyState(target);
        }
    });
}).observe(document.documentElement, { subtree: true, attributes: true, attributeFilter: ['busy'] });

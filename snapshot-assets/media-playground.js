(function () {
    var script = document.currentScript;
    var page = script && script.closest('.media-playground');
    if (!page) return;

    page.querySelectorAll('[data-client-accordion]').forEach(function (accordion) {
        if (accordion.nstarterReady) return;
        accordion.nstarterReady = true;
        accordion.addEventListener('click', function (event) {
            var button = event.target.closest('button[aria-expanded]');
            if (!button || !accordion.contains(button)) return;
            var panel = button.nextElementSibling;
            var expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            if (panel) panel.hidden = expanded;
        });
    });

    var popup = page.querySelector('[data-client-popup]');
    var openButton = page.querySelector('[data-client-popup-open]');
    var closeButton = page.querySelector('[data-client-popup-close]');
    if (popup && openButton && closeButton && !popup.nstarterReady) {
        popup.nstarterReady = true;
        openButton.addEventListener('click', function () { popup.showModal(); });
        closeButton.addEventListener('click', function () { popup.close(); });
    }
}());

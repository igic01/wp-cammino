(function () {
    'use strict';

    const form = document.querySelector('[data-events-calendar-form]');
    const date = document.querySelector('[data-events-date-filter]');

    if (!form || !date) return;

    date.addEventListener('change', function () {
        form.requestSubmit();
    });
}());

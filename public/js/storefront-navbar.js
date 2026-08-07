(function () {
    'use strict';

    var nav = document.querySelector('[data-navbar-items]');
    if (!nav) return;

    function closeSubmenus(except) {
        nav.querySelectorAll('.lt-category-item.is-open').forEach(function (item) {
            if (item === except) return;
            item.classList.remove('is-open');
            var toggle = item.querySelector(':scope > [data-navbar-toggle]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    }

    nav.querySelectorAll('[data-navbar-toggle]').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var item = toggle.parentElement;
            var open = !item.classList.contains('is-open');
            closeSubmenus(item);
            item.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    var menu = document.getElementById('main-menu');
    var menuButton = document.querySelector('.lt-menu-toggle');
    document.addEventListener('click', function (event) {
        if (menu && !menu.contains(event.target) && !(menuButton && menuButton.contains(event.target))) closeSubmenus(null);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        closeSubmenus(null);
        var openButton = nav.querySelector('.lt-category-item.is-open [data-navbar-toggle]');
        if (openButton) openButton.focus();
    });

    window.requestAnimationFrame(function () {
        var root = nav.closest('[data-navbar]') || document.getElementById('main-menu');
        if (root) root.classList.remove('lt-navbar-pending');
    });
}());

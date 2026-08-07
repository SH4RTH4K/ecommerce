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

    // Keep nested brand menus reachable when the parent dropdown is close to
    // the viewport edge. The class is visual only and never changes menu data.
    nav.querySelectorAll('.lt-subcategory-item.has-nested').forEach(function (item) {
        var link = item.querySelector(':scope > .lt-subcategory-link');
        if (link) {
            link.addEventListener('click', function (event) {
                // First click opens the brand list; the next click follows
                // the Router page. The explicit All Router link always works.
                if (!item.classList.contains('is-nested-open')) {
                    event.preventDefault();
                    nav.querySelectorAll('.lt-subcategory-item.is-nested-open').forEach(function (other) {
                        if (other !== item) other.classList.remove('is-nested-open');
                    });
                    item.classList.add('is-nested-open');
                    link.setAttribute('aria-expanded', 'true');
                }
            });
            link.setAttribute('aria-haspopup', 'true');
            link.setAttribute('aria-expanded', 'false');
        }

        function placeNestedMenu() {
            var menu = item.querySelector(':scope > .lt-subcategory-dropdown');
            if (!menu) return;
            item.classList.remove('opens-left');
            var itemRect = item.getBoundingClientRect();
            var menuWidth = Math.min(menu.scrollWidth || 230, 320);
            if (itemRect.right + menuWidth > window.innerWidth - 8 && itemRect.left - menuWidth >= 8) {
                item.classList.add('opens-left');
            }
        }

        item.addEventListener('mouseenter', placeNestedMenu);
        item.addEventListener('focusin', placeNestedMenu);
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

(function () {
    'use strict';

    var nav = document.querySelector('[data-navbar-items]');
    if (!nav) return;

    var layoutRoot = nav.closest('[data-navbar-layout]') || document.getElementById('main-menu');

    // A flex-wrap row naturally leaves an orphan item on the final row. When
    // the administrator chooses an exact maximum row count, distribute the
    // items evenly so (for example) 16 items become two rows of eight.
    function balanceWrappedRows() {
        if (!layoutRoot) return;
        var items = Array.prototype.slice.call(nav.querySelectorAll(':scope > [data-navbar-item]'));
        items.forEach(function (item) {
            item.style.flexBasis = '';
            item.style.maxWidth = '';
        });
        layoutRoot.classList.remove('nav-balanced-rows');

        var wraps = layoutRoot.classList.contains('nav-row-wrap') || layoutRoot.classList.contains('nav-row-auto');
        var maxRows = parseInt(layoutRoot.getAttribute('data-max-rows'), 10);
        if (!wraps || window.innerWidth <= 1200 || !items.length || maxRows < 2 || maxRows > 3) return;

        var itemsPerRow = Math.ceil(items.length / maxRows);
        var columnGap = parseFloat(window.getComputedStyle(nav).columnGap) || 0;
        var availableWidth = nav.clientWidth - (columnGap * (itemsPerRow - 1));
        var itemWidth = Math.max(1, availableWidth / itemsPerRow);
        items.forEach(function (item) {
            item.style.flexBasis = itemWidth + 'px';
            item.style.maxWidth = itemWidth + 'px';
        });
        layoutRoot.classList.add('nav-balanced-rows');
    }

    function closeSubmenus(except) {
        nav.querySelectorAll('.lt-category-item.is-open').forEach(function (item) {
            if (item === except) return;
            item.classList.remove('is-open');
            var toggle = item.querySelector(':scope > [data-navbar-toggle]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
            var categoryLink = item.querySelector(':scope > .lt-category-link');
            if (categoryLink) categoryLink.setAttribute('aria-expanded', 'false');
        });
        nav.querySelectorAll('.lt-subcategory-item.is-nested-open').forEach(function (item) {
            item.classList.remove('is-nested-open');
            var link = item.querySelector(':scope > .lt-subcategory-link');
            if (link) link.setAttribute('aria-expanded', 'false');
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

    // Category links with children act as submenu triggers. The first click
    // opens the submenu; a second click follows the category link normally.
    nav.querySelectorAll('.lt-category-item.has-children > .lt-category-link').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var item = link.parentElement;
            if (item.classList.contains('is-open')) return;

            event.preventDefault();
            event.stopPropagation();
            closeSubmenus(item);
            item.classList.add('is-open');
            link.setAttribute('aria-expanded', 'true');
        });
        link.setAttribute('aria-haspopup', 'true');
        link.setAttribute('aria-expanded', 'false');
    });

    // Desktop hover opens the submenu while closing any previously hovered
    // category, preventing overlapping dropdown panels.
    nav.querySelectorAll('.lt-category-item').forEach(function (item) {
        item.addEventListener('mouseenter', function () {
            if (window.innerWidth <= 720) return;
            var focused = nav.querySelector(':focus');
            if (focused && !item.contains(focused) && focused.blur) focused.blur();
            closeSubmenus(item);
            nav.querySelectorAll('.lt-category-item.is-hover').forEach(function (other) {
                if (other !== item) other.classList.remove('is-hover');
            });
            if (item.querySelector(':scope > .lt-category-dropdown')) item.classList.add('is-hover');
        });
        item.addEventListener('mouseleave', function () {
            item.classList.remove('is-hover');
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
            if (window.innerWidth <= 720) {
                menu.style.position = '';
                menu.style.top = '';
                menu.style.left = '';
                menu.style.right = '';
                return;
            }
            var menuWidth = Math.min(menu.scrollWidth || 230, 320);
            menu.style.position = 'fixed';
            menu.style.top = Math.max(8, itemRect.top - 7) + 'px';
            menu.style.bottom = 'auto';
            if (itemRect.right + menuWidth > window.innerWidth - 8 && itemRect.left - menuWidth >= 8) {
                item.classList.add('opens-left');
                menu.style.left = 'auto';
                menu.style.right = Math.max(8, window.innerWidth - itemRect.left) + 'px';
            } else {
                menu.style.right = 'auto';
                menu.style.left = Math.min(window.innerWidth - menuWidth - 8, itemRect.right) + 'px';
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

    var resizeFrame;
    window.addEventListener('resize', function () {
        window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(balanceWrappedRows);
    });

    window.requestAnimationFrame(function () {
        balanceWrappedRows();
        var root = nav.closest('[data-navbar]') || document.getElementById('main-menu');
        if (root) root.classList.remove('lt-navbar-pending');
    });
}());

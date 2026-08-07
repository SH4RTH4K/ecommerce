(function () {
    'use strict';

    var form = document.querySelector('[data-live-search]');
    if (!form) return;
    var input = form.querySelector('input[type="search"]');
    var panel = form.querySelector('[data-search-panel]');
    var results = form.querySelector('[data-search-results]');
    var allLink = form.querySelector('[data-search-all]');
    var tabs = Array.prototype.slice.call(form.querySelectorAll('[data-search-tab]'));
    var timer = null, controller = null, activeTab = 'products', lastTerm = '';

    function money(value) { return '৳' + Number(value || 0).toLocaleString('en-US'); }
    function escapeHtml(value) {
        return String(value || '').replace(/[&<>'"]/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character];
        });
    }
    function showPanel(show) { panel.hidden = !show; }
    function render(data) {
        var list = activeTab === 'categories' ? data.categories : data.products;
        if (!list.length) {
            results.innerHTML = '<div class="lt-search-empty">No ' + activeTab + ' found.</div>';
        } else if (activeTab === 'categories') {
            results.innerHTML = list.map(function (category) {
                return '<a class="lt-search-category" href="' + category.url + '"><i class="fa fa-folder-open-o"></i><span>' + escapeHtml(category.name) + '</span></a>';
            }).join('');
        } else {
            results.innerHTML = list.map(function (product) {
                var stock = product.in_stock ? '<span class="is-stock">In Stock</span>' : '<span class="is-out">Out Of Stock</span>';
                var price = '<strong>' + money(product.price) + '</strong>' + (product.has_offer ? '<del>' + money(product.regular_price) + '</del>' : '');
                return '<a class="lt-search-product" href="/product-details/' + product.id + '"><img src="' + product.image + '" alt=""><span class="lt-search-product-info"><b>' + escapeHtml(product.name) + '</b><small>' + stock + '</small><span class="lt-search-price">' + price + '</span></span></a>';
            }).join('');
        }
        allLink.href = '/search-product?search_text=' + encodeURIComponent(lastTerm);
        allLink.style.display = lastTerm ? 'block' : 'none';
    }
    function load(term) {
        lastTerm = term;
        if (controller) controller.abort();
        if (!term) { showPanel(false); return; }
        controller = new AbortController();
        fetch('/search-suggestions?q=' + encodeURIComponent(term), {headers: {'Accept': 'application/json'}, signal: controller.signal})
            .then(function (response) { return response.json(); })
            .then(function (data) { render(data); showPanel(true); })
            .catch(function (error) { if (error.name !== 'AbortError') showPanel(false); });
    }
    input.addEventListener('input', function () {
        var term = input.value.trim();
        clearTimeout(timer);
        timer = setTimeout(function () { load(term); }, 180);
    });
    input.addEventListener('focus', function () { if (lastTerm) showPanel(true); });
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activeTab = tab.getAttribute('data-search-tab');
            tabs.forEach(function (item) { var active = item === tab; item.classList.toggle('is-active', active); item.setAttribute('aria-selected', active ? 'true' : 'false'); });
            if (lastTerm) load(lastTerm);
        });
    });
    document.addEventListener('click', function (event) { if (!form.contains(event.target)) showPanel(false); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { showPanel(false); input.blur(); } });
}());

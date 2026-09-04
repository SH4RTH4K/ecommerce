(function () {
    'use strict';
    var form = document.getElementById('snb-form');
    var list = document.getElementById('snb-list');
    if (!form || !list) return;
    var previewDevice = 'desktop';
    var initializing = true;
    var dragged = null;

    function rows() { return Array.prototype.slice.call(list.querySelectorAll('[data-row]')); }
    function layoutField(key) {
        var fields = form.querySelectorAll('[name="layout[' + key + ']"]');
        for (var i = 0; i < fields.length; i += 1) if (fields[i].type !== 'hidden') return fields[i];
        return fields[0] || null;
    }
    function layoutValue(key) {
        var field = layoutField(key);
        return field ? (field.type === 'checkbox' ? (field.checked ? field.value : '0') : field.value) : '';
    }
    function setLayout(key, value) {
        var field = layoutField(key);
        if (!field) return;
        if (field.type === 'checkbox') field.checked = String(value) === '1'; else field.value = value;
    }
    function enabledRows() { return rows().filter(function (row) { return row.querySelector('[data-show]').checked; }); }
    function labelFor(row) {
        var custom = row.querySelector('[data-label]');
        return custom && custom.value.trim() ? custom.value.trim() : row.querySelector('.snb-item-identity strong').textContent.trim();
    }
    function justify(value) { return String(value || 'LEFT').toLowerCase().replace(/_/g, '-'); }

    function renderPreview() {
        var preview = document.getElementById('snb-preview');
        var frame = document.querySelector('[data-preview-frame]');
        var currentRows = enabledRows();
        var isMobile = previewDevice === 'mobile';
        var isTablet = previewDevice === 'tablet';
        var tabletCustom = isTablet && layoutValue('tablet_mode') === 'CUSTOM';
        var alignment = tabletCustom ? layoutValue('tablet_alignment') : layoutValue('alignment');
        var fontSize = isMobile ? layoutValue('font_size_mobile') : (isTablet ? layoutValue('font_size_tablet') : layoutValue('font_size_desktop'));
        var gap = tabletCustom ? layoutValue('tablet_item_gap') : layoutValue('item_gap');
        frame.className = 'snb-preview-frame is-' + previewDevice;
        preview.className = 'snb-preview';
        if (layoutValue('row_mode') !== 'SINGLE_ROW') preview.classList.add('is-wrap');
        if (layoutValue('item_width_mode') === 'EQUAL_WIDTH') preview.classList.add('is-equal');
        if (isMobile) preview.classList.add('is-mobile-menu');
        preview.style.setProperty('--preview-height', layoutValue('minimum_height') + 'px');
        preview.style.setProperty('--preview-gap', gap + 'px');
        preview.style.setProperty('--preview-row-gap', layoutValue('row_gap') + 'px');
        preview.style.setProperty('--preview-px', layoutValue('padding_x') + 'px');
        preview.style.setProperty('--preview-py', layoutValue('padding_y') + 'px');
        preview.style.setProperty('--preview-item-px', layoutValue('item_padding_x') + 'px');
        preview.style.setProperty('--preview-item-py', layoutValue('item_padding_y') + 'px');
        preview.style.setProperty('--preview-font', fontSize + 'px');
        preview.style.setProperty('--preview-weight', layoutValue('font_weight'));
        preview.style.setProperty('--preview-text-align', layoutValue('item_text_alignment').toLowerCase());
        preview.style.setProperty('--preview-item-min', layoutValue('minimum_item_width') + 'px');
        preview.style.setProperty('--preview-radius', layoutValue('border_radius') + 'px');
        preview.style.setProperty('--preview-item-radius', layoutValue('item_radius') + 'px');
        preview.style.setProperty('--preview-white-space', layoutValue('label_wrap') === 'ALLOW_WRAP' ? 'normal' : 'nowrap');
        preview.style.justifyContent = isMobile ? '' : justify(alignment);
        preview.innerHTML = '';
        currentRows.forEach(function (row) { var item = document.createElement('span'); item.textContent = labelFor(row); preview.appendChild(item); });
        if (!currentRows.length) { var empty = document.createElement('span'); empty.textContent = 'No navbar items enabled'; preview.appendChild(empty); }
        document.getElementById('snb-count').textContent = currentRows.length;
        window.requestAnimationFrame(function () {
            var overflowing = !isMobile && preview.scrollWidth > preview.clientWidth + 2;
            document.getElementById('snb-warning').hidden = !overflowing;
            document.getElementById('snb-fit-status').textContent = overflowing ? 'May overflow at this width' : 'Fits this preview width';
        });
    }

    function renumber(container, itemSelector, prioritySelector) {
        container.querySelectorAll(itemSelector).forEach(function (item, index) { var priority = item.querySelector(prioritySelector); if (priority) priority.value = (index + 1) * 10; });
    }
    function updateMoveButtons() {
        var currentRows = rows();
        currentRows.forEach(function (row, index) {
            row.querySelector('[data-move="up"]').disabled = index === 0;
            row.querySelector('[data-move="down"]').disabled = index === currentRows.length - 1;
        });
    }
    function markDirty() {
        if (initializing) return;
        var state = document.getElementById('snb-save-state'); state.textContent = 'Unsaved changes'; state.className = 'is-dirty';
    }
    function afterOrderChange() { renumber(list, '[data-row]', '[data-priority]'); updateMoveButtons(); renderPreview(); markDirty(); }
    function updateFilter() {
        var query = (document.getElementById('snb-search').value || '').trim().toLowerCase();
        var filter = document.getElementById('snb-filter').value;
        var visible = 0;
        rows().forEach(function (row) {
            var shown = row.querySelector('[data-show]').checked;
            var nameMatch = !query || row.getAttribute('data-name').indexOf(query) > -1 || labelFor(row).toLowerCase().indexOf(query) > -1;
            var filterMatch = filter === 'all' || (filter === 'shown' && shown) || (filter === 'hidden' && !shown);
            row.hidden = !(nameMatch && filterMatch); if (!row.hidden) visible += 1;
        });
        document.getElementById('snb-filter-count').textContent = visible + ' matching ' + (visible === 1 ? 'category' : 'categories');
        document.getElementById('snb-no-results').hidden = visible !== 0;
    }
    function updateConditionalFields() {
        form.querySelectorAll('[data-show-when]').forEach(function (element) { var c = element.getAttribute('data-show-when').split(':'); element.hidden = layoutValue(c[0]) !== c[1]; });
        var customTablet = layoutValue('tablet_mode') === 'CUSTOM';
        form.querySelectorAll('[data-tablet-custom]').forEach(function (element) { element.hidden = !customTablet; });
    }
    function updateRowState(row) {
        var shown = row.querySelector('[data-show]').checked;
        row.classList.toggle('is-enabled', shown); row.querySelector('[data-row-status]').textContent = shown ? 'Visible in navbar' : 'Hidden from navbar';
    }

    list.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-settings-toggle]');
        if (toggle) {
            var row = toggle.closest('[data-row]'); var settings = row.querySelector('[data-item-settings]'); settings.hidden = !settings.hidden;
            toggle.setAttribute('aria-expanded', settings.hidden ? 'false' : 'true'); return;
        }
        var mover = event.target.closest('[data-move]');
        if (mover) {
            var movingRow = mover.closest('[data-row]'); var sibling = mover.getAttribute('data-move') === 'up' ? movingRow.previousElementSibling : movingRow.nextElementSibling;
            if (!sibling) return;
            if (mover.getAttribute('data-move') === 'up') list.insertBefore(movingRow, sibling); else list.insertBefore(sibling, movingRow);
            afterOrderChange();
        }
    });
    list.querySelectorAll('[data-drag]').forEach(function (handle) { handle.addEventListener('mousedown', function () { handle.closest('[data-row]').draggable = true; }); });
    list.querySelectorAll('[data-sub-drag]').forEach(function (handle) { handle.addEventListener('mousedown', function () { handle.closest('[data-sub-row]').draggable = true; }); });
    list.addEventListener('dragstart', function (event) {
        dragged = event.target.closest('[data-sub-row]') || event.target.closest('[data-row]'); if (!dragged) return;
        dragged.classList.add('is-dragging'); if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
    });
    list.addEventListener('dragover', function (event) {
        if (!dragged) return;
        var selector = dragged.hasAttribute('data-sub-row') ? '[data-sub-row]' : '[data-row]'; var target = event.target.closest(selector);
        if (!target || target === dragged || target.parentElement !== dragged.parentElement) return;
        event.preventDefault(); var rect = target.getBoundingClientRect(); target.parentElement.insertBefore(dragged, event.clientY < rect.top + rect.height / 2 ? target : target.nextSibling);
    });
    list.addEventListener('dragend', function () {
        if (!dragged) return; var parent = dragged.parentElement; var isSubRow = dragged.hasAttribute('data-sub-row');
        dragged.classList.remove('is-dragging'); dragged.draggable = false; dragged = null;
        if (isSubRow) renumber(parent, '[data-sub-row]', '[data-sub-priority]'); else afterOrderChange(); markDirty();
    });

    document.querySelectorAll('[data-workspace-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = button.getAttribute('data-workspace-tab');
            document.querySelectorAll('[data-workspace-tab]').forEach(function (tab) { tab.classList.toggle('is-active', tab === button); });
            document.querySelectorAll('[data-workspace-panel]').forEach(function (panel) { panel.hidden = panel.getAttribute('data-workspace-panel') !== target; });
        });
    });
    document.querySelectorAll('[data-preview-device]').forEach(function (button) {
        button.addEventListener('click', function () { previewDevice = button.getAttribute('data-preview-device'); document.querySelectorAll('[data-preview-device]').forEach(function (tab) { tab.classList.toggle('is-active', tab === button); }); renderPreview(); });
    });
    var presets = {
        standard:{alignment:'LEFT',row_alignment:'LEFT',row_mode:'SINGLE_ROW',max_rows:'1',overflow_behavior:'HORIZONTAL_SCROLL',item_gap:'0',row_gap:'0',padding_x:'8',padding_y:'8',item_padding_x:'8',item_padding_y:'8',font_size_desktop:'14',font_weight:'600',item_width_mode:'AUTO',minimum_item_width:'80',label_wrap:'NO_WRAP'},
        compact:{alignment:'SPACE_BETWEEN',row_alignment:'LEFT',row_mode:'SINGLE_ROW',max_rows:'1',overflow_behavior:'COMPACT_ITEMS',item_gap:'0',row_gap:'0',padding_x:'4',padding_y:'6',item_padding_x:'4',item_padding_y:'6',font_size_desktop:'12',font_weight:'600',item_width_mode:'AUTO',minimum_item_width:'50',label_wrap:'NO_WRAP'},
        centered:{alignment:'CENTER',row_alignment:'CENTER',row_mode:'SINGLE_ROW',max_rows:'1',overflow_behavior:'HORIZONTAL_SCROLL',item_gap:'8',row_gap:'0',padding_x:'10',padding_y:'8',item_padding_x:'10',item_padding_y:'8',font_size_desktop:'14',font_weight:'600',item_width_mode:'AUTO',minimum_item_width:'80',label_wrap:'NO_WRAP'},
        two_rows:{alignment:'CENTER',row_alignment:'CENTER',row_mode:'WRAP',max_rows:'2',overflow_behavior:'ALLOW_EXTRA_ROW',item_gap:'4',row_gap:'4',padding_x:'8',padding_y:'6',item_padding_x:'8',item_padding_y:'6',font_size_desktop:'14',font_weight:'600',item_width_mode:'AUTO',minimum_item_width:'80',label_wrap:'NO_WRAP'}
    };
    document.querySelectorAll('[data-preset]').forEach(function (button) { button.addEventListener('click', function () { var preset = presets[button.getAttribute('data-preset')]; Object.keys(preset).forEach(function (key) { setLayout(key, preset[key]); }); updateConditionalFields(); renderPreview(); markDirty(); }); });
    document.querySelectorAll('[data-bulk]').forEach(function (button) {
        button.addEventListener('click', function () {
            var checked = button.getAttribute('data-bulk') === 'enable';
            rows().forEach(function (row) { if (!row.hidden) { row.querySelector('[data-show]').checked = checked; updateRowState(row); } });
            renderPreview(); updateFilter(); markDirty();
        });
    });
    document.querySelector('[data-expand-all]').addEventListener('click', function (event) {
        var settings = rows().filter(function (row) { return !row.hidden; }).map(function (row) { return row.querySelector('[data-item-settings]'); });
        var shouldOpen = settings.some(function (panel) { return panel.hidden; });
        settings.forEach(function (panel) { panel.hidden = !shouldOpen; panel.closest('[data-row]').querySelector('[data-settings-toggle]').setAttribute('aria-expanded', shouldOpen ? 'true' : 'false'); });
        event.target.textContent = shouldOpen ? 'Collapse all' : 'Expand all';
    });
    document.getElementById('snb-search').addEventListener('input', updateFilter);
    document.getElementById('snb-filter').addEventListener('change', updateFilter);
    form.addEventListener('input', function (event) {
        if (event.target.matches('[data-show]')) updateRowState(event.target.closest('[data-row]'));
        if (event.target.matches('[data-show],[data-label]')) { renderPreview(); updateFilter(); }
        if (event.target.name && event.target.name.indexOf('layout[') === 0) { updateConditionalFields(); renderPreview(); }
        markDirty();
    });
    form.addEventListener('change', function (event) { if (event.target.name && event.target.name.indexOf('layout[') === 0) { updateConditionalFields(); renderPreview(); } markDirty(); });
    form.addEventListener('submit', function () { renumber(list, '[data-row]', '[data-priority]'); var state = document.getElementById('snb-save-state'); state.textContent = 'Saving…'; state.className = 'is-saved'; });
    rows().forEach(updateRowState); updateMoveButtons(); updateConditionalFields(); updateFilter(); renderPreview(); initializing = false;
}());

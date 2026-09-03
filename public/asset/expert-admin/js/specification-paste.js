(function (window) {
	'use strict';

	function cellLines(cell) {
		var clone = cell.cloneNode(true);
		Array.prototype.slice.call(clone.querySelectorAll('br')).forEach(function (br) {
			br.parentNode.replaceChild(document.createTextNode('\n'), br);
		});

		return (clone.textContent || '')
			.replace(/\u00a0/g, ' ')
			.split(/\r?\n/)
			.map(function (line) { return line.replace(/[\t ]+/g, ' ').trim(); })
			.filter(function (line) { return line !== ''; });
	}

	function normalizeHtml(html) {
		if (!html || !/<table[\s>]/i.test(html) || typeof window.DOMParser !== 'function') return null;

		// The parsed document is inert. Copied scripts, styles, links, and images
		// are never inserted into the administration page.
		var clipboardDocument = new window.DOMParser().parseFromString(html, 'text/html');
		var tables = Array.prototype.slice.call(clipboardDocument.querySelectorAll('table'));
		if (!tables.length) return null;

		// A copied page can contain price/layout tables around the specification.
		tables.sort(function (left, right) {
			return right.querySelectorAll('tr').length - left.querySelectorAll('tr').length;
		});

		var output = [];
		var sectionCount = 0;
		var rowCount = 0;
		Array.prototype.slice.call(tables[0].querySelectorAll('tr')).forEach(function (row) {
			var cells = Array.prototype.slice.call(row.cells || []);
			if (!cells.length) return;

			var firstLines = cellLines(cells[0]);
			var first = firstLines.join(' ').trim();
			if (!first) return;

			if (cells.length === 1 || Number(cells[0].colSpan || 1) > 1) {
				output.push('[' + first.replace(/^\[|\]$/g, '') + ']');
				sectionCount++;
				return;
			}

			var valueLines = [];
			cells.slice(1).forEach(function (cell) {
				valueLines = valueLines.concat(cellLines(cell));
			});
			if (!valueLines.length) return;

			output.push(first + ': ' + valueLines.shift());
			valueLines.forEach(function (line) { output.push('    ' + line); });
			rowCount++;
		});

		if (!rowCount) return null;

		return {
			text: output.join('\n'),
			rowCount: rowCount,
			sectionCount: sectionCount
		};
	}

	window.LucentSpecificationPaste = {
		normalizeHtml: normalizeHtml
	};
}(window));

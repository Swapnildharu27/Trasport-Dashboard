/**
 * Lightweight click-to-sort for tables. Works entirely in the browser —
 * no page reload, no server round-trip. Skips any <th> with the
 * `data-no-sort` attribute (used for the Actions column).
 *
 * Usage: <table class="sortable-table"> ... </table>
 * Include this script once per page after the table markup.
 */
(function () {
    function getCellValue(row, index) {
        const cell = row.children[index];
        return cell ? cell.textContent.trim() : '';
    }

    function toComparable(value) {
        // Treat the "empty" placeholder as blank for sorting purposes
        const raw = value === '—' ? '' : value;

        // Strip currency symbols, commas, and surrounding spaces to test for a number
        const stripped = raw.replace(/[₹,\s]/g, '');
        if (stripped !== '' && !isNaN(stripped) && isFinite(stripped)) {
            return { isNumber: true, value: parseFloat(stripped) };
        }
        return { isNumber: false, value: raw.toLowerCase() };
    }

    function compareRows(a, b, index, direction) {
        const av = toComparable(getCellValue(a, index));
        const bv = toComparable(getCellValue(b, index));

        let result;
        if (av.isNumber && bv.isNumber) {
            result = av.value - bv.value;
        } else {
            result = av.value.localeCompare(bv.value);
        }
        return direction === 'asc' ? result : -result;
    }

    function initSortableTable(table) {
        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');
        if (!thead || !tbody) return;

        const headers = Array.from(thead.querySelectorAll('th'));

        headers.forEach((th, index) => {
            if (th.hasAttribute('data-no-sort')) return;

            th.classList.add('sortable');
            th.setAttribute('tabindex', '0');
            th.setAttribute('role', 'button');
            th.setAttribute('aria-label', 'Sort by ' + th.textContent.trim());

            const sort = () => {
                const currentDirection = th.getAttribute('data-sort-dir');
                const direction = currentDirection === 'asc' ? 'desc' : 'asc';

                headers.forEach(h => {
                    h.removeAttribute('data-sort-dir');
                    h.classList.remove('sorted-asc', 'sorted-desc');
                });
                th.setAttribute('data-sort-dir', direction);
                th.classList.add(direction === 'asc' ? 'sorted-asc' : 'sorted-desc');

                const rows = Array.from(tbody.querySelectorAll('tr'));
                // Don't try to sort a single "No records found" placeholder row
                if (rows.length <= 1 && rows[0] && rows[0].children.length === 1) return;

                rows.sort((a, b) => compareRows(a, b, index, direction));
                rows.forEach(row => tbody.appendChild(row));
            };

            th.addEventListener('click', sort);
            th.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    sort();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table.sortable-table').forEach(initSortableTable);
    });
})();

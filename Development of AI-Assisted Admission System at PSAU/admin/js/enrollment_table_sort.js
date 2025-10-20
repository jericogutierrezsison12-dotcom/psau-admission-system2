document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('.table');
    if (!table) return;

    const headers = table.querySelectorAll('th');
    let currentSort = {
        column: null,
        asc: true
    };

    // Add sort icons and click handlers to sortable headers
    headers.forEach((header, index) => {
        // Skip the "Select" column (index 0)
        if (index === 0) return;

        header.style.cursor = 'pointer';
        header.classList.add('sortable');
        
        // Add sort icons
        const iconSpan = document.createElement('span');
        iconSpan.className = 'ms-1';
        iconSpan.innerHTML = `
            <i class="bi bi-arrow-down-up text-muted"></i>
            <i class="bi bi-arrow-up d-none text-primary"></i>
            <i class="bi bi-arrow-down d-none text-primary"></i>
        `;
        header.appendChild(iconSpan);

        // Add click handler
        header.addEventListener('click', () => {
            sortTable(index);
            updateSortIcons(header);
        });
    });

    function sortTable(column) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Reset all sort icons
        headers.forEach(header => {
            if (header !== headers[column]) {
                const icons = header.querySelectorAll('.bi');
                icons.forEach(icon => {
                    if (icon.classList.contains('bi-arrow-down-up')) {
                        icon.classList.remove('d-none');
                    } else {
                        icon.classList.add('d-none');
                    }
                });
            }
        });

        // Determine sort direction
        if (currentSort.column === column) {
            currentSort.asc = !currentSort.asc;
        } else {
            currentSort.column = column;
            currentSort.asc = true;
        }

        // Sort the rows
        const sortedRows = rows.sort((a, b) => {
            let aVal = a.cells[column].textContent.trim();
            let bVal = b.cells[column].textContent.trim();

            // Check if values are numbers
            const aNum = parseFloat(aVal);
            const bNum = parseFloat(bVal);
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return currentSort.asc ? aNum - bNum : bNum - aNum;
            }

            // Sort as strings
            return currentSort.asc 
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal);
        });

        // Clear and re-append sorted rows
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }
        sortedRows.forEach(row => tbody.appendChild(row));
    }

    function updateSortIcons(header) {
        const icons = header.querySelectorAll('.bi');
        icons.forEach(icon => {
            if (icon.classList.contains('bi-arrow-down-up')) {
                icon.classList.add('d-none');
            } else if (icon.classList.contains('bi-arrow-up')) {
                icon.classList.toggle('d-none', !currentSort.asc);
            } else if (icon.classList.contains('bi-arrow-down')) {
                icon.classList.toggle('d-none', currentSort.asc);
            }
        });
    }
}); 
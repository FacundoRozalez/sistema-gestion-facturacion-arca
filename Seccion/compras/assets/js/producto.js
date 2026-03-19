// producto.js
console.log('Producto JS cargado');

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('productos-container');

    function crearDropdown(input) {
        const list = document.createElement('div');
        list.className = 'autocomplete-list';
        list.style.position = 'absolute';
        list.style.border = '1px solid #ccc';
        list.style.background = '#fff';
        list.style.zIndex = '1000';
        list.style.maxHeight = '150px';
        list.style.overflowY = 'auto';

        const rect = input.getBoundingClientRect();
        list.style.width = rect.width + 'px';
        list.style.top = rect.bottom + window.scrollY + 'px';
        list.style.left = rect.left + window.scrollX + 'px';

        const val = input.value.toLowerCase();
        const matches = productos.filter(prod => prod.nombre.toLowerCase().includes(val));

        matches.forEach(prod => {
            const item = document.createElement('div');
            const precio = parseFloat(prod.precio_compra) || 0;
            item.textContent = `${prod.nombre} ($${precio.toFixed(2)})`;
            item.style.padding = '5px';
            item.style.cursor = 'pointer';

            item.addEventListener('click', () => {
                input.value = prod.nombre;
                const row = input.closest('.producto-row');
                row.querySelector('.id-producto').value = prod.id_producto;
                row.querySelector('input[name="precios[]"]').value = precio;
                list.remove();
            });

            list.appendChild(item);
        });

        document.body.appendChild(list);

        document.addEventListener('click', (e) => {
            if (e.target !== input) list.remove();
        }, { once: true });
    }

    container.addEventListener('input', e => {
        if (e.target.classList.contains('buscar-producto')) {
            const existingList = document.querySelector('.autocomplete-list');
            if (existingList) existingList.remove();
            if (e.target.value.length >= 2) {
                crearDropdown(e.target);
            }
        }
    });

    // Botón para agregar nueva fila
    const addBtn = document.getElementById('add-product');
    addBtn.addEventListener('click', () => {
        const newRow = container.children[0].cloneNode(true);
        newRow.querySelectorAll('input').forEach(inp => {
            if (inp.type !== 'hidden') inp.value = '';
            if (inp.classList.contains('id-producto')) inp.value = '';
        });
        container.appendChild(newRow);
    });

    // Eliminar fila
    container.addEventListener('click', e => {
        if (e.target.classList.contains('remove-product')) {
            if (container.children.length > 1) {
                e.target.closest('.producto-row').remove();
            } else {
                alert('Debe haber al menos un producto');
            }
        }
    });
});

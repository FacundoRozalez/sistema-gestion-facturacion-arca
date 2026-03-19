document.addEventListener('DOMContentLoaded', () => {

    const tabla = document.getElementById('tablaCompras');
    const tbody = tabla.querySelector('tbody');
    const totalGeneralDiv = document.getElementById('totalGeneral');
    const inputBuscar = document.getElementById('buscarCompra');

    // Render dinámico de compras
    function renderCompras(comprasFiltradas) {
        tbody.innerHTML = '';

        if (comprasFiltradas.length === 0) {
            tabla.style.display = 'none';
            totalGeneralDiv.textContent = `Total: $0.00`;
            return;
        }

        tabla.style.display = 'table';

        comprasFiltradas.forEach(compra => {
            const tr = document.createElement('tr');
            tr.dataset.idCompra = compra.id_compra;

            // Render de productos con lote y precio unitario
            let productosHTML = '';
            if (window.detallesCompras[compra.id_compra]) {
                window.detallesCompras[compra.id_compra].forEach(det => {
                    let lote = det.lote ? det.lote : '-';
                    productosHTML += `${det.producto_nombre} (Cant: ${det.cantidad}, Lote: ${lote},Precio Unitario: $${parseFloat(det.precio_unitario).toFixed(2)})<br>`;
                });
            }

            tr.innerHTML = `
                <td>${compra.id_compra}</td>
                <td>${compra.proveedor}</td>
                <td>${compra.fecha}</td>
                <td>$${parseFloat(compra.total).toFixed(2)}</td>
                <td>${productosHTML}</td>
                <td><button class="remove-compra">Eliminar</button></td>
            `;

            tbody.appendChild(tr);
        });

        actualizarTotal();
    }

    // Calcular total general
    function actualizarTotal() {
        let total = 0;
        tbody.querySelectorAll('tr').forEach(fila => {
            total += parseFloat(fila.children[3].textContent.replace('$','')) || 0;
        });
        totalGeneralDiv.textContent = `Total: $${total.toFixed(2)}`;
    }

    // Delegación de evento de eliminar
    tbody.addEventListener('click', function(e) {
        if (!e.target.classList.contains('remove-compra')) return;

        if (!confirm('¿Seguro que desea eliminar esta compra?')) return;

        const fila = e.target.closest('tr');
        const idCompra = fila.dataset.idCompra;

        fetch('eliminar_compra.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_compra: idCompra })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fila.remove();
                actualizarTotal();
                alert('Compra eliminada correctamente.');
            } else {
                alert('Error al eliminar la compra: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión al eliminar la compra.');
        });
    });

    // Filtrado dinámico
    inputBuscar.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        if (query === '') {
            tbody.innerHTML = '';
            tabla.style.display = 'none';
            totalGeneralDiv.textContent = `Total: $0.00`;
            return;
        }

        const filtradas = window.compras.filter(compra => {
            if (compra.id_compra.toString().includes(query)) return true;
            if (compra.proveedor.toLowerCase().includes(query)) return true;
            if (compra.fecha.toLowerCase().includes(query)) return true;

            const detalles = window.detallesCompras[compra.id_compra] || [];
            for (let det of detalles) {
                if (det.producto_nombre.toLowerCase().includes(query)) return true;
                if (det.lote && det.lote.toLowerCase().includes(query)) return true;
            }

            return false;
        });

        renderCompras(filtradas);
    });

    // Inicialmente tabla oculta
    tbody.innerHTML = '';
    tabla.style.display = 'none';
    totalGeneralDiv.textContent = `Total: $0.00`;
});

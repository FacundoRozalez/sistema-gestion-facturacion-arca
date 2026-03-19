console.log('Compras JS cargado');

document.addEventListener('DOMContentLoaded', () => {
    const filas = document.querySelectorAll('.compra-row');

    filas.forEach(fila => {
        fila.addEventListener('click', () => {
            const id = fila.dataset.id;
            const detalle = document.getElementById('detalle-' + id);
            if (!detalle) return; // Evita errores si no existe
            detalle.style.display = detalle.style.display === 'none' || detalle.style.display === '' ? 'table-row' : 'none';
        });
    });

    // Función para calcular total de productos seleccionados
    function calcularTotal() {
        let total = 0;
        const filasProductos = document.querySelectorAll('#productosSeleccionados tr');
        filasProductos.forEach(row => {
            const precio = parseFloat(row.querySelector('.precio').value) || 0;
            const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
            const subtotal = precio * cantidad;

            const subtotalEl = row.querySelector('.subtotal');
            if (subtotalEl) {
                subtotalEl.textContent = '$' + subtotal.toFixed(2);
            }

            total += subtotal;
        });

        const totalCompraEl = document.getElementById('totalCompra');
        if (totalCompraEl) {
            totalCompraEl.textContent = 'Total: $' + total.toFixed(2);
        }
    }

    // Recalcular total cuando cambien cantidades o precios
    const container = document.getElementById('productos-container');
    if (container) {
        container.addEventListener('input', e => {
            if (e.target.classList.contains('cantidad') || e.target.classList.contains('precio')) {
                calcularTotal();
            }
        });
    }

    // Inicializar cálculo al cargar
    calcularTotal();
});

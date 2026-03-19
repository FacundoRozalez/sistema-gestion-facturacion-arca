// total.js
console.log('Total JS cargado');

function calcularTotal() {
    let total = 0;
    document.querySelectorAll('#productos-container .producto-row').forEach(row => {
        const precio = parseFloat(row.querySelector('input[name="precios[]"]').value) || 0;
        const cantidad = parseFloat(row.querySelector('input[name="cantidades[]"]').value) || 0;
        const subtotal = precio * cantidad;

        // Crear span para mostrar subtotal si no existe
        let spanSubtotal = row.querySelector('.subtotal');
        if (!spanSubtotal) {
            spanSubtotal = document.createElement('span');
            spanSubtotal.className = 'subtotal';
            spanSubtotal.style.marginLeft = '10px';
            row.appendChild(spanSubtotal);
        }

        spanSubtotal.textContent = 'Subtotal: $' + subtotal.toFixed(2);
        total += subtotal;
    });

    const totalCompra = document.getElementById('totalCompra');
    if (totalCompra) {
        totalCompra.textContent = 'Total: $' + total.toFixed(2);
    }
}

// Ejecutar cálculo al cambiar cantidad o precio
document.addEventListener('input', e => {
    if (e.target.matches('input[name="cantidades[]"], input[name="precios[]"]')) {
        calcularTotal();
    }
});

// Ejecutar al cargar la página para valores iniciales
document.addEventListener('DOMContentLoaded', calcularTotal);

<h3>Productos</h3>
<div id="productos-container">
    <div class="producto-row">
        <input type="text" name="buscar_producto[]" class="buscar-producto" placeholder="Buscar producto">
        <input type="hidden" name="productos[]" class="id-producto" value="">
        Cantidad: <input type="number" name="cantidades[]" value="1" min="1">
        Precio unitario: <input type="number" name="precios[]" value="0" step="0.01" min="0">
        Lote: <input type="text" name="lotes[]" value="">
        <span class="remove-product" style="cursor:pointer;color:red;">[Eliminar]</span>
    </div>
</div>
<button type="button" id="add-product">Agregar Producto</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contenedor = document.getElementById('productos-container');
    const addBtn = document.getElementById('add-product');

    addBtn.addEventListener('click', () => {
        const nuevaFila = document.createElement('div');
        nuevaFila.classList.add('producto-row');
        nuevaFila.innerHTML = `
            <input type="text" name="buscar_producto[]" class="buscar-producto" placeholder="Buscar producto">
            <input type="hidden" name="productos[]" class="id-producto" value="">
            Cantidad: <input type="number" name="cantidades[]" value="1" min="1">
            Precio unitario: <input type="number" name="precios[]" value="0" step="0.01" min="0">
            Lote: <input type="text" name="lotes[]" value="">
            <span class="remove-product" style="cursor:pointer;color:red;">[Eliminar]</span>
        `;
        contenedor.appendChild(nuevaFila);
        attachRemoveEvent(nuevaFila.querySelector('.remove-product'));
    });

    function attachRemoveEvent(span) {
        span.addEventListener('click', () => {
            span.parentElement.remove();
        });
    }

    document.querySelectorAll('.remove-product').forEach(span => attachRemoveEvent(span));
});
</script>

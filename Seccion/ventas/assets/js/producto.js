console.log("✅ producto.js cargado correctamente");

document.addEventListener('DOMContentLoaded', () => {

    // --- ELEMENTOS DEL DOM ---
    const buscarProducto = document.getElementById('buscarProducto');
    const tablaProductosEncontrados = document.getElementById('tablaProductosEncontrados');
    const tbodyEncontrados = tablaProductosEncontrados?.querySelector('tbody');

    const tablaSeleccionados = document.getElementById('tablaProductosSeleccionados');
    const tbodySeleccionados = tablaSeleccionados?.querySelector('tbody');

    const baseProductosEl = document.getElementById('baseProductos');
    const IVAEl = document.getElementById('IVA');
    const totalFinalEl = document.getElementById('totalFinal');

    const manualNombre = document.getElementById('manualNombre');
    const manualPrecio = document.getElementById('manualPrecio');
    const manualCantidad = document.getElementById('manualCantidad');
    const btnAgregarManual = document.getElementById('btnAgregarManual');

    const btnFacturaAFIP = document.getElementById('btnFacturaAFIP');
    const btnVentaComun = document.getElementById('btnVentaComun');

    // --- FUNCIONES UTILES ---
    function escapeHtml(text) {
        if (typeof text !== 'string') text = text == null ? '' : String(text);
        return text.replace(/&/g,"&amp;")
                   .replace(/</g,"&lt;")
                   .replace(/>/g,"&gt;")
                   .replace(/"/g,"&quot;")
                   .replace(/'/g,"&#039;");
    }

    function limpiarPrecio(texto){
        if(!texto) return 0;
        return parseFloat(String(texto).replace(/[^0-9.-]+/g,"")) || 0;
    }

    // --- ACTUALIZAR RESUMEN ---
    function actualizarResumenTotal() {
        if(!tbodySeleccionados) return;

        let subtotal = 0;

        tbodySeleccionados.querySelectorAll('tr').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.cantidad')?.value) || 1;
            const precioCompra = limpiarPrecio(row.querySelector('.precioCompra')?.textContent || row.querySelector('.precioCompra')?.value);
            const porcGanancia = parseFloat(row.querySelector('.porcGanancia')?.value) || 0;
            const descuento = parseFloat(row.querySelector('.descuento')?.value) || 0;

            const precioBaseUnitario = precioCompra * (1 + porcGanancia / 100);
            const precioBaseTotal = precioBaseUnitario * cantidad;
            const precioFinalTotal = precioBaseTotal * (1 - descuento / 100);

            row.querySelector('.precioBase').textContent = precioBaseTotal.toFixed(2);
            row.querySelector('.precioVenta').textContent = precioFinalTotal.toFixed(2);

            subtotal += precioFinalTotal;
        });

        if(baseProductosEl) baseProductosEl.textContent = subtotal.toFixed(2);

        const tipoVenta = document.querySelector('input[name="tipo_venta"]:checked')?.value || 'AFIP';
        const alicuota = parseFloat(document.getElementById('alicuotaIVA')?.value) || 21;

        if(tipoVenta === 'AFIP') {
            const iva = subtotal * alicuota / 100;
            if(IVAEl) IVAEl.textContent = iva.toFixed(2);
            if(totalFinalEl) totalFinalEl.textContent = (subtotal + iva).toFixed(2);
        } else {
            if(IVAEl) IVAEl.textContent = '0.00';
            if(totalFinalEl) totalFinalEl.textContent = subtotal.toFixed(2);
        }

    }

    // --- ACTUALIZAR JSON ---
    function actualizarProductosJson() {
        if(!tbodySeleccionados) return;

        const productos = [];

        tbodySeleccionados.querySelectorAll('tr').forEach(tr => {
            const nombre = tr.querySelector('.nombre')?.textContent.trim() || '';
            const cantidad = parseInt(tr.querySelector('.cantidad')?.value) || 1;
            const precioCompra = limpiarPrecio(tr.querySelector('.precioCompra')?.textContent || tr.querySelector('.precioCompra')?.value);
            const porcGanancia = parseFloat(tr.querySelector('.porcGanancia')?.value) || 0;
            const descuento = parseFloat(tr.querySelector('.descuento')?.value) || 0;
            const precioBase = precioCompra * (1 + porcGanancia/100);
            const precioFinal = precioBase * cantidad * (1 - descuento / 100);
            const idProducto = tr.dataset.idProducto || 0;

            productos.push({
                Nombre: nombre,
                Cantidad: cantidad,
                PrecioCompra: precioCompra,
                PorcentajeGanancia: porcGanancia,
                Descuento: descuento,
                PrecioBase: precioBase,
                PrecioFinal: precioFinal,
                IdProducto: idProducto
            });
        });

        const inputProductos = document.getElementById('productos_json');
        if(inputProductos) inputProductos.value = JSON.stringify(productos);
    }

    function agregarProductoSeleccionado(nombre, precioCompra, cantidad = 1, idProducto = 0) {
    if(!tbodySeleccionados) return;

    const porcGananciaDefault = 30;
    const descuentoDefault = 0; // valor inicial del descuento

    // Cálculo inicial
    const precioBaseUnitario = precioCompra * (1 + porcGananciaDefault / 100);
    const precioBaseTotal = precioBaseUnitario * cantidad;
    const precioFinalTotal = precioBaseTotal * (1 - descuentoDefault / 100);

    const trSel = document.createElement('tr');
    trSel.dataset.idProducto = idProducto;
    trSel.innerHTML = `
        <td class="nombre">${escapeHtml(nombre)}</td>
        <td><input type="number" class="cantidad" value="${cantidad}" min="1" style="width:50px;"></td>
        <td>$<span class="precioCompra">${parseFloat(precioCompra).toFixed(2)}</span> <button type="button" class="modificarPrecioBtn">✎</button></td>
        <td>
            <select class="porcGanancia" style="width:70px;">
                <option value="30" selected>30%</option>
                <option value="50">50%</option>
                <option value="100">100%</option>
            </select>
        </td>
        <td>$<span class="precioBase">${precioBaseTotal.toFixed(2)}</span></td>
        <td><input type="number" class="descuento" value="${descuentoDefault}" min="0" max="100" style="width:50px;">%</td>
        <td>$<span class="precioVenta">${precioFinalTotal.toFixed(2)}</span></td>
        <td><button type="button" class="quitarBtn">Quitar</button></td>
    `;
    tbodySeleccionados.appendChild(trSel);
    if(tablaSeleccionados) tablaSeleccionados.style.display = 'table';

    // --- Eventos ---
    trSel.querySelector('.cantidad')?.addEventListener('input', () => { actualizarResumenTotal(); actualizarProductosJson(); });
    trSel.querySelector('.porcGanancia')?.addEventListener('change', () => { actualizarResumenTotal(); actualizarProductosJson(); });
    trSel.querySelector('.descuento')?.addEventListener('input', () => { actualizarResumenTotal(); actualizarProductosJson(); });

    trSel.querySelector('.modificarPrecioBtn')?.addEventListener('click', () => {
        const span = trSel.querySelector('.precioCompra');
        const nuevo = prompt('Ingrese nuevo precio de compra:', span?.textContent);
        if(nuevo && !isNaN(nuevo)) {
            span.textContent = parseFloat(nuevo).toFixed(2);
            actualizarResumenTotal();
            actualizarProductosJson();
        }
    });

    trSel.querySelector('.quitarBtn')?.addEventListener('click', () => { 
        trSel.remove(); 
        actualizarResumenTotal(); 
        actualizarProductosJson(); 
    });

    // Actualizar resumen y JSON al agregar el producto
    actualizarResumenTotal();
    actualizarProductosJson();
}


    /// --- FUNCION PARA CARGAR PRODUCTOS (BUSQUEDA) ---
async function cargarProductos(buscar){
    if(!buscar || buscar.length < 2) {
        tbodyEncontrados.innerHTML = '';
        tablaProductosEncontrados.style.display = 'none';
        return;
    }

    const url = `/CristianFerreteria/Seccion/ventas/includes/producto_buscar.php?buscar=${encodeURIComponent(buscar)}`;
    try {
        const res = await fetch(url);
        const data = await res.json();

        tbodyEncontrados.innerHTML = '';
        const uploadsPath = '/CristianFerreteria/Seccion/productos/uploads/';

        if(data.length > 0){
            data.forEach(prod => {
                const precioCompra = parseFloat(prod.precio_compra).toFixed(2);

                const tr = document.createElement('tr');

                // Crear celda de imagen con evento de agrandar
                const tdImg = document.createElement('td');
                if(prod.imagen){
                    const imgEl = document.createElement('img');
                    imgEl.src = uploadsPath + prod.imagen;
                    imgEl.width = 50;
                    imgEl.style.cursor = 'pointer';

                    imgEl.addEventListener('click', () => {
                        const modal = document.createElement('div');
                        modal.style.position = 'fixed';
                        modal.style.top = '0';
                        modal.style.left = '0';
                        modal.style.width = '100%';
                        modal.style.height = '100%';
                        modal.style.background = 'rgba(0,0,0,0.7)';
                        modal.style.display = 'flex';
                        modal.style.alignItems = 'center';
                        modal.style.justifyContent = 'center';
                        modal.style.zIndex = '9999';
                        modal.innerHTML = `<img src="${uploadsPath + prod.imagen}" style="max-width:90%; max-height:90%;">`;
                        modal.addEventListener('click', () => modal.remove());
                        document.body.appendChild(modal);
                    });

                    tdImg.appendChild(imgEl);
                } else {
                    tdImg.innerHTML = `<div style="width:50px;height:50px;background:#f0f0f0;text-align:center;line-height:50px;color:#999;">IMG</div>`;
                }

                // Otras celdas
                const tdNombre = document.createElement('td');
                tdNombre.textContent = prod.nombre;

                                // 👇 nueva celda
                const tdDescripcion = document.createElement('td');
                tdDescripcion.textContent = prod.descripcion || '—';
                
                const tdMarca = document.createElement('td');
                tdMarca.textContent = prod.marca || '—'; // si no hay marca, mostrar '—'

                const tdStock = document.createElement('td');
                tdStock.textContent = prod.stock != null ? prod.stock : '0'; // mostrar 0 si no hay stock

                const tdBtn = document.createElement('td');
                const btn = document.createElement('button');

                btn.type = 'button';
                btn.textContent = 'Agregar';
                btn.className = 'agregarBtn';
                btn.addEventListener('click', async () => {
    console.log('Click en agregar producto'); // LOG EXTRA
    const urlAgregar = `/CristianFerreteria/Seccion/ventas/includes/producto_buscar.php?buscar=${encodeURIComponent(prod.nombre)}&agregar=1`;
    try {
        const res = await fetch(urlAgregar);
        const data = await res.json();
        console.log('Respuesta producto_buscar:', data); // LOG PARA DEPURAR ID
        // Seleccionar el producto original con stock > 0
        const prodOriginal = data.find(p => p.stock > 0);
        if (prodOriginal) {
            const precioCompra = parseFloat(prodOriginal.precio_compra || 0).toFixed(2);
            agregarProductoSeleccionado(prodOriginal.nombre, precioCompra, 1, prodOriginal.id_producto);
        } else {
            alert('No hay stock disponible para este producto.');
        }
    } catch(err){
        console.error(err);
        // fallback en caso de error
        agregarProductoSeleccionado(prod.nombre, 0, 1, prod.id_producto);
    }
    // Limpiar búsqueda
    tablaProductosEncontrados.style.display = 'none';
    tbodyEncontrados.innerHTML = '';
    buscarProducto.value = '';
});

                tdBtn.appendChild(btn);

                // Agregar celdas a la fila
                tr.appendChild(tdImg);
                tr.appendChild(tdNombre);
                tr.appendChild(tdDescripcion);
                tr.appendChild(tdMarca);   // 👈 agregada
                tr.appendChild(tdStock);
                tr.appendChild(tdBtn);

                tbodyEncontrados.appendChild(tr);
            });

            tablaProductosEncontrados.style.display = 'table';
        } else {
            tablaProductosEncontrados.style.display = 'none';
        }

    } catch(err){
        console.error(err);
    }
}


    // --- BUSQUEDA PRODUCTOS ---
    buscarProducto?.addEventListener('input', () => {
        const texto = buscarProducto.value.trim();
        cargarProductos(texto);
    });

    // --- AGREGAR PRODUCTO MANUAL ---
    btnAgregarManual?.addEventListener('click', async () => {
        const nombre = manualNombre?.value.trim();
        const precio = parseFloat(manualPrecio?.value);
        const cantidad = parseInt(manualCantidad?.value) || 1;

        if(!nombre || isNaN(precio)){
            alert('Ingrese nombre y precio válido');
            return;
        }

        // Validación mejorada: verificar si el producto ya existe en la base de datos (ignorando mayúsculas, minúsculas y espacios)
        const url = `/CristianFerreteria/Seccion/ventas/includes/producto_buscar.php?buscar=${encodeURIComponent(nombre)}`;
        try {
            const res = await fetch(url);
            const data = await res.json();
            const nombreNormalizado = nombre.toLowerCase().replace(/\s+/g, '');
            const existe = data.some(prod => (prod.nombre || '').toLowerCase().replace(/\s+/g, '') === nombreNormalizado);
            if(existe){
                alert('Este producto ya existe en la base de datos. Por favor, búsquelo y agréguelo desde la búsqueda.');
                return;
            }
        } catch(err){
            console.error('Error al verificar producto:', err);
        }

        agregarProductoSeleccionado(nombre, precio, cantidad);
        manualNombre.value = '';
        manualPrecio.value = '';
        manualCantidad.value = 1;
    });

    // --- MODO PRODUCTO ---
    document.querySelectorAll('input[name="modo_producto"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.getElementById('buscarProductoContainer').style.display = radio.value === 'buscar' ? 'block' : 'none';
            document.getElementById('productoManualContainer').style.display = radio.value === 'manual' ? 'block' : 'none';
        });
    });

    // --- BOTONES SEGÚN TIPO DE VENTA ---
    function actualizarBotonesVenta() {
        const tipo = document.querySelector('input[name="tipo_venta"]:checked')?.value;
        if(tipo === 'AFIP'){ btnFacturaAFIP.disabled = false; btnVentaComun.disabled = true; }
        else if(tipo === 'COMUN'){ btnFacturaAFIP.disabled = true; btnVentaComun.disabled = false; }
    }

    btnFacturaAFIP?.addEventListener('click', e => { 
        if(btnFacturaAFIP.disabled){ 
            e.preventDefault(); 
            alert('Esta opción no está disponible para el tipo de venta actual.'); 
        } 
    });
    btnVentaComun?.addEventListener('click', e => { 
        if(btnVentaComun.disabled){ 
            e.preventDefault(); 
            alert('Esta opción no está disponible para el tipo de venta actual.'); 
        } 
    });
    document.querySelectorAll('input[name="tipo_venta"]').forEach(radio => radio.addEventListener('change', actualizarBotonesVenta));
    actualizarBotonesVenta();

    // --- INICIALIZACION ---
    actualizarResumenTotal();
});

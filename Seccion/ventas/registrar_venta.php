<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require __DIR__ . '/../../Panel/includes/conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Registrar Venta</title>
<link rel="stylesheet" href="/CristianFerreteria/Seccion/ventas/assets/css/estilos.css">
</head>
<body id="ventas">

<h2>Registrar Venta</h2>

<form method="post" id="formVenta">

<!-- ===== TIPO DE VENTA ===== -->
<div class="form-section">
    <h3>Tipo de Venta</h3>
    <label><input type="radio" name="tipo_venta" value="AFIP" checked> ARCA</label>
    <label><input type="radio" name="tipo_venta" value="COMUN"> Común</label>
</div>

<!-- ===== CLIENTE ===== -->
<div class="form-section">
    <h3>Cliente</h3>
    <label><input type="radio" name="modo_cliente" value="existente" checked> Cliente existente</label>
    <label><input type="radio" name="modo_cliente" value="nuevo"> Nuevo cliente</label>

    <div id="clienteExistente" class="visible" style="margin-top:10px;">
        <input type="text" id="buscarCliente" placeholder="Buscar cliente por nombre, apellido, DNI, teléfono o email">
        <div id="clientesEncontrados" style="max-height:200px; overflow-y:auto;"></div>
    </div>

    <div id="clienteFormulario" class="hidden" style="margin-top:10px;">
        <input type="text" name="nuevo_nombre" placeholder="Nombre" id="nuevo_nombre"><br>
        <input type="text" name="nuevo_apellido" placeholder="Apellido" id="nuevo_apellido"><br>
        <input type="text" name="nuevo_dni" placeholder="DNI/CUIT" id="nuevo_dni"><br>
        <input type="text" name="nuevo_telefono" placeholder="Teléfono" id="nuevo_telefono"><br>
        <input type="email" name="nuevo_email" placeholder="Email" id="nuevo_email"><br>
        <input type="text" name="nuevo_direccion" placeholder="Dirección" id="nuevo_direccion">
    </div>

    <div id="clienteSeleccionado" class="hidden" style="margin-top:10px; padding:5px; border:1px solid #ccc; background:#f9f9f9;">
        <strong>Cliente seleccionado:</strong>
        <p id="infoCliente"></p>
        <button type="button" id="cambiarClienteBtn">Cambiar Cliente</button>
    </div>

    <input type="hidden" name="id_cliente" id="id_cliente_hidden">
    <input type="hidden" name="cliente_json" id="cliente_json_hidden">
</div>

<!-- ===== TIPO DE COMPROBANTE ===== -->
<div class="form-section">
    <h3>Tipo de Comprobante</h3>
    <select id="tipo_comprobante" name="tipo_comprobante">
        <option value="1">Factura A</option>
        <option value="6">Factura B</option>
        <option value="4">Factura M (Monotributo)</option>
    </select>
</div>

<!-- ===== PRODUCTOS ===== -->
<div class="form-section">
    <h3>Productos</h3>

    <label><input type="radio" name="modo_producto" value="buscar" checked> Buscar producto</label>
    <label><input type="radio" name="modo_producto" value="manual"> Agregar manualmente</label>

    <div id="buscarProductoContainer" style="margin-top:10px;">
    <input type="text" id="buscarProducto" placeholder="Escriba para buscar productos">

    <!-- Contenedor para el scroll -->
    <div id="tablaProductosEncontradosContainer" style="max-height:300px; overflow-y:auto;">
        <table id="tablaProductosEncontrados">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Marca</th> <!-- nueva columna -->
                    <th>Stock</th>
                    <th>Agregar</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

    <div id="productoManualContainer" style="display:none; margin-top:10px;">
        <input type="text" id="manualNombre" placeholder="Nombre producto" />
        <input type="number" id="manualPrecio" placeholder="Precio de Compra" step="0.01" min="0" />
        <button type="button" id="btnAgregarManual">Agregar manual</button>
    </div>

    <h3>Productos seleccionados</h3>
    <table id="tablaProductosSeleccionados" style="display:none; margin-top:10px;">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Cantidad</th>
                <th>Precio Compra</th>
                <th>Porcentaje Ganancia</th>
                <th>Precio Base</th>
                <th>Descuento (%)</th>
                <th>Precio Final</th>
                <th>Quitar Producto</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- ===== RESUMEN TOTAL ===== -->
<div class="form-section" id="resumenTotal">
    <h3>Resumen Total</h3>
    <p>Sub Total: $<span id="baseProductos">0.00</span></p>
    <p>IVA: $<span id="IVA">0.00</span></p>
    <p><strong>Total final a pagar: $<span id="totalFinal">0.00</span></strong></p>
</div>

<!-- INPUTS OCULTOS -->
<input type="hidden" id="alicuotaIVA" value="21">
<input type="hidden" name="productos" id="productos_json">
<input type="hidden" name="tipo_venta_hidden" id="tipo_venta_hidden">

<!-- ALERTAS AMIGABLES -->
<div id="alertaFormulario" style="display:none; padding:10px; margin-bottom:10px; border:1px solid #f44336; background:#fdecea; color:#a94442; border-radius:5px;"></div>

<!-- BOTONES -->
<button type="submit" id="btnFacturaAFIP" formaction="/CristianFerreteria/Seccion/ventas/generar_factura.php" formtarget="_blank">Generar Factura ARCA</button>
<button type="submit" id="btnVentaComun" formaction="/CristianFerreteria/Seccion/ventas/generar_venta_comun.php" formtarget="_blank">Generar Venta Común</button>
<button type="submit" id="btnPresupuesto" formaction="/CristianFerreteria/Seccion/ventas/generar_presupuesto.php" formtarget="_blank">Generar Presupuesto PDF</button>

<!-- SCRIPTS -->
<script src="/CristianFerreteria/Seccion/ventas/assets/js/cliente.js"></script>
<script src="/CristianFerreteria/Seccion/ventas/assets/js/producto.js"></script>

<script>
// =======================================
// Funciones auxiliares
// =======================================
function getTipoVenta() {
    return document.querySelector('input[name="tipo_venta"]:checked')?.value || 'AFIP';
}

function actualizarBotonesVenta() {
    const tipo = getTipoVenta();
    document.getElementById('btnFacturaAFIP').disabled = tipo !== 'AFIP';
    document.getElementById('btnVentaComun').disabled = tipo === 'AFIP';
}

function actualizarTipoComprobante() {
    const tipoVenta = getTipoVenta();
    const select = document.getElementById('tipo_comprobante');
    select.innerHTML = '';
    if (tipoVenta === 'COMUN') {
        const opcion = document.createElement('option');
        opcion.value = '0';
        opcion.textContent = 'Factura Común';
        select.appendChild(opcion);
    } else if (tipoVenta === 'AFIP') {
        const opcion = document.createElement('option');
        opcion.value = '6';
        opcion.textContent = 'Factura B';
        select.appendChild(opcion);
    }
}

// =======================================
// Actualizar resumen de productos
// =======================================
function actualizarResumenTotalProductos() {
    const tbodySeleccionados = document.querySelector('#tablaProductosSeleccionados tbody');
    if (!tbodySeleccionados) return;

    const tipoVenta = getTipoVenta(); // 'AFIP' o 'COMUN'
    const alicuota = parseFloat(document.getElementById('alicuotaIVA')?.value) || 21;
    let subtotal = 0;

    tbodySeleccionados.querySelectorAll('tr').forEach(row => {
        const cantidad = parseFloat(row.querySelector('.cantidad')?.value) || 1;
        const precioCompra = parseFloat(row.querySelector('.precioCompra')?.textContent) || 0;
        const porcentajeGanancia = parseFloat(row.querySelector('.porcGanancia')?.value) || 0;
        const descuento = parseFloat(row.querySelector('.descuento')?.value) || 0;

        // Precio base y precio final SIN IVA
        const precioBase = cantidad * precioCompra * (1 + porcentajeGanancia / 100);
        const precioConDescuento = precioBase * (1 - descuento / 100);

        // Actualizar fila
        row.querySelector('.precioBase').textContent = precioBase.toFixed(2);
        row.querySelector('.precioVenta').textContent = precioConDescuento.toFixed(2);

        subtotal += precioConDescuento;
    });

    const baseProductosEl = document.getElementById('baseProductos').parentElement;
    const ivaEl = document.getElementById('IVA').parentElement;

    if (tipoVenta === 'AFIP') {
        const totalIVA = subtotal * alicuota / 100;
        baseProductosEl.style.display = 'block';
        ivaEl.style.display = 'block';
        document.getElementById('baseProductos').textContent = subtotal.toFixed(2);
        document.getElementById('IVA').textContent = totalIVA.toFixed(2);
        document.getElementById('totalFinal').textContent = (subtotal + totalIVA).toFixed(2);
    } else {
        baseProductosEl.style.display = 'none';
        ivaEl.style.display = 'none';
        document.getElementById('totalFinal').textContent = subtotal.toFixed(2);
    }
}

// =======================================
// Eventos dinámicos
// =======================================
document.querySelectorAll('input[name="tipo_venta"]').forEach(radio => {
    radio.addEventListener('change', function() {
        actualizarBotonesVenta();
        actualizarTipoComprobante();
        actualizarResumenTotalProductos();
    });
});

const tbodySeleccionados = document.querySelector('#tablaProductosSeleccionados tbody');
if (tbodySeleccionados) {
    tbodySeleccionados.addEventListener('input', actualizarResumenTotalProductos);
    tbodySeleccionados.addEventListener('change', actualizarResumenTotalProductos);
}

// =======================================
// Manejo envío formulario
// =======================================
document.getElementById('formVenta').addEventListener('submit', function(e) {
    const alertaDiv = document.getElementById('alertaFormulario');
    alertaDiv.style.display = 'none'; // oculta alertas previas
    alertaDiv.innerHTML = '';

    // ====================================
    // Validación de campos obligatorios
    // ====================================
    let clienteValido = false;

    if(document.querySelector('input[name="modo_cliente"]:checked').value === 'existente') {
        const idCliente = document.getElementById('id_cliente_hidden').value;
        if(idCliente && idCliente !== "") clienteValido = true;
    } else { // cliente nuevo
        const nombre = document.getElementById('nuevo_nombre').value.trim();
        const apellido = document.getElementById('nuevo_apellido').value.trim();
        if(nombre !== "" && apellido !== "") clienteValido = true;
    }

    const productos = document.querySelectorAll('#tablaProductosSeleccionados tbody tr');
    const productosValido = productos.length > 0;

    if(!clienteValido || !productosValido) {
        e.preventDefault(); // bloquea envío
        let mensaje = "<strong>Por favor complete los siguientes campos obligatorios:</strong><ul>";
        if(!clienteValido) mensaje += "<li>Cliente</li>";
        if(!productosValido) mensaje += "<li>Producto</li>";
        mensaje += "</ul>";
        alertaDiv.innerHTML = mensaje;
        alertaDiv.style.display = 'block';
        alertaDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    // ====================================
    // Construir JSON de productos
    // ====================================
    const productosSeleccionados = [];
    productos.forEach(row => {
        const nombre = row.querySelector('.nombre')?.textContent || '';
        const cantidad = parseFloat(row.querySelector('.cantidad')?.value) || 1;
        const precioCompra = parseFloat(row.querySelector('.precioCompra')?.textContent) || 0;
        const porcentajeGanancia = parseFloat(row.querySelector('.porcGanancia')?.value) || 0;
        const descuento = parseFloat(row.querySelector('.descuento')?.value) || 0;

        const precioBase = cantidad * precioCompra * (1 + porcentajeGanancia / 100);
        const precioFinal = precioBase * (1 - descuento / 100);

        productosSeleccionados.push({
            Nombre: nombre,
            Cantidad: cantidad,
            PrecioCompra: precioCompra,
            PorcentajeGanancia: porcentajeGanancia,
            Descuento: descuento,
            PrecioBase: precioBase,
            PrecioFinal: precioFinal,
            IdProducto: row.dataset.idProducto || 0
        });
    });

    document.getElementById('productos_json').value = JSON.stringify(productosSeleccionados);
    document.getElementById('tipo_venta_hidden').value = getTipoVenta();

    // ====================================
    // Cliente nuevo en JSON
    // ====================================
    if (document.querySelector('input[name="modo_cliente"]:checked').value === 'nuevo') {
        const nuevoCliente = {
            nombre: document.getElementById('nuevo_nombre').value,
            apellido: document.getElementById('nuevo_apellido').value,
            dni: document.getElementById('nuevo_dni').value,
            telefono: document.getElementById('nuevo_telefono').value,
            email: document.getElementById('nuevo_email').value,
            direccion: document.getElementById('nuevo_direccion').value
        };
        document.getElementById('cliente_json_hidden').value = JSON.stringify(nuevoCliente);
    }
});


// =======================================
// Bloquear Enter
// =======================================
document.getElementById('formVenta').addEventListener('keydown', function(e) {
    if(e.key === 'Enter') e.preventDefault();
});

// =======================================
// Inicialización al cargar la página
// =======================================
document.addEventListener('DOMContentLoaded', function() {
    actualizarBotonesVenta();
    actualizarTipoComprobante();
    actualizarResumenTotalProductos(); // recalcula correctamente TOTAL SIN IVA
});
</script>

</body>
</html>

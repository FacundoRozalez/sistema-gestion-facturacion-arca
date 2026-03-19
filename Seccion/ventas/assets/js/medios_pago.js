// =====================================
// medios_pago.js final (AFIP / COMUN con IVA)
// =====================================

const container = document.getElementById('mediosPagoContainer');
const btnAgregar = document.getElementById('agregarMedioPagoBtn');
const totalParcialEl = document.getElementById('baseProductos'); // ✅ usar baseProductos para subtotal real
const tipoVenta = document.querySelector('input[name="tipo_venta"]:checked'); 
let contadorMedios = 0;

function escapeHtml(text) {
    if (typeof text !== 'string') text = text == null ? '' : String(text);
    return text.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
               .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
}

function obtenerSubtotalActual() {
    return parseFloat(totalParcialEl?.textContent) || 0;
}

function obtenerSubtotalConIVA() {
    const subtotal = obtenerSubtotalActual();
    let totalIntereses = 0;

    container.querySelectorAll('.medio-pago').forEach(div => {
        const cuotas = parseInt(div.querySelector('.select-cuotas')?.value) || 1;
        const interes = parseFloat(div.querySelector('.input-interes')?.value) || 0;
        totalIntereses += subtotal * ((cuotas -1)*interes)/100;
    });

    const alicuotaIVA = parseFloat(document.getElementById('alicuotaIVA')?.value) || 0;
    let totalIVA = 0;
    const tipo = document.querySelector('input[name="tipo_venta"]:checked')?.value || 'AFIP';
    if(tipo==='AFIP' && alicuotaIVA>0){
        totalIVA = (subtotal + totalIntereses)*alicuotaIVA/100;
        if(document.getElementById('IVA')?.parentElement) document.getElementById('IVA').parentElement.style.display = 'block';
    } else {
        if(document.getElementById('IVA')?.parentElement) document.getElementById('IVA').parentElement.style.display = 'none';
    }

    return subtotal + totalIntereses + totalIVA;
}

function crearMedioPagoHtml(index) {
    const opciones = mediosPagoData.map(m =>
        `<option 
            value="${m.id_medio_pago}" 
            data-max-cuotas="${m.max_cuotas||1}" 
            data-interes="${m.interes_cuota||0}" 
            data-requiere-datos="${m.requiere_datos||'No'}"
        >${escapeHtml(m.nombre)}</option>`).join('');

    const montoInicial = obtenerSubtotalConIVA().toFixed(2);

    return `
    <div class="medio-pago" data-index="${index}" style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <label>Medio de Pago:</label>
        <select name="medios[${index}][id]" class="select-medio" required>
            <option value="">-- Seleccione --</option>
            ${opciones}
        </select>
        <div class="datos-adicionales" style="margin-top:5px; display:none;">
            <label>Referencia / Datos:</label>
            <input type="text" name="medios[${index}][referencia]" placeholder="Ingrese referencia o datos">
        </div>
        <div class="cuotas-container" style="margin-top:5px; display:block;">
            <label>Cuotas:</label>
            <select name="medios[${index}][cuotas]" class="select-cuotas"></select>
            <label>Interés %:</label>
            <input type="number" class="input-interes" step="0.01" value="0" min="0" style="width:70px;">
        </div>
        <br>
        <p>Monto a pagar: $<span class="monto-pagar">${montoInicial}</span></p>
        <button type="button" class="quitar-medio" style="margin-left:10px;">Quitar</button>
    </div>`;
}

function actualizarMontoMediosPago() {
    const subtotalActual = obtenerSubtotalActual();
    let totalIntereses = 0;

    container.querySelectorAll('.medio-pago').forEach(div => {
        const selectMedio = div.querySelector('.select-medio');
        const cuotasSelect = div.querySelector('.select-cuotas');
        const inputInteres = div.querySelector('.input-interes');
        const montoPagarSpan = div.querySelector('.monto-pagar');

        if(!montoPagarSpan) return;

        const selectedOption = selectMedio.options[selectMedio.selectedIndex];
        if(!selectedOption || selectedOption.value===''){
            inputInteres.value = 0;
            montoPagarSpan.textContent = subtotalActual.toFixed(2);
            return;
        }

        const maxCuotas = parseInt(selectedOption.getAttribute('data-max-cuotas')) || 12;
        let currentCuota = parseInt(cuotasSelect.value) || 1;
        let opcionesCuotas = '';
        for(let i=1;i<=maxCuotas;i++){
            opcionesCuotas += `<option value="${i}"${i===currentCuota?' selected':''}>${i}</option>`;
        }
        cuotasSelect.innerHTML = opcionesCuotas;

        if(!inputInteres.value) inputInteres.value = parseFloat(selectedOption.getAttribute('data-interes'))||0;

        const cuotas = parseInt(cuotasSelect.value) || 1;
        const interesPorCuota = parseFloat(inputInteres.value)||0;
        const interesTotal = (cuotas-1)*interesPorCuota;
        totalIntereses += subtotalActual*(interesTotal/100);

        const alicuotaIVA = parseFloat(document.getElementById('alicuotaIVA')?.value) || 0;
        const tipo = document.querySelector('input[name="tipo_venta"]:checked')?.value || 'AFIP';
        let montoFinal = subtotalActual*(1 + interesTotal/100);

        if(tipo==='AFIP' && alicuotaIVA>0){
            montoFinal = montoFinal*(1 + alicuotaIVA/100);
        }

        montoPagarSpan.textContent = montoFinal.toFixed(2);
    });

    // 🔔 Disparar evento personalizado con intereses
    document.dispatchEvent(new CustomEvent('interesesActualizados', { detail: { totalIntereses } }));
}

// --- Eventos ---
container.addEventListener('change', e => {
    if(e.target.classList.contains('select-medio') || e.target.classList.contains('select-cuotas')){
        actualizarMontoMediosPago();
    }
});
container.addEventListener('input', e => {
    if(e.target.classList.contains('input-interes')){
        actualizarMontoMediosPago();
    }
});
container.addEventListener('click', e => {
    if(e.target.classList.contains('quitar-medio')){
        const div = e.target.closest('.medio-pago');
        if(div){
            div.remove();
            actualizarMontoMediosPago();
        }
    }
});

// --- Agregar medio de pago ---
function agregarMedioPago(){
    const index = contadorMedios++;
    const div = document.createElement('div');
    div.innerHTML = crearMedioPagoHtml(index);
    container.appendChild(div.firstElementChild);
    actualizarMontoMediosPago();
}
btnAgregar?.addEventListener('click', agregarMedioPago);

// Inicialización
window.addEventListener('load', () => {
    if(container.children.length===0) agregarMedioPago();
    actualizarMontoMediosPago();
});

// Recalcular al cambiar tipo de venta
document.querySelectorAll('input[name="tipo_venta"]').forEach(radio => radio.addEventListener('change', actualizarMontoMediosPago));

// 🔹 Recalcular cuando se actualizan productos
document.addEventListener('productosActualizados', actualizarMontoMediosPago);

<h3>Proveedor</h3>
<label><input type="radio" name="modo_proveedor" value="existente" checked> Proveedor existente</label>
<label><input type="radio" name="modo_proveedor" value="nuevo"> Nuevo proveedor</label>

<div id="proveedorExistente" style="margin-top:10px;">
    <select name="id_proveedor">
        <option value="">Seleccione un proveedor</option>
        <?php foreach($proveedores as $prov): ?>
            <option value="<?= $prov['id_proveedor'] ?>"><?= htmlspecialchars($prov['razon_social']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div id="proveedorFormulario" style="display:none; margin-top:10px;">
    <input type="text" name="nuevo_nombre" placeholder="Razón Social">
    <input type="text" name="nuevo_cuit" placeholder="CUIT">
    <input type="text" name="nuevo_telefono" placeholder="Teléfono">
    <input type="email" name="nuevo_email" placeholder="Email">
    <input type="text" name="nuevo_direccion" placeholder="Dirección">
    <input type="hidden" id="id_proveedor_autocomplete" name="id_proveedor_autocomplete" value="">
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="modo_proveedor"]');
    const divExistente = document.getElementById('proveedorExistente');
    const divNuevo = document.getElementById('proveedorFormulario');
    const nuevoNombre = divNuevo.querySelector('input[name="nuevo_nombre"]');
    const nuevoCuit = divNuevo.querySelector('input[name="nuevo_cuit"]');
    const idProveedorHidden = document.getElementById('id_proveedor_autocomplete');

    // Toggle entre existente y nuevo
    radios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'nuevo' && radio.checked) {
                divNuevo.style.display = 'block';
                divExistente.style.display = 'none';
                nuevoNombre.required = true;
            } else {
                divNuevo.style.display = 'none';
                divExistente.style.display = 'block';
                nuevoNombre.required = false;
                idProveedorHidden.value = '';
            }
        });
    });

    // Autocompletado CUIT solo si modo nuevo
    nuevoCuit.addEventListener('blur', function() {
        if (!document.querySelector('input[name="modo_proveedor"][value="nuevo"]').checked) return;
        const cuit = this.value.trim();
        if(cuit === '') return;

        const formData = new FormData();
        formData.append('nuevo_cuit', cuit);
        formData.append('nuevo_nombre', 'X'); // activar chequeo

        fetch('procesar.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.existe) {
                    alert("Este CUIT ya está registrado. Se completarán los datos automáticamente.");
                    nuevoNombre.value = data.razon_social;
                    divNuevo.querySelector('input[name="nuevo_telefono"]').value = data.telefono;
                    divNuevo.querySelector('input[name="nuevo_email"]').value = data.email;
                    divNuevo.querySelector('input[name="nuevo_direccion"]').value = data.direccion;
                    idProveedorHidden.value = data.id_proveedor;
                }
            });
    });
});
</script>

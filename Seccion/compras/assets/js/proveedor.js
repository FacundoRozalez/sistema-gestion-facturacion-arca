document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="modo_proveedor"]');
    const nuevoNombre = document.querySelector('#proveedorFormulario input[name="nuevo_nombre"]');

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            const esExistente = this.value === 'existente';
            document.getElementById('proveedorExistente').style.display = esExistente ? 'block' : 'none';
            document.getElementById('proveedorFormulario').style.display = esExistente ? 'none' : 'block';

            // Solo marcar required si está visible
            nuevoNombre.required = !esExistente;
        });
    });
});

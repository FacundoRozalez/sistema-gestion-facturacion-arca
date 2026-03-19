document.addEventListener('DOMContentLoaded', () => {
    const modoExistenteRadio = document.querySelector('input[name="modo_cliente"][value="existente"]');
    const modoNuevoRadio = document.querySelector('input[name="modo_cliente"][value="nuevo"]');
    const clienteExistenteDiv = document.getElementById('clienteExistente');
    const clienteFormularioDiv = document.getElementById('clienteFormulario');
    const clienteSeleccionadoDiv = document.getElementById('clienteSeleccionado');
    const infoClienteDiv = document.getElementById('infoCliente');
    const idClienteHidden = document.getElementById('id_cliente_hidden');
    const buscarClienteInput = document.getElementById('buscarCliente');
    const clientesEncontradosDiv = document.getElementById('clientesEncontrados');
    const cambiarClienteBtn = document.getElementById('cambiarClienteBtn');
    const clienteJsonHidden = document.getElementById('cliente_json_hidden');

    // Campos del formulario de cliente
    const inputNombre = document.getElementById('nuevo_nombre');
    const inputApellido = document.getElementById('nuevo_apellido');
    const inputDni = document.getElementById('nuevo_dni');
    const inputTelefono = document.getElementById('nuevo_telefono');
    const inputEmail = document.getElementById('nuevo_email');
    const inputDireccion = document.getElementById('nuevo_direccion');

    // Cambiar modo de cliente
    modoExistenteRadio.addEventListener('change', () => {
        clienteExistenteDiv.classList.remove('hidden');
        clienteFormularioDiv.classList.add('hidden');
        clienteSeleccionadoDiv.classList.add('hidden');
        limpiarFormulario();
    });

    modoNuevoRadio.addEventListener('change', () => {
        clienteFormularioDiv.classList.remove('hidden');
        clienteExistenteDiv.classList.add('hidden');
        clienteSeleccionadoDiv.classList.add('hidden');
        idClienteHidden.value = '';
        limpiarFormulario();
    });

    // Buscar clientes (simulación, reemplazar con fetch real)
    buscarClienteInput.addEventListener('input', () => {
        const query = buscarClienteInput.value.trim();
        clientesEncontradosDiv.innerHTML = '';
        if (query.length < 2) return;

        const clientesSimulados = [
            {id: 1, nombre: 'Juan', apellido: 'Perez', dni: '12345678', telefono: '11111111', email: 'juan@example.com', direccion: 'Calle Falsa 123'},
            {id: 2, nombre: 'Ana', apellido: 'Gomez', dni: '87654321', telefono: '22222222', email: 'ana@example.com', direccion: 'Av. Siempreviva 456'}
        ].filter(c => c.nombre.toLowerCase().includes(query.toLowerCase()) || c.apellido.toLowerCase().includes(query.toLowerCase()));

        renderClientes(clientesSimulados);
    });

    function renderClientes(clientes) {
        clientesEncontradosDiv.innerHTML = '';
        clientes.forEach(c => {
            const div = document.createElement('div');
            div.classList.add('clienteItem');
            div.style.display = 'flex';
            div.style.justifyContent = 'space-between';
            div.style.marginBottom = '5px';

            const spanInfo = document.createElement('span');
            spanInfo.textContent = `${c.nombre} ${c.apellido} - DNI: ${c.dni}`;

            const btnSeleccionar = document.createElement('button');
            btnSeleccionar.type = 'button';
            btnSeleccionar.textContent = 'Seleccionar';
            btnSeleccionar.addEventListener('click', () => seleccionarCliente(c));

            div.appendChild(spanInfo);
            div.appendChild(btnSeleccionar);
            clientesEncontradosDiv.appendChild(div);
        });
    }

    function seleccionarCliente(cliente) {
    clienteSeleccionadoDiv.classList.remove('hidden');
    clienteExistenteDiv.classList.add('hidden');
    clienteFormularioDiv.classList.remove('hidden'); // editable

    inputNombre.value = cliente.nombre;
    inputApellido.value = cliente.apellido;
    inputDni.value = cliente.dni;
    inputTelefono.value = cliente.telefono;
    inputEmail.value = cliente.email;
    inputDireccion.value = cliente.direccion;

    infoClienteDiv.textContent = `Cliente seleccionado: ${cliente.nombre} ${cliente.apellido}`;
    idClienteHidden.value = cliente.id;

    // 🔹 Guardar cliente existente en hidden como JSON
    clienteJsonHidden.value = JSON.stringify({
        id: cliente.id,
        nombre: cliente.nombre,
        apellido: cliente.apellido,
        dni: cliente.dni,
        telefono: cliente.telefono,
        email: cliente.email,
        direccion: cliente.direccion
    });
}

    cambiarClienteBtn.addEventListener('click', () => {
        clienteSeleccionadoDiv.classList.add('hidden');
        clienteExistenteDiv.classList.remove('hidden');
        clienteFormularioDiv.classList.add('hidden');
        buscarClienteInput.value = '';
        clientesEncontradosDiv.innerHTML = '';
        idClienteHidden.value = '';
        limpiarFormulario();
    });

    function limpiarFormulario() {
        inputNombre.value = '';
        inputApellido.value = '';
        inputDni.value = '';
        inputTelefono.value = '';
        inputEmail.value = '';
        inputDireccion.value = '';

        clienteJsonHidden.value = '';
    }
});

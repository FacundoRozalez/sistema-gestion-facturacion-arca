<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../../Panel/includes/conexion.php';

// ================================================
// AJAX: Buscar / Mostrar Todo
// ================================================
if(isset($_GET['ajax'])){
    $buscar = $_GET['buscar'] ?? '';
    $mostrarTodo = isset($_GET['mostrar_todo']);

    if($mostrarTodo){
        $sql="SELECT * FROM Cliente ORDER BY fecha_registro DESC";
        $stmt=$conn->prepare($sql);
    }else{
        $sql="SELECT * FROM Cliente WHERE nombre LIKE ? OR apellido LIKE ? OR dni_cuit LIKE ? OR telefono LIKE ? OR email LIKE ? ORDER BY fecha_registro DESC";
        $stmt=$conn->prepare($sql);
        $param="%$buscar%";
        $stmt->bind_param("sssss",$param,$param,$param,$param,$param);
    }
    $stmt->execute();
    $res=$stmt->get_result();
    $clientes=[];
    while($row=$res->fetch_assoc()) $clientes[]=$row;

    header('Content-Type: application/json');
    echo json_encode($clientes);
    exit;
}

// ================================================
// AJAX: Agregar Cliente
// ================================================
if(isset($_POST['accion']) && $_POST['accion']==='agregar'){
    $nombre=$_POST['nombre']??'';
    $apellido=$_POST['apellido']??'';
    $dni_cuit=$_POST['dni_cuit']??'';
    $telefono=$_POST['telefono']??'';
    $email=$_POST['email']??'';
    $direccion=$_POST['direccion']??'';

    if(trim($nombre)===''){
        echo json_encode(['success'=>false,'message'=>'El nombre es obligatorio']);
        exit;
    }

    $stmt=$conn->prepare("INSERT INTO Cliente (nombre, apellido, dni_cuit, telefono, email, direccion) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss",$nombre,$apellido,$dni_cuit,$telefono,$email,$direccion);
    $stmt->execute();

    echo json_encode(['success'=>true,'id'=>$stmt->insert_id]);
    exit;
}

// ================================================
// AJAX: Editar Cliente
// ================================================
if(isset($_POST['accion']) && $_POST['accion']==='editar'){
    $id=$_POST['id_cliente']??0;
    $nombre=$_POST['nombre']??'';
    $apellido=$_POST['apellido']??'';
    $dni_cuit=$_POST['dni_cuit']??'';
    $telefono=$_POST['telefono']??'';
    $email=$_POST['email']??'';
    $direccion=$_POST['direccion']??'';

    $stmt=$conn->prepare("UPDATE Cliente SET nombre=?, apellido=?, dni_cuit=?, telefono=?, email=?, direccion=? WHERE id_cliente=?");
    $stmt->bind_param("ssssssi",$nombre,$apellido,$dni_cuit,$telefono,$email,$direccion,$id);
    $stmt->execute();

    echo json_encode(['success'=>true]);
    exit;
}

// ================================================
// AJAX: Eliminar Cliente
// ================================================
if(isset($_POST['accion']) && $_POST['accion']==='eliminar'){
    $id=$_POST['id_cliente']??0;
    $stmt=$conn->prepare("DELETE FROM Cliente WHERE id_cliente=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();

    echo json_encode(['success'=>true]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Clientes</title>
<style>
/* ----------------- RESET BÁSICO ----------------- */
* { box-sizing: border-box; margin:0; padding:0; font-family: Arial, sans-serif; }

/* ----------------- LAYOUT ----------------- */
body { background:#f4f6f8; color:#333; padding:20px; }
h2,h3{ color:#2a7ae2; margin-bottom:10px; }

form{ margin-bottom:20px; }
table{ border-collapse: collapse; width:100%; margin-top:20px; }
th,td{ border:1px solid #ccc; padding:8px; text-align:left; }
th{ background:#eee; }

button { padding:5px 10px; border:none; border-radius:5px; cursor:pointer; margin-right:5px; }
button.editar { background:#007bff; color:#fff; }
button.eliminar { background:red; color:#fff; }
button:hover { opacity:0.9; }

#formAgregar,#formEditar{ margin-top:20px; border:1px solid #ccc; padding:15px; max-width:500px; }
#formAgregar input,#formEditar input, #formAgregar textarea,#formEditar textarea{ margin-bottom:10px; padding:5px; width:100%; }
</style>
</head>
<body>

<h2>Buscar Clientes</h2>
<form id="formBuscar" onsubmit="event.preventDefault();buscarClientes();">
    <input type="text" id="buscar" placeholder="Nombre, Apellido, DNI, Teléfono o Email">
    <button type="submit">Buscar</button>
    <button type="button" onclick="buscarClientes(true);">Mostrar todo</button>
</form>

<!-- FORMULARIO AGREGAR CLIENTE -->
<div id="formAgregar">
<h3>Agregar Cliente</h3>
<form id="formAgregarCliente">
<input type="text" name="nombre" placeholder="Nombre" required>
<input type="text" name="apellido" placeholder="Apellido" required>
<input type="text" name="dni_cuit" placeholder="DNI/CUIT">
<input type="text" name="telefono" placeholder="Teléfono">
<input type="email" name="email" placeholder="Email">
<textarea name="direccion" placeholder="Dirección"></textarea>
<button type="submit">Guardar Cliente</button>
</form>
</div>

<!-- FORMULARIO EDITAR CLIENTE -->
<div id="formEditar" style="display:none;">
<h3>Editar Cliente</h3>
<form id="formEditarCliente">
<input type="hidden" name="id_cliente">
<input type="text" name="nombre" placeholder="Nombre" required>
<input type="text" name="apellido" placeholder="Apellido" required>
<input type="text" name="dni_cuit" placeholder="DNI/CUIT">
<input type="text" name="telefono" placeholder="Teléfono">
<input type="email" name="email" placeholder="Email">
<textarea name="direccion" placeholder="Dirección"></textarea>
<button type="submit">Guardar Cambios</button>
<button type="button" onclick="cancelarEdicion()">Cancelar</button>
</form>
</div>

<!-- RESULTADOS -->
<div id="resultados"></div>

<script>
const baseUrl='/CristianFerreteria/Seccion/clientes/clientes.php';

function escapeHtml(text){if(typeof text!=='string') text=text==null?'':String(text); return text.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;");}

function mostrarResultados(data, soloEditar=false){
    if(!soloEditar){
        document.getElementById('formAgregar').style.display='block';
        document.getElementById('formEditar').style.display='none';
    } else {
        document.getElementById('formAgregar').style.display='none';
        document.getElementById('formEditar').style.display='block';
    }

    if(data.length===0){document.getElementById('resultados').innerHTML='<p>No se encontraron clientes.</p>'; return;}

    let html='<table><thead><tr>'+
    '<th>Nombre</th><th>Apellido</th><th>DNI/CUIT</th><th>Teléfono</th><th>Email</th><th>Dirección</th><th>Acciones</th>'+
    '</tr></thead><tbody>';

    data.forEach(c=>{
        html+='<tr>'+
        '<td>'+escapeHtml(c.nombre)+'</td>'+
        '<td>'+escapeHtml(c.apellido)+'</td>'+
        '<td>'+escapeHtml(c.dni_cuit||'')+'</td>'+
        '<td>'+escapeHtml(c.telefono||'')+'</td>'+
        '<td>'+escapeHtml(c.email||'')+'</td>'+
        '<td>'+escapeHtml(c.direccion||'')+'</td>'+
        '<td>'+
        '<button class="editar" onclick="editarCliente('+c.id_cliente+')">Editar</button>'+
        '<button class="eliminar" onclick="eliminarCliente('+c.id_cliente+')">Eliminar</button>'+
        '</td>'+
        '</tr>';
    });

    html+='</tbody></table>';
    document.getElementById('resultados').innerHTML=html;
}

// BUSCAR CLIENTES
function buscarClientes(mostrarTodo=false){
    const buscarInput=document.getElementById('buscar');
    let url=baseUrl+'?ajax=1';
    if(mostrarTodo) url+='&mostrar_todo=1';
    else url+='&buscar='+encodeURIComponent(buscarInput.value.trim());
    document.getElementById('resultados').innerHTML='<p>Cargando...</p>';
    fetch(url).then(r=>r.json()).then(data=>mostrarResultados(data));
}

// AGREGAR CLIENTE
document.getElementById('formAgregarCliente').addEventListener('submit', function(e){
    e.preventDefault();
    const formData=new FormData(this); formData.append('accion','agregar');
    fetch(baseUrl,{method:'POST',body:formData}).then(r=>r.json()).then(res=>{
        if(res.success){alert('Cliente agregado'); this.reset(); buscarClientes(true);}
        else alert('Error: '+res.message);
    });
});

// EDITAR CLIENTE
function editarCliente(id){
    fetch(baseUrl+'?ajax=1').then(r=>r.json()).then(data=>{
        const c=data.find(x => x.id_cliente == id);
        if(!c) return alert('Cliente no encontrado');

        const form=document.getElementById('formEditarCliente');
        form.id_cliente.value=c.id_cliente;
        form.nombre.value=c.nombre;
        form.apellido.value=c.apellido;
        form.dni_cuit.value=c.dni_cuit||'';
        form.telefono.value=c.telefono||'';
        form.email.value=c.email||'';
        form.direccion.value=c.direccion||'';

        mostrarResultados([c], true);
    });
}

document.getElementById('formEditarCliente').addEventListener('submit', function(e){
    e.preventDefault();
    const formData=new FormData(this); formData.append('accion','editar'); formData.append('id_cliente',this.id_cliente.value);
    fetch(baseUrl,{method:'POST',body:formData}).then(r=>r.json()).then(res=>{
        if(res.success){alert('Cliente editado'); this.reset(); cancelarEdicion(); buscarClientes(true);}
        else alert('Error al editar');
    });
});

function cancelarEdicion(){
    document.getElementById('formEditar').style.display='none';
    document.getElementById('formAgregar').style.display='block';
}

// ELIMINAR CLIENTE
function eliminarCliente(id){
    if(!confirm('Desea eliminar este cliente?')) return;
    const data=new FormData(); data.append('accion','eliminar'); data.append('id_cliente',id);
    fetch(baseUrl,{method:'POST',body:data}).then(r=>r.json()).then(res=>{
        if(res.success) buscarClientes(true);
        else alert('Error al eliminar');
    });
}

// CARGA INICIAL
buscarClientes(true);
</script>
</body>
</html>

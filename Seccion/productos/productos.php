<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../../Panel/includes/conexion.php';

$uploadDir = __DIR__ . '/uploads/';
if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

function subirImagen($inputName, $uploadDir){
    if(isset($_FILES[$inputName]) && $_FILES[$inputName]['error']===0){
        $tmp_name = $_FILES[$inputName]['tmp_name'];
        $nombre = uniqid().'_'.basename($_FILES[$inputName]['name']);
        $destino = $uploadDir.$nombre;
        if(move_uploaded_file($tmp_name, $destino)){
            return ['nombre'=>$nombre];
        } else {
            return ['error'=>'No se pudo mover la imagen'];
        }
    }
    return ['nombre'=>'']; 
}

// ================================================
// AJAX: Traer producto por ID para edición
// ================================================
if(isset($_GET['id_producto'])){
    $id = intval($_GET['id_producto']);
    if($id > 0){
        $stmt = $conn->prepare("SELECT * FROM Producto WHERE id_producto = ?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $res = $stmt->get_result();
        $producto = $res->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($producto);
        exit;
    } else {
        echo json_encode(['error'=>'ID inválido']);
        exit;
    }
}

// ================================================
// AJAX: Buscar / Mostrar con paginación
// ================================================
if(isset($_GET['ajax']) || (isset($_GET['accion']) && $_GET['accion']==='buscar')){
    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $limit = intval($_GET['limit'] ?? 10); // 10 productos por página
    $buscar = trim($_GET['buscar'] ?? '');
    $mostrarTodo = intval($_GET['mostrarTodo'] ?? 0);

    // calcular offset
    $offset = ($pagina - 1) * $limit;

    if($buscar === ''){
        // ✅ Consulta paginada normal
        $stmt = $conn->prepare("SELECT * FROM Producto ORDER BY nombre LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);

        // ✅ Consulta separada para el total
        $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM Producto");
    } else {
        $param = "%$buscar%";
        $stmt = $conn->prepare("SELECT * FROM Producto 
            WHERE nombre LIKE ? OR descripcion LIKE ? OR marca LIKE ? OR codigo_barras LIKE ?
            ORDER BY nombre LIMIT ? OFFSET ?");
        $stmt->bind_param("ssssii", $param, $param, $param, $param, $limit, $offset);

        $stmtCount = $conn->prepare("SELECT COUNT(*) as total 
            FROM Producto 
            WHERE nombre LIKE ? OR descripcion LIKE ? OR marca LIKE ? OR codigo_barras LIKE ?");
        $stmtCount->bind_param("ssss", $param, $param, $param, $param);
    }

    // Ejecutar consulta principal
    $stmt->execute();
    $res = $stmt->get_result();
    $productos = [];
    while($row = $res->fetch_assoc()){
        $productos[] = $row;
    }

    // Ejecutar consulta de conteo
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    $totalRow = $resCount->fetch_assoc();
    $total = $totalRow['total'] ?? count($productos);

    $totalPaginas = ceil($total / $limit);

    echo json_encode([
        'success' => true,
        'productos' => $productos,
        'totalPaginas' => $totalPaginas,
        'paginaActual' => $pagina,
        'total' => $total
    ]);
    exit;
}




// ================================================
// AJAX: Agregar Producto
// ================================================
if(isset($_POST['accion']) && $_POST['accion']==='agregar'){
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $codigo_barras = $_POST['codigo_barras'] ?? '';
    $precio_compra = $_POST['precio_compra'] ?? 0;
    $stock = $_POST['stock'] ?? 0;

    if(trim($nombre) === ''){
        echo json_encode(['success'=>false,'message'=>'El nombre es obligatorio']);
        exit;
    }

    // ✅ Verificar duplicado solo por código de barras
    if(trim($codigo_barras) !== ''){
        $stmtCheck = $conn->prepare("SELECT id_producto FROM Producto WHERE codigo_barras = ?");
        $stmtCheck->bind_param("s", $codigo_barras);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        if($resCheck->num_rows > 0){
            echo json_encode(['success'=>false,'message'=>'Ya existe un producto con este código de barras']);
            exit;
        }
    }

    // Subir imagen
    $imagen_res = subirImagen('imagen',$uploadDir);
    if(isset($imagen_res['error'])){
        echo json_encode(['success'=>false,'message'=>$imagen_res['error']]);
        exit;
    }
    $imagen = $imagen_res['nombre'];

    // Insertar producto
    $stmt = $conn->prepare("INSERT INTO Producto (nombre,descripcion,marca,codigo_barras,precio_compra,stock,imagen) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssdis", $nombre, $descripcion, $marca, $codigo_barras, $precio_compra, $stock, $imagen);
    $stmt->execute();

    echo json_encode(['success'=>true,'id'=>$stmt->insert_id]);
    exit;
}


// ================================================
// AJAX: Editar Producto
// ================================================
if(isset($_POST['accion']) && $_POST['accion']==='editar'){
    $id = intval($_POST['id'] ?? 0);
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $codigo_barras = $_POST['codigo_barras'] ?? '';
    $precio_compra = $_POST['precio_compra'] ?? 0;
    $stock = $_POST['stock'] ?? 0;

    if($id <= 0){
        echo json_encode(['success'=>false, 'message'=>'ID de producto inválido']);
        exit;
    }

    // Validar duplicado de código de barras, excluyendo este producto
    if(trim($codigo_barras) !== ''){
        $stmtCheck = $conn->prepare("SELECT id_producto FROM Producto WHERE codigo_barras = ? AND id_producto != ?");
        $stmtCheck->bind_param("si", $codigo_barras, $id);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        if($resCheck->num_rows > 0){
            echo json_encode(['success'=>false, 'message'=>'Ya existe un producto con este código de barras']);
            exit;
        }
    }

    // Obtener imagen actual de manera segura
    $stmtImg = $conn->prepare("SELECT imagen FROM Producto WHERE id_producto=?");
    $stmtImg->bind_param("i",$id);
    $stmtImg->execute();
    $resImg = $stmtImg->get_result();
    $row = $resImg->fetch_assoc();
    $imagen_actual = $row['imagen'] ?? '';

    // Subir nueva imagen si existe
    $imagen_res = subirImagen('imagen', $uploadDir);
    if(isset($imagen_res['error'])){
        echo json_encode(['success'=>false,'message'=>$imagen_res['error']]);
        exit;
    }

    // Si no se sube nueva imagen, mantener la actual
    $imagen_final = $imagen_res['nombre'] !== '' ? $imagen_res['nombre'] : $imagen_actual;

    // Actualizar producto
    $stmt = $conn->prepare("UPDATE Producto 
        SET nombre=?, descripcion=?, marca=?, codigo_barras=?, precio_compra=?, stock=?, imagen=? 
        WHERE id_producto=?");
    $stmt->bind_param("ssssdisi", $nombre, $descripcion, $marca, $codigo_barras, $precio_compra, $stock, $imagen_final, $id);
    $stmt->execute();

    echo json_encode(['success'=>true]);
    exit;
}

// ================================================
// AJAX: Eliminar Producto
// ================================================
if(isset($_POST['accion']) && $_POST['accion']==='eliminar'){
    $id = $_POST['id'] ?? 0;

    // Borrar imagen del servidor
    $res = $conn->query("SELECT imagen FROM Producto WHERE id_producto=$id");
    if($row=$res->fetch_assoc()){
        if($row['imagen'] && file_exists($uploadDir.$row['imagen'])) unlink($uploadDir.$row['imagen']);
    }

    $stmt = $conn->prepare("DELETE FROM Producto WHERE id_producto = ?");
    $stmt->bind_param("i", $id);

    try {
        $stmt->execute();
        if ($stmt->affected_rows > 0) echo json_encode(['success' => true]);
        else echo json_encode(['success' => false, 'message' => 'No se pudo eliminar. Puede que existan registros dependientes o el producto no exista.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $conn->error]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Productos</title>
<style>
form{margin-bottom:20px;}
table{border-collapse:collapse;width:100%;margin-top:20px;}
th,td{border:1px solid #ccc;padding:8px;text-align:left;}
th{background-color:#eee;}
#formAgregar,#formEditar{margin-top:20px;border:1px solid #ccc;padding:15px;max-width:500px;}
#formAgregar input,#formEditar input{margin-bottom:10px;padding:5px;width:100%;}
#formAgregar button,#formEditar button{padding:8px 15px;}
button.editar { background-color: #007bff; color: white; border: none; padding:5px 10px; margin-right:5px; cursor:pointer; }
button.eliminar { background-color: red; color: white; border: none; padding:5px 10px; cursor:pointer; }
img.producto { max-width:50px; max-height:50px; }
button:hover { opacity: 0.9; }
</style>
</head>
<body>

<h2>Buscar Productos</h2>
<form id="formBuscar">
    <input type="text" id="buscar" placeholder="Ingrese cualquier dato para buscar">
    <button type="submit">Buscar</button>
    <button type="button" id="btnMostrarTodo">Mostrar todo</button>
</form>


<div id="formAgregar">
<h3>Agregar Producto</h3>
<form id="formAgregarProducto" enctype="multipart/form-data">
<input type="text" name="nombre" placeholder="Nombre" required>
<input type="text" name="descripcion" placeholder="Descripción">
<input type="text" name="marca" placeholder="Marca">
<input type="text" name="codigo_barras" placeholder="Código">
<input type="number" step="0.01" name="precio_compra" placeholder="Precio Compra" required>
<input type="number" name="stock" placeholder="Stock">
<input type="file" name="imagen" accept="image/*">
<button type="submit">Guardar Producto</button>
</form>
</div>

<div id="formEditar" style="display:none;">
<h3>Editar Producto</h3>
<form id="formEditarProducto" enctype="multipart/form-data">
<input type="hidden" name="id">
<input type="text" name="nombre" placeholder="Nombre" required>
<input type="text" name="descripcion" placeholder="Descripción">
<input type="text" name="marca" placeholder="Marca">
<input type="text" name="codigo_barras" placeholder="Código">
<input type="number" step="0.01" name="precio_compra" placeholder="Precio Compra" required>
<input type="number" name="stock" placeholder="Stock">
<img id="imgActual" src="" class="producto"><br>
<input type="file" name="imagen" accept="image/*" id="inputImagen">
<button type="submit">Guardar Cambios</button>
<button type="button" onclick="cancelarEdicion()">Cancelar</button>
</form>
</div>

<div id="resultados"></div>

<script>
const baseUrl='/CristianFerreteria/Seccion/productos/productos.php';
const uploadsPath = '/CristianFerreteria/Seccion/productos/uploads/';
let paginaActual = 1;
let totalPaginas = 1;
let ultimoBuscar = '';

function escapeHtml(text){
    if(typeof text!=='string') text=text==null?'':String(text);
    return text.replace(/&/g,"&amp;").replace(/</g,"&lt;")
               .replace(/>/g,"&gt;").replace(/"/g,"&quot;")
               .replace(/'/g,"&#039;");
}

function mostrarResultados(data, soloEditar=false){
    if(!soloEditar){
        document.getElementById('formAgregar').style.display='block';
        document.getElementById('formEditar').style.display='none';
    } else {
        document.getElementById('formAgregar').style.display='none';
        document.getElementById('formEditar').style.display='block';
    }

    const contenedor = document.getElementById('resultados');
    const productos = data.productos || [];
    totalPaginas = data.totalPaginas || 1;
    paginaActual = data.paginaActual || 1;

    if(productos.length===0){ 
        contenedor.innerHTML='<p>No se encontraron productos.</p>'; 
        return; 
    }

    let html='<table><thead><tr>'+
        '<th>Imagen</th><th>Nombre</th><th>Descripción</th><th>Marca</th><th>Código</th><th>Precio Compra</th><th>Stock</th><th>Acciones</th>'+
        '</tr></thead><tbody>';

    productos.forEach(p=>{
        let precio = p.precio_compra && p.precio_compra!="0.00"? Number(p.precio_compra).toFixed(2).replace('.',','):'';
        let img = p.imagen ? uploadsPath + p.imagen : '';
        html+='<tr>';
        html+='<td>'+(img?'<img src="'+img+'" class="producto" loading="lazy" style="cursor:pointer;max-height:50px;">':'')+'</td>';
        html+='<td>'+escapeHtml(p.nombre)+'</td>'+
              '<td>'+escapeHtml(p.descripcion||'')+'</td>'+
              '<td>'+escapeHtml(p.marca||'')+'</td>'+
              '<td>'+escapeHtml(p.codigo_barras||'')+'</td>'+
              '<td>'+precio+'</td>'+
              '<td>'+escapeHtml(p.stock)+'</td>'+
              '<td>'+
              '<button class="editar" onclick="editarProducto('+p.id_producto+')">Editar</button>'+
              '<button class="eliminar" onclick="eliminarProducto('+p.id_producto+')">Eliminar</button>'+
              '</td>';
        html+='</tr>';
    });

    html+='</tbody></table>';

    // ===== Paginación moderna =====
    html+='<div style="margin-top:10px; display:flex; align-items:center; gap:5px; flex-wrap:wrap;">';

    const rango = 2; // número de páginas antes y después de la actual
    let startPage = Math.max(1, paginaActual - rango);
    let endPage = Math.min(totalPaginas, paginaActual + rango);

    // Botón Primera
    if(paginaActual > 1) html += `<button onclick="cargarPagina(1)">Primera</button>`;

    // Botón Anterior
    if(paginaActual > 1) html += `<button onclick="cargarPagina(${paginaActual-1})">Anterior</button>`;

    // Rangos de páginas
    for(let i=startPage; i<=endPage; i++){
        if(i === paginaActual){
            html += `<button disabled style="font-weight:bold;">${i}</button>`;
        } else {
            html += `<button onclick="cargarPagina(${i})">${i}</button>`;
        }
    }

    // Botón Siguiente
    if(paginaActual < totalPaginas) html += `<button onclick="cargarPagina(${paginaActual+1})">Siguiente</button>`;

    // Botón Última
    if(paginaActual < totalPaginas) html += `<button onclick="cargarPagina(${totalPaginas})">Última</button>`;

    // Input ir a página
    html += ` <input type="number" id="gotoPagina" style="width:50px;" min="1" max="${totalPaginas}" placeholder="Ir a">`;
    html += ` <button onclick="irAPagina()">Ir</button>`;

    html+='</div>';

    contenedor.innerHTML = html;

    // Modal de imagen
    document.querySelectorAll('img.producto').forEach(imgEl=>{
        imgEl.onclick = ()=>{
            const modal = document.createElement('div');
            modal.style.cssText='position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:9999;';
            modal.innerHTML=`<img src="${imgEl.src}" style="max-width:90%; max-height:90%; border-radius:5px;">`;
            modal.onclick=(e)=>{if(e.target===modal) modal.remove();};
            document.body.appendChild(modal);
        };
    });
}

// Función para ir a página desde input
function irAPagina(){
    const input = document.getElementById('gotoPagina');
    let p = parseInt(input.value);
    if(p >= 1 && p <= totalPaginas){
        cargarPagina(p);
    } else {
        alert('Número de página inválido');
    }
}



// Cargar página específica
function cargarPagina(pagina){
    paginaActual = pagina;
    const buscarInput=document.getElementById('buscar');
    ultimoBuscar = buscarInput.value.trim();
    let url=baseUrl+'?ajax=1&pagina='+pagina+'&limit=10';
    if(ultimoBuscar) url+='&buscar='+encodeURIComponent(ultimoBuscar);
    document.getElementById('resultados').innerHTML='<p>Cargando...</p>';
    fetch(url).then(r=>r.json()).then(data=>mostrarResultados(data));
}


// Buscar productos
document.getElementById('formBuscar').addEventListener('submit', function(e){
    e.preventDefault();
    paginaActual = 1; // resetear al buscar
    cargarPagina(paginaActual);
});

// Evitar saturar el servidor al tipear
let timer;
document.getElementById('buscar').addEventListener('input', function(){
    clearTimeout(timer);
    timer = setTimeout(()=>{
        paginaActual = 1;
        cargarPagina(paginaActual);
    }, 300); // espera 300ms después de escribir
});


// Mostrar todo
document.getElementById('btnMostrarTodo').addEventListener('click', function(){
    document.getElementById('buscar').value = '';
    ultimoBuscar = '';
    paginaActual = 1;
    cargarPagina(paginaActual);
});

// Agregar producto
document.getElementById('formAgregarProducto').addEventListener('submit', function(e){
    e.preventDefault();
    const formData=new FormData(this); 
    formData.append('accion','agregar');
    fetch(baseUrl,{method:'POST',body:formData}).then(r=>r.json()).then(res=>{
        if(res.success){alert('Producto agregado'); this.reset(); cargarPagina(1);}
        else alert('Error: '+res.message);
    });
});

// Editar producto
// Editar producto por ID directamente
function editarProducto(id){
    if(!id || id <= 0) return alert('ID de producto inválido');

    const formAgregar = document.getElementById('formAgregar');
    const formEditar = document.getElementById('formEditar');
    formAgregar.style.display = 'none';
    formEditar.style.display = 'block';

    // Traer solo el producto seleccionado
    let url = baseUrl+'?id_producto='+id;
    fetch(url)
        .then(r => r.json())
        .then(p => {
            if(!p || p.error) return alert('Producto no encontrado');

            const form = document.getElementById('formEditarProducto');
            form.id.value = p.id_producto;
            form.nombre.value = p.nombre;
            form.descripcion.value = p.descripcion || '';
            form.marca.value = p.marca || '';
            form.codigo_barras.value = p.codigo_barras || '';
            form.precio_compra.value = p.precio_compra || '';
            form.stock.value = p.stock || '';

            document.getElementById('imgActual').src = p.imagen ? uploadsPath + p.imagen : '';
            document.getElementById('imgActual').dataset.nombre = p.imagen || ''; // Guarda el nombre original

        })
        .catch(err => {
            console.error(err);
            alert('Error al cargar el producto');
        });
}
// Previsualizar nueva imagen al seleccionar
document.getElementById('inputImagen').addEventListener('change', function(e){
    const file = this.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(ev){
            document.getElementById('imgActual').src = ev.target.result;
        }
        reader.readAsDataURL(file);
    } else {
        // Si se quita la selección, volvemos a mostrar la imagen original
        const original = document.getElementById('imgActual').dataset.nombre;
        document.getElementById('imgActual').src = original ? uploadsPath + original : '';
    }
});



document.getElementById('formEditarProducto').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('accion','editar');
    fetch(baseUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success){
                alert('Producto editado correctamente');
                cancelarEdicion();
                cargarPagina(paginaActual); // refresca solo la tabla
            } else {
                alert('Error: ' + res.message);
            }
        })
        .catch(err => console.error(err));
});

// Cancelar edición
function cancelarEdicion(){
    const form = document.getElementById('formEditarProducto');
    form.reset(); // limpia campos
    document.getElementById('imgActual').src = '';
    document.getElementById('formEditar').style.display='none';
    document.getElementById('formAgregar').style.display='block';
}

// Eliminar
function eliminarProducto(id){
    if(!confirm('Desea eliminar este producto?')) return;
    const data = new FormData();
    data.append('accion','eliminar');
    data.append('id',id);

    fetch(baseUrl,{method:'POST',body:data})
    .then(r=>r.json())
    .then(res=>{
        if(res.success) cargarPagina(paginaActual);
        else alert(res.message || 'Error al eliminar');
    }).catch(err=>{
        alert('Error al procesar la solicitud');
        console.error(err);
    });
}

// Cargar la primera página al inicio
// cargarPagina(1);

</script>
<!-- Lightbox con efecto -->
<div id="lightbox" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background: rgba(0,0,0,0.8); justify-content:center; align-items:center; z-index:9999; transition: opacity 0.3s;">
    <img id="lightboxImg" src="" style="max-width:90%; max-height:90%; border:3px solid white; border-radius:5px; transform: scale(0); transition: transform 0.3s;">
</div>

</body>
</html>

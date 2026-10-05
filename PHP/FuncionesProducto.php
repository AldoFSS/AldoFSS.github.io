<?php
include('../BD/conexion.php'); 
function InsertarProducto($connection)
{    
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Recoger datos del formulario y evitar inyecciones SQL
    $nombre = $_POST['Product_nombre'];
    $descripcion = $_POST['Descripcion'];
    $costo = $_POST['Costo'];
    $categoria = $_POST['Categoria']; // ID de la categoría
    if(isset($_FILES['ImgProduct'])&& $_FILES['ImgProduct']['error']== UPLOAD_ERR_OK)
    {
        $ImgNombre = $_FILES['ImgProduct']['name'];
        $ImgRuta = $_FILES['ImgProduct']['tmp_name'];
        $ImgExt = strtolower(pathinfo($ImgNombre,PATHINFO_EXTENSION));
        $extensionesPermitidas = ['jpg', 'jpeg', 'png'];
        if (in_array($ImgExt, $extensionesPermitidas))
        {
            $ImgDestino = '../PRODUCTOS/'.$nombre.'.'.$ImgExt;
            if(move_uploaded_file($ImgRuta,$ImgDestino))
            {
                $ImgRuta = '../PRODUCTOS/'.$nombre.'.'.$ImgExt;
            }
        }
    }
    // Preparar la declaración SQL
    $query = "INSERT INTO menuproductos (Product_nombre, Descripcion, Costo,Imagen, id_categoria) VALUES ('$nombre', '$descripcion', '$costo','$ImgRuta', '$categoria')";
    $resultado= $connection->prepare($query);
    // Ejecutar la declaración
    if ($resultado->execute()) {
        header('location: ../listProductos.php');  
        exit();
    } else {
        echo "Error al Insertar: " . $resultado->error;
    }
    // Cerrar la declaración
    $resultado->close();
    } else {
        echo "Error en la preparación de la consulta: " . $connection->error;      
    }
    // Cerrar la conexión
    $connection->close();
    
}
function EliminarProducto($connection)
{
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Recoger datos del formulario y evitar inyecciones SQL
        $id = $_POST['id'];

        // Verificar que el id no esté vacío
        if (!empty($id)) {
            // Preparar la declaración SQL
            $query1="DELETE FROM detallesorden WHERE ID_Productos = ?";
            $resultado1 = $connection->prepare($query1);
            $resultado1->bind_param('i', $id);

            $query = "DELETE FROM menuproductos WHERE id = ?";
            $resultado = $connection->prepare($query);
            $resultado->bind_param('i', $id);

            // Ejecutar la declaración
            if ($resultado1->execute()) {
                if($resultado->execute()){
                    header('Location: ../listProductos.php'); // Redirigir después de eliminar
                    exit();
                }
            } else {
                echo "Error al Eliminar: " . $resultado->error;
            }

            // Cerrar la declaración
            $resultado->close();
        } else {
            echo "ID del producto no proporcionado.";
        }
    } else {
        echo "Método de solicitud no válido.";
    }

    // Cerrar la conexión
    $connection->close();
}

function ActualizarProducto($connection)
{
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Recoger datos del formulario y evitar inyecciones SQL
        $id = $_POST['id']; // ID del producto para actualizar
        $nombre = $_POST['Product_nombre'];//Nombre del Producto
        $descripcion = $_POST['Descripcion'];//Descripcion del Producto
        $costo = $_POST['Costo'];//Coste del Producto
        $categoria = $_POST['Categoria']; // ID de la categoría

        // Verificar si se ha subido una nueva imagen
        if (isset($_FILES['ImgProduct']) && $_FILES['ImgProduct']['error'] == UPLOAD_ERR_OK) {
            $ImgNombre = $_FILES['ImgProduct']['name'];
            $ImgRuta = $_FILES['ImgProduct']['tmp_name'];
            $ImgExt = strtolower(pathinfo($ImgNombre, PATHINFO_EXTENSION));
            $extensionesPermitidas = ['jpg', 'jpeg', 'png'];
            if (in_array($ImgExt, $extensionesPermitidas)) {
                $ImgDestino = '../PRODUCTOS/' . $nombre . '.' . $ImgExt;
                if (move_uploaded_file($ImgRuta, $ImgDestino)) {
                    $ImgRuta = '../PRODUCTOS/' . $nombre . '.' . $ImgExt;
                }
            }
        } else {
            // Si no se ha subido una nueva imagen, usar la imagen existente (si existe)
            $ImgRuta = $_POST['ImagenActual'];
        }

        // Preparar la declaración SQL para actualizar el producto
        $query = "UPDATE menuproductos 
                  SET Product_nombre = ?, Descripcion = ?, Costo = ?, Imagen = ?, id_categoria = ? 
                  WHERE id = ?";
        $resultado = $connection->prepare($query);

        // Ejecutar la declaración
        if ($resultado->execute([$nombre, $descripcion, $costo, $ImgRuta, $categoria, $id])) {
             // Cerrar la conexión
            $connection->close();
            header('Location: ../listProductos.php');
        } else {
            echo "Error al Actualizar: " . $resultado->error;
        }

        // Cerrar la declaración
        $resultado->close();
    } else {
        echo "Error en la preparación de la consulta: " . $connection->error;
    }
   
}
function InsertarCategoria($connection)
{
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Recoger datos del formulario y evitar inyecciones SQL
    $Categoria = $_POST['Categoria'];

    // Preparar la declaración SQL
    $query = "INSERT INTO menucategoria (Categoria) VALUES ('$Categoria')";
    $resultado= $connection->prepare($query);
    // Ejecutar la declaración
    if ($resultado->execute()) {
        // Cerrar la conexión
        $connection->close();
        header('location: ../listCategorias.php');
    } else {
        echo "Error al Insertar: " . $resultado->error;
    }
    // Cerrar la declaración
    $resultado->close();
    } else {
        echo "Error en la preparación de la consulta: " . $connection->error;
    }

}
function EditarCategoria($connection){
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Recoger datos del formulario y evitar inyecciones SQL
        $id = $_POST['id']; // ID del producto para actualizar
        $categoria = $_POST['Categoria']; // ID de la categoría
        // Preparar la declaración SQL para actualizar el producto
        $query = "UPDATE menucategoria 
                  SET Categoria = ?
                  WHERE ID_Cat = ?";
        $resultado = $connection->prepare($query);

        // Ejecutar la declaración
        if ($resultado->execute([$categoria, $id])) {
             // Cerrar la conexión
            $connection->close();
            header('Location: ../listCategorias.php');
        } else {
            echo "Error al Actualizar: " . $resultado->error;
        }

        // Cerrar la declaración
        $resultado->close();
    } else {
        echo "Error en la preparación de la consulta: " . $connection->error;
    }
}
function Eliminarcategoria($connection)
{
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Recoger datos del formulario y evitar inyecciones SQL
        $id = $_POST['id'];

        // Verificar que el id no esté vacío
        if (!empty($id)) {
            // Preparar la declaración SQL
            $query1 = "DELETE FROM menuproductos WHERE id_categoria = ?";
            $resultado1 = $connection->prepare($query1);
            $resultado1->bind_param('i', $id);

            $query = "DELETE FROM menucategoria WHERE ID_Cat = ?";
            $resultado = $connection->prepare($query);
            $resultado->bind_param('i', $id);


            // Ejecutar la declaración
            if ($resultado1->execute()) {
                if($resultado-> execute()){
                    header('Location: ../listCategorias.php'); // Redirigir después de eliminar
                    exit();
                }
            } else {
                echo "Error al Eliminar: " . $resultado->error;
            }

            // Cerrar la declaración
            $resultado->close();
        } else {
            echo "ID del producto no proporcionado.";
        }
    } else {
        echo "Método de solicitud no válido.";
    }

    // Cerrar la conexión
    $connection->close();
}
// Determinar qué función llamar según el parámetro de acción
if (isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'insertar':
            InsertarProducto($connection);
            break;
        case 'actualizar':
            ActualizarProducto($connection);
            break;
        case 'eliminar':
            EliminarProducto($connection);
            break;
        case 'categoria':
            InsertarCategoria($connection);
            break;
        case 'actualizarCat':
            EditarCategoria($connection);
            break;
        case 'eliminarCat':
            Eliminarcategoria($connection);
            break;
        default:
            echo "Acción no válida.";
    }
} else {
    echo "Acción no especificada.";
}
?>
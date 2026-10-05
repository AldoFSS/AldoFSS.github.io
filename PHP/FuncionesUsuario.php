<?php 
include('../BD/conexion.php');
function BuscarUsuario($connection, $datab) {
    session_start(); 

    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
        // Recoger datos del formulario
        $nombre = $_POST['nombre_usuario'];
        $pwp = md5($_POST['contraseña_usuario']);
        // Consultar la base de datos para verificar las credenciales
        $resultado = mysqli_query($connection, "SELECT * FROM usuarios WHERE nombre_usuario='$nombre' and contraseña_usuario='$pwp'");
        $filas = mysqli_num_rows($resultado);

        if ($filas > 0) {
            // Si las credenciales son correctas, obtener la información del usuario
            $usuario = mysqli_fetch_assoc($resultado);
            $_SESSION['nombre_usuario'] = $usuario['nombre_usuario']; // Guarda el nombre en la sesión
            $_SESSION['user_Cargo'] = $usuario['Cargo']; // Guarda el cargo en la sesión
            $_SESSION['ID_Usuario'] = $usuario['id']; // Guardar el ID del usuario en la sesión
            
            if ($_SESSION['user_Cargo'] == 'administrador') {
                header("Location: ../index.php?alert=admin");
            } else {
                header("Location: ../index.php?alert=success");
            }
            exit();
        } else {
            // Si las credenciales son incorrectas
            header("Location: ../login.php?alert=error");
            exit();
        }

        mysqli_free_result($resultado);
        mysqli_close($connection);
    }
}

function InsertarUsuario($connection,$datab)
{
//hacemos llamado al imput de formuario
$usuario = $_POST["nombre_usuario"] ;
$pwp = md5($_POST["contraseña_usuario"]);
$cargo = "usuario";
$verificar = "SELECT * FROM usuarios WHERE nombre_usuario='$usuario'";
$resultado = $connection->query($verificar);
if($resultado->num_rows > 0){
    header("Location: ../registro.php?alert=errorUser");
    exit();
}else{
    //insertamos datos de registro al mysql xamp, indicando nombre de la tabla y sus atributos
    $consulta1 = "INSERT INTO usuarios (nombre_usuario, contraseña_usuario, cargo)
    VALUES ('$usuario','$pwp','$cargo')";
    $resultado1 = mysqli_query($connection,$consulta1);
    
    if($resultado1)
    {
        header("Location: ../login.php?alert=register");
        exit();
    }else
    {
        header("Location: ../login.php?alert=errorUser");
        exit();
    }
}

}
function EliminarUsuario($connection)
{
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Recoger datos del formulario y evitar inyecciones SQL
        $id = $_POST['id'];
        // Verificar que el id no esté vacío
        if (!empty($id)) {
            // Preparar la declaración SQL
            $query = "DELETE FROM usuarios WHERE id = ?";
            $resultado = $connection->prepare($query);
            $resultado->bind_param('i', $id);

            // Ejecutar la declaración
            if ($resultado->execute()) {
                header('Location: ../listUsuarios.php'); // Redirigir después de eliminar
                exit();
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

function ActualizarUsuario($connection)
{
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Recoger datos del formulario y evitar inyecciones SQL
        $id = $_POST['id']; // ID del producto para actualizar
        $nombre = $_POST['Nombre'];
        $cargo = $_POST['Cargo'];

        // Preparar la declaración SQL para actualizar el producto
        $query = "UPDATE usuarios SET nombre_usuario = ?, Cargo = ? WHERE id = ?";
        $resultado = $connection->prepare($query);

        // Ejecutar la declaración
        if ($resultado->execute([$nombre, $cargo, $id])) {
            header('Location: ../listUsuarios.php'); // Redirigir después de actualizar
            exit();
        } else {
            echo "Error al Actualizar: " . $resultado->error;
        }

        // Cerrar la declaración
        $resultado->close();
    } else {
        echo "Error en la preparación de la consulta: " . $connection->error;
    }
    // Cerrar la conexión
    $connection->close();
}

if (isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'InsertarUsuario':
            InsertarUsuario($connection,$datab);
            break;
        case 'BuscarUsuario':
            BuscarUsuario($connection,$datab);
            break;
        case 'actualizar':
            ActualizarUsuario($connection);
            break;
        case 'eliminar':
            EliminarUsuario($connection);
        break;
        default:
            echo "Acción no válida.";
    }
} else {
    echo "Acción no especificada.";
}
?>
<style>
        .table td, .table th {
            text-align: center; /* Centra el texto en las celdas */
        }
        
        .table .btn {
            margin: 0 5px; /* Espacio entre los botones */
        }
        
        .table .btn-group {
            display: flex;
            border-radius: 10px;
            justify-content: center; /* Centra los botones */
        }
    </style>
<div class="container mt-4">
    <div class="table-responsive">
        <table class="table table-hover" id="table_User">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Cargo</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php
                include('./BD/conexion.php');
                $consulta = "SELECT * FROM usuarios";
                $resultado = mysqli_query($connection,$consulta);
                while($fila = mysqli_fetch_array($resultado)){
                echo "<tr>";
                echo "<th scope='row'>{$fila['id']}</th>";
                echo "<td>{$fila['nombre_usuario']}</td>";
                echo "<td>{$fila['Cargo']}</td>";
                echo "<td>
                        <div class='btn-group'>
                            <a href='#' class='btn boton_m' data-bs-toggle='modal' data-bs-target='#EditarUsuarioModal' data-id='{$fila['id']}' data-nombre='{$fila['nombre_usuario']}' data-cargo='{$fila['Cargo']}'>
                                <i class='bi bi-pencil-square'></i>
                            </a>
                            <a href='#' class='btn boton_m' data-bs-toggle='modal' data-bs-target='#DeleteModalUser' data-id='{$fila['id']}'>
                                <i class='bi bi-trash'></i>
                            </a>
                        </div>
                    </td>";
                echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        var editarProductoModal = document.getElementById('EditarUsuarioModal');

        editarProductoModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Botón que abrió el modal
            var id = button.getAttribute('data-id');
            var nombre = button.getAttribute('data-nombre');
            var cargo = button.getAttribute('data-cargo');
            var form = document.getElementById('formularioUserProducto');

            form.querySelector('#ID').value = id;
            form.querySelector('#Nombre_User').value = nombre;
            form.querySelector('#cargo_editar').value = cargo;

        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    var deleteModal = document.getElementById('DeleteModalUser');

    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Botón que abrió el modal
        var id = button.getAttribute('data-id');

        var form = deleteModal.querySelector('form');
        form.querySelector('#ID').value = id;
    });
});

</script>


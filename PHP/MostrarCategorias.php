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

<div class="table-responsive">
    <table class="table table-hover" id="table_Productos">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Categoria</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
            include('./BD/conexion.php');
            $consulta = "SELECT * FROM menucategoria";
            $resultado = mysqli_query($connection,$consulta);

            while($fila = mysqli_fetch_array($resultado)){
            echo "<tr>";
            echo "<th scope='row'>{$fila['ID_Cat']}</th>";
            echo "<td>{$fila['Categoria']}</td>";
            echo "<td>
                    <div class='btn-group'>
                        <a href='#' aria-label='Editar Categoria' class='btn boton_m' data-bs-toggle='modal' data-bs-target='#EditarCategoriaModal' 
                            data-id='{$fila['ID_Cat']}' data-categoria='{$fila['Categoria']}'>
                            <i class='bi bi-pencil-square'></i>
                        </a>
                        <a href='#' aria-label='Eliminar producto' class='btn boton_m' data-bs-toggle='modal' data-bs-target='#DeleteModalCategoria' data-id='{$fila['ID_Cat']}'>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var editarProductoModal = document.getElementById('EditarCategoriaModal');

        editarProductoModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Botón que abrió el modal
            var ID = button.getAttribute('data-id');
            var Cat = button.getAttribute('data-categoria');

            var modalTitle = editarProductoModal.querySelector('.modal-title');
            var form = document.getElementById('formularioEditarCategoria');

            form.querySelector('#EditID').value = ID;
            form.querySelector('#EditCategoria').value = Cat;
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var deleteModal = document.getElementById('DeleteModalCategoria');

        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Botón que abrió el modal
            var ID = button.getAttribute('data-id');

            var form = deleteModal.querySelector('form');
            form.querySelector('#DeleteID').value = ID;
        });
    });
</script>

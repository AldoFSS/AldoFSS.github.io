<!-- Modal -->
<div class="modal fade" id="EditarUsuarioModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5 titulo_modal">Editar Usuario</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <form id="formularioUserProducto" action="./PHP/FuncionesUsuario.php" method="POST">
    <input type="hidden" name="accion" value="actualizar">
    <input type="hidden" id="ID" name="id">
    <div class="mb-3">
        <label class="form-label">Nombre del Usuario:</label>
        <input type="text" id="Nombre_User" name="Nombre" class="form-control" />
    </div>
    <div class="mb-3">
        <label class="form-label">Cargo:</label>
        <select id="cargo_editar" name="Cargo" class="form-select" required>
            <option selected value="">Seleccione</option>
            <option value="administrador">administrador</option>
            <option value="usuario">usuario</option>
        </select>
    </div>
    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">
            Actualizar Usuario
        </button>
    </div>
</form>
            </div>
        </div>
    </div>
</div>


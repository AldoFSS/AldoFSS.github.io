<!-- Modal para confirmación de eliminación -->
<div class="modal fade" id="DeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title titulo_modal">Por favor Confirmar tu Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este producto?
                <br> Al eliminar el producto eliminaras todo los pedidos referenciados al producto
            </div>
            <div class="modal-footer">
                <form id="formularioEliminarProducto" action="./PHP/FuncionesProducto.php" method="POST">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" id="DeleteID" name="id">
                <div class="d-grip gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" data-bs-dismiss="modal" id="confirmDeleteBtn">Eliminar</button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
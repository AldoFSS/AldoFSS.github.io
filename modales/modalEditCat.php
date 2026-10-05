<!-- Modal -->
<div class="modal fade" id="EditarCategoriaModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5 titulo_modal">Editar categoria</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formularioEditarCategoria" action="./PHP/FuncionesProducto.php" method="POST" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="accion" value="actualizarCat">
                    <input type="hidden" id="EditID" name="id">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Categoria:</label>
                        <input type="text" id="EditCategoria" name="Categoria" value="" class="form-control" />
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn_add">Actualizar Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


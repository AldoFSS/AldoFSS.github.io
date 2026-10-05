
    <!-- Modal -->
    <div class="modal fade" id="agregarProductModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 titulo_modal">Registrar Nuevo Producto</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formularioProducto" action="./PHP/FuncionesProducto.php" method="POST" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="accion" value="insertar">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Producto</label>
                            <input type="text" name="Product_nombre" class="form-control" required/>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <input type="text" name="Descripcion" class="form-control" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Costo</label>
                            <input type="text" name="Costo" class="form-control"required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seleccione la categoria</label>
                            <select name="Categoria" class="form-select" required>
                                <option selected value="">Seleccione</option>
                                <?php
                                include("../BD/conexion.php");
                                $consulta = "SELECT * FROM menucategoria";
                                $resultado = mysqli_query($connection, $consulta);
                                while ($catg = mysqli_fetch_array($resultado)) {
                                    echo "<option value='" . $catg['ID_Cat'] . "'>" . $catg['Categoria'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3 mt-4">
                            <label class="form-label">Cambiar Foto del Producto</label>
                            <input class="form-control form-control-sm" type="file" name="ImgProduct" accept="image/png, image/jpeg" />
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn_add">
                                Registrar nuevo Producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


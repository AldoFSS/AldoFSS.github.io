<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <style>
        .img-fluid {
            max-width: 100%;
            height: auto;
            object-fit: cover;
        }
    </style>
    <?php include("includes/navbar.php"); ?>
    <div class="menu-box">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="heading-title text-center">
                        <h2>Especial de Caféliz</h2>
                    </div>
                </div>
            </div>
            <!-- Formulario de búsqueda -->
            <div class="row">
                <div class="col-lg-12">
                    <form method="GET" action="menu.php" class="mb-4">
                        <div class="d-flex">
                            <input type="text" class="form-control" alt="buscar" name="search" placeholder="Buscar.."
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                            <button class="buscar" name="submit" type="submit">Buscar</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="container mt-4">
                <!-- Categorías -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-center">
                            <a class="btn boton_m categoria mx-2 mb-2" href="menu.php">TODOS</a>
                            <?php
                            include("BD/conexion.php");
                            $datab = "cafeteria";
                            $db = mysqli_select_db($connection, $datab);
                            $consulta = "SELECT * FROM menucategoria";
                            $resultado = mysqli_query($connection, $consulta);
                            while ($fila = mysqli_fetch_array($resultado)) {
                                ?>
                                <a class="btn boton_m categoria mx-2 mb-2"
                                    href="menu.php?categoriaid=<?php echo $fila['ID_Cat'] ?>">
                                    <?php echo $fila['Categoria']; ?>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- Productos -->
                <div class="row">
                    <div class="col-12" id="lista-productos">
                        <div class="tab-content" id="v-pills-tabContent">
                            <div class="tab-pane fade show active" id="v-pills-all" role="tabpanel"
                                aria-labelledby="v-pills-all-tab">
                                <div class="row">
                                    <?php
                                    include("BD/conexion.php");
                                    $datab = "cafeteria";
                                    $db = mysqli_select_db($connection, $datab);

                                    $search_query = '';
                                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                                        $search = mysqli_real_escape_string($connection, $_GET['search']);
                                        $search_query = " AND Product_Nombre LIKE '%$search%'";
                                    }

                                    if (isset($_GET['categoriaid']) && !empty($_GET['categoriaid'])) {
                                        $categoria_id = $_GET['categoriaid'];
                                        $consulta = "SELECT * FROM menuproductos WHERE id_categoria = $categoria_id $search_query";
                                    } else {
                                        $consulta = "SELECT * FROM menuproductos WHERE 1=1 $search_query";
                                    }

                                    $resultado = mysqli_query($connection, $consulta);
                                    if (mysqli_num_rows($resultado) > 0) {
                                        while ($fila = mysqli_fetch_array($resultado)) {
                                            ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="gallery-single fix">
                                                    <img src="<?php echo substr($fila['Imagen'], 1) ?>" class="img-fluid"
                                                        alt="Image">
                                                    <div class="why-text">
                                                        <h4 class="my-0 font-weight-bold"><?php echo $fila['Product_Nombre'] ?>
                                                        </h4>
                                                        <p><?php echo $fila['Descripcion'] ?></p>
                                                        <h5 class="card-title pricing-card-title precio">
                                                            $<span><?php echo $fila['Costo'] ?></span></h5>
                                                        <?php
                                                        if (isset($_SESSION['nombre_usuario']))
                                                            echo '<a class="agregar-carrito text-decoration-none" href="#" data-id=' . $fila["id"] . '>Comprar</a>';
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } else {
                                        echo "<p class='text-center'>No se encontraron productos para esta búsqueda.</p>";
                                    }
                                    mysqli_free_result($resultado);
                                    mysqli_close($connection);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php include("includes/footer.html"); ?>
</body>
<script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

</html>
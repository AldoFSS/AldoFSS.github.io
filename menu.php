<?php
require_once __DIR__ . '/PHP/seguridad.php';
iniciarSesionSegura();
header('Cache-Control: ' . (!empty($_SESSION['ID_Usuario']) ? 'no-store' : 'private, no-cache, must-revalidate'));
$busqueda = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';
$categoria = filter_var($_GET['categoriaid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$menuUrl = function ($id) use ($busqueda) {
    $parametros = [];
    if ($id) $parametros['categoriaid'] = $id;
    if ($busqueda !== '') $parametros['search'] = $busqueda;
    return 'menu.php' . ($parametros ? '?' . http_build_query($parametros) : '');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FFFFFF">
    <title data-i18n="documentTitle">Menú · Caféliz</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/cafeliz-pwa-192-v2.png">
</head>
<body class="menu-page">
    <?php include("includes/navbar.php"); ?>
    <main class="menu-box">
        <div class="container">
            <header class="menu-heading">
                <span class="menu-eyebrow" data-i18n="eyebrow">PREPARADO PARA TI</span>
                <h1 data-i18n="title">El menú de Caféliz</h1>
                <p data-i18n="lead">Tu café favorito y algo rico para acompañarlo.</p>
            </header>
            <div class="menu-language-row">
                <label for="menu-language" data-i18n="languageLabel">Idioma</label>
                <select id="menu-language" name="language" aria-label="Idioma del menú"></select>
                <span id="menu-translation-status" role="status" aria-live="polite"></span>
            </div>
            <form id="menu-search-form" method="GET" action="menu.php" class="menu-search" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="menu-search" data-i18n="searchLabel">Buscar en el menú</label>
                <input id="menu-search" type="search" name="search" placeholder="¿Qué se te antoja hoy?"
                    data-i18n-placeholder="searchPlaceholder"
                    value="<?php echo htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($categoria): ?><input type="hidden" name="categoriaid" value="<?php echo $categoria; ?>"><?php endif; ?>
                <button type="submit" data-i18n="searchButton">Buscar</button>
            </form>
            <nav class="menu-categories" aria-label="Categorías del menú" data-i18n-aria="categoriesLabel">
                <a class="menu-category <?php echo !$categoria ? 'is-active' : ''; ?>"
                   <?php if (!$categoria) echo 'aria-current="page"'; ?>
                   data-category-id="all" data-spanish-name="Todo el menú"
                   href="<?php echo htmlspecialchars($menuUrl(0), ENT_QUOTES, 'UTF-8'); ?>">Todo el menú</a>
                <?php
                require("BD/conexion.php");
                $categorias = $connection->query('SELECT ID_Cat, Categoria FROM menucategoria ORDER BY ID_Cat');
                while ($cat = $categorias->fetch_assoc()):
                ?>
                    <a class="menu-category <?php echo $categoria === (int) $cat['ID_Cat'] ? 'is-active' : ''; ?>"
                       <?php if ($categoria === (int) $cat['ID_Cat']) echo 'aria-current="page"'; ?>
                       data-category-id="<?php echo (int) $cat['ID_Cat']; ?>"
                       data-spanish-name="<?php echo htmlspecialchars($cat['Categoria'], ENT_QUOTES, 'UTF-8'); ?>"
                       href="<?php echo htmlspecialchars($menuUrl((int) $cat['ID_Cat']), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($cat['Categoria'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endwhile; ?>
            </nav>
            <?php
            $consulta = $connection->prepare('SELECT * FROM menuproductos WHERE (? = 0 OR id_categoria = ?) ORDER BY id');
            $consulta->bind_param('ii', $categoria, $categoria);
            $consulta->execute();
            $resultado = $consulta->get_result();
            ?>
            <div class="menu-results">
                <span id="menu-results-count"><?php echo $resultado->num_rows; ?> opciones para disfrutar</span>
                <span><span data-i18n="madeToOrder">Hecho al momento</span> <i class="bi bi-cup-hot" aria-hidden="true"></i></span>
            </div>
            <p class="menu-barista-note" id="barista-translation-note" data-i18n="baristaNote">
                El nombre original del producto en español se enviará a barra.
            </p>
            <div class="menu-grid" id="lista-productos">
                <?php while ($fila = $resultado->fetch_assoc()):
                    $nombre = htmlspecialchars($fila['Product_Nombre'], ENT_QUOTES, 'UTF-8');
                    $descripcion = $fila['Descripcion'] ?: '';
                    $imagen = htmlspecialchars(substr($fila['Imagen'], 1), ENT_QUOTES, 'UTF-8');
                    $precio = number_format((float) $fila['Costo'], 2, '.', '');
                ?>
                    <article class="menu-product gallery-single" data-product-id="<?php echo (int) $fila['id']; ?>"
                        data-spanish-name="<?php echo $nombre; ?>"
                        data-spanish-description="<?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="menu-product-image">
                            <img src="<?php echo $imagen; ?>" alt="<?php echo $nombre; ?>" loading="lazy" width="480" height="360">
                            <span class="product-in-cart" data-cart-quantity="<?php echo (int) $fila['id']; ?>" hidden></span>
                        </div>
                        <div class="menu-product-info">
                            <h2><?php echo $nombre; ?></h2>
                            <p><?php echo htmlspecialchars($fila['Descripcion'] ?: 'Preparado al momento, para disfrutar a tu gusto.', ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="menu-product-bottom">
                                <span class="menu-product-price">$<?php echo $precio; ?></span>
                                <?php if (isset($_SESSION['nombre_usuario'])): ?>
                                    <button type="button" class="agregar-carrito"
                                        data-id="<?php echo (int) $fila['id']; ?>"
                                        data-name="<?php echo $nombre; ?>" data-display-name="<?php echo $nombre; ?>"
                                        data-price="<?php echo $precio; ?>" data-image="<?php echo $imagen; ?>"
                                        aria-label="Agregar <?php echo $nombre; ?> al carrito">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i><span data-i18n="add">Agregar</span>
                                    </button>
                                <?php else: ?>
                                    <a class="product-login" href="login.php"><span data-i18n="loginToOrder">Inicia sesión para pedir</span> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <div class="menu-no-results" <?php echo $resultado->num_rows ? 'hidden' : ''; ?>>
                <i class="bi bi-search" aria-hidden="true"></i><h2 data-i18n="noResultsTitle">No encontramos ese antojo</h2>
                <p data-i18n="noResultsDescription">Prueba con otro nombre o explora todo el menú.</p>
                <a href="menu.php" data-i18n="seeFullMenu">Ver todo el menú</a>
            </div>
            <?php $connection->close(); ?>
        </div>
    </main>
    <?php include("includes/footer.html"); ?>
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu-i18n.js?v=2"></script>
</body>
</html>

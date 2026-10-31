<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="node_modules/datatables.net-bs5/css/dataTables.bootstrap5.css"/>
<link rel="stylesheet" href="node_modules/toastr/build/toastr.min.css"/>
<link rel="stylesheet" href="Awesome/css/fontawesome.css">
<link rel="stylesheet" href="Awesome/css/solid.css">
<link rel="stylesheet" href="CSS/estilos.css">
<link rel="stylesheet" href="CSS/home.css">
<link rel="stylesheet" href="CSS/pwa.css">
<link rel="stylesheet" href="CSS/menu-cart.css?v=5">
<link rel="stylesheet" href="CSS/admin.css?v=5">

<!-- Scripts JavaScript -->
<script src="node_modules/jquery/dist/jquery.min.js"></script>
<script src="node_modules/chart.js/dist/chart.umd.js"></script>
<script src="JS/main.js?v=3"></script>

</head>

<body>
  <?php 
  require_once __DIR__ . '/../PHP/seguridad.php';
  iniciarSesionSegura();
  ?>
  <div class="Barra">
    <nav class="navbar navbar-expand-xl ftco_navbar ftco-navbar-light" id="ftco-navbar" style="z-index: 10;">
      <div class="container">
      <img src="./IMG/CAFELIZ.png" alt="logo de la Cafeteria" width="50px" height="50px">
        <a class="navbar-brand" href="index.php">CAFELIZ</a>
        <span class="navbar-date"><?php echo date('d/m/Y'); ?></span>
        <button id="open_cart" type="button" aria-label="Ver carrito" aria-controls="sidecart" aria-expanded="false">
          <div class="cart">
            <div class="cart_container d-flex flex-row">
              <div class="cart_icon">
                <span class="fa-solid fa-cart-shopping"></span>
              </div>
              <div class="cart_count"><span>0</span></div>
              <div class="cart-content">
                <div class="cart_price">$0.00</div>
              </div>
            </div>
          </div>
        </button>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Abrir navegación" data-i18n-aria="navToggleAria">
          <span class="fa fa-bars" aria-hidden="true"></span>
          <span class="visually-hidden" data-i18n="navToggle">Abrir navegación</span>
        </button>

        <aside id="sidecart" class="sidecart" role="dialog" aria-modal="true" aria-labelledby="cart-title" aria-hidden="true" inert>
          <header class="cart_header">
            <div>
              <span class="cart-kicker" data-i18n="cartKicker">UN MOMENTO PARA DISFRUTAR</span>
              <h2 id="cart-title" data-i18n="cartTitle">Tu carrito</h2>
              <p id="cart-item-count">0 productos en tu pedido</p>
            </div>
            <button id="close_btn" type="button" aria-label="Cerrar carrito" data-i18n-aria="closeCart"><i class="bi bi-x" aria-hidden="true"></i></button>
          </header>
          <div class="cart_items"></div>
          <footer class="cart_actions">
            <div class="subtotal"><p data-i18n="estimatedTotal">Total estimado</p><p>$<span id="subtotal_price">0.00</span></p></div>
            <p class="cart-note" data-i18n="cartNote">Un paso más y tu pedido estará listo para registrar.</p>
            <button id="sales_cart" type="button" disabled><span data-i18n="continueOrder">Continuar con mi pedido</span> <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            <button id="empty_cart" type="button" disabled data-i18n="emptyCartButton">Vaciar carrito</button>
          </footer>
        </aside>
        <div class="backdrop" aria-hidden="true"></div>
        <div id="cart-toast" class="cart-toast" role="status" aria-live="polite" aria-atomic="true">
          <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
          <div><strong data-i18n="addedToCart">Agregado a tu carrito</strong><p></p></div>
          <button type="button" data-open-cart hidden><span data-i18n="viewCart">Ver carrito</span> <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </div>

        <div class="collapse navbar-collapse navbar-menu-row" id="navbarNav">
            <ul class="navbar-nav align-items-xl-center">
              <li class="nav-item"><a href="./index.php" class="nav-link" data-i18n="homeNav">Home</a></li>
              <li class="nav-item"><a href="./menu.php" class="nav-link" data-i18n="menuNav">Menu</a></li>
              <li class="nav-item"><a href="./reservas.php" class="nav-link" data-i18n="reservationsNav">Reservar espacio</a></li>
              <li class="nav-item"><a href="./login.php" class="nav-link" data-i18n="loginNav">Sesion</a></li>
              <?php if (!empty($_SESSION['ID_Usuario']) && in_array($_SESSION['user_Cargo'] ?? '', ['usuario', 'barista', 'administrador'], true)): ?>
                <?php if (in_array($_SESSION['user_Cargo'] ?? '', ['barista', 'administrador'], true)): ?>
                  <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="greenNavMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Huella verde</a>
                    <ul class="dropdown-menu" aria-labelledby="greenNavMenu">
                      <li><a class="dropdown-item" href="./verde.php">Mi huella verde</a></li>
                      <li><a class="dropdown-item" href="./verde-barra.php">Caja verde</a></li>
                    </ul>
                  </li>
                <?php else: ?>
                  <li class="nav-item"><a href="./verde.php" class="nav-link">Huella verde</a></li>
                <?php endif; ?>
              <?php endif; ?>
              <?php if (($_SESSION['user_Cargo'] ?? '') === 'administrador'): ?>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" id="adminNavMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Administración</a>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminNavMenu">
                    <li><a class="dropdown-item" href="./listProductos.php"><i class="bi bi-cup-hot" aria-hidden="true"></i> Productos</a></li>
                    <li><a class="dropdown-item" href="./listCategorias.php"><i class="bi bi-tags" aria-hidden="true"></i> Categorías</a></li>
                    <li><a class="dropdown-item" href="./listUsuarios.php"><i class="bi bi-people" aria-hidden="true"></i> Usuarios</a></li>
                    <li><a class="dropdown-item" href="./listPedidos.php"><i class="bi bi-receipt" aria-hidden="true"></i> Pedidos</a></li>
                    <li><a class="dropdown-item" href="./listInventario.php"><i class="bi bi-box-seam" aria-hidden="true"></i> Inventario</a></li>
                  </ul>
                </li>
              <?php endif; ?>
            </ul>
          <div class="pwa-actions" aria-label="Estado de la aplicación">
            <span id="pwa-status" class="pwa-status" role="status" aria-live="polite"></span>
            <span id="pwa-feedback" class="pwa-status" role="status" aria-live="polite"></span>
            <button id="pwa-update" class="pwa-install" type="button" hidden>
              Actualizar app
            </button>
            <?php if (!empty($_SESSION['ID_Usuario'])): ?>
              <button id="pwa-notifications" class="pwa-install" type="button" hidden>
                Activar notificaciones
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>
  </div>
  
  <?php include("./PHP/compra.php") ?>
  <script src="JS/pwa.js" defer></script>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra</title>
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    <script>
        emailjs.init('g-LYD0idyVihTx0P3');
    </script>
    <style>
        .text {
            font-weight: bold;
        }
        .modal-content {
            border-radius: 0.5rem;
        }
        .btn-group-vertical {
            display: flex;
            flex-direction: column;
            gap: 0.5rem; /* Ajuste de espacio entre botones */
        }
    </style>
</head>
<body>

<!-- Carrito de compras simulado -->
<!-- Modal de pago -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="paymentModalLabel" data-i18n="checkoutTitle">Confirmar pedido</h3>
            </div>
            <div class="modal-body">
                <div class="row gx-3">
                    <div class="col-12">
                        <div class="d-flex flex-column">
                            <label for="personName" class="text mb-1" data-i18n="nameLabel">Nombre:</label>
                            <input class="form-control mb-3" id="personName" name="personName" type="text" placeholder="Nombre" data-i18n-placeholder="namePlaceholder" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-column">
                            <label for="email" class="text mb-1" data-i18n="emailLabel">Correo:</label>
                            <input class="form-control mb-3" id="email" name="email" type="email" placeholder="Correo" data-i18n-placeholder="emailPlaceholder" required>
                        </div>
                    </div>
                    <p class="text-muted" data-i18n="paymentNote">Se registrará tu pedido. El pago se realiza en caja; esta aplicación todavía no procesa tarjetas.</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-group-vertical w-100">
                    <button type="button" class="btn boton_m" id="payButton">
                        <span class="ps-3" id="totalAmount" data-i18n="registrationButton"> Registrar pedido</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 Script -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>

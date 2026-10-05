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
                <h3 class="modal-title" id="paymentModalLabel">Detalles del Pago</h3>
            </div>
            <div class="modal-body">
                <div class="row gx-3">
                    <div class="col-12">
                        <div class="d-flex flex-column">
                            <label for="personName" class="text mb-1">Nombre:</label>
                            <input class="form-control mb-3" id="personName" name="personName" type="text" placeholder="Nombre" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-column">
                            <label for="email" class="text mb-1">Correo:</label>
                            <input class="form-control mb-3" id="email" name="email" type="email" placeholder="Correo" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-column">
                            <label for="cardNumber" class="text mb-1">Número de la Tarjeta:</label>
                            <input class="form-control mb-3" id="cardNumber" name="cardNumber" type="text" placeholder="1234 5678 4356 7890" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex flex-column">
                            <label for="expiryDate" class="text mb-1">Expiración:</label>
                            <input class="form-control mb-3" id="expiryDate" name="expiryDate" type="text" placeholder="MM/YYYY" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex flex-column">
                            <label for="cvv" class="text mb-1">CVV/CVC:</label>
                            <input class="form-control mb-3 pt-2" id="cvv" name="cvv" type="password" placeholder="***" required>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-group-vertical w-100">
                    <button type="button" class="btn boton_m" id="payButton">
                        <span class="ps-3" id="totalAmount"> Pagar $0.00</span>
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


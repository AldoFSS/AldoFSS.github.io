<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra</title>
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
                    <section class="checkout-green-coupon col-12" aria-labelledby="green-coupon-title">
                        <h4 id="green-coupon-title"><i class="bi bi-ticket-perforated" aria-hidden="true"></i> <span data-i18n="greenCouponTitle">Recompensa Huella verde</span></h4>
                        <p data-i18n="greenCouponHelp">Usa un cupón vigente para liberar una bebida incluida en tu pedido.</p>
                        <label for="green-coupon-select" data-i18n="greenCouponLabel">Cupón disponible</label>
                        <select class="form-select mb-3" id="green-coupon-select">
                            <option value="" data-i18n="greenCouponNone">No usar cupón</option>
                        </select>
                        <label for="green-coupon-product" data-i18n="greenCouponProductLabel">Bebida que deseas recibir gratis</label>
                        <select class="form-select" id="green-coupon-product" disabled>
                            <option value="" data-i18n="greenCouponChooseProduct">Selecciona una bebida de tu pedido</option>
                        </select>
                        <p id="green-coupon-status" class="checkout-coupon-status" role="status" aria-live="polite"></p>
                    </section>
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

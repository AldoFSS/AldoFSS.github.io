document.addEventListener('DOMContentLoaded', () => {
    const drawer = document.getElementById('sidecart');
    if (!drawer) return;
    const cartList = drawer.querySelector('.cart_items');
    const toast = document.getElementById('cart-toast');
    const couponSelect = document.getElementById('green-coupon-select');
    const couponProductSelect = document.getElementById('green-coupon-product');
    const couponStatus = document.getElementById('green-coupon-status');
    const money = value => Number(value).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const text = (key, fallback, values = {}) =>
        window.cafelizI18n?.text(key, values) || fallback;
    const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[char]));
    const safeImage = value => {
        try {
            const url = new URL(value, location.href);
            return url.origin === location.origin && /^https?:$/.test(url.protocol) ? escapeHtml(url.href) : '';
        } catch { return ''; }
    };
    function readCart() {
        try {
            const saved = JSON.parse(localStorage.getItem('cart') || '[]');
            if (!Array.isArray(saved)) return [];
            const unique = new Map();
            saved.slice(0, 100).forEach(item => {
                if (!item || !/^\d+$/.test(String(item.id)) || Number(item.id) < 1) return;
                const price = Number(item.price), quantity = Number(item.quantity);
                if (!Number.isFinite(price) || price < 0 || !Number.isInteger(quantity) || quantity < 1) return;
                const id = String(Number(item.id));
                const previous = unique.get(id);
                unique.set(id, {id, name: String(item.name || 'Producto'), image: String(item.image || ''),
                    price, quantity: Math.min(999, quantity + (previous?.quantity || 0))});
            });
            return Array.from(unique.values());
        } catch { return []; }
    }
    let cart = readCart();
    let checkoutOptions = null;
    let toastTimer;
    const buttonTimers = new WeakMap();

    function selectedCouponDiscountCents() {
        if (!couponSelect?.value || !couponProductSelect?.value) return 0;
        const product = cart.find(item => item.id === couponProductSelect.value);
        return product ? Math.round(product.price * 100) : 0;
    }

    function updateCouponProducts() {
        if (!couponSelect || !couponProductSelect) return;
        const previousProduct = couponProductSelect.value;
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = text('greenCouponChooseProduct', 'Selecciona una bebida de tu pedido');
        couponProductSelect.replaceChildren(placeholder);

        const inCart = new Set(cart.map(item => item.id));
        const beverages = (checkoutOptions?.bebidas || []).filter(item => inCart.has(String(item.id)));
        beverages.forEach(beverage => {
            const option = document.createElement('option');
            option.value = String(beverage.id);
            const name = window.cafelizI18n?.productName(beverage.id, beverage.nombre) || beverage.nombre;
            const product = cart.find(item => item.id === option.value);
            option.textContent = name + ' · $' + money(product?.price || 0);
            couponProductSelect.append(option);
        });

        couponProductSelect.disabled = !couponSelect.value || beverages.length === 0;
        if (beverages.some(item => String(item.id) === previousProduct)) {
            couponProductSelect.value = previousProduct;
        }
        if (!couponSelect.value) {
            couponStatus.textContent = checkoutOptions?.cupones.length
                ? text('greenCouponHelp', 'Usa un cupón vigente para liberar una bebida incluida en tu pedido.')
                : text('greenCouponEmpty', 'No tienes cupones vigentes. Completa ocho vasos reutilizables para obtener uno.');
        } else if (!beverages.length) {
            couponProductSelect.value = '';
            couponStatus.textContent = text('greenCouponAddDrink', 'Agrega una bebida al pedido para usar este cupón.');
        } else if (couponProductSelect.value) {
            couponStatus.textContent = text('greenCouponSavings', 'Descuento estimado: ${discount}. Se verificará al registrar el pedido.', {
                discount: money(selectedCouponDiscountCents() / 100)
            });
        } else {
            couponStatus.textContent = text('greenCouponChooseDrink', 'Elige qué bebida de tu pedido quieres recibir gratis.');
        }
    }

    async function loadCheckoutPromotions() {
        const submitButton = document.getElementById('payButton');
        const previousCoupon = couponSelect.value;
        couponStatus.textContent = text('greenCouponLoading', 'Buscando cupones vigentes…');
        try {
            const response = await fetch('PHP/FidelidadVerde.php?accion=checkout', {
                credentials: 'same-origin',
                headers: {Accept: 'application/json'}
            });
            const data = await response.json().catch(() => null);
            if (!data) throw new Error('No se pudo consultar la billetera de cupones.');
            if (!response.ok || !data.success) throw new Error(data.message || 'No se pudieron cargar los cupones.');
            checkoutOptions = data;
            const none = document.createElement('option');
            none.value = '';
            none.textContent = text('greenCouponNone', 'No usar cupón');
            couponSelect.replaceChildren(none);
            data.cupones.forEach(coupon => {
                const option = document.createElement('option');
                option.value = coupon.codigo;
                const expiry = new Date(coupon.expira.replace(' ', 'T')).toLocaleDateString(
                    window.cafelizI18n?.language || 'es-MX'
                );
                option.textContent = text('greenCouponOption', 'Bebida gratis · vence {date}', {date: expiry});
                couponSelect.append(option);
            });
            couponSelect.value = data.cupones.some(coupon => coupon.codigo === previousCoupon)
                ? previousCoupon : '';
            updateCouponProducts();
        } catch (error) {
            checkoutOptions = null;
            couponSelect.replaceChildren();
            const none = document.createElement('option');
            none.value = '';
            none.textContent = text('greenCouponNone', 'No usar cupón');
            couponSelect.append(none);
            couponProductSelect.replaceChildren();
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = text('greenCouponChooseProduct', 'Selecciona una bebida de tu pedido');
            couponProductSelect.append(placeholder);
            couponProductSelect.disabled = true;
            couponStatus.textContent = error.message + ' ' +
                text('greenCouponContinue', 'Puedes continuar sin cupón.');
            console.warn('No se pudieron cargar las opciones de cupones.', error);
        } finally {
            submitButton.disabled = false;
        }
    }

    function notify(title, detail = '', showCart = false) {
        if (!toast) return;
        toast.querySelector('strong').textContent = title;
        toast.querySelector('p').textContent = detail;
        toast.querySelector('button').hidden = !showCart;
        toast.classList.add('is-visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 3200);
    }
    toast?.querySelector('button').addEventListener('click', () => toast.classList.remove('is-visible'));

    function updateCart() {
        let totalCents = 0, totalItems = 0;
        cartList.innerHTML = cart.length ? cart.map(item => {
            const lineCents = Math.round(item.price * 100) * item.quantity;
            totalCents += lineCents;
            totalItems += item.quantity;
            const id = escapeHtml(item.id);
            const displayName = window.cafelizI18n?.productName(item.id, item.name) || item.name;
            const safeDisplayName = escapeHtml(displayName);
            return `<article class="cart_item" data-cart-id="${id}">
                <div class="item_img"><img src="${safeImage(item.image)}" alt="${safeDisplayName}"></div>
                <div class="item_details">
                    <div class="item-heading"><h3>${safeDisplayName}</h3>
                        <button type="button" class="remove_item" data-id="${id}" aria-label="${escapeHtml(text('removeItem', 'Eliminar {name}', {name: displayName}))}"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                    </div>
                    <span class="item-unit-price">${escapeHtml(text('unitPrice', '$' + money(item.price) + ' por unidad', {price: money(item.price)}))}</span>
                    <div class="item-bottom">
                        <div class="qty" role="group" aria-label="${escapeHtml(text('quantityLabel', 'Cantidad de {name}', {name: displayName}))}">
                            <button type="button" class="decrease" data-id="${id}" aria-label="${escapeHtml(text('removeOne', 'Quitar una unidad de {name}', {name: displayName}))}" ${item.quantity <= 1 ? 'disabled' : ''}>−</button>
                            <strong aria-label="${escapeHtml(text(item.quantity === 1 ? 'oneUnit' : 'manyUnits', '{count} unidades', {count: item.quantity}))}">${item.quantity}</strong>
                            <button type="button" class="increase" data-id="${id}" aria-label="${escapeHtml(text('addOne', 'Agregar una unidad de {name}', {name: displayName}))}" ${item.quantity >= 999 ? 'disabled' : ''}>+</button>
                        </div>
                        <strong class="item-total">$${money(lineCents / 100)}</strong>
                    </div>
                </div>
            </article>`;
        }).join('') : `<div class="cart-empty">
            <i class="bi bi-bag-heart" aria-hidden="true"></i>
            <h3>${escapeHtml(text('cartEmptyTitle', 'Tu próximo antojo empieza aquí'))}</h3>
            <p>${escapeHtml(text('cartEmptyDescription', 'Elige algo del menú y lo guardaremos en tu carrito.'))}</p>
            <a href="menu.php" data-close-cart>${escapeHtml(text('exploreMenu', 'Explorar el menú'))} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>`;
        document.querySelector('.cart_count span').textContent = totalItems;
        document.querySelector('.cart_price').textContent = '$' + money(totalCents / 100);
        drawer.querySelector('#subtotal_price').textContent = money(totalCents / 100);
        document.getElementById('cart-item-count').textContent = text(totalItems === 1 ? 'cartOne' : 'cartMany',
            totalItems + (totalItems === 1 ? ' producto en tu pedido' : ' productos en tu pedido'), {count: totalItems});
        document.getElementById('open_cart').setAttribute('aria-label', text('cartAria',
            'Ver carrito: ' + totalItems + ' productos, $' + money(totalCents / 100),
            {count: totalItems, total: money(totalCents / 100)}));
        document.getElementById('sales_cart').disabled = !cart.length;
        document.getElementById('empty_cart').disabled = !cart.length;
        updateCouponProducts();
        const totalForCheckout = Math.max(0, totalCents - selectedCouponDiscountCents());
        document.getElementById('totalAmount').textContent = text('orderButton',
            'Registrar pedido · $' + money(totalForCheckout / 100), {total: money(totalForCheckout / 100)});
        document.querySelectorAll('[data-cart-quantity]').forEach(badge => {
            const quantity = cart.find(item => item.id === badge.dataset.cartQuantity)?.quantity || 0;
            badge.hidden = !quantity;
            badge.textContent = text('badgeCart', quantity + ' en tu carrito', {count: quantity});
        });
    }
    function saveCart(next) {
        try {
            localStorage.setItem('cart', JSON.stringify(next));
        } catch {
            notify(text('storageError', 'No se pudo guardar'), text('storageHelp', 'Habilita el almacenamiento del navegador.'));
            return false;
        }
        cart = next;
        updateCart();
        return true;
    }
    function clearCart() { return saveCart([]); }
    function ProductsInCart() { return cart.length > 0; }
    function closeModalAndReset() {
        document.dispatchEvent(new CustomEvent('cafeliz:close-cart'));
    }
    function animateAdded(button, id) {
        const trigger = document.getElementById('open_cart');
        trigger.classList.remove('cart-bump');
        void trigger.offsetWidth;
        trigger.classList.add('cart-bump');
        button.classList.add('is-added');
        button.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i><span>' + escapeHtml(text('added', 'Agregado')) + '</span>';
        clearTimeout(buttonTimers.get(button));
        buttonTimers.set(button, setTimeout(() => {
            button.classList.remove('is-added');
            button.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i><span>' + escapeHtml(text('add', 'Agregar')) + '</span>';
        }, 1000));
        const row = cartList.querySelector('[data-cart-id="' + id + '"]');
        row?.classList.add('cart-item-added');
    }
    document.addEventListener('click', event => {
        const add = event.target.closest('.agregar-carrito');
        if (add) {
            event.preventDefault();
            if (sendingOrder) return;
            const id = String(add.dataset.id);
            const item = cart.find(product => product.id === id);
            if (item?.quantity >= 999) {
                notify(text('quantityLimitTitle', 'Cantidad máxima alcanzada'),
                    text('quantityLimitMessage', 'Puedes agregar hasta 999 unidades.'));
                return;
            }
            const next = cart.map(product => ({...product}));
            if (item) next.find(product => product.id === id).quantity++;
            else {
                if (cart.length >= 100) {
                    notify(text('cartFullTitle', 'Tu carrito está lleno'),
                        text('cartFullMessage', 'Puedes elegir hasta 100 productos diferentes.'));
                    return;
                }
                const price = Number(add.dataset.price);
                if (!Number.isFinite(price) || price < 0) return;
                next.push({id, name: add.dataset.displayName || add.dataset.name, price, quantity: 1, image: add.dataset.image});
            }
            if (!saveCart(next)) return;
            animateAdded(add, id);
            const saved = cart.find(product => product.id === id);
            const count = text(saved.quantity === 1 ? 'oneUnit' : 'manyUnits',
                '{count} unidades', {count: saved.quantity});
            notify(text('addedToCart', 'Agregado a tu carrito'), saved.name + ' · ' + count, true);
            return;
        }
        const control = event.target.closest('#sidecart .remove_item, #sidecart .increase, #sidecart .decrease');
        if (!control || control.disabled || sendingOrder) return;
        const id = control.dataset.id;
        const item = cart.find(product => product.id === id);
        if (!item) return;
        const remove = control.classList.contains('remove_item');
        const direction = control.classList.contains('increase') ? 1 : -1;
        const next = remove ? cart.filter(product => product.id !== id)
            : cart.map(product => product.id === id ? {...product, quantity: Math.max(1, Math.min(999, product.quantity + direction))} : product);
        const action = remove ? null : (direction > 0 ? 'increase' : 'decrease');
        if (!saveCart(next)) return;
        if (remove) notify(text('productRemoved', 'Producto eliminado'), item.name);
        const focusTarget = action ? cartList.querySelector('.' + action + '[data-id="' + id + '"]:not(:disabled)') : null;
        (focusTarget || cartList.querySelector('button:not(:disabled)') || document.getElementById('close_btn')).focus({preventScroll: true});
    });
    document.getElementById('empty_cart').addEventListener('click', () => {
        if (sendingOrder || !cart.length) return;
        if (clearCart()) {
            notify(text('cartCleared', 'Carrito vacío'), text('cartClearedMessage', 'Puedes elegir algo más del menú.'));
            document.getElementById('close_btn').focus();
        }
    });
    couponSelect?.addEventListener('change', updateCouponProducts);
    couponProductSelect?.addEventListener('change', updateCouponProducts);
    document.addEventListener('cafeliz:languagechange', updateCouponProducts);
    document.getElementById('sales_cart').addEventListener('click', async () => {
        if (!ProductsInCart() || sendingOrder) return;
        closeModalAndReset();
        document.getElementById('payButton').disabled = true;
        await loadCheckoutPromotions();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('checkoutModal')).show();
    });
    window.addEventListener('storage', event => {
        if (event.key === 'cart' && !sendingOrder) { cart = readCart(); updateCart(); }
    });
    document.addEventListener('cafeliz:languagechange', updateCart);
    let sendingOrder = false;
    document.getElementById('payButton').addEventListener('click', function() {
        if (sendingOrder) return;
        if (!ProductsInCart()) {
            Swal.fire(text('emptyCartTitle', 'Carrito vacío'), text('addBeforeOrder',
                'Agrega productos antes de registrar un pedido.'), 'warning');
            return;
        }
        if (!navigator.onLine) {
            Swal.fire(text('offlineTitle', 'Sin conexión'), text('offlineMessage',
                'Tu carrito está guardado. Conéctate para confirmar el pedido.'), 'warning');
            return;
        }
        const orderCart = cart.map(item => ({id: String(item.id), quantity: item.quantity}))
            .sort((a, b) => Number(a.id) - Number(b.id));
        const couponCode = couponSelect.value;
        const couponProductId = couponProductSelect.value;
        if (couponCode && !couponProductId) {
            Swal.fire(text('greenCouponChooseDrinkTitle', 'Elige una bebida'),
                text('greenCouponChooseDrink', 'Elige qué bebida de tu pedido quieres recibir gratis.'), 'warning');
            return;
        }
        const fingerprint = couponCode
            ? JSON.stringify({cart: orderCart, couponCode, couponProductId})
            : JSON.stringify(orderCart);
        let pending;
        try {
            pending = JSON.parse(localStorage.getItem('cafeliz-pending-order') || 'null');
            if (!pending || pending.fingerprint !== fingerprint) {
                const bytes = new Uint8Array(16);
                window.crypto.getRandomValues(bytes);
                pending = {fingerprint, requestId: Array.from(bytes, value => value.toString(16).padStart(2, '0')).join('')};
                localStorage.setItem('cafeliz-pending-order', JSON.stringify(pending));
            }
        } catch (error) {
            Swal.fire('No se pudo preparar el pedido', 'Habilita el almacenamiento del navegador y reintenta.', 'error');
            return;
        }
        sendingOrder = true;
        const button = document.getElementById('payButton');
        button.disabled = true;
        $.ajax({
            url: './PHP/FuncionesCompra.php',
            type: 'POST',
            data: JSON.stringify({
                cart: orderCart,
                requestId: pending.requestId,
                ...(couponCode ? {couponCode, couponProductId} : {})
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (!response || !response.success) {
                    Swal.fire(text('orderError', 'Pedido sin confirmar'), response?.message ||
                        text('tryAgain', 'Reintenta sin cambiar el carrito.'), 'error');
                    return;
                }
                clearCart();
                localStorage.removeItem('cafeliz-pending-order');
                bootstrap.Modal.getInstance(document.getElementById('checkoutModal'))?.hide();
                closeModalAndReset();
                const data = response.data;
                const message = text('orderRegistered',
                    'Pedido #{orderId} por ${total}. El cobro se realiza en caja.',
                    {orderId: data.orderId, total: data.total});
                const discountMessage = Number(data.discount) > 0
                    ? '\n' + text('greenCouponApplied', 'Cupón aplicado: ahorraste ${discount}.',
                        {discount: data.discount})
                    : '';
                couponSelect.value = '';
                couponProductSelect.value = '';
                updateCart();
                Swal.fire(text('orderRegisteredTitle', 'Pedido registrado'), message + discountMessage, 'success');
            },
            error: function(xhr) {
                Swal.fire('Pedido sin confirmar', xhr.responseJSON?.message || 'No se recibió confirmación. Reintenta sin cambiar el carrito.', 'error');
            },
            complete: function() {
                sendingOrder = false;
                button.disabled = false;
            }
        });
    });

    updateCart();
});

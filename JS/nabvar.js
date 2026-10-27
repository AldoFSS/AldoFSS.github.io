document.addEventListener('DOMContentLoaded', () => {
    const drawer = document.getElementById('sidecart');
    if (!drawer) return;
    const cartList = drawer.querySelector('.cart_items');
    const toast = document.getElementById('cart-toast');
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
    let toastTimer;
    const buttonTimers = new WeakMap();

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
        document.getElementById('totalAmount').textContent = text('orderButton',
            'Registrar pedido · $' + money(totalCents / 100), {total: money(totalCents / 100)});
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
    document.getElementById('sales_cart').addEventListener('click', () => {
        if (!ProductsInCart() || sendingOrder) return;
        closeModalAndReset();
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
        const name = document.getElementById('personName').value.trim();
        const emailInput = document.getElementById('email');
        if (!name || !emailInput.value || !emailInput.checkValidity()) {
            Swal.fire(text('checkDetails', 'Revisa tus datos'), text('validNameEmail',
                'Escribe tu nombre y un correo válido.'), 'warning');
            return;
        }
        const fingerprint = JSON.stringify(cart.map(item => ({id: String(item.id), quantity: item.quantity}))
            .sort((a, b) => Number(a.id) - Number(b.id)));
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
            data: JSON.stringify({cart: JSON.parse(fingerprint), requestId: pending.requestId}),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (!response || !response.success) {
                    Swal.fire(text('orderError', 'Pedido sin confirmar'), response?.message ||
                        text('tryAgain', 'Reintenta sin cambiar el carrito.'), 'error');
                    return;
                }
                // Vaciar al confirmar en el servidor, independientemente del correo.
                clearCart();
                localStorage.removeItem('cafeliz-pending-order');
                bootstrap.Modal.getInstance(document.getElementById('checkoutModal'))?.hide();
                closeModalAndReset();
                const data = response.data;
                Swal.fire(text('orderRegisteredTitle', 'Pedido registrado'), text('orderRegistered',
                    'Pedido #{orderId} por ${total}. El cobro se realiza en caja.',
                    {orderId: data.orderId, total: data.total}), 'success');
                if (typeof emailjs !== 'undefined') {
                    emailjs.send('service_drxc3un', 'template_q3ldygo', {
                        user_name: data.userName, total: '$' + data.total, fecha: data.fecha,
                        productos: data.productos.map(item => item.Nombre_Producto + ' - Cantidad: ' + item.Cantidad).join('\n')
                    }).catch(error => console.warn('Pedido registrado; correo no enviado.', error));
                }
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


document.addEventListener('DOMContentLoaded', function() {    // Cargar el carrito desde el almacenamiento local
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Función para actualizar el carrito en la UI
    function updateCart() {
        const cartContainer = document.getElementById('sidecart');
        const cartList = cartContainer.querySelector('.cart_items');
        cartList.innerHTML = ''; // Limpiar los elementos actuales del carrito
        let subtotal = 0; // Se inicialeiza en 0
        let totalItems = 0;
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
            totalItems += item.quantity;
            cartList.innerHTML += `
                <div class="cart_item">
                    <div class="remove_item" data-id="${item.id}">&times;</div>
                    <div class="item_img"><img src="${item.image}" alt=""></div>
                    <div class="item_details">
                        <p>${item.name}</p>
                        <strong>$${item.price}</strong>
                        <div class="qty">
                            <span class="decrease" data-id="${item.id}">-</span>
                            <strong>${item.quantity}</strong>
                            <span class="increase" data-id="${item.id}">+</span>
                        </div>
                    </div>
                </div>
            `;
        });
        // Actualizar la cuenta de productos y el subtotal
        document.querySelector('.cart_count span').innerText = totalItems;
        document.querySelector('.cart_price').innerText = `$${subtotal.toFixed(2)}`;
        cartContainer.querySelector('#subtotal_price').innerText = subtotal.toFixed(2);
    }
    // Función para vaciar el carrito
    function clearCart() {
        cart = [];
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCart();
    }
    
    // Función para verificar si el carrito tiene productos
    function ProductsInCart() {
        return cart.length > 0;
    }

    // Manejo de eventos de clic
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('agregar-carrito')) {
            event.preventDefault();
            const id = event.target.getAttribute('data-id');
            const productElement = event.target.closest('.gallery-single');
            const name = productElement.querySelector('h4').innerText;
            const price = parseFloat(productElement.querySelector('.precio span').innerText);
            const image = productElement.querySelector('img').getAttribute('src');

            let item = cart.find(item => item.id === id);
            if (item) {
                // Usar SweetAlert
                Swal.fire({
                    icon: 'info',
                    title: 'Producto en el carrito',
                    text: 'Lo siento, el producto ya está en el carrito',
                    confirmButtonText: 'Ok'
                });
                return;
            }
            cart.push({ id, name, price, quantity: 1, image });
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCart();
        }
        //Eliminar el producto especificado
        if (event.target.classList.contains('remove_item')) {
            const id = event.target.getAttribute('data-id');
            cart = cart.filter(item => item.id !== id);
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCart();
        }
        if (event.target.classList.contains('increase') || event.target.classList.contains('decrease')) {
            const id = event.target.getAttribute('data-id');
            const item = cart.find(item => item.id === id);

            if (event.target.classList.contains('increase')) {
                item.quantity++;
            } else {
                item.quantity = Math.max(1, item.quantity - 1);
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCart();
        }
    });
    
    // Evento para el botón "Vaciar Carrito"
    document.getElementById('empty_cart').addEventListener('click', function() {
        clearCart();
        Swal.fire('Carrito Vacío', 'El carrito ha sido vaciado.', 'success');
        const subtotal = parseFloat(document.querySelector('#subtotal_price').textContent);
        document.getElementById('totalAmount').textContent = `$${subtotal.toFixed(2)}`;
        subtotal=0;
    });
    
    // Evento para el botón "Comprar"
    document.getElementById('sales_cart').addEventListener('click', function(event) {
        const subtotal = parseFloat(document.querySelector('#subtotal_price').textContent);
        document.getElementById('totalAmount').textContent = `$${subtotal.toFixed(2)}`;
        const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
        checkoutModal.show();
    });

// Función para cerrar el modal y restablecer el estado del sidecart y backdrop
function closeModalAndReset() {
    // Asegúrate de que el sidecart tenga la clase sidecart
    $('#sidecart').removeClass('open'); // Eliminar la clase 'open'

    // Eliminar los backdrop duplicados, si existen
    $('.modal-backdrop').remove();

    // Asegúrate de que el backdrop esté presente
    $('.backdrop').removeClass('show'); // Eliminar la clase 'show'
}

document.getElementById('payButton').addEventListener('click', function() {
    if (!ProductsInCart()) {
        Swal.fire('Carrito Vacío', 'Agrega productos al carrito antes de realizar la compra.', 'warning');
        return;
    } else {
        const name = document.getElementById('personName').value;
        const email = document.getElementById('email').value;

        if (!name || !email) {
            Swal.fire('Error', 'Por favor completa todos los campos.', 'error');
            return;
        }

        const totalAmount = parseFloat(document.getElementById('totalAmount').textContent.replace('$', ''));
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const orderData = {
            name,
            email,
            total: totalAmount,
            cart: cart
        };

        $.ajax({
            url: './PHP/FuncionesCompra.php',
            type: 'POST',
            data: JSON.stringify(orderData),
            contentType: 'application/json',
            success: function(response) {
                if (response && response.success) {
                    const emailData = response.data;
                    let productosTexto = emailData.productos.map(producto => {
                        return `${producto.Nombre_Producto} - Cantidad: ${producto.Cantidad}`;
                    }).join('\n');

                    const emailParams = {
                        user_name: emailData.userName,
                        total: `$${emailData.total}`,
                        fecha: emailData.fecha,
                        productos: productosTexto // Usa el texto generado aquí
                    };

                    // Verifica si los productos fueron insertados correctamente
                    if (response.productosInsertados) { // Asegúrate de que este campo esté presente en la respuesta
                        // Enviar el correo con EmailJS
                        emailjs.send("service_drxc3un","template_q3ldygo", emailParams)
                            .then(function() {
                                Swal.fire('Éxito', 'Compra realizada con éxito.', 'success').then(() => {
                                    $('#checkoutModal').modal('hide'); 
                                    closeModalAndReset();
                                    clearCart();
                                });
                            })
                            
                    }
                } else {
                    Swal.fire('Error', 'Ocurrió un error al procesar la compra.', 'error');
                }
            },
            error: function(error) {
                console.error('Error en la conexión:', error);
                Swal.fire('Error', 'Error en la conexión. Inténtalo más tarde.', 'error');
            }
        });
    }
});




// Función para cerrar el modal y restablecer el estado del sidecart y backdrop
function closeModalAndReset() {
    $('#sidecart').removeClass('open'); 
    $('.modal-backdrop').remove();
    $('.backdrop').removeClass('show'); 
}
// Evento que se dispara al abrir el modal
$('#checkoutModal').on('shown.bs.modal', function () {
    // Eliminar cualquier backdrop duplicado
    if ($('.modal-backdrop').length > 1) {
        $('.modal-backdrop').not(':first').remove(); // Eliminar los backdrop adicionales
    }
});
    // Actualizar el carrito al cargar la página
    updateCart();
});

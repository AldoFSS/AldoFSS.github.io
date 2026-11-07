document.addEventListener('DOMContentLoaded', () => {
    const ordersRoot = document.getElementById('kds-orders');
    const connectionStatus = document.getElementById('kds-connection');
    const labels = {
        pendiente: 'Pendiente',
        preparando: 'En preparación',
        listo: 'Listo para entregar',
        entregado: 'Entregado'
    };
    let loading = false;
    let timer;

    function createElement(tag, className, text) {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    }

    function renderOrders(orders) {
        const fragment = document.createDocumentFragment();
        if (!orders.length) {
            fragment.append(createElement('p', 'kds-empty', 'No hay comandas pendientes.'));
            ordersRoot.replaceChildren(fragment);
            return;
        }

        orders.forEach(order => {
            const card = createElement('article', 'kds-ticket');
            const heading = createElement('header', 'kds-ticket-heading');
            const title = createElement('h2', '', `Pedido #${order.id}`);
            const status = createElement('span', `kds-status is-${order.status}`, labels[order.status] || order.status);
            heading.append(title, status);
            card.append(heading);

            const items = createElement('ul', 'kds-ticket-items');
            order.items.forEach(item => {
                items.append(createElement('li', '', `${item.quantity} × ${item.name}`));
            });
            card.append(items);

            const footer = createElement('footer', 'kds-ticket-footer');
            const total = Number(order.total);
            footer.append(createElement('span', '', Number.isFinite(total)
                ? new Intl.NumberFormat('es-MX', {style: 'currency', currency: 'MXN'}).format(total)
                : ''));
            if (order.status !== 'entregado') {
                const nextStatus = {
                    pendiente: 'preparando',
                    preparando: 'listo',
                    listo: 'entregado'
                }[order.status];
                if (nextStatus) {
                    const button = createElement('button', 'kds-next', {
                        preparando: 'Iniciar preparación',
                        listo: 'Marcar como listo',
                        entregado: 'Marcar como entregado'
                    }[nextStatus]);
                    button.type = 'button';
                    button.dataset.orderId = String(order.id);
                    button.dataset.nextStatus = nextStatus;
                    footer.append(button);
                }
            }
            card.append(footer);
            fragment.append(card);
        });
        ordersRoot.replaceChildren(fragment);
    }

    async function refresh() {
        if (loading || document.hidden) return;
        loading = true;
        try {
            const response = await fetch('PHP/ColaBarra.php', {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {Accept: 'application/json'}
            });
            const data = await response.json();
            if (!response.ok || !data.success || !Array.isArray(data.orders)) {
                throw new Error(data.message || 'No se pudo actualizar la cola.');
            }
            renderOrders(data.orders);
            connectionStatus.textContent = 'Conectado · actualización automática cada 5 segundos';
            connectionStatus.classList.add('is-connected');
            connectionStatus.classList.remove('is-error');
        } catch (error) {
            connectionStatus.textContent = error.message || 'No se pudo conectar con la barra.';
            connectionStatus.classList.add('is-error');
            connectionStatus.classList.remove('is-connected');
        } finally {
            loading = false;
            clearTimeout(timer);
            timer = setTimeout(refresh, 5000);
        }
    }

    ordersRoot.addEventListener('click', async event => {
        const button = event.target.closest('[data-order-id][data-next-status]');
        if (!button || button.disabled) return;
        button.disabled = true;
        try {
            const response = await fetch('PHP/ColaBarra.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', Accept: 'application/json'},
                body: JSON.stringify({
                    orderId: button.dataset.orderId,
                    status: button.dataset.nextStatus
                })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'No se pudo actualizar la comanda.');
            }
            await refresh();
        } catch (error) {
            connectionStatus.textContent = error.message || 'No se pudo actualizar la comanda.';
            connectionStatus.classList.add('is-error');
        } finally {
            button.disabled = false;
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });
    refresh();
});

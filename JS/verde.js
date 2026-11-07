(() => {
    const api = 'PHP/FidelidadVerde.php';
    const qrcodeRoot = document.getElementById('qr-code');
    const qrStatus = document.getElementById('qr-status');
    const generateButton = document.getElementById('generar-qr');
    const showQrButton = document.getElementById('mostrar-qr-recompensas');
    const celebration = document.getElementById('recompensa-celebracion');
    const closeCelebrationButton = document.getElementById('cerrar-celebracion');
    let qrTimer = 0;
    let knownCoupons = null;
    let generatingQr = false;
    const metricCards = (container, values) => {
        const cards = container.querySelectorAll('article strong');
        values.forEach((value, index) => {
            if (cards[index]) cards[index].textContent = value;
        });
    };
    const format = (value, unit = '') => `${Number(value || 0).toLocaleString('es-MX')}${unit}`;
    const showStatus = (element, message, error = false) => {
        element.textContent = message;
        element.classList.toggle('is-error', error);
    };
    const getJson = async (url) => {
        const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const result = await response.json().catch(() => null);
        if (!result) throw new Error(response.status === 403 ? 'No tienes permiso para esta acción.' : 'El servidor devolvió una respuesta no válida.');
        if (!response.ok || !result.success) throw new Error(result.message || 'No se pudo consultar la información.');
        return result;
    };

    async function loadPersonal() {
        const data = await getJson(`${api}?accion=personal`);
        const couponKeys = new Set(data.cupones.map(coupon => `${coupon.creado}:${coupon.referencia}`));
        const newAvailableCoupons = knownCoupons === null
            ? []
            : data.cupones.filter(coupon => coupon.estado === 'disponible' &&
                !knownCoupons.has(`${coupon.creado}:${coupon.referencia}`));
        knownCoupons = couponKeys;
        const progress = document.getElementById('vasos-progreso');
        const progressCaption = document.getElementById('progreso-insignias');
        const progressCount = document.getElementById('vasos-contador');
        const liquid = document.getElementById('nivel-taza');
        const earnedRewards = document.getElementById('recompensas-ganadas');
        if (!progress || !progressCaption || !progressCount || !liquid || !earnedRewards) {
            throw new Error('La taza de progreso no está disponible. Recarga la página para actualizarla.');
        }
        metricCards(document.getElementById('metricas-personales'), [
            format(data.vasos),
            `${format(data.co2)} g`,
            `${format(data.residuos)} g`,
            `${format(data.agua)} ml`
        ]);
        earnedRewards.textContent =
            `${format(data.insignias)} ${data.insignias === 1 ? 'bebida gratis ganada' : 'bebidas gratis ganadas'}`;
        const cups = Math.min(8, Math.max(0, Number(data.progreso) || 0));
        progress.setAttribute('aria-valuenow', String(cups));
        progress.setAttribute('aria-label',
            `${cups} de 8 vasos reutilizables para la siguiente recompensa`);
        liquid.setAttribute('y', String(207 - (cups / 8) * 164));
        progressCount.textContent = `${cups} de 8 vasos`;
        progressCaption.textContent = cups === 0 && data.vasos > 0
            ? `¡Meta alcanzada! Ya puedes disfrutar tu bebida gratis.`
            : `Te ${8 - cups === 1 ? 'falta' : 'faltan'} ${8 - cups} ${8 - cups === 1 ? 'vaso' : 'vasos'} para tu siguiente cupón.`;
        if (newAvailableCoupons.length && celebration) celebration.hidden = false;
        const couponList = document.getElementById('lista-cupones');
        couponList.replaceChildren();
        if (!data.cupones.length) {
            const empty = document.createElement('p');
            empty.className = 'verde-muted';
            empty.textContent = 'Tus cupones disponibles y usados aparecerán aquí. Completa ocho vasos reutilizables para obtener una bebida gratis.';
            couponList.append(empty);
            return;
        }
        const list = document.createElement('div');
        list.className = 'verde-coupon-list';
        data.cupones.forEach((coupon) => {
            const card = document.createElement('article');
            card.className = 'verde-coupon';
            card.classList.toggle('is-used', coupon.estado === 'canjeado');
            card.classList.toggle('is-expired', coupon.estado === 'vencido');
            const code = document.createElement('div');
            code.className = 'verde-coupon-code';
            const details = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = 'Bebida gratis';
            const description = document.createElement('p');
            const expiration = new Date(coupon.expira.replace(' ', 'T')).toLocaleDateString('es-MX');
            description.textContent = coupon.estado === 'disponible'
                ? `Cupón ${coupon.referencia} · Vence el ${expiration}. Úsalo en un pedido desde el menú.`
                : coupon.estado === 'canjeado'
                    ? `Cupón ${coupon.referencia} · Canjeado.`
                    : `Cupón ${coupon.referencia} · Venció el ${expiration}.`;
            const state = document.createElement('span');
            state.className = 'verde-coupon-state';
            state.textContent = coupon.estado === 'disponible'
                ? 'Disponible'
                : coupon.estado === 'canjeado' ? 'Canjeado' : 'Vencido';
            details.append(title, description, state);
            if (coupon.estado === 'disponible') {
                const useLink = document.createElement('a');
                useLink.className = 'verde-coupon-menu';
                useLink.href = './menu.php';
                useLink.textContent = 'Usar en mi pedido';
                details.append(useLink);
            }
            card.append(code, details);
            list.append(card);
            if (coupon.estado === 'disponible' && coupon.codigo && window.QRCode) {
                new QRCode(code, { text: coupon.codigo, width: 88, height: 88, correctLevel: QRCode.CorrectLevel.M });
            } else if (coupon.estado === 'disponible') {
                code.textContent = coupon.referencia;
            } else {
                code.innerHTML = coupon.estado === 'canjeado'
                    ? '<i class="bi bi-check-circle-fill" aria-hidden="true"></i>'
                    : '<i class="bi bi-clock-history" aria-hidden="true"></i>';
            }
        });
        couponList.append(list);
    }

    async function loadCommunity() {
        const data = await getJson(`${api}?accion=comunidad`);
        metricCards(document.getElementById('metricas-comunidad'), [
            format(data.vasos),
            `${format(data.co2)} g`,
            `${format(data.residuos)} g`,
            `${format(data.agua)} ml`
        ]);
    }

    async function generateQr() {
        if (generatingQr) return;
        generatingQr = true;
        generateButton.disabled = true;
        if (showQrButton) showQrButton.disabled = true;
        if (qrTimer) window.clearInterval(qrTimer);
        qrTimer = 0;
        qrcodeRoot.replaceChildren();
        try {
            const response = await fetch(api, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ accion: 'emitir_qr' })
            });
            const data = await response.json().catch(() => null);
            if (!data) throw new Error(response.status === 403 ? 'No tienes permiso para generar el código.' : 'El servidor devolvió una respuesta no válida.');
            if (!response.ok || !data.success) throw new Error(data.message || 'No se pudo generar el código.');
            if (!window.QRCode) throw new Error('No se pudo cargar el generador local de códigos QR.');
            new QRCode(qrcodeRoot, { text: data.qr, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
            let remaining = data.vigenciaSegundos;
            showStatus(qrStatus, `Código listo. Caduca en ${remaining} segundos.`);
            qrTimer = window.setInterval(() => {
                remaining -= 1;
                if (remaining <= 0) {
                    window.clearInterval(qrTimer);
                    qrTimer = 0;
                    qrcodeRoot.replaceChildren();
                    showStatus(qrStatus, 'El código venció. Genera uno nuevo.', true);
                } else {
                    showStatus(qrStatus, `Código listo. Caduca en ${remaining} segundos.`);
                }
            }, 1000);
        } catch (error) {
            showStatus(qrStatus, error.message, true);
        } finally {
            generatingQr = false;
            generateButton.disabled = false;
            if (showQrButton) showQrButton.disabled = false;
        }
    }

    generateButton.addEventListener('click', generateQr);
    showQrButton?.addEventListener('click', async () => {
        const qrPanel = document.getElementById('pase-reutilizable');
        qrPanel?.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            block: 'center'
        });
        if (!qrcodeRoot.children.length || !qrTimer) await generateQr();
        qrStatus.focus({ preventScroll: true });
    });
    closeCelebrationButton?.addEventListener('click', () => {
        celebration.hidden = true;
    });

    loadPersonal().catch((error) => {
        const couponList = document.getElementById('lista-cupones');
        if (couponList) couponList.textContent = error.message;
        if (qrStatus) showStatus(qrStatus, error.message, true);
    });
    window.setInterval(() => {
        if (!document.hidden && navigator.onLine) {
            loadPersonal().catch((error) => console.warn('No se pudo actualizar la billetera verde.', error));
        }
    }, 30000);
    const communityCards = document.getElementById('metricas-comunidad');
    loadCommunity().catch(() => {
        metricCards(communityCards, ['No disponible', 'No disponible', 'No disponible', 'No disponible']);
        communityCards?.setAttribute('aria-label', 'No se pudieron cargar las métricas comunitarias');
    });
    window.setInterval(() => {
        loadCommunity().catch(() => {
            metricCards(communityCards, ['No disponible', 'No disponible', 'No disponible', 'No disponible']);
            communityCards?.setAttribute('aria-label', 'No se pudieron actualizar las métricas comunitarias');
        });
    }, 30000);
})();

(() => {
    const api = 'PHP/FidelidadVerde.php';
    const qrcodeRoot = document.getElementById('qr-code');
    const qrStatus = document.getElementById('qr-status');
    const generateButton = document.getElementById('generar-qr');
    let qrTimer = 0;
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
        metricCards(document.getElementById('metricas-personales'), [
            format(data.vasos),
            `${format(data.co2)} g`,
            `${format(data.residuos)} g`,
            `${format(data.agua)} ml`
        ]);
        document.getElementById('avance-insignias').textContent =
            `${format(data.puntos)} puntos verdes · ${format(data.insignias)} ${data.insignias === 1 ? 'insignia completa' : 'insignias completas'}`;
        document.getElementById('progreso-insignias').textContent =
            `${data.progreso} de 8 vasos para tu siguiente cupón`;
        document.querySelector('.verde-progress').setAttribute('aria-valuenow', String(data.progreso));
        document.getElementById('barra-progreso').style.width = `${(data.progreso / 8) * 100}%`;
        const couponList = document.getElementById('lista-cupones');
        couponList.replaceChildren();
        if (!data.cupones.length) {
            const empty = document.createElement('p');
            empty.className = 'verde-muted';
            empty.textContent = 'Completa ocho vasos reutilizables para obtener una bebida gratis.';
            couponList.append(empty);
            return;
        }
        const list = document.createElement('div');
        list.className = 'verde-coupon-list';
        data.cupones.forEach((coupon) => {
            const card = document.createElement('article');
            card.className = 'verde-coupon';
            const code = document.createElement('div');
            code.className = 'verde-coupon-code';
            const details = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = 'Bebida gratis';
            const description = document.createElement('p');
            description.textContent = `Cupón ${coupon.codigo.slice(-8)} · Muéstralo en caja.`;
            details.append(title, description);
            card.append(code, details);
            list.append(card);
            if (window.QRCode) new QRCode(code, { text: coupon.codigo, width: 88, height: 88, correctLevel: QRCode.CorrectLevel.M });
            else code.textContent = coupon.codigo;
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

    generateButton.addEventListener('click', async () => {
        generateButton.disabled = true;
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
            generateButton.disabled = false;
        }
    });

    loadPersonal().catch((error) => {
        document.getElementById('lista-cupones').textContent = error.message;
        showStatus(qrStatus, 'No se pudo cargar tu billetera. Recarga la página.', true);
    });
    const communityCards = document.getElementById('metricas-comunidad');
    loadCommunity().catch(() => {
        metricCards(communityCards, ['No disponible', 'No disponible', 'No disponible', 'No disponible']);
        communityCards.setAttribute('aria-label', 'No se pudieron cargar las métricas comunitarias');
    });
    window.setInterval(() => {
        loadCommunity().catch(() => {
            metricCards(communityCards, ['No disponible', 'No disponible', 'No disponible', 'No disponible']);
            communityCards.setAttribute('aria-label', 'No se pudieron actualizar las métricas comunitarias');
        });
    }, 30000);
})();

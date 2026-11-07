(() => {
    const video = document.getElementById('lector-video');
    const cameraStage = document.getElementById('marco-camara');
    const startButton = document.getElementById('iniciar-camara');
    const nextScanButton = document.getElementById('siguiente-escaneo');
    const stopButton = document.getElementById('detener-camara');
    const cameraStatus = document.getElementById('estado-camara');
    const result = document.getElementById('resultado-escaneo');
    const resultIcon = document.getElementById('icono-resultado');
    const resultTitle = document.getElementById('titulo-resultado');
    const resultDetail = document.getElementById('detalle-resultado');
    const input = document.getElementById('codigo-qr');
    const form = document.getElementById('formulario-qr');
    const processButton = document.getElementById('procesar-codigo');
    const modeDescription = document.getElementById('descripcion-modo');
    const modeButtons = [...document.querySelectorAll('[data-mode]')];
    let stream = null;
    let detector = null;
    let scanFrame = 0;
    let processing = false;
    let selectedMode = 'vaso';

    function showResult(title, detail, state = 'success') {
        resultTitle.textContent = title;
        resultDetail.textContent = detail;
        resultIcon.className = 'verde-result-icon bi ' +
            (state === 'success' ? 'bi-check-lg' : state === 'warning' ? 'bi-exclamation-lg' : 'bi-x-lg');
        result.classList.toggle('is-error', state === 'error');
        result.classList.toggle('is-warning', state === 'warning');
        result.hidden = false;
    }

    function setMode(mode) {
        if (processing || !['vaso', 'cupon'].includes(mode)) return;
        selectedMode = mode;
        modeButtons.forEach((button) => {
            const active = button.dataset.mode === mode;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        const isCup = mode === 'vaso';
        modeDescription.textContent = isCup
            ? 'Confirma que el cliente trajo su taza reutilizable y escanea su pase verde.'
            : 'Escanea el cupón del cliente y confirma la bebida gratis antes de entregarla.';
        processButton.innerHTML = isCup
            ? '<i class="bi bi-check2-circle" aria-hidden="true"></i> Registrar vaso'
            : '<i class="bi bi-ticket-perforated" aria-hidden="true"></i> Validar cupón';
        input.placeholder = isCup ? 'Pase verde: CAFELIZ-VERDE:…' : 'Cupón: CAFELIZ-CUPON:…';
        result.hidden = true;
    }

    function stopCamera() {
        if (scanFrame) cancelAnimationFrame(scanFrame);
        scanFrame = 0;
        if (stream) stream.getTracks().forEach((track) => track.stop());
        stream = null;
        video.srcObject = null;
        video.hidden = true;
        cameraStage.classList.remove('is-active');
        startButton.disabled = false;
        stopButton.disabled = true;
    }

    function pauseAfterRead() {
        stopCamera();
        startButton.hidden = true;
        nextScanButton.hidden = false;
        cameraStatus.textContent = 'Lectura terminada. Cámara pausada para evitar registros duplicados.';
        nextScanButton.focus({ preventScroll: true });
    }

    async function submitCode(code, fromCamera = false) {
        if (processing) return;
        const normalized = code.trim();
        let accion;
        if (/^CAFELIZ-VERDE:[a-f0-9]{48}$/i.test(normalized)) accion = 'registrar_termo';
        else if (/^CAFELIZ-CUPON:[a-f0-9]{32}$/i.test(normalized)) accion = 'canjear_cupon';
        else {
            showResult('Código no reconocido', 'Escanea un pase verde o un cupón Caféliz.', 'error');
            if (fromCamera || stream) pauseAfterRead();
            return;
        }

        const expectedMode = accion === 'registrar_termo' ? 'vaso' : 'cupon';
        if (expectedMode !== selectedMode) {
            showResult(
                accion === 'registrar_termo' ? 'Este código registra un vaso' : 'Este código es un cupón',
                'No se realizó ninguna acción. Cambia el tipo de operación y vuelve a escanear.',
                'warning'
            );
            if (fromCamera || stream) pauseAfterRead();
            return;
        }

        processing = true;
        modeButtons.forEach((button) => { button.disabled = true; });
        input.disabled = true;
        processButton.disabled = true;
        try {
            const response = await fetch('PHP/FidelidadVerde.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    accion,
                    ...(accion === 'registrar_termo' ? { qr: normalized } : { codigo: normalized })
                })
            });
            const data = await response.json().catch(() => null);
            if (!data) throw new Error(response.status === 403
                ? 'Tu cuenta no tiene permiso para registrar esta acción.'
                : 'El servidor devolvió una respuesta no válida.');
            if (!response.ok || !data.success) {
                showResult(
                    response.status === 409 ? 'Código no disponible' : 'No se pudo procesar',
                    data.message || 'Verifica el código e inténtalo de nuevo.',
                    response.status === 409 ? 'warning' : 'error'
                );
            } else if (accion === 'registrar_termo') {
                const vasosTotales = Number(data.vasos) || 0;
                const vasosDelCiclo = vasosTotales % 8;
                const progreso = vasosDelCiclo === 0 ? 8 : vasosDelCiclo;
                const faltantes = 8 - progreso;
                const cantidadVasos = `${vasosTotales} ${vasosTotales === 1 ? 'vaso' : 'vasos'}`;
                const detalle = data.cupon
                    ? `La cuenta lleva ${cantidadVasos}. ¡Se desbloqueó un cupón de bebida gratis!`
                    : `La cuenta lleva ${cantidadVasos}. Faltan ${faltantes} ${faltantes === 1 ? 'vaso' : 'vasos'} para el próximo cupón.`;
                showResult(
                    data.cupon ? '¡Vaso registrado y recompensa desbloqueada!' : '¡Vaso reutilizable registrado!',
                    detalle
                );
            } else {
                showResult('¡Cupón validado!', data.message || 'Entrega una bebida gratis al cliente.');
            }
            input.value = '';
        } catch (error) {
            showResult('Error de conexión', error.message || 'No se pudo procesar el código. Inténtalo de nuevo.', 'error');
        } finally {
            processing = false;
            modeButtons.forEach((button) => { button.disabled = false; });
            input.disabled = false;
            processButton.disabled = false;
            if (fromCamera || stream) pauseAfterRead();
        }
    }

    async function scan() {
        if (!stream || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA || processing) {
            if (stream) scanFrame = requestAnimationFrame(scan);
            return;
        }
        try {
            const codes = await detector.detect(video);
            if (codes.length && codes[0].rawValue) {
                await submitCode(codes[0].rawValue, true);
                return;
            }
        } catch {
            stopCamera();
            cameraStatus.textContent = 'No se pudo leer el QR. Prueba acercarlo al recuadro o usa el lector manual.';
            return;
        }
        if (stream) scanFrame = requestAnimationFrame(scan);
    }

    async function startCamera() {
        if (!window.isSecureContext) {
            cameraStatus.textContent = 'La cámara requiere HTTPS. También puedes procesar el código manualmente.';
            return;
        }
        if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
            cameraStatus.textContent = 'Este navegador no permite escanear con cámara; utiliza un lector QR o el campo manual.';
            return;
        }
        startButton.disabled = true;
        startButton.innerHTML = '<i class="bi bi-camera-video-fill" aria-hidden="true"></i> Iniciando cámara…';
        try {
            detector = new BarcodeDetector({ formats: ['qr_code'] });
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            video.srcObject = stream;
            video.hidden = false;
            cameraStage.classList.add('is-active');
            await video.play();
            startButton.hidden = false;
            startButton.disabled = true;
            startButton.textContent = 'Cámara activa';
            stopButton.disabled = false;
            nextScanButton.hidden = true;
            cameraStatus.textContent = 'Centra el QR dentro del recuadro. La cámara se pausará al leerlo.';
            scanFrame = requestAnimationFrame(scan);
        } catch (error) {
            stopCamera();
            startButton.textContent = 'Activar cámara';
            cameraStatus.textContent = error.name === 'NotAllowedError'
                ? 'Permite el acceso a la cámara en el navegador o utiliza el campo manual.'
                : 'No se pudo activar la cámara. Utiliza el lector o el campo manual.';
        }
    }

    modeButtons.forEach((button) => {
        button.addEventListener('click', () => setMode(button.dataset.mode));
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitCode(input.value, Boolean(stream));
    });
    startButton.addEventListener('click', startCamera);
    nextScanButton.addEventListener('click', () => {
        nextScanButton.hidden = true;
        startButton.hidden = false;
        startButton.textContent = 'Activar cámara';
        startCamera();
    });
    stopButton.addEventListener('click', () => {
        stopCamera();
        startButton.textContent = 'Activar cámara';
        cameraStatus.textContent = 'Cámara detenida. Puedes volver a activarla cuando quieras.';
        startButton.focus();
    });
    window.addEventListener('pagehide', stopCamera);
})();

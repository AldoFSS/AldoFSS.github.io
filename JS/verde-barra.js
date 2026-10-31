(() => {
    const video = document.getElementById('lector-video');
    const startButton = document.getElementById('iniciar-camara');
    const stopButton = document.getElementById('detener-camara');
    const cameraStatus = document.getElementById('estado-camara');
    const result = document.getElementById('resultado-escaneo');
    const input = document.getElementById('codigo-qr');
    const form = document.getElementById('formulario-qr');
    let stream = null;
    let detector = null;
    let scanFrame = 0;
    let processing = false;

    function showResult(message, error = false) {
        result.textContent = message;
        result.classList.toggle('is-error', error);
    }

    function stopCamera() {
        if (scanFrame) cancelAnimationFrame(scanFrame);
        scanFrame = 0;
        if (stream) stream.getTracks().forEach((track) => track.stop());
        stream = null;
        video.srcObject = null;
        video.hidden = true;
        startButton.disabled = false;
        stopButton.disabled = true;
    }

    async function submitCode(code) {
        if (processing) return;
        const normalized = code.trim();
        let accion;
        if (/^CAFELIZ-VERDE:[a-f0-9]{48}$/i.test(normalized)) accion = 'registrar_termo';
        else if (/^CAFELIZ-CUPON:[a-f0-9]{32}$/i.test(normalized)) accion = 'canjear_cupon';
        else {
            showResult('Código no reconocido. Escanea un pase verde o un cupón Caféliz.', true);
            return;
        }
        processing = true;
        try {
            const response = await fetch('PHP/FidelidadVerde.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ accion, ...(accion === 'registrar_termo' ? { qr: normalized } : { codigo: normalized }) })
            });
            const data = await response.json().catch(() => null);
            if (!data) throw new Error(response.status === 403
                ? 'Tu cuenta no tiene permiso para registrar esta acción.'
                : 'El servidor devolvió una respuesta no válida.');
            if (!response.ok || !data.success) throw new Error(data.message || 'No se pudo procesar el código.');
            if (accion === 'registrar_termo') {
                const extra = data.cupon ? ' ¡Se generó un cupón de bebida gratis!' : '';
                showResult(`Vaso registrado. La cuenta acumula ${data.vasos} vasos reutilizables.${extra}`);
            } else {
                showResult(data.message);
            }
            input.value = '';
        } catch (error) {
            showResult(error.message, true);
        } finally {
            processing = false;
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
                await submitCode(codes[0].rawValue);
                if (stream) scanFrame = requestAnimationFrame(scan);
                return;
            }
        } catch {
            cameraStatus.textContent = 'No se pudo leer el código. Cámara detenida; utiliza el lector o el campo manual.';
            stopCamera();
            return;
        }
        if (stream) scanFrame = requestAnimationFrame(scan);
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitCode(input.value);
    });

    startButton.addEventListener('click', async () => {
        if (!window.isSecureContext) {
            cameraStatus.textContent = 'Para usar la cámara, abre CaféLiz mediante HTTPS. También puedes introducir el código manualmente.';
            return;
        }
        if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
            cameraStatus.textContent = 'Este navegador no permite escanear con cámara; utiliza el lector o el campo manual.';
            return;
        }
        startButton.disabled = true;
        try {
            detector = new BarcodeDetector({ formats: ['qr_code'] });
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            video.srcObject = stream;
            video.hidden = false;
            await video.play();
            stopButton.disabled = false;
            cameraStatus.textContent = 'Cámara activa. Centra el código QR en la imagen.';
            scanFrame = requestAnimationFrame(scan);
        } catch (error) {
            stopCamera();
            cameraStatus.textContent = error.name === 'NotAllowedError'
                ? 'Permite el acceso a la cámara o utiliza el campo manual.'
                : 'No se pudo activar la cámara. Utiliza el campo manual.';
        }
    });

    stopButton.addEventListener('click', () => {
        stopCamera();
        cameraStatus.textContent = 'Cámara detenida.';
    });
    window.addEventListener('pagehide', stopCamera);
})();

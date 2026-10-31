(() => {
    const path = window.location.pathname.split('/').pop()?.toLowerCase() || '';
    const moduleByPage = {
        'reservas.php': 'reservas',
        'verde.php': 'verde',
        'verde-barra.php': 'verde',
        'listinventario.php': 'inventario'
    };
    const module = moduleByPage[path];
    if (module) {
        document.querySelectorAll('.offline-module').forEach(section => {
            section.hidden = section.id !== `offline-${module}`;
        });
        const messages = {
            reservas: 'No se pudo consultar el servidor. Puedes guardar tus preferencias como borrador local; la disponibilidad no está confirmada.',
            verde: 'La billetera verde necesita conexión. Ningún QR, vaso o cupón se valida en modo offline.',
            inventario: 'El inventario administrativo no está disponible sin conexión; solo puedes conservar una nota local.'
        };
        document.getElementById('offline-message').textContent = messages[module];
    }

    const reservaForm = document.getElementById('reserva-borrador');
    const reservaStatus = document.getElementById('estado-reserva-borrador');
    const continuarReserva = document.getElementById('continuar-reserva');
    const reservaKey = 'cafeliz-reserva-borrador';

    if (reservaForm && reservaStatus && continuarReserva) {
        const fechaInput = reservaForm.elements.namedItem('fecha');
        const horaInput = reservaForm.elements.namedItem('hora');
        const paqueteInput = reservaForm.elements.namedItem('paquete');
        const requisitoInput = reservaForm.elements.namedItem('requisito');
        const today = new Date();
        const tomorrow = new Date(today.getTime() + 24 * 60 * 60 * 1000);
        const isoDate = date => {
            const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
            return localDate.toISOString().slice(0, 10);
        };
        fechaInput.min = isoDate(today);
        fechaInput.value = isoDate(tomorrow);

        const updateResumeLink = () => {
            const params = new URLSearchParams({
                fecha: fechaInput.value,
                hora: horaInput.value,
                paquete: paqueteInput.value,
                requisito: requisitoInput.value
            });
            continuarReserva.href = `reservas.php?${params}`;
            continuarReserva.hidden = !navigator.onLine;
        };

        try {
            const saved = JSON.parse(localStorage.getItem(reservaKey) || 'null');
            if (saved && typeof saved === 'object' &&
                /^\d{4}-\d{2}-\d{2}$/.test(saved.fecha) &&
                /^\d{2}:\d{2}$/.test(saved.hora) &&
                ['medio_dia', 'dia_completo'].includes(saved.paquete) &&
                ['', 'silla_ruedas', 'zurdo'].includes(saved.requisito)) {
                fechaInput.value = saved.fecha;
                horaInput.value = saved.hora;
                paqueteInput.value = saved.paquete;
                requisitoInput.value = saved.requisito;
                reservaStatus.textContent = `Borrador guardado el ${new Date(saved.guardedAt).toLocaleString()}. Aún no es una reserva confirmada.`;
                continuarReserva.hidden = false;
            }
        } catch (error) {
            reservaStatus.textContent = 'No se pudo leer el borrador local. El almacenamiento del navegador puede estar desactivado.';
        }

        reservaForm.addEventListener('input', updateResumeLink);
        reservaForm.addEventListener('change', updateResumeLink);
        reservaForm.addEventListener('submit', event => {
            event.preventDefault();
            if (!reservaForm.reportValidity()) return;
            const draft = {
                fecha: fechaInput.value,
                hora: horaInput.value,
                paquete: paqueteInput.value,
                requisito: requisitoInput.value,
                guardedAt: new Date().toISOString()
            };
            try {
                localStorage.setItem(reservaKey, JSON.stringify(draft));
                reservaStatus.textContent = 'Borrador guardado solo en este dispositivo. La mesa y la disponibilidad aún no están confirmadas.';
                updateResumeLink();
            } catch (error) {
                reservaStatus.textContent = 'No se pudo guardar el borrador. Revisa el almacenamiento disponible en el navegador.';
            }
        });

        document.getElementById('borrar-reserva-borrador').addEventListener('click', () => {
            try {
                localStorage.removeItem(reservaKey);
                reservaStatus.textContent = 'Borrador eliminado de este dispositivo.';
                continuarReserva.hidden = true;
            } catch (error) {
                reservaStatus.textContent = 'No se pudo borrar el borrador local.';
            }
        });

        window.addEventListener('online', updateResumeLink);
        updateResumeLink();
    }

    const inventoryNote = document.getElementById('nota-inventario');
    const inventoryStatus = document.getElementById('estado-nota-inventario');
    const inventoryKey = 'cafeliz-inventario-nota';
    if (inventoryNote && inventoryStatus) {
        try {
            inventoryNote.value = localStorage.getItem(inventoryKey) || '';
        } catch (error) {
            inventoryStatus.textContent = 'No se pudo leer la nota local. El almacenamiento del navegador puede estar desactivado.';
        }

        document.getElementById('guardar-nota-inventario').addEventListener('click', () => {
            try {
                localStorage.setItem(inventoryKey, inventoryNote.value.trim());
                inventoryStatus.textContent = 'Nota guardada en este dispositivo. El inventario no se ha modificado.';
            } catch (error) {
                inventoryStatus.textContent = 'No se pudo guardar la nota local.';
            }
        });

        document.getElementById('borrar-nota-inventario').addEventListener('click', () => {
            try {
                localStorage.removeItem(inventoryKey);
                inventoryNote.value = '';
                inventoryStatus.textContent = 'Nota eliminada de este dispositivo.';
            } catch (error) {
                inventoryStatus.textContent = 'No se pudo borrar la nota local.';
            }
        });
    }
})();

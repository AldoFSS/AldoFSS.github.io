(() => {
    const searchForm = document.getElementById('formulario-disponibilidad');
    const packageOptions = [...document.querySelectorAll('.pases-grid input[type="radio"]')];
    const startTime = document.querySelector('.busqueda-reservas input[name="hora"]');
    const timeLimit = document.getElementById('limite-horario');
    const dateInput = document.querySelector('.busqueda-reservas input[name="fecha"]');
    const weekdayHint = document.getElementById('fecha-dia-semana');
    const accessInput = document.querySelector('.busqueda-reservas select[name="requisito"]');
    const schedule = document.getElementById('horarios-disponibles');
    const scheduleHint = document.getElementById('horarios-ayuda');
    const summary = document.getElementById('resumen-reserva');
    const summarySpace = document.getElementById('resumen-mesa');
    const summaryPackage = document.getElementById('resumen-paquete');
    const summaryDate = document.getElementById('resumen-fecha');
    const summaryPrice = document.getElementById('resumen-precio');
    const summarySpaceInput = document.getElementById('resumen-espacio');
    const continueButton = document.getElementById('continuar-reserva');
    const spaceButtons = [...document.querySelectorAll('.seleccionar-mesa')];

    if (!searchForm || !packageOptions.length || !startTime || !timeLimit || !dateInput || !accessInput) return;

    function updatePackageDetails() {
        const selectedPackage = packageOptions.find((option) => option.checked);
        if (!selectedPackage) return;

        const hours = Number(selectedPackage.dataset.hours);
        if (!Number.isInteger(hours) || hours <= 0) return;

        const latestStart = 20 - hours;
        const maxTime = `${String(latestStart).padStart(2, '0')}:00`;
        const adjustedTime = startTime.value > maxTime;
        startTime.max = maxTime;
        if (adjustedTime) startTime.value = maxTime;

        timeLimit.textContent = adjustedTime
            ? `Ajustamos la hora de inicio a ${maxTime} para que el pase termine a más tardar a las 20:00.`
            : `Último inicio para este pase: ${maxTime}.`;

        packageOptions.forEach((option) => {
            option.closest('.pase-card')?.classList.toggle('pase-activo', option.checked);
        });
        updateSummary();
    }

    function currentPackage() {
        return packageOptions.find((option) => option.checked);
    }

    function formatDate(dateValue) {
        const parts = dateValue.split('-').map(Number);
        if (parts.length !== 3 || parts.some((part) => !part)) return dateValue;
        return new Intl.DateTimeFormat('es-MX', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }).format(new Date(parts[0], parts[1] - 1, parts[2], 12));
    }

    function updateWeekday() {
        if (!weekdayHint || !dateInput.value) return;
        const [year, month, day] = dateInput.value.split('-').map(Number);
        const weekday = new Intl.DateTimeFormat('es-MX', { weekday: 'long' })
            .format(new Date(year, month - 1, day, 12));
        weekdayHint.textContent = `Tu reserva será el ${weekday}.`;
    }

    function updateSummary() {
        if (!summary) return;

        const selectedPackage = currentPackage();
        const hours = Number(selectedPackage?.dataset.hours || 0);
        const start = startTime.value;
        const endHour = Number(start.slice(0, 2)) + hours;
        const end = Number.isInteger(endHour) ? `${String(endHour).padStart(2, '0')}:00` : '';
        const formattedDate = formatDate(dateInput.value);
        const price = selectedPackage?.dataset.price;

        if (summaryPackage && selectedPackage) summaryPackage.textContent = selectedPackage.closest('.pase-card').querySelector('.pase-nombre').textContent;
        if (summaryDate) summaryDate.textContent = `${formattedDate} · ${start}${end ? `–${end}` : ''}`;
        if (summaryPrice && price) summaryPrice.textContent = `$${Number(price).toFixed(2)} MXN`;
        summary.querySelector('[name="fecha"]').value = dateInput.value;
        summary.querySelector('[name="hora"]').value = start;
        summary.querySelector('[name="paquete"]').value = selectedPackage?.value || '';
        summary.querySelector('[name="requisito"]').value = accessInput.value;
    }

    function clearSpaceSelection() {
        spaceButtons.forEach((button) => {
            button.setAttribute('aria-pressed', 'false');
            const status = button.querySelector('.plano-mesa-estado');
            if (status) status.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i>Libre';
            button.closest('.plano-estacion')?.classList.remove('is-selected');
        });
        if (summarySpace) summarySpace.textContent = 'Selecciona una mesa para continuar';
        if (summarySpaceInput) summarySpaceInput.value = '';
        if (continueButton) continueButton.disabled = true;
    }

    function refreshAvailabilityState() {
        if (!schedule) return;
        const isCurrent =
            dateInput.value === schedule.dataset.resultsDate &&
            currentPackage()?.value === schedule.dataset.resultsPackage &&
            accessInput.value === schedule.dataset.resultsAccess &&
            startTime.value === schedule.dataset.resultsHour;

        schedule.classList.toggle('is-stale', !isCurrent);
        schedule.querySelectorAll('.horario-opcion').forEach((button) => {
            button.disabled = !isCurrent || button.dataset.available !== 'true';
            const selected = isCurrent && button.dataset.hora === startTime.value;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-pressed', String(selected));
        });
        if (scheduleHint) {
            scheduleHint.textContent = isCurrent
                ? 'Selecciona una hora para consultar las mesas libres en ese horario.'
                : 'Cambiaste los filtros. Busca de nuevo para actualizar los horarios y espacios disponibles.';
        }

        spaceButtons.forEach((button) => { button.disabled = !isCurrent; });
        if (!isCurrent) clearSpaceSelection();
        if (summary) {
            summary.classList.toggle('is-stale', !isCurrent);
            if (continueButton) continueButton.disabled = !isCurrent || !summarySpaceInput?.value;
        }
    }

    function selectSpace(button) {
        spaceButtons.forEach((otherButton) => {
            const selected = otherButton === button;
            otherButton.setAttribute('aria-pressed', String(selected));
            const status = otherButton.querySelector('.plano-mesa-estado');
            if (status) {
                status.innerHTML = selected
                    ? '<i class="bi bi-check-lg" aria-hidden="true"></i>Seleccionada'
                    : '<i class="bi bi-check-circle-fill" aria-hidden="true"></i>Libre';
            }
            otherButton.closest('.plano-estacion')?.classList.toggle('is-selected', selected);
        });
        if (summarySpace) summarySpace.textContent = button.dataset.espacioNombre;
        if (summarySpaceInput) summarySpaceInput.value = button.dataset.espacioId;
        if (continueButton) continueButton.disabled = false;
        summary?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    packageOptions.forEach((option) => option.addEventListener('change', updatePackageDetails));
    packageOptions.forEach((option) => option.addEventListener('change', refreshAvailabilityState));
    dateInput.addEventListener('change', () => {
        updateWeekday();
        updateSummary();
        refreshAvailabilityState();
    });
    startTime.addEventListener('change', () => {
        updateSummary();
        refreshAvailabilityState();
    });
    accessInput.addEventListener('change', () => {
        updateSummary();
        refreshAvailabilityState();
    });

    schedule?.querySelectorAll('.horario-opcion').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.disabled) return;
            startTime.value = button.dataset.hora;
            updateSummary();
            searchForm.requestSubmit(searchForm.querySelector('button[type="submit"]'));
        });
    });

    spaceButtons.forEach((button) => button.addEventListener('click', () => selectSpace(button)));

    updatePackageDetails();
    updateWeekday();
    refreshAvailabilityState();

    if (new URLSearchParams(window.location.search).has('fecha')) {
        document.getElementById('espacios-disponibles')?.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            block: 'start'
        });
    }
})();

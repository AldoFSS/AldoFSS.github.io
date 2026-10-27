document.addEventListener('DOMContentLoaded', () => {
    const opener = document.getElementById('open_cart');
    const drawer = document.getElementById('sidecart');
    const closer = document.getElementById('close_btn');
    const backdrop = document.querySelector('.backdrop');
    if (!opener || !drawer || !closer || !backdrop) return;
    let lastFocus;
    const setOpen = (open, restoreFocus = true) => {
        if (open) {
            lastFocus = document.activeElement;
            document.getElementById('cart-toast')?.classList.remove('is-visible');
        }
        drawer.classList.toggle('open', open);
        backdrop.classList.toggle('show', open);
        document.body.classList.toggle('cart-is-open', open);
        opener.setAttribute('aria-expanded', String(open));
        drawer.inert = !open;
        drawer.setAttribute('aria-hidden', String(!open));
        if (open) closer.focus({preventScroll: true});
        else if (restoreFocus && lastFocus?.isConnected) lastFocus.focus({preventScroll: true});
    };
    opener.addEventListener('click', () => setOpen(true));
    closer.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));
    document.addEventListener('cafeliz:open-cart', () => setOpen(true));
    document.addEventListener('cafeliz:close-cart', () => setOpen(false, false));
    drawer.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
        }
        if (event.key !== 'Tab') return;
        const controls = Array.from(drawer.querySelectorAll('button:not(:disabled), a[href], input:not(:disabled), [tabindex="0"]'))
            .filter(element => element.getClientRects().length);
        const first = controls[0], last = controls[controls.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    });
    document.addEventListener('click', event => {
        if (event.target.closest('[data-open-cart]')) setOpen(true);
        if (event.target.closest('[data-close-cart]')) setOpen(false);
    });
});

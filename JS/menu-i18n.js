document.addEventListener('DOMContentLoaded', () => {
    const languages = {
        es: 'Español', en: 'English', fr: 'Français', de: 'Deutsch', it: 'Italiano',
        pt: 'Português', ja: '日本語', zh: '简体中文', ko: '한국어', nl: 'Nederlands',
        ru: 'Русский', ar: 'العربية', hi: 'हिन्दी', tr: 'Türkçe', pl: 'Polski',
        sv: 'Svenska', da: 'Dansk', no: 'Norsk bokmål', fi: 'Suomi', el: 'Ελληνικά',
        he: 'עברית', th: 'ไทย', vi: 'Tiếng Việt', id: 'Bahasa Indonesia', ms: 'Bahasa Melayu',
        uk: 'Українська', cs: 'Čeština', hu: 'Magyar', ro: 'Română', bg: 'Български',
        hr: 'Hrvatski', sk: 'Slovenčina', sr: 'Српски', ca: 'Català', is: 'Íslenska',
        sw: 'Kiswahili', fa: 'فارسی', bn: 'বাংলা', tl: 'Filipino', et: 'Eesti', lv: 'Latviešu',
        lt: 'Lietuvių'
    };
    const picker = document.getElementById('menu-language');
    const status = document.getElementById('menu-translation-status');
    if (!picker) return;

    picker.replaceChildren(...Object.entries(languages).map(([code, name]) => {
        const option = document.createElement('option');
        option.value = code;
        option.textContent = name;
        return option;
    }));

    let savedLanguage = '';
    try {
        savedLanguage = localStorage.getItem('cafeliz-language') || '';
    } catch (error) {
        status.textContent = 'No se pudo guardar tu preferencia de idioma.';
    }
    const browserLanguage = (navigator.languages?.[0] || navigator.language || 'es')
        .split('-')[0].toLowerCase();
    let currentLanguage = languages[savedLanguage] ? savedLanguage
        : (languages[browserLanguage] ? browserLanguage : 'es');
    let requestSequence = 0;

    const spanishTranslation = () => {
        const translation = {
            ui: {
                languageLabel: 'Idioma', documentTitle: 'Menú · Caféliz',
                navToggle: 'Menú', navToggleAria: 'Abrir navegación',
                homeNav: 'Inicio', menuNav: 'Menú',
                reservationsNav: 'Reservar espacio', loginNav: 'Sesión',
                contactLabel: 'Datos:',
                contactText: 'Para reservar, contáctanos por redes sociales o directamente por teléfono.',
                eyebrow: 'PREPARADO PARA TI', title: 'El menú de Caféliz',
                lead: 'Tu café favorito y algo rico para acompañarlo.',
                searchLabel: 'Buscar en el menú', searchPlaceholder: '¿Qué se te antoja hoy?',
                searchButton: 'Buscar', categoriesLabel: 'Categorías del menú',
                allCategories: 'Todo el menú', resultsMany: '{count} opciones para disfrutar',
                resultsOne: '{count} opción para disfrutar', madeToOrder: 'Hecho al momento',
                add: 'Agregar', added: 'Agregado', loginToOrder: 'Inicia sesión para pedir',
                noResultsTitle: 'No encontramos ese antojo',
                noResultsDescription: 'Prueba con otro nombre o explora todo el menú.',
                seeFullMenu: 'Ver todo el menú',
                menuDefaultDescription: 'Preparado al momento, para disfrutar a tu gusto.',
                baristaNote: 'El nombre original del producto en español se enviará a barra.',
                cartKicker: 'UN MOMENTO PARA DISFRUTAR', cartTitle: 'Tu carrito',
                estimatedTotal: 'Total estimado',
                closeCart: 'Cerrar carrito', cartNote: 'Un paso más y tu pedido estará listo para registrar.',
                continueOrder: 'Continuar con mi pedido', addedToCart: 'Agregado a tu carrito',
                viewCart: 'Ver carrito', cartEmptyTitle: 'Tu próximo antojo empieza aquí',
                cartEmptyDescription: 'Elige algo del menú y lo guardaremos en tu carrito.',
                exploreMenu: 'Explorar el menú', removeItem: 'Eliminar {name}',
                unitPrice: '${price} por unidad', quantityLabel: 'Cantidad de {name}',
                removeOne: 'Quitar una unidad de {name}', addOne: 'Agregar una unidad de {name}',
                oneUnit: '{count} unidad', manyUnits: '{count} unidades',
                cartOne: '{count} producto en tu pedido', cartMany: '{count} productos en tu pedido',
                cartAria: 'Ver carrito: {count} productos, ${total}',
                badgeCart: '{count} en tu carrito', orderButton: 'Registrar pedido · ${total}',
                emptyCartButton: 'Vaciar carrito', checkoutTitle: 'Confirmar pedido',
                nameLabel: 'Nombre:', namePlaceholder: 'Nombre', emailLabel: 'Correo:',
                emailPlaceholder: 'Correo',
                paymentNote: 'Se registrará tu pedido. El pago se realiza en caja; esta aplicación todavía no procesa tarjetas.',
                registrationButton: 'Registrar pedido',
                orderRegisteredTitle: 'Pedido registrado',
                orderRegistered: 'Pedido #{orderId} por ${total}. El cobro se realiza en caja.',
                orderError: 'Pedido sin confirmar', tryAgain: 'Reintenta sin cambiar el carrito.',
                requestFailed: 'No se pudo completar la solicitud.',
                offlineTitle: 'Sin conexión',
                offlineMessage: 'Tu carrito está guardado. Conéctate para confirmar el pedido.',
                checkDetails: 'Revisa tus datos', validNameEmail: 'Escribe tu nombre y un correo válido.',
                emptyCartTitle: 'Carrito vacío', addBeforeOrder: 'Agrega productos antes de registrar un pedido.',
                storageError: 'No se pudo guardar', storageHelp: 'Habilita el almacenamiento del navegador.',
                quantityLimitTitle: 'Cantidad máxima alcanzada',
                quantityLimitMessage: 'Puedes agregar hasta 999 unidades.',
                cartFullTitle: 'Tu carrito está lleno',
                cartFullMessage: 'Puedes elegir hasta 100 productos diferentes.',
                productRemoved: 'Producto eliminado', cartCleared: 'Carrito vacío',
                cartClearedMessage: 'Puedes elegir algo más del menú.',
                translationUnavailable: 'No se pudo traducir el menú. Se muestra el contenido original en español.'
            },
            categories: {},
            products: {}
        };
        document.querySelectorAll('[data-category-id]:not([data-category-id="all"])').forEach(element => {
            translation.categories[element.dataset.categoryId] = element.dataset.spanishName;
        });
        document.querySelectorAll('[data-product-id]').forEach(element => {
            translation.products[element.dataset.productId] = {
                name: element.dataset.spanishName,
                description: element.dataset.spanishDescription || translation.ui.menuDefaultDescription
            };
        });
        return translation;
    };

    const filterProducts = () => {
        const query = document.getElementById('menu-search')?.value.trim().toLocaleLowerCase() || '';
        const cards = [...document.querySelectorAll('.menu-product')];
        const visible = cards.filter(card => {
            const name = card.querySelector('.menu-product-info h2')?.textContent || '';
            const description = card.querySelector('.menu-product-info > p')?.textContent || '';
            const matches = `${name} ${description}`.toLocaleLowerCase().includes(query);
            card.hidden = !matches;
            return matches;
        }).length;
        const results = document.getElementById('menu-results-count');
        const ui = window.cafelizI18n?.ui || {};
        if (results) {
            const template = visible === 1 ? ui.resultsOne : ui.resultsMany;
            results.textContent = (template || '{count} opciones para disfrutar')
                .replace('{count}', String(visible));
        }
        const empty = document.querySelector('.menu-no-results');
        if (empty) empty.hidden = visible > 0;
    };

    const applyTranslation = translation => {
        const ui = translation.ui || {};
        document.documentElement.lang = currentLanguage;
        if (typeof ui.documentTitle === 'string') document.title = ui.documentTitle;
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const value = ui[element.dataset.i18n];
            if (typeof value === 'string') element.textContent = value;
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
            const value = ui[element.dataset.i18nPlaceholder];
            if (typeof value === 'string') element.setAttribute('placeholder', value);
        });
        document.querySelectorAll('[data-i18n-aria]').forEach(element => {
            const value = ui[element.dataset.i18nAria];
            if (typeof value === 'string') element.setAttribute('aria-label', value);
        });
        picker.setAttribute('aria-label', ui.languageLabel || 'Idioma');
        document.querySelectorAll('[data-category-id]').forEach(element => {
            if (element.dataset.categoryId === 'all') {
                element.textContent = ui.allCategories || element.dataset.spanishName;
            } else {
                const value = translation.categories?.[element.dataset.categoryId];
                element.textContent = typeof value === 'string' ? value : element.dataset.spanishName;
            }
        });
        document.querySelectorAll('[data-product-id]').forEach(card => {
            const product = translation.products?.[card.dataset.productId];
            const name = product?.name || card.dataset.spanishName;
            const description = product?.description || card.dataset.spanishDescription ||
                ui.menuDefaultDescription || '';
            const title = card.querySelector('.menu-product-info h2');
            const text = card.querySelector('.menu-product-info > p');
            const image = card.querySelector('.menu-product-image img');
            const button = card.querySelector('.agregar-carrito');
            if (title) title.textContent = name;
            if (text) text.textContent = description;
            if (image) image.alt = name;
            if (button) {
                button.dataset.displayName = name;
                button.setAttribute('aria-label', `${ui.add || 'Agregar'} ${name}`);
                if (!button.classList.contains('is-added')) {
                    button.querySelector('span').textContent = ui.add || 'Agregar';
                }
            }
        });

        const baristaNote = document.getElementById('barista-translation-note');
        if (baristaNote) baristaNote.textContent = ui.baristaNote || '';
        window.cafelizI18n = {
            language: currentLanguage,
            ui,
            text(key, values = {}) {
                let value = ui[key];
                if (typeof value !== 'string') return '';
                Object.entries(values).forEach(([name, replacement]) => {
                    value = value.replaceAll(`{${name}}`, String(replacement));
                });
                return value;
            },
            productName(id, fallback) {
                return translation.products?.[String(id)]?.name || fallback;
            }
        };
        document.dispatchEvent(new CustomEvent('cafeliz:languagechange'));
        filterProducts();
    };

    const setLanguage = async language => {
        const requestNumber = ++requestSequence;
        currentLanguage = language;
        picker.value = language;
        status.textContent = language === 'es' ? '' : 'Traduciendo el menú…';
        try {
            localStorage.setItem('cafeliz-language', language);
        } catch (error) {
            status.textContent = 'No se pudo guardar tu preferencia de idioma.';
        }
        if (language === 'es') {
            applyTranslation(spanishTranslation());
            return;
        }

        try {
            const response = await fetch('PHP/TraducirMenu.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({language})
            });
            const result = await response.json();
            if (requestNumber !== requestSequence) return;
            if (!response.ok || !result.success || !result.translation) {
                throw new Error(result.message || 'Translation request failed.');
            }
            applyTranslation(result.translation);
            status.textContent = '';
        } catch (error) {
            if (requestNumber !== requestSequence) return;
            currentLanguage = 'es';
            picker.value = 'es';
            status.textContent = 'No se pudo traducir el menú. Se muestra el contenido original en español.';
            applyTranslation(spanishTranslation());
        }
    };

    document.getElementById('menu-search-form')?.addEventListener('submit', event => {
        event.preventDefault();
        filterProducts();
    });
    document.getElementById('menu-search')?.addEventListener('input', filterProducts);
    picker.addEventListener('change', () => setLanguage(picker.value));
    setLanguage(currentLanguage);
});

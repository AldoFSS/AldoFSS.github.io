-- Traducciones fijas del menú para inglés y francés; el español es el catálogo original.
CREATE TABLE IF NOT EXISTS menu_traducciones_estaticas (
    idioma CHAR(2) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    contenido LONGTEXT NOT NULL,
    actualizado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idioma)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO menu_traducciones_estaticas (idioma, contenido) VALUES
('en', '{"ui":{"languageLabel":"Language","eyebrow":"MADE FOR YOU","documentTitle":"Menu · Caféliz","navToggle":"Menu","navToggleAria":"Open navigation","homeNav":"Home","menuNav":"Menu","reservationsNav":"Reserve a space","loginNav":"Sign in","contactLabel":"Contact:","contactText":"To make a reservation, contact us on social media or by phone.","title":"The Caféliz menu","lead":"Your favorite coffee and something delicious to go with it.","searchLabel":"Search the menu","searchPlaceholder":"What are you craving today?","searchButton":"Search","categoriesLabel":"Menu categories","allCategories":"Full menu","resultsMany":"{count} options to enjoy","resultsOne":"{count} option to enjoy","madeToOrder":"Made to order","add":"Add","added":"Added","loginToOrder":"Sign in to order","noResultsTitle":"We couldn''t find that item","noResultsDescription":"Try another name or browse the full menu.","seeFullMenu":"View the full menu","menuDefaultDescription":"Made fresh to enjoy just the way you like it.","baristaNote":"The original Spanish product name will be sent to the barista.","cartKicker":"A MOMENT TO ENJOY","cartTitle":"Your cart","estimatedTotal":"Estimated total","closeCart":"Close cart","cartNote":"One more step and your order will be ready to submit.","continueOrder":"Continue to checkout","addedToCart":"Added to your cart","viewCart":"View cart","cartEmptyTitle":"Your next craving starts here","cartEmptyDescription":"Choose something from the menu and we''ll save it in your cart.","exploreMenu":"Explore the menu","removeItem":"Remove {name}","unitPrice":"${price} each","quantityLabel":"Quantity of {name}","removeOne":"Remove one {name}","addOne":"Add one {name}","oneUnit":"{count} item","manyUnits":"{count} items","cartOne":"{count} item in your order","cartMany":"{count} items in your order","cartAria":"View cart: {count} items, ${total}","badgeCart":"{count} in your cart","orderButton":"Place order · ${total}","emptyCartButton":"Empty cart","checkoutTitle":"Confirm order","nameLabel":"Name:","namePlaceholder":"Name","emailLabel":"Email:","emailPlaceholder":"Email","paymentNote":"Your order will be submitted. Payment is made at the counter; this app does not process card payments yet.","registrationButton":"Place order","requestFailed":"The request could not be completed.","orderRegisteredTitle":"Order placed","orderRegistered":"Order #{orderId} for ${total}. Payment is due at the counter.","orderError":"Order not confirmed","tryAgain":"Try again without changing your cart.","offlineTitle":"You are offline","offlineMessage":"Your cart is saved. Connect to the internet to place your order.","checkDetails":"Check your details","validNameEmail":"Enter your name and a valid email address.","emptyCartTitle":"Your cart is empty","addBeforeOrder":"Add items before placing an order.","storageError":"Could not save","storageHelp":"Enable browser storage.","quantityLimitTitle":"Maximum quantity reached","quantityLimitMessage":"You can add up to 999 units.","cartFullTitle":"Your cart is full","cartFullMessage":"You can choose up to 100 different products.","productRemoved":"Item removed","cartCleared":"Cart emptied","cartClearedMessage":"You can choose something else.","translationUnavailable":"The saved translation could not be loaded. The menu is shown in Spanish."},"categories":{"1":"Drinks","2":"Breakfast","4":"Food","9":"Snacks"},"products":{"24":{"name":"Frappe","description":"Made fresh to enjoy just the way you like it."},"25":{"name":"Ham sandwich","description":"Ham"},"26":{"name":"Impossible cake (chocoflan)","description":"Chocolate, vanilla"},"28":{"name":"Crepe","description":"Nutella or savory"},"31":{"name":"Chocolate milkshake","description":"Strawberries, sprinkles"},"32":{"name":"Croissant","description":"Made fresh to enjoy just the way you like it."},"36":{"name":"Ice cream cake","description":"Made fresh to enjoy just the way you like it."}}}'),
('fr', '{"ui":{"languageLabel":"Langue","eyebrow":"PRÉPARÉ POUR VOUS","documentTitle":"Menu · Caféliz","navToggle":"Menu","navToggleAria":"Ouvrir la navigation","homeNav":"Accueil","menuNav":"Menu","reservationsNav":"Réserver un espace","loginNav":"Se connecter","contactLabel":"Contact :","contactText":"Pour réserver, contactez-nous sur les réseaux sociaux ou par téléphone.","title":"Le menu de Caféliz","lead":"Votre café préféré et une délicieuse gourmandise pour l’accompagner.","searchLabel":"Rechercher dans le menu","searchPlaceholder":"Qu’est-ce qui vous ferait envie aujourd’hui ?","searchButton":"Rechercher","categoriesLabel":"Catégories du menu","allCategories":"Tout le menu","resultsMany":"{count} choix à savourer","resultsOne":"{count} choix à savourer","madeToOrder":"Préparé à la commande","add":"Ajouter","added":"Ajouté","loginToOrder":"Connectez-vous pour commander","noResultsTitle":"Nous n’avons pas trouvé cet article","noResultsDescription":"Essayez un autre nom ou parcourez tout le menu.","seeFullMenu":"Voir tout le menu","menuDefaultDescription":"Préparé à la minute, selon vos envies.","baristaNote":"Le nom original du produit en espagnol sera transmis au barista.","cartKicker":"UN INSTANT À SAVOURER","cartTitle":"Votre panier","estimatedTotal":"Total estimé","closeCart":"Fermer le panier","cartNote":"Encore une étape et votre commande sera prête à être enregistrée.","continueOrder":"Continuer ma commande","addedToCart":"Ajouté à votre panier","viewCart":"Voir le panier","cartEmptyTitle":"Votre prochaine envie commence ici","cartEmptyDescription":"Choisissez un article du menu : nous l’ajouterons à votre panier.","exploreMenu":"Découvrir le menu","removeItem":"Retirer {name}","unitPrice":"${price} l’unité","quantityLabel":"Quantité de {name}","removeOne":"Retirer un {name}","addOne":"Ajouter un {name}","oneUnit":"{count} article","manyUnits":"{count} articles","cartOne":"{count} article dans votre commande","cartMany":"{count} articles dans votre commande","cartAria":"Voir le panier : {count} articles, ${total}","badgeCart":"{count} dans votre panier","orderButton":"Enregistrer la commande · ${total}","emptyCartButton":"Vider le panier","checkoutTitle":"Confirmer la commande","nameLabel":"Nom :","namePlaceholder":"Nom","emailLabel":"E-mail :","emailPlaceholder":"E-mail","paymentNote":"Votre commande sera enregistrée. Le paiement s’effectue à la caisse ; cette application ne traite pas encore les paiements par carte.","registrationButton":"Enregistrer la commande","requestFailed":"La demande n’a pas pu aboutir.","orderRegisteredTitle":"Commande enregistrée","orderRegistered":"Commande nº {orderId} de ${total}. Le paiement s’effectue à la caisse.","orderError":"Commande non confirmée","tryAgain":"Réessayez sans modifier votre panier.","offlineTitle":"Hors connexion","offlineMessage":"Votre panier est enregistré. Connectez-vous pour confirmer la commande.","checkDetails":"Vérifiez vos informations","validNameEmail":"Saisissez votre nom et une adresse e-mail valide.","emptyCartTitle":"Votre panier est vide","addBeforeOrder":"Ajoutez des articles avant d’enregistrer la commande.","storageError":"Enregistrement impossible","storageHelp":"Activez le stockage du navigateur.","quantityLimitTitle":"Quantité maximale atteinte","quantityLimitMessage":"Vous pouvez ajouter jusqu’à 999 unités.","cartFullTitle":"Votre panier est plein","cartFullMessage":"Vous pouvez choisir jusqu’à 100 produits différents.","productRemoved":"Article retiré","cartCleared":"Panier vidé","cartClearedMessage":"Vous pouvez choisir autre chose.","translationUnavailable":"La traduction enregistrée n’a pas pu être chargée. Le menu est affiché en espagnol."},"categories":{"1":"Boissons","2":"Petit-déjeuner","4":"Plats","9":"Collations"},"products":{"24":{"name":"Frappé","description":"Préparé à la minute, selon vos envies."},"25":{"name":"Sandwich au jambon","description":"Jambon"},"26":{"name":"Gâteau impossible (chocoflan)","description":"Chocolat, vanille"},"28":{"name":"Crêpe","description":"Nutella ou version salée"},"31":{"name":"Milk-shake au chocolat","description":"Fraises, vermicelles en sucre"},"32":{"name":"Croissant","description":"Préparé à la minute, selon vos envies."},"36":{"name":"Gâteau glacé","description":"Préparé à la minute, selon vos envies."}}}')
ON DUPLICATE KEY UPDATE contenido=VALUES(contenido), actualizado=CURRENT_TIMESTAMP;

INSERT INTO menu_traducciones_estaticas (idioma, contenido) VALUES
('es', JSON_OBJECT(
    'ui', JSON_OBJECT(
        'languageLabel','Idioma','eyebrow','PREPARADO PARA TI','documentTitle','Menú · Caféliz',
        'navToggle','Menú','navToggleAria','Abrir navegación','homeNav','Inicio','menuNav','Menú',
        'reservationsNav','Reservar espacio','loginNav','Sesión','contactLabel','Datos:',
        'contactText','Para reservar, contáctanos por redes sociales o directamente por teléfono.',
        'title','El menú de Caféliz','lead','Tu café favorito y algo rico para acompañarlo.',
        'searchLabel','Buscar en el menú','searchPlaceholder','¿Qué se te antoja hoy?',
        'searchButton','Buscar','categoriesLabel','Categorías del menú','allCategories','Todo el menú',
        'resultsMany','{count} opciones para disfrutar','resultsOne','{count} opción para disfrutar',
        'madeToOrder','Hecho al momento','add','Agregar','added','Agregado',
        'loginToOrder','Inicia sesión para pedir','noResultsTitle','No encontramos ese antojo',
        'noResultsDescription','Prueba con otro nombre o explora todo el menú.',
        'seeFullMenu','Ver todo el menú',
        'menuDefaultDescription','Preparado al momento, para disfrutar a tu gusto.',
        'baristaNote','El nombre original del producto en español se enviará a barra.',
        'cartKicker','UN MOMENTO PARA DISFRUTAR','cartTitle','Tu carrito',
        'estimatedTotal','Total estimado','closeCart','Cerrar carrito',
        'cartNote','Un paso más y tu pedido estará listo para registrar.',
        'continueOrder','Continuar con mi pedido','addedToCart','Agregado a tu carrito',
        'viewCart','Ver carrito','cartEmptyTitle','Tu próximo antojo empieza aquí',
        'cartEmptyDescription','Elige algo del menú y lo guardaremos en tu carrito.',
        'exploreMenu','Explorar el menú','removeItem','Eliminar {name}',
        'unitPrice','${price} por unidad','quantityLabel','Cantidad de {name}',
        'removeOne','Quitar una unidad de {name}','addOne','Agregar una unidad de {name}',
        'oneUnit','{count} unidad','manyUnits','{count} unidades',
        'cartOne','{count} producto en tu pedido','cartMany','{count} productos en tu pedido',
        'cartAria','Ver carrito: {count} productos, ${total}','badgeCart','{count} en tu carrito',
        'orderButton','Registrar pedido · ${total}','emptyCartButton','Vaciar carrito',
        'checkoutTitle','Confirmar pedido','nameLabel','Nombre:','namePlaceholder','Nombre',
        'emailLabel','Correo:','emailPlaceholder','Correo',
        'paymentNote','Se registrará tu pedido. El pago se realiza en caja; esta aplicación todavía no procesa tarjetas.',
        'registrationButton','Registrar pedido','requestFailed','No se pudo completar la solicitud.',
        'orderRegisteredTitle','Pedido registrado',
        'orderRegistered','Pedido #{orderId} por ${total}. El cobro se realiza en caja.',
        'orderError','Pedido sin confirmar','tryAgain','Reintenta sin cambiar el carrito.',
        'offlineTitle','Sin conexión',
        'offlineMessage','Tu carrito está guardado. Conéctate para confirmar el pedido.',
        'checkDetails','Revisa tus datos','validNameEmail','Escribe tu nombre y un correo válido.',
        'emptyCartTitle','Carrito vacío','addBeforeOrder','Agrega productos antes de registrar un pedido.',
        'storageError','No se pudo guardar','storageHelp','Habilita el almacenamiento del navegador.',
        'quantityLimitTitle','Cantidad máxima alcanzada',
        'quantityLimitMessage','Puedes agregar hasta 999 unidades.',
        'cartFullTitle','Tu carrito está lleno',
        'cartFullMessage','Puedes elegir hasta 100 productos diferentes.',
        'productRemoved','Producto eliminado','cartCleared','Carrito vacío',
        'cartClearedMessage','Puedes elegir algo más.',
        'translationUnavailable','No se pudo cargar la traducción guardada. Se muestra el menú en español.'
    ),
    'categories', JSON_OBJECT(),
    'products', JSON_OBJECT()
))
ON DUPLICATE KEY UPDATE contenido=VALUES(contenido), actualizado=CURRENT_TIMESTAMP;

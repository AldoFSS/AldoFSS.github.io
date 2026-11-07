const CACHE_NAME = "cafeliz-pwa-v45";
const CACHE_PREFIX = "cafeliz-pwa-";
const APP_SHELL = [
  "./offline.html",
  "./manifest.webmanifest",
  "./CSS/admin.css?v=6",
  "./CSS/estilos.css?v=2",
  "./CSS/footer.css?v=1",
  "./CSS/form.css",
  "./CSS/home.css",
  "./CSS/inventario.css?v=2",
  "./CSS/menu-cart.css?v=5",
  "./CSS/pwa.css",
  "./CSS/reservas.css?v=14",
  "./CSS/verde.css",
  "./JS/ValidarUser.js",
  "./JS/inventario.js",
  "./JS/main.js?v=3",
  "./JS/menu-i18n.js?v=3",
  "./JS/nabvar.js?v=3",
  "./JS/offline-experience.js",
  "./JS/pwa.js",
  "./JS/reservas.js?v=4",
  "./JS/verde-barra.js?v=2",
  "./JS/verde.js?v=3",
  "./IMG/CAFELIZ.png",
  "./IMG/CAFELIZ.webp",
  "./IMG/blog-img-01.webp",
  "./IMG/blog-img-02.webp",
  "./IMG/blog-img-03.webp",
  "./IMG/blog-img-04.webp",
  "./IMG/blog-img-05.webp",
  "./IMG/blog-img-06.webp",
  "./IMG/blog-img-07.webp",
  "./IMG/blog-img-08.webp",
  "./IMG/cafeliz-app-logo.png",
  "./IMG/cafeliz-pwa-192-v2.png",
  "./IMG/cafeliz-pwa-512-v2.png",
  "./IMG/cafeliz-pwa-maskable-512-v2.png",
  "./IMG/f1.webp",
  "./IMG/f2.webp",
  "./IMG/f5.webp",
  "./PRODUCTOS/Cafe.jpg",
  "./PRODUCTOS/Creapa.jpg",
  "./PRODUCTOS/Cuernito.jpg",
  "./PRODUCTOS/Frappe.jpg",
  "./PRODUCTOS/Guajolote.jpg",
  "./PRODUCTOS/Malteada%20de%20Chocolate.jpg",
  "./PRODUCTOS/Paste.jpg",
  "./PRODUCTOS/Pastel%20Helado.jpg",
  "./PRODUCTOS/Pastel%20heladp.jpg",
  "./PRODUCTOS/Pastel%20Imposible.jpg",
  "./PRODUCTOS/Sandwich.jpg",
  "./PRODUCTOS/Soda.jpg",
  "./PRODUCTOS/Test.jpg",
  "./PRODUCTOS/torta.jpg",
  "./Awesome/css/fontawesome.css",
  "./Awesome/css/solid.css",
  "./Awesome/webfonts/fa-solid-900.woff2",
  "./Awesome/webfonts/fa-solid-900.ttf",
  "./Awesome/webfonts/fa-regular-400.woff2",
  "./Awesome/webfonts/fa-regular-400.ttf",
  "./Awesome/webfonts/fa-brands-400.woff2",
  "./Awesome/webfonts/fa-brands-400.ttf",
  "./Awesome/webfonts/fa-v4compatibility.woff2",
  "./Awesome/webfonts/fa-v4compatibility.ttf",
  "./node_modules/bootstrap/dist/css/bootstrap.min.css",
  "./node_modules/bootstrap/dist/js/bootstrap.bundle.min.js",
  "./node_modules/bootstrap-icons/font/bootstrap-icons.min.css",
  "./node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff2",
  "./node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff",
  "./node_modules/chart.js/dist/chart.umd.js",
  "./node_modules/datatables.net-bs5/css/dataTables.bootstrap5.css",
  "./node_modules/jquery/dist/jquery.min.js",
  "./node_modules/lightbox2/dist/css/lightbox.min.css",
  "./node_modules/lightbox2/dist/js/lightbox.min.js",
  "./node_modules/qrcodejs/qrcode.min.js",
  "./node_modules/sweetalert2/dist/sweetalert2.all.min.js",
  "./node_modules/toastr/build/toastr.min.css",
  "./node_modules/toastr/build/toastr.min.js"
];
const PUBLIC_OFFLINE_PAGES = [
  new URL("./index.php", self.registration.scope),
  new URL("./menu.php", self.registration.scope),
  new URL("./login.php", self.registration.scope),
  new URL("./registro.php", self.registration.scope),
  new URL("./reservas.php", self.registration.scope)
];
const OFFLINE_PAGE = new URL("./offline.html", self.registration.scope).href;
const STATIC_ASSET_PATTERN = /\.(?:css|js|png|jpe?g|webp|gif|svg|ico|woff2?|ttf|webmanifest)$/i;

function publicPageFor(url) {
  const scopePath = new URL(self.registration.scope).pathname;
  if (url.pathname === scopePath) {
    return PUBLIC_OFFLINE_PAGES[0].href;
  }
  return PUBLIC_OFFLINE_PAGES.find((page) => page.pathname === url.pathname)?.href || null;
}

function hasNoStore(response) {
  return (response.headers.get("Cache-Control") || "")
    .split(",")
    .some((directive) => /^\s*no-store(?:\s*=|$)/i.test(directive));
}

function cacheableResponse(response) {
  const vary = (response.headers.get("Vary") || "").toLowerCase();
  return response.ok &&
    response.type === "basic" &&
    !response.redirected &&
    !hasNoStore(response) &&
    !vary.split(",").some((header) => ["cookie", "authorization"].includes(header.trim()));
}

function cacheableStaticResponse(response, url) {
  if (!cacheableResponse(response)) {
    return false;
  }

  const contentType = (response.headers.get("Content-Type") || "").toLowerCase();
  if (contentType.includes("text/html")) {
    return false;
  }

  return !url.pathname.toLowerCase().endsWith(".webmanifest") ||
    /^application\/(?:manifest\+json|json)(?:\s*;|$)/.test(contentType);
}

async function precacheAppShell() {
  const cache = await caches.open(CACHE_NAME);
  await Promise.all(APP_SHELL.map(async (path) => {
    const request = new Request(new URL(path, self.registration.scope), { cache: "reload" });
    const response = await fetch(request);
    const url = new URL(request.url);
    const isOfflinePage = url.pathname.toLowerCase().endsWith("/offline.html");
    const validResponse = isOfflinePage
      ? cacheableResponse(response) &&
        (response.headers.get("Content-Type") || "").toLowerCase().includes("text/html")
      : cacheableStaticResponse(response, url);

    if (!validResponse) {
      throw new Error(
        `No se puede precargar ${url.pathname}: HTTP ${response.status}, ` +
        `Content-Type ${response.headers.get("Content-Type") || "ausente"}.`
      );
    }

    await cache.put(request, response);
  }));
}

function cacheablePublicPage(response, url) {
  return !url.search &&
    response.headers.get("X-PWA-Cacheable") === "public" &&
    cacheableResponse(response);
}

async function showCachedPage(response) {
  if (!(response.headers.get("Content-Type") || "").toLowerCase().includes("text/html")) {
    return response;
  }

  const html = await response.text();
  const markedHtml = html.replace(/<body\b([^>]*)>/i, (_tag, attributes) => {
    const markedAttributes = /\bdata-pwa-cached(?:\s|=|$)/i.test(attributes)
      ? attributes
      : `${attributes} data-pwa-cached="true"`;
    return `<body${markedAttributes}><aside id="pwa-cache-notice" class="pwa-offline-banner" role="status" style="position:sticky;top:0;z-index:1000;padding:.85rem 1rem;background:#fff7df;color:#513b12;text-align:center;font-weight:600">Mostrando una copia guardada. Puede estar desactualizada; las operaciones que requieren conexión no se han realizado.</aside>`;
  });
  if (markedHtml === html) {
    return response;
  }

  const headers = new Headers(response.headers);
  headers.delete("Content-Length");
  headers.delete("Content-Encoding");
  headers.delete("ETag");
  headers.set("X-PWA-Cached-Page", "true");
  return new Response(markedHtml, {
    status: response.status,
    statusText: response.statusText,
    headers
  });
}

self.addEventListener("install", (event) => {
  event.waitUntil(precacheAppShell());
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => Promise.all(
        cacheNames
          .filter((cacheName) => cacheName.startsWith(CACHE_PREFIX) && cacheName !== CACHE_NAME)
          .map((cacheName) => caches.delete(cacheName))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("message", (event) => {
  if (event.data && event.data.type === "SKIP_WAITING") {
    self.skipWaiting();
  }
});

self.addEventListener("push", (event) => {
  let notification = {
    title: "CaféLiz",
    body: "Tienes una actualización.",
    url: "./"
  };
  if (event.data) {
    try {
      const payload = event.data.json();
      const message = {
        ...payload,
        ...(payload.notification && typeof payload.notification === "object"
          ? payload.notification
          : {}),
        ...(payload.data && typeof payload.data === "object" ? payload.data : {})
      };
      notification = {
        ...notification,
        title: typeof message.title === "string" ? message.title : notification.title,
        body: typeof message.body === "string" ? message.body : notification.body,
        url: typeof message.url === "string" ? message.url : notification.url
      };
    } catch (error) {
      console.error("La notificación push recibida no contiene JSON válido.", error);
    }
  }
  const target = new URL(notification.url, self.registration.scope);
  if (target.origin !== self.location.origin ||
      !target.pathname.startsWith(new URL(self.registration.scope).pathname)) {
    target.href = self.registration.scope;
  }
  event.waitUntil(self.registration.showNotification(notification.title, {
    body: notification.body,
    icon: "./IMG/cafeliz-pwa-192-v2.png",
    badge: "./IMG/cafeliz-pwa-192-v2.png",
    data: { url: target.href }
  }));
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  const target = event.notification.data?.url || self.registration.scope;
  event.waitUntil(
    self.clients.matchAll({ type: "window", includeUncontrolled: true })
      .then((clients) => {
        const client = clients.find((candidate) => candidate.url === target);
        if (client) {
          return client.focus();
        }
        return self.clients.openWindow(target);
      })
  );
});

self.addEventListener("fetch", (event) => {
  const request = event.request;
  const requestUrl = new URL(request.url);

  if (request.method !== "GET" || requestUrl.origin !== self.location.origin) {
    return;
  }

  if (request.mode === "navigate") {
    event.respondWith(
      (async () => {
        const page = publicPageFor(requestUrl);
        const cache = await caches.open(CACHE_NAME);

        try {
          const response = await fetch(request);
          if (page) {
            if (hasNoStore(response) ||
                (response.status < 500 && !requestUrl.search &&
                    !cacheablePublicPage(response, requestUrl))) {
              await cache.delete(page);
            } else if (cacheablePublicPage(response, requestUrl)) {
              await cache.put(page, response.clone());
            }
          }

          if (response.status >= 500 && page && !hasNoStore(response)) {
            const cachedPage = await cache.match(page);
            if (cachedPage) {
              return showCachedPage(cachedPage);
            }
          }
          return response;
        } catch {
          const cachedPage = page ? await cache.match(page) : null;
          if (cachedPage) {
            return showCachedPage(cachedPage);
          }
          return await cache.match(OFFLINE_PAGE) || Response.error();
        }
      })()
    );
    return;
  }

  if (!STATIC_ASSET_PATTERN.test(requestUrl.pathname)) {
    return;
  }

  if (requestUrl.searchParams.has("v") || /[.-][a-f0-9]{8,}\./i.test(requestUrl.pathname)) {
    event.respondWith(
      (async () => {
        const cache = await caches.open(CACHE_NAME);
        const cachedResponse = await cache.match(request);
        if (cachedResponse) {
          return cachedResponse;
        }

        const response = await fetch(request);
        if (cacheableStaticResponse(response, requestUrl)) {
          await cache.put(request, response.clone());
        } else {
          await cache.delete(request);
        }
        return response;
      })()
    );
    return;
  }

  const update = caches.open(CACHE_NAME)
    .then(async (cache) => {
      const response = await fetch(request);
      if (cacheableStaticResponse(response, requestUrl)) {
        await cache.put(request, response.clone());
      } else {
        await cache.delete(request);
      }
      return response;
    });
  event.waitUntil(update.then(() => undefined, () => undefined));
  event.respondWith(
    caches.open(CACHE_NAME)
      .then((cache) => cache.match(request))
      .then((cachedResponse) => cachedResponse || update)
  );
});

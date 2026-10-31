const CACHE_NAME = "cafeliz-pwa-v12";
const APP_SHELL = [
  "./offline.html",
  "./JS/offline-experience.js",
  "./manifest.webmanifest",
  "./CSS/pwa.css",
  "./CSS/estilos.css",
  "./CSS/home.css",
  "./CSS/menu-cart.css",
  "./CSS/admin.css",
  "./CSS/reservas.css",
  "./CSS/verde.css",
  "./CSS/inventario.css",
  "./node_modules/lightbox2/dist/css/lightbox.min.css",
  "./JS/pwa.js",
  "./IMG/cafeliz-app-logo.png",
  "./IMG/CAFELIZ.png",
  "./IMG/CAFELIZ.webp",
  "./IMG/cafeliz-pwa-192-v2.png",
  "./IMG/cafeliz-pwa-512-v2.png",
  "./IMG/cafeliz-pwa-maskable-512-v2.png",
  "./IMG/f1.webp",
  "./IMG/f2.webp",
  "./IMG/f5.webp",
  "./IMG/blog-img-01.webp",
  "./IMG/blog-img-02.webp",
  "./IMG/blog-img-03.webp",
  "./IMG/blog-img-04.webp",
  "./IMG/blog-img-05.webp",
  "./IMG/blog-img-06.webp",
  "./IMG/blog-img-07.webp",
  "./IMG/blog-img-08.webp",
  "./node_modules/bootstrap/dist/css/bootstrap.min.css",
  "./node_modules/bootstrap/dist/js/bootstrap.bundle.min.js",
  "./node_modules/bootstrap-icons/font/bootstrap-icons.min.css",
  "./node_modules/datatables.net-bs5/css/dataTables.bootstrap5.css",
  "./node_modules/toastr/build/toastr.min.css",
  "./Awesome/css/fontawesome.css",
  "./Awesome/css/solid.css"
];
const OFFLINE_PAGES = [
  new URL("./index.php", self.registration.scope).href,
  new URL("./menu.php", self.registration.scope).href
];
const OFFLINE_VIEW_PAGES = [
  new URL("./reservas.php", self.registration.scope).href,
  new URL("./verde.php", self.registration.scope).href,
  new URL("./listInventario.php", self.registration.scope).href
];

function offlinePageFor(url) {
  const scopePath = new URL(self.registration.scope).pathname;
  if (url.pathname === scopePath) {
    return OFFLINE_PAGES[0];
  }
  const page = OFFLINE_PAGES.find((candidate) => new URL(candidate).pathname === url.pathname);
  return page || null;
}

function offlineViewPageFor(url) {
  if (url.search) {
    return null;
  }
  return OFFLINE_VIEW_PAGES.find((candidate) => new URL(candidate).pathname === url.pathname) || null;
}

function offlineViewDataFor(url) {
  if (url.pathname !== new URL("./PHP/FidelidadVerde.php", self.registration.scope).pathname) {
    return false;
  }
  return url.search === "?accion=personal" || url.search === "?accion=comunidad";
}

function cacheableResponse(response) {
  return response.ok &&
    response.type === "basic" &&
    !response.headers.get("Cache-Control")?.toLowerCase().includes("no-store");
}

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(APP_SHELL))
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => Promise.all(
        cacheNames
          .filter((cacheName) => cacheName.startsWith("cafeliz-pwa-") && cacheName !== CACHE_NAME)
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
      notification = { ...notification, ...event.data.json() };
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
        const page = offlinePageFor(requestUrl);
        const offlineViewPage = offlineViewPageFor(requestUrl);
        const cache = await caches.open(CACHE_NAME);

        try {
          const response = await fetch(request);
          if (page && response.headers.get("Cache-Control")?.toLowerCase().includes("no-store")) {
            await cache.delete(page);
          } else if (page && !requestUrl.search && cacheableResponse(response)) {
            await cache.put(page, response.clone());
          } else if (offlineViewPage && response.ok && response.type === "basic" &&
              response.headers.get("X-PWA-Cache-View") === "1") {
            await cache.put(offlineViewPage, response.clone());
          }
          return response;
        } catch {
          const cachedPage = page
            ? await cache.match(page)
            : offlineViewPage ? await cache.match(offlineViewPage) : null;
          return cachedPage || await cache.match("./offline.html");
        }
      })()
    );
    return;
  }

  if (offlineViewDataFor(requestUrl)) {
    event.respondWith(
      (async () => {
        const cache = await caches.open(CACHE_NAME);
        try {
          const response = await fetch(request);
          if (response.ok && response.type === "basic" &&
              response.headers.get("X-PWA-Cache-View") === "1") {
            await cache.put(request, response.clone());
          }
          return response;
        } catch {
          return await cache.match(request) || new Response(
            JSON.stringify({ success: false, message: "Sin conexión y sin datos guardados." }),
            { status: 503, headers: { "Content-Type": "application/json; charset=utf-8" } }
          );
        }
      })()
    );
    return;
  }

  if (!/\.(?:css|js|png|jpe?g|webp|gif|svg|ico|woff2?|ttf)$/i.test(requestUrl.pathname)) {
    return;
  }

  event.respondWith(
    (async () => {
      const cache = await caches.open(CACHE_NAME);
      const cacheKey = new URL(requestUrl.pathname, self.location.origin).href;
      const cachedResponse = await cache.match(cacheKey);
      if (cachedResponse) {
        return cachedResponse;
      }
      const response = await fetch(request);
      if (cacheableResponse(response)) {
        await cache.put(cacheKey, response.clone());
      }
      return response;
    })()
  );
});

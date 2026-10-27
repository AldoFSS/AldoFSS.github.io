const CACHE_NAME = "cafeliz-pwa-v3";
const APP_SHELL = [
  "./offline.html",
  "./manifest.webmanifest",
  "./CSS/pwa.css",
  "./JS/pwa.js",
  "./IMG/pwa-icon.svg",
  "./IMG/CAFELIZ.png",
  "./IMG/f1.webp",
  "./IMG/f2.webp",
  "./IMG/f5.webp",
  "./node_modules/bootstrap/dist/css/bootstrap.min.css",
  "./node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"
];

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

self.addEventListener("fetch", (event) => {
  const request = event.request;
  const requestUrl = new URL(request.url);

  if (request.method !== "GET" || requestUrl.origin !== self.location.origin) {
    return;
  }

  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request).catch(() => caches.match("./offline.html"))
    );
    return;
  }

  if (!/\.(?:css|js|png|jpe?g|webp|gif|svg|ico|woff2?|ttf)$/i.test(requestUrl.pathname)) {
    return;
  }

  event.respondWith(
    caches.match(request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      return fetch(request).then((response) => {
        if (response.ok && response.type === "basic" && !requestUrl.search) {
          const responseCopy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, responseCopy));
        }
        return response;
      });
    })
  );
});

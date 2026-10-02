const VERSION = 'nomina-v2';
const CACHE = VERSION;
const PRECACHE = [
    '/manifest.webmanifest',
    '/offline.html',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-512.png',
    '/icons/apple-touch-icon.png',
    '/icons/icon.svg',
    '/favicon.ico',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE).then(function (cache) {
            return cache.addAll(PRECACHE);
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (llaves) {
            return Promise.all(llaves.map(function (llave) {
                return llave === CACHE ? null : caches.delete(llave);
            }));
        }).then(function () {
            return self.clients.claim();
        })
    );
});

/**
 * Solo se cachean archivos estaticos (iconos/manifiesto). Las paginas HTML nunca
 * se guardan: la app tiene sesion de 60 s y datos privados, una pagina en cache
 * podria mostrar informacion de otro usuario o desactualizada.
 */
self.addEventListener('fetch', function (event) {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(function () {
                return caches.match('/offline.html');
            })
        );
        return;
    }

    const esEstatico = url.pathname.indexOf('/icons/') === 0
        || /\.(png|jpe?g|gif|webp|svg|ico|webmanifest)$/i.test(url.pathname);

    if (!esEstatico) return;

    event.respondWith(
        caches.match(request).then(function (enCache) {
            if (enCache) return enCache;

            return fetch(request).then(function (respuesta) {
                if (respuesta && respuesta.ok) {
                    const copia = respuesta.clone();
                    caches.open(CACHE).then(function (cache) {
                        cache.put(request, copia);
                    });
                }

                return respuesta;
            });
        })
    );
});
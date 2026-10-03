{{-- Installable como app (PWA): manifiesto + service worker + boton "Instalar app".
     Las paginas HTML nunca se cachean (sesion corta y datos privados); el service
     worker solo guarda iconos/manifiesto y muestra /offline.html si no hay red. --}}
<meta name="theme-color" content="#7c3aed">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
<link rel="icon" href="/icons/icon-192.png" type="image/png" sizes="192x192">
<link rel="alternate icon" href="/favicon.ico" sizes="32x32">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Panda">
<script>
    (function () {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            var boton = document.getElementById('installApp');
            if (!boton) return;

            var instalado = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            var esIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
            var pendiente = null;

            if (instalado || (!esIos && !('onbeforeinstallprompt' in window))) return;

            boton.hidden = false;

            window.addEventListener('beforeinstallprompt', function (event) {
                event.preventDefault();
                pendiente = event;
            });

            window.addEventListener('appinstalled', function () {
                pendiente = null;
                boton.hidden = true;
            });

            boton.addEventListener('click', function () {
                if (pendiente) {
                    pendiente.prompt();
                    pendiente.userChoice.then(function () {
                        pendiente = null;
                        boton.hidden = true;
                    });
                    return;
                }

                alert('En iPhone o iPad: toca el botón "Compartir" y luego "Añadir a pantalla de inicio".\n\nEn Android: abre el menú del navegador y elige "Instalar app" o "Añadir a pantalla de inicio".');
            });
        });
    }());
</script>
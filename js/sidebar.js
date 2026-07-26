/**
 * js/sidebar.js
 * Control del sidebar en vista móvil, hecho con JavaScript puro
 */

document.addEventListener('DOMContentLoaded', () => {
    const boton    = document.getElementById('btn-toggle-sidebar');
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('overlay-sidebar');

    if (!boton || !sidebar || !overlay) return;

    const abrir = () => {
        sidebar.classList.add('abierto');
        overlay.classList.add('visible');
        document.body.style.overflow = 'hidden';
    };

    const cerrar = () => {
        sidebar.classList.remove('abierto');
        overlay.classList.remove('visible');
        document.body.style.overflow = '';
    };

    // Abre/cierra el sidebar al hacer clic en el botón hamburguesa
    boton.addEventListener('click', () => {
        sidebar.classList.contains('abierto') ? cerrar() : abrir();
    });

    // Cierra el sidebar al hacer clic en el overlay
    overlay.addEventListener('click', cerrar);

    // Cierra el sidebar automáticamente al elegir una sección (en móvil)
    sidebar.querySelectorAll('.nav-link').forEach(enlace => {
        enlace.addEventListener('click', cerrar);
    });

    // Si la pantalla vuelve a tamaño de escritorio, se limpia cualquier estado móvil
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) cerrar();
    });

    // Nota: el botón de "Cerrar sesión" ahora es un enlace normal
    // (<a href="logout.php">) que navega directamente, sin JavaScript.
    // A propósito NO se intercepta aquí con preventDefault()/fetch, para
    // que cerrar sesión funcione siempre, incluso si algo falla en el JS
    // o en la conexión a la base de datos.
});
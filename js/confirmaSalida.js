document.addEventListener("DOMContentLoaded", function () {
    const botonCerrar = document.getElementById("cerrarSesionBtn");

    if (botonCerrar) {
        botonCerrar.addEventListener("click", function (event) {
            const confirmar = confirm("¿Seguro que quieres cerrar la sesión?");
            if (!confirmar) {
                event.preventDefault(); // cancela el enlace
            }
        });
    }
});
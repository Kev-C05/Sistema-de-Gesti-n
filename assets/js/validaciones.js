// Validacion del lado del cliente y confirmaciones.

document.addEventListener('DOMContentLoaded', function () {
    // Formularios con validacion.
    var formularios = document.querySelectorAll('form.needs-validation');

    formularios.forEach(function (form) {
        var clave = form.querySelector('#password');
        var confirmar = form.querySelector('#password_confirmar');

        function comprobarClaves() {
            if (clave && confirmar) {
                if (confirmar.value !== clave.value) {
                    confirmar.setCustomValidity('Las contraseñas no coinciden.');
                } else {
                    confirmar.setCustomValidity('');
                }
            }
        }

        if (clave && confirmar) {
            clave.addEventListener('input', comprobarClaves);
            confirmar.addEventListener('input', comprobarClaves);
        }

        form.addEventListener('submit', function (evento) {
            comprobarClaves();
            if (!form.checkValidity()) {
                evento.preventDefault();
                evento.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Formularios que piden confirmacion antes de enviarse al basurero.
    var confirmables = document.querySelectorAll('form[data-confirmar]');

    confirmables.forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            if (!window.confirm(form.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });
});

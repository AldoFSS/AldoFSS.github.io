document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const alertType = urlParams.get('alert');

    if (alertType) {
        let title, text, icon;
        //Dependiendo de los casos se mostrara el alerta
        switch (alertType) {
            case 'success':
                title = 'Inicio de sesión correcto';
                text = 'Has iniciado sesión exitosamente.';
                icon = 'success';
                break;
            case 'error':
                title = 'Datos incorrectos';
                text = 'El nombre de usuario o la contraseña son incorrectos.';
                icon = 'error';
                break;
            case 'admin':
                title = 'Inicio de sesión como Admin';
                text = 'Has iniciado sesión como administrador.';
                icon = 'success';
                break;
            case 'register':
                title = 'Registro correcto';
                text = 'datos registrados correctamente';
                icon = 'success';
                break;
            case 'errorUser':
                title = 'Usuario existente';
                text = 'El nombre de usuario ya existe';
                icon = 'error';
                break;
        }

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonText: 'Aceptar'
        });
    }
});
function validar()
{
    let nombre = document.getElementById('name').value;
    let apellidos = document.getElementById('apell').value;
    let correo = document.getElementById('gmail').value;
    let tel = document.getElementById('tel').value;
    let comentarios = document.getElementById('coment').value;
    
     // Crear el mensaje de alerta
    const message = `Nombre: ${nombre}\nApellidos: ${apellidos} \nCorreo: ${correo} \nTelefono: ${tel} \nComentarios: ${comentarios}`;

    // Mostrar la alerta con los datos
    alert(message)



}
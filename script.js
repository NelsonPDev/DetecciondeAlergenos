// script.js - Validación del formulario de registro de alergias alimentarias

const formulario = document.getElementById('formularioRegistro');

// Manejar cambios en alergenos
document.querySelectorAll('input[name="alergenos"]').forEach(checkbox => {
    checkbox.addEventListener('change', actualizarResumenAlergenos);
});

function actualizarResumenAlergenos() {
    const alergenos = document.querySelectorAll('input[name="alergenos"]:checked');
    const gravedadDiv = document.getElementById('gravedadAlergenos');
    const resumenDiv = document.getElementById('resumenAlergenos');

    if (alergenos.length > 0) {
        const nombres = Array.from(alergenos)
            .map(el => el.parentElement.textContent.trim())
            .join(', ');
        
        resumenDiv.textContent = `✓ ${alergenos.length} alergeno(s) seleccionado(s): ${nombres}`;
        gravedadDiv.style.display = 'block';
        document.getElementById('gravedadAlergena').required = true;
    } else {
        resumenDiv.textContent = 'Ninguna seleccionada';
        gravedadDiv.style.display = 'none';
        document.getElementById('gravedadAlergena').required = false;
        limpiarError('gravedad');
    }
}

// Validar formulario
formulario.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    document.getElementById('mensajeError').style.display = 'none';
    document.getElementById('mensajeExito').style.display = 'none';
    
    if (validarFormulario()) {
        await enviarFormulario();
    }
});

function validarFormulario() {
    let esValido = true;

    // Validar nombre
    const nombre = document.getElementById('nombre').value.trim();
    if (nombre === '' || nombre.length < 3) {
        mostrarError('nombre', 'El nombre debe tener al menos 3 caracteres');
        esValido = false;
    } else {
        limpiarError('nombre');
    }

    // Validar email
    const email = document.getElementById('email').value.trim();
    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regexEmail.test(email)) {
        mostrarError('email', 'Ingresa un correo electrónico válido');
        esValido = false;
    } else {
        limpiarError('email');
    }

    // Validar teléfono
    const telefono = document.getElementById('telefono').value.trim();
    const regexTelefono = /^[0-9+\s\-()]{7,}$/;
    if (!regexTelefono.test(telefono)) {
        mostrarError('telefono', 'Ingresa un número de teléfono válido');
        esValido = false;
    } else {
        limpiarError('telefono');
    }

    // Validar edad
    const edad = document.getElementById('edad').value;
    if (edad === '' || edad < 1 || edad > 120) {
        mostrarError('edad', 'Ingresa una edad válida (1-120)');
        esValido = false;
    } else {
        limpiarError('edad');
    }

    // Validar género
    const genero = document.getElementById('genero').value;
    if (genero === '') {
        mostrarError('genero', 'Selecciona un género');
        esValido = false;
    } else {
        limpiarError('genero');
    }

    // Validar alergenos seleccionados
    const alergenos = document.querySelectorAll('input[name="alergenos"]:checked');
    if (alergenos.length === 0) {
        mostrarError('alergenos', 'Selecciona al menos un alergeno');
        esValido = false;
    } else {
        limpiarError('alergenos');

        // Si hay alergenos, validar gravedad
        const gravedad = document.getElementById('gravedadAlergena').value;
        if (gravedad === '') {
            mostrarError('gravedad', 'Selecciona la gravedad de tus alergias');
            esValido = false;
        } else {
            limpiarError('gravedad');
        }
    }

    // Validar términos
    const terminos = document.getElementById('terminos').checked;
    if (!terminos) {
        mostrarError('terminos', 'Debes aceptar los términos y condiciones');
        esValido = false;
    } else {
        limpiarError('terminos');
    }

    return esValido;
}

function mostrarError(elementId, mensaje) {
    const errorElement = document.getElementById('error' + elementId.charAt(0).toUpperCase() + elementId.slice(1));
    if (errorElement) {
        errorElement.textContent = mensaje;
    }
}

function limpiarError(elementId) {
    const errorElement = document.getElementById('error' + elementId.charAt(0).toUpperCase() + elementId.slice(1));
    if (errorElement) {
        errorElement.textContent = '';
    }
}

async function enviarFormulario() {
    const formData = new FormData(formulario);
    
    // Convertir alergenos seleccionados a array
    const alergenos = Array.from(document.querySelectorAll('input[name="alergenos"]:checked'))
        .map(el => el.value);
    
    // Enviar los alergenos como array
    formData.delete('alergenos');
    alergenos.forEach(alergeno => {
        formData.append('alergenos[]', alergeno);
    });

    try {
        const response = await fetch('api/registrar_usuario.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await response.json();

        if (resultado.exito) {
            document.getElementById('mensajeExito').style.display = 'block';
            formulario.reset();
            document.getElementById('gravedadAlergenos').style.display = 'none';
            actualizarResumenAlergenos();
            
            document.querySelectorAll('.error').forEach(error => {
                error.textContent = '';
            });

            setTimeout(() => {
                window.location.href = 'scanner.html';
            }, 2000);
        } else {
            document.getElementById('mensajeError').style.display = 'block';
            alert('Error: ' + resultado.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('mensajeError').style.display = 'block';
    }
}

// Validación en tiempo real
document.getElementById('nombre').addEventListener('blur', function() {
    const nombre = this.value.trim();
    if (nombre === '' || nombre.length < 3) {
        mostrarError('nombre', 'El nombre debe tener al menos 3 caracteres');
    } else {
        limpiarError('nombre');
    }
});

document.getElementById('email').addEventListener('blur', function() {
    const email = this.value.trim();
    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regexEmail.test(email)) {
        mostrarError('email', 'Ingresa un correo electrónico válido');
    } else {
        limpiarError('email');
    }
});

document.getElementById('telefono').addEventListener('blur', function() {
    const telefono = this.value.trim();
    const regexTelefono = /^[0-9+\s\-()]{7,}$/;
    if (!regexTelefono.test(telefono)) {
        mostrarError('telefono', 'Ingresa un número de teléfono válido');
    } else {
        limpiarError('telefono');
    }
});

document.getElementById('edad').addEventListener('blur', function() {
    const edad = this.value;
    if (edad === '' || edad < 1 || edad > 120) {
        mostrarError('edad', 'Ingresa una edad válida (1-120)');
    } else {
        limpiarError('edad');
    }
});

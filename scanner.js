// scanner.js - Lógica del escáner de alergenos con sesiones

let usuarioActual = null;
let usuarioId = null;
let isScannerActive = false;

// Verificar sesión y cargar datos al iniciar
document.addEventListener('DOMContentLoaded', async function() {
    await verificarSesion();

    const btnEscanear = document.getElementById('btn-escanear');
    if (btnEscanear) {
        btnEscanear.addEventListener('click', () => {
            if (isScannerActive) {
                detenerScanner();
            } else {
                iniciarScanner();
            }
        });
    }

    const inputCodigo = document.getElementById('codigoBarras');
    if (inputCodigo) {
        inputCodigo.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarProducto();
            }
        });
    }
});

// Iniciar el escáner de código de barras
function iniciarScanner() {
    const scannerContainer = document.getElementById('scanner-container');
    const btnEscanear = document.getElementById('btn-escanear');

    scannerContainer.style.display = 'block';
    btnEscanear.textContent = '📷 Detener Escáner';
    isScannerActive = true;

    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: scannerContainer,
            constraints: {
                width: 400,
                height: 300,
                facingMode: "environment"
            },
            frequency: 10, // Intentar procesar 10 fotogramas por segundo
        },
        decoder: {
            readers: ["ean_reader"]
        },
        locate: true, // Habilitar la localización del código de barras
    }, function(err) {
        if (err) {
            console.error('Error al iniciar Quagga:', err);
            mostrarModalError('No se pudo iniciar la cámara. Asegúrate de tener los permisos necesarios.');
            detenerScanner();
            return;
        }
        Quagga.start();
    });

    Quagga.onDetected(data => {
        const codigo = data.codeResult.code;
        document.getElementById('codigoBarras').value = codigo;
        detenerScanner();
        buscarProducto();
    });
}

// Detener el escáner
function detenerScanner() {
    const scannerContainer = document.getElementById('scanner-container');
    const btnEscanear = document.getElementById('btn-escanear');

    Quagga.stop();
    scannerContainer.style.display = 'none';
    btnEscanear.textContent = '📷 Iniciar Escáner';
    isScannerActive = false;
}

// Verificar sesión del usuario
async function verificarSesion() {
    try {
        const response = await fetch('api/verificar_sesion.php');
        const data = await response.json();

        if (!data.logueado) {
            window.location.href = 'login.html';
            return;
        }

        usuarioId = data.usuario.id;
        document.getElementById('nombreUsuarioHeader').textContent = data.usuario.nombre;
        document.getElementById('emailUsuarioHeader').textContent = data.usuario.email;

        // Cargar datos del usuario
        await cargarUsuarioActual(usuarioId);
        await cargarAlergenos();
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        window.location.href = 'login.html';
    }
}

// Cargar datos del usuario actual
async function cargarUsuarioActual(id) {
    try {
        const response = await fetch('api/obtener_usuario.php');
        const data = await response.json();

        if (data.exito && data.usuario) {
            usuarioActual = data.usuario;
            await mostrarPerfilUsuario(data.usuario);
        }
    } catch (error) {
        console.error('Error al cargar usuario:', error);
    }
}

// Mostrar perfil del usuario
async function mostrarPerfilUsuario(usuario) {
    const perfilDiv = document.getElementById('perfilUsuario');
    if (!perfilDiv) return;
    
    // Mostrar fecha de nacimiento formateada
    let fechaFormato = '-';
    if (usuario.fecha_nacimiento) {
        const fecha = new Date(usuario.fecha_nacimiento + 'T00:00:00');
        fechaFormato = fecha.toLocaleDateString('es-ES');
    }
    
    let html = `<h3>${usuario.nombre} - ${fechaFormato}</h3><p>Tus alergenos:</p><div class="alergias-usuario" id="alergenuarioContainer">`;

    try {
        const response = await fetch('api/obtener_alergenos_usuario.php');
        const resultado = await response.json();
        
        const alergenos = resultado.alergenos || [];

        if (Array.isArray(alergenos) && alergenos.length > 0) {
            alergenos.forEach(alergeno => {
                html += `<div class="chip-alergia">${alergeno}</div>`;
            });
        } else {
            html += '<p style="color: #90EE90;">✓ Sin alergenos registrados</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        html += '<p style="color: #999;">Error al cargar alergenos</p>';
    }

    html += '</div>';
    perfilDiv.innerHTML = html;
}

// Cargar tabla de alergenos
async function cargarAlergenos() {
    try {
        const response = await fetch('api/obtener_alergenos.php');
        const data = await response.json();

        const tbody = document.getElementById('tablaAlergenosBody');
        if (!tbody) return;

        if (data.exito && data.alergenos) {
            data.alergenos.forEach(alergeno => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${alergeno.nombre}</strong></td>
                    <td>${alergeno.descripcion || 'Sin descripción'}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (error) {
        console.error('Error al cargar alergenos:', error);
    }
}

// Buscar producto por código de barras
async function buscarProducto() {
    const codigo = document.getElementById('codigoBarras').value.trim();
    
    if (!codigo) {
        mostrarModalError('Por favor ingresa un código de barras');
        return;
    }

    // Mostrar que está cargando
    const resultadoDiv = document.getElementById('resultadoContainer');
    resultadoDiv.style.display = 'block';
    resultadoDiv.innerHTML = '<p style="text-align: center; color: #667eea;"><strong>Buscando producto...</strong></p>';

    try {
        const formData = new FormData();
        formData.append('codigo_barras', codigo);
        formData.append('usuario_id', usuarioId);

        const response = await fetch('api/buscar_producto.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        mostrarResultado(data, codigo);
    } catch (error) {
        console.error('Error:', error);
        resultadoDiv.innerHTML = '<p style="color: red;">Error al buscar el producto</p>';
    }
}

// Mostrar resultado de la búsqueda
function mostrarResultado(data, codigo) {
    const resultadoDiv = document.getElementById('resultadoContainer');
    let html = '';

    if (data.exito && data.producto) {
        const producto = data.producto;
        html = `
            <h3>${producto.nombre}</h3>
            ${producto.imagen ? `<img src="${producto.imagen}" alt="Imagen de ${producto.nombre}" style="max-width: 150px; height: auto; border-radius: 8px; margin-bottom: 15px;">` : ''}
            <div class="info-producto">
                <p><strong>Marca:</strong> ${producto.marca || 'N/A'}</p>
                <p><strong>Código de Barras:</strong> ${producto.codigo_barras}</p>
            </div>
        `;

        // Mostrar el mensaje de advertencia principal si existe
        if (data.mensaje_advertencia) {
            html += `
                <div style="background-color: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; padding: 20px; border-radius: 8px; margin-top: 15px; font-size: 1.1em; text-align: center;">
                    <strong>${data.mensaje_advertencia}</strong>
                </div>
            `;
        }

        // Listar todos los alérgenos detectados en el producto (para información)
        if (data.alergenos_detectados && data.alergenos_detectados.length > 0) {
            html += '<h4 style="margin-top: 20px;">Alérgenos declarados en el producto:</h4>';
            html += '<ul style="list-style-type:none; padding-left: 0;">';
            data.alergenos_detectados.forEach(alergeno => {
                html += `<li>${alergeno}</li>`;
            });
            html += '</ul>';
        } else {
            html += `
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center;">
                    <strong>✓ Este producto no declara alérgenos comunes.</strong>
                </div>
            `;
        }
        
        // Mostrar lista de ingredientes
        if (data.ingredientes) {
            html += `
                <div style="margin-top: 20px;">
                    <h4>Ingredientes:</h4>
                    <p style="font-size: 0.9em; color: #666;">${data.ingredientes}</p>
                </div>
            `;
        }

    } else {
        html = `
            <p style="color: #999;">Producto no encontrado en la base de datos de Open Food Facts.</p>
            <p style="color: #666;">Código buscado: <strong>${codigo}</strong></p>
        `;
    }

    resultadoDiv.innerHTML = html;
}

// Obtener alergenos del usuario
async function obtenerAlergosUsuario(usuarioId) {
    try {
        const response = await fetch('api/obtener_alergenos_usuario.php');
        const data = await response.json();
        
        if (data.exito && data.alergenos) {
            // Convertir array de strings a array de objetos para compatibilidad
            return data.alergenos.map(nombre => ({nombre}));
        }
        return [];
    } catch (error) {
        console.error('Error:', error);
        return [];
    }
}

// Funciones para el modal de error
function mostrarModalError(mensaje) {
    const modal = document.getElementById('errorModal');
    const mensajeError = document.getElementById('modalErrorMessage');
    if (modal && mensajeError) {
        mensajeError.textContent = mensaje;
        modal.style.display = 'flex'; // Usar flex para centrar
    }
}

function cerrarModal() {
    const modal = document.getElementById('errorModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Limpiar resultado
function limpiarResultado() {
    document.getElementById('codigoBarras').value = '';
    document.getElementById('resultadoContainer').style.display = 'none';
    document.getElementById('codigoBarras').focus();
}

// Cerrar sesión (placeholder - will be overridden by dashboard.html)
async function cerrarSesion() {
    try {
        const response = await fetch('api/logout.php');
        const data = await response.json();
        if (data.exito) {
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Error:', error);
        window.location.href = 'login.html';
    }
}



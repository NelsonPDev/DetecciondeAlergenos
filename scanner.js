// scanner.js - Lógica del escáner de alergenos con sesiones

let usuarioActual = null;
let usuarioId = null;
let historialEscaneos = [];

// Verificar sesión y cargar datos al iniciar
document.addEventListener('DOMContentLoaded', async function() {
    await verificarSesion();
});

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
        restaurarHistorial();
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
        alert('Por favor ingresa un código de barras');
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
        agregarHistorial(codigo, data);
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
            html += '<ul>';
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

// Agregar al historial
function agregarHistorial(codigo, data) {
    const item = {
        codigo: codigo,
        nombre: data.producto ? data.producto.nombre : 'Producto no encontrado',
        fecha: new Date().toLocaleTimeString('es-ES'),
        tieneAlergenos: data.alergenos && data.alergenos.length > 0
    };

    historialEscaneos.unshift(item);
    if (historialEscaneos.length > 10) {
        historialEscaneos.pop();
    }

    guardarHistorial();
    mostrarHistorial();
}

// Mostrar historial
function mostrarHistorial() {
    const historialDiv = document.getElementById('historialContainer');
    const historialLista = document.getElementById('historialLista');

    if (!historialDiv || !historialLista) return;

    if (historialEscaneos.length > 0) {
        historialDiv.style.display = 'block';
        historialLista.innerHTML = '';

        historialEscaneos.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'item-historial';
            div.innerHTML = `
                <strong>${item.nombre}</strong>
                <p style="margin: 5px 0; font-size: 0.9em;">
                    Código: ${item.codigo}
                    ${item.tieneAlergenos ? '⚠️ Tiene alergenos' : '✓ Sin alergenos'}
                </p>
                <small>${item.fecha}</small>
            `;
            div.style.cursor = 'pointer';
            div.onclick = function() {
                document.getElementById('codigoBarras').value = item.codigo;
                buscarProducto();
            };
            historialLista.appendChild(div);
        });
    }
}

// Guardar historial en localStorage
function guardarHistorial() {
    localStorage.setItem('historialEscaneos', JSON.stringify(historialEscaneos));
}

// Restaurar historial desde localStorage
function restaurarHistorial() {
    const saved = localStorage.getItem('historialEscaneos');
    if (saved) {
        try {
            historialEscaneos = JSON.parse(saved);
            mostrarHistorial();
        } catch (e) {
            console.error('Error al restaurar historial:', e);
        }
    }
}

// Limpiar resultado
function limpiarResultado() {
    document.getElementById('codigoBarras').value = '';
    document.getElementById('resultadoContainer').style.display = 'none';
    document.getElementById('codigoBarras').focus();
}

// Cerrar sesión
async function cerrarSesion() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
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
}

// Permitir buscar presionando Enter
document.addEventListener('DOMContentLoaded', function() {
    const inputCodigo = document.getElementById('codigoBarras');
    if (inputCodigo) {
        inputCodigo.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarProducto();
            }
        });
    }
});

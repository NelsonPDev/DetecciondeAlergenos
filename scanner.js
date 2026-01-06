// scanner.js - Lógica del escáner de alergenos

let usuarioActual = null;
let historialEscaneos = [];

// Cargar usuarios al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarUsuarios();
    cargarAlergenos();
    restaurarHistorial();
});

// Cargar lista de usuarios
function cargarUsuarios() {
    fetch('api/obtener_usuarios.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('selectUsuario');
            if (data.exito && data.usuarios) {
                data.usuarios.forEach(usuario => {
                    const option = document.createElement('option');
                    option.value = usuario.id;
                    option.textContent = usuario.nombre;
                    option.dataset.usuario = JSON.stringify(usuario);
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error al cargar usuarios:', error));
}

// Manejar selección de usuario
document.getElementById('selectUsuario').addEventListener('change', function() {
    if (this.value) {
        const option = this.options[this.selectedIndex];
        usuarioActual = JSON.parse(option.dataset.usuario);
        mostrarPerfilUsuario(usuarioActual);
    } else {
        usuarioActual = null;
        document.getElementById('perfilUsuario').style.display = 'none';
    }
});

// Mostrar perfil del usuario seleccionado
function mostrarPerfilUsuario(usuario) {
    const perfilDiv = document.getElementById('perfilUsuario');
    const nombreDiv = document.getElementById('nombreUsuario');
    const alergenuarioDiv = document.getElementById('alergenuarioContainer');

    nombreDiv.textContent = usuario.nombre + ' (' + usuario.edad + ' años)';
    
    // Cargar alergenos del usuario
    fetch('api/obtener_alergenos_usuario.php?usuario_id=' + usuario.id)
        .then(response => response.json())
        .then(data => {
            alergenuarioDiv.innerHTML = '';
            if (data.exito && data.alergenos) {
                if (data.alergenos.length === 0) {
                    alergenuarioDiv.innerHTML = '<p style="color: #90EE90;">✓ Sin alergenos registrados</p>';
                } else {
                    data.alergenos.forEach(alergeno => {
                        const chip = document.createElement('div');
                        chip.className = 'chip-alergia ' + (alergeno.gravedad || 'leve');
                        chip.textContent = alergeno.nombre;
                        alergenuarioDiv.appendChild(chip);
                    });
                }
            }
            perfilDiv.style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alergenuarioDiv.innerHTML = '<p style="color: #999;">Error al cargar alergenos</p>';
        });
}

// Cargar tabla de alergenos
function cargarAlergenos() {
    fetch('api/obtener_alergenos.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('tablaAlergenosBody');
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
        })
        .catch(error => console.error('Error al cargar alergenos:', error));
}

// Buscar producto por código de barras
function buscarProducto() {
    const codigo = document.getElementById('codigoBarras').value.trim();
    
    if (!codigo) {
        alert('Por favor ingresa un código de barras');
        return;
    }

    // Mostrar que está cargando
    const resultadoDiv = document.getElementById('resultadoContainer');
    resultadoDiv.style.display = 'block';
    resultadoDiv.innerHTML = '<p style="text-align: center; color: #667eea;"><strong>Buscando producto...</strong></p>';

    // Buscar en la base de datos
    fetch('api/buscar_producto.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'codigo_barras=' + encodeURIComponent(codigo)
    })
    .then(response => response.json())
    .then(data => {
        mostrarResultado(data, codigo);
        agregarHistorial(codigo, data);
    })
    .catch(error => {
        console.error('Error:', error);
        resultadoDiv.innerHTML = '<p style="color: red;">Error al buscar el producto</p>';
    });
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
                <p><strong>Categoría:</strong> ${producto.categoria || 'N/A'}</p>
                <p><strong>Código de Barras:</strong> ${producto.codigo_barras}</p>
            </div>
        `;

        if (data.alergenos && data.alergenos.length > 0) {
            html += '<h4 style="color: #dc3545;">⚠️ Alergenos Detectados:</h4>';
            
            data.alergenos.forEach(alergeno => {
                const tieneAlergeno = usuarioActual ? 
                    usuarioActual.alergenos && usuarioActual.alergenos.includes(alergeno.id) : 
                    false;

                html += `
                    <div class="alergeno-encontrado">
                        <strong>${alergeno.nombre}</strong>
                        <p>${alergeno.tipo_presencia === 'Trazas' ? '⚠️ Trazas' : '🚫 Contiene'}</p>
                        ${tieneAlergeno ? '<p style="color: red; font-weight: bold;">⛔ ¡PELIGROSO PARA TI!</p>' : ''}
                    </div>
                `;
            });
        } else {
            html += '<div class="alergeno-seguro"><strong>✓ Este producto no contiene alergenos registrados</strong></div>';
        }
    } else {
        html = `
            <p style="color: #999;">Producto no encontrado en la base de datos</p>
            <p style="color: #666;">Código buscado: <strong>${codigo}</strong></p>
            <p>Puedes sugerir agregar este producto a la base de datos</p>
        `;
    }

    resultadoDiv.innerHTML = html;
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

// Permitir buscar presionando Enter
document.getElementById('codigoBarras').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        buscarProducto();
    }
});

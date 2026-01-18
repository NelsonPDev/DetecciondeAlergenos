document.addEventListener('DOMContentLoaded', function() {
    cargarHistorial();
});

async function cargarHistorial() {
    try {
        const response = await fetch('api/obtener_historial.php');
        const data = await response.json();

        if (data.exito && data.historial) {
            mostrarHistorial(data.historial);
        } else {
            console.error('Error al cargar el historial:', data.mensaje);
        }
    } catch (error) {
        console.error('Error de red al cargar el historial:', error);
    }
}

function mostrarHistorial(historial) {
    const contenedor = document.getElementById('historial-lista');
    if (!contenedor) return;

    if (historial.length === 0) {
        contenedor.innerHTML = '<p>No hay productos en tu historial.</p>';
        return;
    }

    let html = '';
    historial.forEach(item => {
        const fecha = new Date(item.fecha_escaneo).toLocaleString('es-ES');
        const alergenos = item.alergenos_detectados && item.alergenos_detectados.length > 0
            ? `<p class="alergia-detectada">Contiene: ${item.alergenos_detectados.join(', ')}</p>`
            : '<p class="sin-alergia">✓ Sin alérgenos declarados.</p>';

        html += `
            <div class="historial-item">
                <img src="${item.imagen_url || 'https://via.placeholder.com/100'}" alt="${item.nombre_producto}" class="historial-imagen">
                <div class="historial-info">
                    <h4>${item.nombre_producto}</h4>
                    <p><strong>Marca:</strong> ${item.marca || 'N/A'}</p>
                    <p><strong>Código:</strong> ${item.codigo_barras}</p>
                    ${alergenos}
                </div>
                <div class="historial-fecha">
                    ${fecha}
                </div>
            </div>
        `;
    });

    contenedor.innerHTML = html;
}

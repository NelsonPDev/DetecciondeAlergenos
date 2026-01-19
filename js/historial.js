document.addEventListener('DOMContentLoaded', function() {
    // Initial load of history without filters
    cargarHistorial();

    // Event listeners for filter buttons
    document.getElementById('applyFiltersBtn').addEventListener('click', cargarHistorial);
    document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
});

async function cargarHistorial() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const searchQuery = document.getElementById('searchQuery').value;

    let url = 'api/obtener_historial.php';
    const params = new URLSearchParams();

    if (startDate) {
        params.append('start_date', startDate);
    }
    if (endDate) {
        params.append('end_date', endDate);
    }
    if (searchQuery) {
        params.append('search_query', searchQuery);
    }

    if (params.toString()) {
        url += '?' + params.toString();
    }

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.exito && data.historial) {
            mostrarHistorial(data.historial);
        } else if (data.mensaje) {
            mostrarMensaje(data.mensaje, 'error');
            mostrarHistorial([]); // Clear list if error or no results
        } else {
            console.error('Error al cargar el historial:', data);
            mostrarMensaje('Error al cargar el historial.', 'error');
            mostrarHistorial([]);
        }
    } catch (error) {
        console.error('Error de red al cargar el historial:', error);
        mostrarMensaje('Error de red al cargar el historial.', 'error');
        mostrarHistorial([]);
    }
}

function clearFilters() {
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    document.getElementById('searchQuery').value = '';
    cargarHistorial(); // Reload history after clearing filters
}

function mostrarHistorial(historial) {
    const contenedor = document.getElementById('historial-lista');
    if (!contenedor) return;

    if (historial.length === 0) {
        contenedor.innerHTML = '<p>No hay productos en tu historial que coincidan con los filtros aplicados.</p>';
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

// Global function to display messages, assuming it's available from a global scope or other script
// If not, it needs to be defined or imported. For now, assuming it's accessible.
function mostrarMensaje(texto, tipo) {
    const elemento = document.getElementById('mensaje');
    if (elemento) {
        elemento.textContent = texto;
        elemento.className = 'mensaje ' + tipo;
        setTimeout(() => {
            elemento.className = 'mensaje';
            elemento.textContent = ''; // Clear text after hiding
        }, 4000);
    } else {
        console.warn('Elemento de mensaje no encontrado:', texto, tipo);
    }
}
document.addEventListener('DOMContentLoaded', function () {
    // Si Chart.js no cargo (sin internet), no se dibuja nada.
    if (typeof Chart === 'undefined') {
        return;
    }

    var colores = ['#0d6efd', '#6f42c1', '#d63384', '#fd7e14', '#ffc107',
                   '#198754', '#20c997', '#0dcaf0', '#6c757d', '#343a40'];

    var lienzoCategorias = document.getElementById('grafica-categorias');
    if (lienzoCategorias) {
        var etiquetas = JSON.parse(lienzoCategorias.dataset.etiquetas);
        var valores = JSON.parse(lienzoCategorias.dataset.valores);

        new Chart(lienzoCategorias, {
            type: 'doughnut',
            data: {
                labels: etiquetas,
                datasets: [{
                    data: valores,
                    backgroundColor: etiquetas.map(function (_, i) {
                        return colores[i % colores.length];
                    })
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    var lienzoComparacion = document.getElementById('grafica-comparacion');
    if (lienzoComparacion) {
        new Chart(lienzoComparacion, {
            type: 'bar',
            data: {
                labels: ['Ingresos', 'Gastos'],
                datasets: [{
                    data: JSON.parse(lienzoComparacion.dataset.valores),
                    backgroundColor: ['#198754', '#dc3545']
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});
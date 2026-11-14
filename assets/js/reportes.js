// Graficas de reportes. Los datos vienen de la base de datos.

document.addEventListener('DOMContentLoaded', function () {
    // Si Chart.js no cargo (sin internet), no se dibuja nada.
    if (typeof Chart === 'undefined') {
        return;
    }

    var colores = ['#0d6efd', '#6f42c1', '#d63384', '#fd7e14', '#ffc107',
                   '#198754', '#20c997', '#0dcaf0', '#6c757d', '#343a40'];

    function leer(lienzo, atributo) {
        return JSON.parse(lienzo.dataset[atributo]);
    }

    // Comparacion mensual: barras de ingresos y gastos, linea de balance.
    var mensual = document.getElementById('grafica-mensual');
    if (mensual) {
        new Chart(mensual, {
            type: 'bar',
            data: {
                labels: leer(mensual, 'etiquetas'),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Ingresos',
                        data: leer(mensual, 'ingresos'),
                        backgroundColor: '#198754'
                    },
                    {
                        type: 'bar',
                        label: 'Gastos',
                        data: leer(mensual, 'gastos'),
                        backgroundColor: '#dc3545'
                    },
                    {
                        type: 'line',
                        label: 'Balance',
                        data: leer(mensual, 'balance'),
                        borderColor: '#0d6efd',
                        backgroundColor: '#0d6efd',
                        tension: 0.2
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // Graficas de dona por categoria (gastos e ingresos).
    function dona(id) {
        var lienzo = document.getElementById(id);
        if (!lienzo) {
            return;
        }
        var etiquetas = leer(lienzo, 'etiquetas');

        new Chart(lienzo, {
            type: 'doughnut',
            data: {
                labels: etiquetas,
                datasets: [{
                    data: leer(lienzo, 'valores'),
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

    dona('grafica-gastos-categoria');
    dona('grafica-ingresos-categoria');
});
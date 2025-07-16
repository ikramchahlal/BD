<?php
$page_title = "Statistiques des Documents";
$active_page = "stats";
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Vérifier si admin
if($_SESSION['user_role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Récupérer les données statistiques
$documents_by_type = $document->countDocumentsByType();
$documents_by_status = $document->countDocumentsByStatus();
$documents_by_month = $document->getDocumentsByMonth();
$documents_by_user = $document->getDocumentsByUser();
?>

<?php include 'includes/header.php'; ?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
        --accent-color: #8a63d2;
        --light-bg: #f8f9fa;
        --card-shadow: 0 10px 30px -15px rgba(0, 0, 0, 0.1);
    }
    
    body {
        background-color: #f5f7ff;
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    }
    
    .stat-card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        margin-bottom: 30px;
        overflow: hidden;
        background: white;
    }
    
    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.15);
    }
    
    .card-header {
        background: var(--primary-gradient);
        color: white;
        padding: 18px 25px;
        border-bottom: none;
        position: relative;
    }
    
    .card-header h5 {
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .card-header i {
        margin-right: 10px;
        font-size: 1.1rem;
    }
    
    .card-body {
        padding: 25px;
    }
    
    .chart-container {
        position: relative;
        height: 250px;
        width: 100%;
    }
    
    .page-header {
        background: var(--primary-gradient);
        color: white;
        padding: 25px 30px;
        border-radius: 12px;
        margin-bottom: 40px;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
    }
    
    .page-header::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: rgba(255, 255, 255, 0.1);
        transform: rotate(30deg);
    }
    
    .page-header h2 {
        margin-bottom: 0;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }
    
    .page-header i {
        margin-right: 15px;
    }
    
    .container {
        max-width: 1200px;
        padding-top: 30px;
        padding-bottom: 50px;
    }
    
    /* Animation subtile pour les cartes */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stat-card {
        animation: fadeInUp 0.6s ease forwards;
    }
    
    .stat-card:nth-child(2) {
        animation-delay: 0.1s;
    }
    
    .stat-card:nth-child(3) {
        animation-delay: 0.2s;
    }
    
    .stat-card:nth-child(4) {
        animation-delay: 0.3s;
    }
</style>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-chart-pie"></i> Tableau de Bord Statistique</h2>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="stat-card card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i>Répartition par Type</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="stat-card card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags"></i>Statut des Documents</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="stat-card card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i>Évolution Mensuelle</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="stat-card card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i>Documents par Utilisateur</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Palette de couleurs modernes
    const colors = {
        purple: {
            default: 'rgba(102, 126, 234, 1)',
            half: 'rgba(102, 126, 234, 0.5)',
            quarter: 'rgba(102, 126, 234, 0.25)',
            zero: 'rgba(102, 126, 234, 0)'
        },
        indigo: {
            default: 'rgba(118, 75, 162, 1)',
            half: 'rgba(118, 75, 162, 0.5)',
            quarter: 'rgba(118, 75, 162, 0.25)'
        },
        pink: {
            default: 'rgba(251, 194, 235, 1)',
            half: 'rgba(251, 194, 235, 0.5)'
        },
        teal: {
            default: 'rgba(23, 198, 181, 1)',
            half: 'rgba(23, 198, 181, 0.5)'
        }
    };

    // Options communes pour tous les graphiques
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 20,
                    font: {
                        size: 12,
                        family: "'Segoe UI', Roboto, 'Helvetica Neue', sans-serif"
                    },
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.85)',
                titleFont: {
                    size: 13,
                    weight: 'bold',
                    family: "'Segoe UI', Roboto, 'Helvetica Neue', sans-serif"
                },
                bodyFont: {
                    size: 12,
                    family: "'Segoe UI', Roboto, 'Helvetica Neue', sans-serif"
                },
                padding: 12,
                cornerRadius: 8,
                displayColors: true,
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.raw;
                        return label;
                    }
                }
            },
            datalabels: {
                display: false
            }
        }
    };

    // Graphique des types
    new Chart(document.getElementById('typeChart'), {
        type: 'pie',
        data: {
            labels: [<?php foreach($documents_by_type as $type) echo "'".ucfirst($type->type)."',"; ?>],
            datasets: [{
                data: [<?php foreach($documents_by_type as $type) echo $type->count.','; ?>],
                backgroundColor: [
                    colors.purple.default,
                    colors.indigo.default,
                    colors.teal.default,
                    colors.pink.default,
                    '#f6c23e',
                    '#e74a3b'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            ...chartOptions,
            plugins: {
                ...chartOptions.plugins,
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    formatter: (value) => {
                        return value > 0 ? value : '';
                    }
                }
            },
            cutout: '65%'
        },
        plugins: [ChartDataLabels]
    });

    // Graphique des statuts
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($documents_by_status as $status) echo "'".ucfirst(str_replace('_', ' ', $status->status))."',"; ?>],
            datasets: [{
                label: 'Nombre de documents',
                data: [<?php foreach($documents_by_status as $status) echo $status->count.','; ?>],
                backgroundColor: [
                    colors.purple.default,
                    colors.indigo.default,
                    colors.teal.default,
                    colors.pink.half,
                    '#f6c23e'
                ],
                borderRadius: 8,
                borderWidth: 0,
                barPercentage: 0.7
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        stepSize: 1
                    },
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                ...chartOptions.plugins,
                legend: {
                    display: false
                }
            }
        }
    });

    // Graphique mensuel
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: [<?php 
                for($i = 11; $i >= 0; $i--) {
                    echo "'".date('M Y', strtotime("-$i months"))."',";
                }
            ?>],
            datasets: [{
                label: 'Documents créés',
                data: [<?php 
                foreach(range(0, 11) as $i) {
                    $month = date('Y-m', strtotime("-$i months"));
                    $found = false;
                    foreach($documents_by_month as $doc) {
                        if($doc->month == $month) {
                            echo $doc->count.',';
                            $found = true;
                            break;
                        }
                    }
                    if(!$found) echo '0,';
                }
                ?>],
                borderColor: colors.indigo.default,
                backgroundColor: colors.indigo.quarter,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: colors.indigo.default,
                pointBorderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        stepSize: 1
                    },
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                ...chartOptions.plugins,
                legend: {
                    position: 'top',
                    align: 'end'
                }
            }
        }
    });

    // Graphique par utilisateur
    new Chart(document.getElementById('userChart'), {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($documents_by_user as $user) echo "'".$user->username."',"; ?>],
            datasets: [{
                data: [<?php foreach($documents_by_user as $user) echo $user->count.','; ?>],
                backgroundColor: [
                    colors.purple.default,
                    colors.indigo.default,
                    colors.teal.default,
                    colors.pink.default,
                    '#f6c23e',
                    '#e74a3b',
                    '#6610f2',
                    '#fd7e14'
                ],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            ...chartOptions,
            cutout: '75%',
            plugins: {
                ...chartOptions.plugins,
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    formatter: (value) => {
                        return value > 0 ? value : '';
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
});
</script>

<?php include 'includes/footer.php'; ?>
<?php
/**
 * ======================================================
 * PÁGINA DE EFICIÊNCIA (v3.0)
 * ======================================================
 * Análise de eficiência das linhas com filtros
 * Refatorado para usar FiltroGlobal e QueryBuilder
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';
require_once '../includes/Filtros.php';
require_once '../includes/QueryBuilder.php';

$titulo = 'Eficiência - Sistema Industrial JFM';

// Inicializar filtro
$filtro = new FiltroGlobal($pdo);
$filtro->aplicarFiltros($_GET);

$eficienciaLinhas = [];
$tendencia = [];
$eficienciaMedia = 0;

try {
    // Eficiência por linha para o período selecionado
    $where = $filtro->getWhereClauses('r', 'p');
    
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(l.linha, 'Sem Linha') as linha,
            l.id as linha_id,
            ROUND(AVG(COALESCE(v.velocidade, 0)), 2) as velocidade_media,
            COUNT(v.id) as quantidade_registros,
            s.nome as setor
        FROM velocidades v
        LEFT JOIN registros r ON v.registro_id = r.id
        LEFT JOIN linhas l ON r.linha_id = l.id
        LEFT JOIN setores s ON r.setor_id = s.id
        WHERE $where
        GROUP BY l.id
        ORDER BY velocidade_media DESC
    ");
    $stmt->execute();
    $eficienciaLinhas = $stmt->fetchAll();
    
    // Calcular velocidade média geral
    $eficienciaMedia = 0;
    if (!empty($eficienciaLinhas)) {
        $eficienciaMedia = array_sum(array_column($eficienciaLinhas, 'velocidade_media')) / count($eficienciaLinhas);
    }
    
    // Tendência diária (últimos 30 dias)
    $filtros = $filtro->getFiltros();
    $dataFim = $filtros['data_fim'] ?? date('Y-m-d');
    $dataInicio = date('Y-m-d', strtotime('-29 days', strtotime($dataFim)));
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(r.data_registro) as data,
            ROUND(AVG(COALESCE(v.velocidade, 0)), 2) as velocidade_media
        FROM velocidades v
        LEFT JOIN registros r ON v.registro_id = r.id
        WHERE DATE(r.data_registro) >= :data_inicio
        AND DATE(r.data_registro) <= :data_fim
        GROUP BY DATE(r.data_registro)
        ORDER BY DATE(r.data_registro) ASC
    ");
    $stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataFim]);
    $tendencia = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Erro ao carregar eficiência: ' . $e->getMessage());
}

// Preparar dados para gráfico
$datesJson = json_encode(array_map(function($r) { return formatarData($r['data']); }, $tendencia));
$eficienciasJson = json_encode(array_map(function($r) { return $r['velocidade_media']; }, $tendencia));
$linhasJson = json_encode(array_map(function($r) { return $r['linha'] ?? 'Indefinido'; }, $eficienciaLinhas));
$eficienciasLinhasJson = json_encode(array_map(function($r) { return $r['velocidade_media']; }, $eficienciaLinhas));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">JFM</div>
                <div class="sidebar-title">
                    <div class="brand-name">JFM</div>
                    <div class="brand-subtitle">Dashboard Industrial</div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">Painéis</div>
                <a href="/dashboard/index.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Dashboard Principal</span></a>
                <a href="/dashboard/oee.php" class="nav-item"><i class="fas fa-star"></i><span>OEE Análise</span></a>
                <a href="/dashboard/paradas.php" class="nav-item"><i class="fas fa-pause-circle"></i><span>Paradas</span></a>
                <a href="/dashboard/velocidade.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Velocidade</span></a>
                <a href="/dashboard/eficiencia.php" class="nav-item active"><i class="fas fa-chart-bar"></i><span>Eficiência</span></a>
                <div class="nav-section">Sistema</div>
                <a href="/admin/usuarios.php" class="nav-item"><i class="fas fa-users"></i><span>Usuários</span></a>
                <a href="/admin/auditoria.php" class="nav-item"><i class="fas fa-history"></i><span>Auditoria</span></a>
                <a href="/processos/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Sair</span></a>
            </nav>
            <div class="sidebar-footer">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($usuario_nome ?? 'User'); ?>&background=random" class="sidebar-user-avatar" alt="Avatar">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($usuario_nome ?? 'Usuário'); ?></div>
                    <div class="sidebar-user-role"><?php echo htmlspecialchars($perfil ?? 'Operador'); ?></div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="toggle-sidebar-btn" id="toggleSidebarBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1><i class="fas fa-chart-bar"></i> Análise de Eficiência</h1>
                        <p>Eficiência operacional por linha • Período: <strong><?php echo formatarData($filtro->getFiltros()['data_inicio']); ?></strong> a <strong><?php echo formatarData($filtro->getFiltros()['data_fim']); ?></strong></p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="status-indicator">
                        <span class="status-dot"></span>
                        <span>Sistema Ativo</span>
                    </div>
                </div>
            </header>

            <div class="content-scroll" style="padding: 1.5rem;">
                <!-- FILTRO GLOBAL -->
                <?php include '../includes/filtro-componente.html'; ?>
                <!-- KPI VELOCIDADE MÉDIA -->
                <div class="kpi-grid" style="margin-bottom: 2rem;">
                    <div class="kpi-card" style="grid-column: 1/-1; text-align: center; padding: 2rem;">
                        <div style="font-size: 1.1rem; margin-bottom: 1rem;">Velocidade Média do Período</div>
                        <div style="font-size: 3.5rem; font-weight: 700; color: #10b981; margin: 1rem 0;">
                            <?php echo formatarNumero($eficienciaMedia, 1); ?>
                        </div>
                        <div style="font-size: 0.9rem; color: #94a3b8;">
                            <?php echo count($eficienciaLinhas); ?> linha<?php echo count($eficienciaLinhas) !== 1 ? 's' : ''; ?> com dados
                        </div>
                    </div>
                </div>

                <!-- GRÁFICO TENDÊNCIA -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i> Tendência de Velocidade Média (Últimos 30 dias)
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartTendencia"></canvas>
                        </div>
                    </div>
                </div>

                <!-- GRÁFICO POR LINHA -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <i class="fas fa-chart-bar"></i> Velocidade Média por Linha
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartLinhas"></canvas>
                        </div>
                    </div>
                </div>

                <!-- TABELA DE LINHAS -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-table"></i> Detalhes por Linha
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; color: #e2e8f0;">
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,.1);">
                                        <th style="padding: 0.75rem; text-align: left;">Linha</th>
                                        <th style="padding: 0.75rem; text-align: center;">Velocidade Média</th>
                                        <th style="padding: 0.75rem; text-align: center;">Registros</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($eficienciaLinhas)): ?>
                                        <?php foreach ($eficienciaLinhas as $linha): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,.05);">
                                            <td style="padding: 0.75rem; font-weight: bold;"><?php echo htmlspecialchars($linha['linha']); ?></td>
                                            <td style="padding: 0.75rem; text-align: center; font-weight: bold; color: #10b981;">
                                                <?php echo formatarNumero($linha['velocidade_media'], 2); ?>
                                            </td>
                                            <td style="padding: 0.75rem; text-align: center; color: #94a3b8;">
                                                <?php echo $linha['quantidade_registros']; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="padding: 1rem; text-align: center; color: #94a3b8;">
                                                Nenhum dado de eficiência para a data selecionada
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Sidebar toggle
document.getElementById('toggleSidebarBtn').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
});

document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth < 768) {
            sidebar.classList.remove('open');
        }
    });
});

// Gráfico tendência
const datesTend = <?php echo $datesJson; ?>;
const eficienciasTend = <?php echo $eficienciasJson; ?>;

const ctxTend = document.getElementById('chartTendencia').getContext('2d');
new Chart(ctxTend, {
    type: 'line',
    data: {
        labels: datesTend,
        datasets: [{
            label: 'Velocidade Média',
            data: eficienciasTend,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)', drawBorder: false }
            },
            x: {
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)', drawBorder: false }
            }
        }
    }
});

// Gráfico por linha
const linhas = <?php echo $linhasJson; ?>;
const eficienciasLinhas = <?php echo $eficienciasLinhasJson; ?>;

const ctxLinhas = document.getElementById('chartLinhas').getContext('2d');
new Chart(ctxLinhas, {
    type: 'bar',
    data: {
        labels: linhas,
        datasets: [{
            label: 'Velocidade Média',
            data: eficienciasLinhas,
            backgroundColor: [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6',
                '#ec4899'
            ],
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                beginAtZero: true,
                max: 150,
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)', drawBorder: false }
            },
            y: {
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)', drawBorder: false }
            }
        }
    }
});

// (Seletor de data removido - usar filtro global ao invés)
</script>

</body>
</html>

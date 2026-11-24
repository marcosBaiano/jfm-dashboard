<?php
/**
 * ======================================================
 * PÁGINA DE VELOCIDADE (v3.0)
 * ======================================================
 * Monitorar velocidades planejada vs real com filtros
 * Refatorado para usar FiltroGlobal e QueryBuilder
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';
require_once '../includes/Filtros.php';
require_once '../includes/QueryBuilder.php';

$titulo = 'Velocidade - Sistema Industrial JFM';

// Inicializar filtro
$filtro = new FiltroGlobal($pdo);
$filtro->aplicarFiltros($_GET);

$velocidades = [];
$eficienciaMedia = 0;
$velocidadeMedia = 0;
$velocidadesHistorico = [];

try {
    // Obter velocidades usando filtro
    $where = $filtro->getWhereClauses('r', 'p');
    
    // Velocidades do período selecionado
    $sql = "
        SELECT 
            v.id,
            v.hora,
            v.velocidade_planejada,
            v.velocidade,
            ROUND((v.velocidade / NULLIF(v.velocidade_planejada, 0)) * 100, 2) as eficiencia,
            r.data_registro,
            l.linha,
            s.nome as setor,
            m.nome as maquina
        FROM velocidades v
        LEFT JOIN registros r ON v.registro_id = r.id
        LEFT JOIN linhas l ON r.linha_id = l.id
        LEFT JOIN setores s ON r.setor_id = s.id
        LEFT JOIN maquinas m ON r.maquina_id = m.id
        WHERE $where
        ORDER BY v.hora DESC
        LIMIT 1000
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $velocidades = $stmt->fetchAll();
    
    // Calcular eficiência média
    $stmt = $pdo->prepare("
        SELECT 
            ROUND(AVG(v.velocidade), 2) as velocidade_media,
            ROUND(AVG(CASE WHEN v.velocidade_planejada > 0 
                THEN (v.velocidade / v.velocidade_planejada) * 100 
                ELSE 0 END), 2) as eficiencia_media
        FROM velocidades v
        LEFT JOIN registros r ON v.registro_id = r.id
        WHERE $where
    ");
    $stmt->execute();
    $stats = $stmt->fetch();
    $velocidadeMedia = $stats['velocidade_media'] ?? 0;
    $eficienciaMedia = $stats['eficiencia_media'] ?? 0;
    
    // Histórico para gráfico (últimos 30 dias)
    $filtros = $filtro->getFiltros();
    $dataFim = $filtros['data_fim'] ?? date('Y-m-d');
    $dataInicio = date('Y-m-d', strtotime('-29 days', strtotime($dataFim)));
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(r.data_registro) as data,
            ROUND(AVG((v.velocidade / NULLIF(v.velocidade_planejada, 0)) * 100), 2) as eficiencia
        FROM velocidades v
        LEFT JOIN registros r ON v.registro_id = r.id
        WHERE DATE(r.data_registro) >= :data_inicio
        AND DATE(r.data_registro) <= :data_fim
        AND v.velocidade_planejada > 0
        GROUP BY DATE(r.data_registro)
        ORDER BY DATE(r.data_registro) ASC
    ");
    $stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataFim]);
    $velocidadesHistorico = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Erro ao carregar velocidades: ' . $e->getMessage());
}

// Preparar dados para gráfico
$datesJson = json_encode(array_map(function($r) { return formatarData($r['data']); }, $velocidadesHistorico));
$eficienciasJson = json_encode(array_map(function($r) { return $r['eficiencia']; }, $velocidadesHistorico));
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
                <a href="/dashboard/velocidade.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i><span>Velocidade</span></a>
                <a href="/dashboard/eficiencia.php" class="nav-item"><i class="fas fa-chart-bar"></i><span>Eficiência</span></a>
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
                        <h1><i class="fas fa-tachometer-alt"></i> Controle de Velocidade</h1>
                        <p>Monitoramento de velocidades planejada vs real • Período: <strong><?php echo formatarData($filtro->getFiltros()['data_inicio']); ?></strong> a <strong><?php echo formatarData($filtro->getFiltros()['data_fim']); ?></strong></p>
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
                <!-- KPI EFICIÊNCIA -->
                <div class="kpi-grid" style="margin-bottom: 2rem;">
                    <div class="kpi-card" style="grid-column: 1/-1; text-align: center; padding: 2rem;">
                        <div style="font-size: 1.1rem; margin-bottom: 1rem;">Eficiência Média em <?php echo formatarData($filtro->getFiltros()['data_fim'] ?? date('Y-m-d')); ?></div>
                        <div style="font-size: 3.5rem; font-weight: 700; color: #3b82f6; margin: 1rem 0;">
                            <?php echo formatarPercentual($eficienciaMedia, 1); ?>
                        </div>
                        <div style="font-size: 0.9rem; color: #94a3b8;">
                            <?php echo count($velocidades); ?> registro<?php echo count($velocidades) !== 1 ? 's' : ''; ?>
                        </div>
                    </div>
                </div>

                <!-- GRÁFICO TENDÊNCIA -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i> Tendência de Eficiência (Últimos 30 dias)
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartVelocidade"></canvas>
                        </div>
                    </div>
                </div>

                <!-- TABELA DE VELOCIDADES -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-table"></i> Registros do Dia
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; color: #e2e8f0;">
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,.1);">
                                        <th style="padding: 0.75rem; text-align: left;">Data/Hora</th>
                                        <th style="padding: 0.75rem; text-align: left;">Máquina</th>
                                        <th style="padding: 0.75rem; text-align: center;">Planejada</th>
                                        <th style="padding: 0.75rem; text-align: center;">Real</th>
                                        <th style="padding: 0.75rem; text-align: center;">Eficiência</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($velocidades)): ?>
                                        <?php foreach ($velocidades as $vel): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,.05);">
                                            <td style="padding: 0.75rem;"><?php echo formatarData($vel['data_registro']) . ' ' . $vel['hora']; ?></td>
                                            <td style="padding: 0.75rem; font-weight: bold;">Linha</td>
                                            <td style="padding: 0.75rem; text-align: center;"><?php echo formatarNumero($vel['velocidade_planejada'], 2); ?></td>
                                            <td style="padding: 0.75rem; text-align: center;"><?php echo formatarNumero($vel['velocidade'], 2); ?></td>
                                            <td style="padding: 0.75rem; text-align: center; font-weight: bold; color: #3b82f6;">
                                                <?php echo formatarPercentual($vel['eficiencia'], 1); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="padding: 1rem; text-align: center; color: #94a3b8;">
                                                Nenhum registro de velocidade para a data selecionada
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

// Gráfico velocidade
const datesVel = <?php echo $datesJson; ?>;
const eficienciasVel = <?php echo $eficienciasJson; ?>;

const ctxVel = document.getElementById('chartVelocidade').getContext('2d');
new Chart(ctxVel, {
    type: 'line',
    data: {
        labels: datesVel,
        datasets: [{
            label: 'Eficiência (%)',
            data: eficienciasVel,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#3b82f6',
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
                max: 150,
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

// (Seletor de data removido - usar filtro global ao invés)
</script>

</body>
</html>

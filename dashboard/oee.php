<?php
/**
 * ======================================================
 * PÁGINA DE OEE (v3.0)
 * ======================================================
 * Cálculo e análise de OEE com filtros
 * Refatorado para usar FiltroGlobal e QueryBuilder
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';
require_once '../includes/Filtros.php';
require_once '../includes/QueryBuilder.php';

$titulo = 'OEE - Sistema Industrial JFM';

// Inicializar filtro
$filtro = new FiltroGlobal($pdo);
$filtro->aplicarFiltros($_GET);

// Inicializar variáveis
$disponibilidade = $performance = $qualidade = $oee = 0;
$statusOEE = 'Crítico';
$corStatus = '#dc2626';
$tempoProduzindo = 0;
$tempoJornada = 960;
$producaoReal = 0;
$producaoPlanejada = 400;
$producaoTotal = 0;
$producaoBoa = 0;
$oeeHistorico = [];

try {
    $tempoJornada = 16 * 60; // 960 minutos
    $where = $filtro->getWhereClauses('r', 'p');
    
    // 1. DISPONIBILIDADE
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado
        FROM paradas p
        INNER JOIN registros r ON p.registro_id = r.id
        WHERE $where
    ");
    $stmt->execute();
    $tempoParado = $stmt->fetch()['tempo_parado'] ?? 0;
    $tempoProduzindo = $tempoJornada - $tempoParado;
    $disponibilidade = calcularDisponibilidade($tempoProduzindo, $tempoParado);
    
    // 2. PERFORMANCE
    $producaoPlanejada = ($tempoJornada / 60) * 25;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(pr.producao), 0) as producao_real
        FROM producao pr
        INNER JOIN registros r ON pr.registro_id = r.id
        WHERE $where
    ");
    $stmt->execute();
    $producaoReal = $stmt->fetch()['producao_real'] ?? 0;
    $performance = calcularPerformance($producaoReal, $producaoPlanejada);
    
    // 3. QUALIDADE
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(pr.producao), 0) as total,
            COALESCE(SUM(pr.producao - (pr.perda_1 + pr.perda_2 + pr.perda_3 + pr.perda_4)), 0) as boa
        FROM producao pr
        INNER JOIN registros r ON pr.registro_id = r.id
        WHERE $where
    ");
    $stmt->execute();
    $dados = $stmt->fetch();
    $producaoTotal = $dados['total'] ?? 0;
    $producaoBoa = $dados['boa'] ?? 0;
    $qualidade = calcularQualidade($producaoBoa, $producaoTotal);
    
    // OEE FINAL
    $oee = calcularOEE($disponibilidade, $performance, $qualidade);
    
    // HISTÓRICO OEE (ÚLTIMOS 30 DIAS)
    $dataInicio = date('Y-m-d', strtotime('-30 days'));
    $dataFim = date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT DATE(data_registro) as data FROM registros
        WHERE DATE(data_registro) BETWEEN :data_inicio AND :data_fim
        GROUP BY DATE(data_registro)
        ORDER BY DATE(data_registro) DESC
    ");
    $stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataFim]);
    $datas = $stmt->fetchAll();
    
    $oeeHistorico = [];
    foreach ($datas as $data) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado
            FROM paradas p
            INNER JOIN registros r ON p.registro_id = r.id
            WHERE DATE(r.data_registro) = :data
        ");
        $stmt->execute([':data' => $data['data']]);
        $tempoParadoDia = $stmt->fetch()['tempo_parado'] ?? 0;
        $tempoProduzindoDia = $tempoJornada - $tempoParadoDia;
        $disponibilidadeDia = calcularDisponibilidade($tempoProduzindoDia, $tempoParadoDia);
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(pr.producao), 0) as producao_real
            FROM producao pr
            INNER JOIN registros r ON pr.registro_id = r.id
            WHERE DATE(r.data_registro) = :data
        ");
        $stmt->execute([':data' => $data['data']]);
        $producaoRealDia = $stmt->fetch()['producao_real'] ?? 0;
        $performanceDia = calcularPerformance($producaoRealDia, $producaoPlanejada);
        
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(pr.producao), 0) as total,
                COALESCE(SUM(pr.producao - (pr.perda_1 + pr.perda_2 + pr.perda_3 + pr.perda_4)), 0) as boa
            FROM producao pr
            INNER JOIN registros r ON pr.registro_id = r.id
            WHERE DATE(r.data_registro) = :data
        ");
        $stmt->execute([':data' => $data['data']]);
        $dadosDia = $stmt->fetch();
        $producaoTotalDia = $dadosDia['total'] ?? 0;
        $producaoBoaDia = $dadosDia['boa'] ?? 0;
        $qualidadeDia = calcularQualidade($producaoBoaDia, $producaoTotalDia);
        
        $oeeDia = calcularOEE($disponibilidadeDia, $performanceDia, $qualidadeDia);
        
        $oeeHistorico[] = [
            'data' => $data['data'],
            'oee' => $oeeDia,
            'disponibilidade' => $disponibilidadeDia,
            'performance' => $performanceDia,
            'qualidade' => $qualidadeDia
        ];
    }
    
} catch (Exception $e) {
    error_log('Erro ao calcular OEE: ' . $e->getMessage());
}

// Determinar status OEE
if ($oee >= 85) {
    $statusOEE = 'Excelente';
    $corStatus = '#16a34a';
} elseif ($oee >= 70) {
    $statusOEE = 'Bom';
    $corStatus = '#ea580c';
}

// Preparar dados para JavaScript
if (!empty($oeeHistorico)) {
    $datesOEEJson = json_encode(array_map(function($r) { return formatarData($r['data']); }, $oeeHistorico));
    $valoresOEEJson = json_encode(array_map(function($r) { return round($r['oee'], 2); }, $oeeHistorico));
} else {
    $datesOEEJson = json_encode([date('d/m')]);
    $valoresOEEJson = json_encode([0]);
}
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
                <a href="/dashboard/oee.php" class="nav-item active"><i class="fas fa-star"></i><span>OEE Análise</span></a>
                <a href="/dashboard/paradas.php" class="nav-item"><i class="fas fa-pause-circle"></i><span>Paradas</span></a>
                <a href="/dashboard/velocidade.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Velocidade</span></a>
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
                        <h1><i class="fas fa-star"></i> Overall Equipment Effectiveness (OEE)</h1>
                        <p>Análise de eficiência operacional • Período: <strong><?php echo formatarData($filtro->getFiltros()['data_inicio']); ?></strong> a <strong><?php echo formatarData($filtro->getFiltros()['data_fim']); ?></strong></p>
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
                
                <!-- OEE HOJE -->
                <div class="kpi-grid" style="margin-bottom: 2rem;">
                    <div class="kpi-card" style="grid-column: 1/-1; text-align: center; padding: 2.5rem;">
                        <div style="font-size: 1.2rem; margin-bottom: 1rem;">OEE DO PERÍODO</div>
                        <div style="font-size: 4rem; font-weight: 700; color: <?php echo $corStatus; ?>; margin: 1rem 0;">
                            <?php echo number_format($oee, 1); ?>%
                        </div>
                        <div style="font-size: 1.1rem; color: #e2e8f0;">
                            Status: <strong style="color: <?php echo $corStatus; ?>;"><?php echo $statusOEE; ?></strong>
                        </div>
                    </div>
                </div>

                <!-- COMPONENTES DO OEE -->
                <div class="kpi-grid" style="margin-bottom: 2rem;">
                    <div class="kpi-card">
                        <div style="text-align: center;">
                            <div class="kpi-icon" style="color: #1e40af;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="kpi-label">Disponibilidade</div>
                            <div class="kpi-value" style="color: #1e40af;">
                                <?php echo formatarPercentual($disponibilidade, 1); ?>
                            </div>
                            <div class="kpi-detail">
                                <?php echo $tempoProduzindo; ?> de <?php echo $tempoJornada; ?> min
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div style="text-align: center;">
                            <div class="kpi-icon" style="color: #ea580c;">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <div class="kpi-label">Performance</div>
                            <div class="kpi-value" style="color: #ea580c;">
                                <?php echo formatarPercentual($performance, 1); ?>
                            </div>
                            <div class="kpi-detail">
                                <?php echo formatarNumero($producaoReal, 0); ?> / <?php echo formatarNumero($producaoPlanejada, 0); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div style="text-align: center;">
                            <div class="kpi-icon" style="color: #16a34a;">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="kpi-label">Qualidade</div>
                            <div class="kpi-value" style="color: #16a34a;">
                                <?php echo formatarPercentual($qualidade, 1); ?>
                            </div>
                            <div class="kpi-detail">
                                <?php echo formatarNumero($producaoBoa, 0); ?> / <?php echo formatarNumero($producaoTotal, 0); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GRÁFICO TENDÊNCIA OEE -->
                <div class="charts-grid" style="margin-bottom: 2rem;">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> Tendência OEE (Últimos 30 dias)
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartOEE"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HISTÓRICO DETALHADO -->
                <div class="card" style="grid-column: 1/-1;">
                    <div class="card-header">
                        <i class="fas fa-table"></i> Histórico OEE
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; color: #e2e8f0;">
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,.1);">
                                        <th style="padding: 0.75rem; text-align: left;">Data</th>
                                        <th style="padding: 0.75rem; text-align: center;">Disponibilidade</th>
                                        <th style="padding: 0.75rem; text-align: center;">Performance</th>
                                        <th style="padding: 0.75rem; text-align: center;">Qualidade</th>
                                        <th style="padding: 0.75rem; text-align: center;">OEE</th>
                                        <th style="padding: 0.75rem; text-align: center;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($oeeHistorico)): ?>
                                        <?php foreach ($oeeHistorico as $registro): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,.05);">
                                            <td style="padding: 0.75rem;"><?php echo formatarData($registro['data']); ?></td>
                                            <td style="padding: 0.75rem; text-align: center;"><?php echo formatarPercentual($registro['disponibilidade'], 1); ?></td>
                                            <td style="padding: 0.75rem; text-align: center;"><?php echo formatarPercentual($registro['performance'], 1); ?></td>
                                            <td style="padding: 0.75rem; text-align: center;"><?php echo formatarPercentual($registro['qualidade'], 1); ?></td>
                                            <td style="padding: 0.75rem; text-align: center; font-weight: bold;">
                                                <?php echo formatarPercentual($registro['oee'], 1); ?>
                                            </td>
                                            <td style="padding: 0.75rem; text-align: center;">
                                                <?php $oee_val = $registro['oee']; ?>
                                                <?php if ($oee_val >= 85): ?>
                                                    <span style="display: inline-block; background-color: #10b98133; color: #10b981; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Excelente</span>
                                                <?php elseif ($oee_val >= 70): ?>
                                                    <span style="display: inline-block; background-color: #f59e0b33; color: #f59e0b; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Bom</span>
                                                <?php else: ?>
                                                    <span style="display: inline-block; background-color: #ef444433; color: #ef4444; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Crítico</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="padding: 1rem; text-align: center; color: #94a3b8;">
                                                Sem dados de OEE nos últimos 30 dias
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><!-- Fecha content-scroll -->
        </div><!-- Fecha main-content -->
    </div><!-- Fecha dashboard-container -->

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

// Seletor de data
document.getElementById('dataSelecionada').addEventListener('change', function() {
    const data = this.value;
    window.location.href = '?data=' + data;
});

// Gráfico OEE
const datesOEE = <?php echo $datesOEEJson; ?>;
const valoresOEE = <?php echo $valoresOEEJson; ?>;

const ctxOEE = document.getElementById('chartOEE').getContext('2d');
new Chart(ctxOEE, {
    type: 'line',
    data: {
        labels: datesOEE,
        datasets: [{
            label: 'OEE (%)',
            data: valoresOEE,
            borderColor: '#7c3aed',
            backgroundColor: 'rgba(124, 58, 237, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#7c3aed',
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
                max: 100,
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
</script>

</body>
</html>

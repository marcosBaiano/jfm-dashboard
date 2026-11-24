<?php
/**
 * ======================================================
 * PÁGINA DE PARADAS (v3.0)
 * ======================================================
 * Visualizar paradas registradas com filtros avançados
 * Refatorado para usar FiltroGlobal e QueryBuilder
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';
require_once '../includes/Filtros.php';
require_once '../includes/QueryBuilder.php';

$titulo = 'Paradas - Sistema Industrial JFM';

// Inicializar filtro
$filtro = new FiltroGlobal($pdo);
$filtro->aplicarFiltros($_GET);

$paradas = [];
$totalParadas = 0;
$tempoTotalParado = 0;
$tempoMedioParado = 0;

try {
    // Obter dados de paradas usando QueryBuilder
    $builder = new QueryBuilder($pdo, $filtro);
    
    // Montar query de paradas detalhadas
    $where = $filtro->getWhereClauses('r', 'p');
    
    $sql = "
        SELECT 
            p.id,
            p.inicio,
            p.fim,
            p.codigo,
            p.descricao,
            COALESCE(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim), 0) as tempo_parado,
            l.linha,
            r.operador,
            s.nome as setor,
            m.nome as maquina,
            gp.nome as grupo_parada,
            mp.motivo as motivo_parada
        FROM paradas p
        INNER JOIN registros r ON p.registro_id = r.id
        LEFT JOIN linhas l ON r.linha_id = l.id
        LEFT JOIN setores s ON r.setor_id = s.id
        LEFT JOIN maquinas m ON r.maquina_id = m.id
        LEFT JOIN grupos_parada gp ON p.grupo_parada_id = gp.id
        LEFT JOIN motivos_parada mp ON p.motivo_parada_id = mp.id
        WHERE $where
        ORDER BY p.inicio DESC
        LIMIT 1000
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $paradas = $stmt->fetchAll();
    
    // Calcular estatísticas
    $totalParadas = count($paradas);
    $tempoTotalParado = array_sum(array_column($paradas, 'tempo_parado'));
    $tempoMedioParado = $totalParadas > 0 ? round($tempoTotalParado / $totalParadas, 2) : 0;
    
} catch (Exception $e) {
    error_log('Erro ao carregar paradas: ' . $e->getMessage());
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
                <a href="/dashboard/paradas.php" class="nav-item active"><i class="fas fa-pause-circle"></i><span>Paradas</span></a>
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
                        <h1><i class="fas fa-pause-circle"></i> Paradas Registradas</h1>
                        <p>Análise detalhada de paradas de produção • Período: <strong><?php echo formatarData($filtro->getFiltros()['data_inicio']); ?></strong> a <strong><?php echo formatarData($filtro->getFiltros()['data_fim']); ?></strong></p>
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
                <!-- KPI PARADAS -->
                <div class="kpi-grid" style="margin-bottom: 2rem;">
                    <div class="kpi-card" style="grid-column: 1/-1; text-align: center; padding: 2rem;">
                        <div style="font-size: 1.2rem; margin-bottom: 1rem;">Tempo Total Parado</div>
                        <div style="font-size: 3.5rem; font-weight: 700; color: #ef4444; margin: 1rem 0;">
                            <?php echo $tempoTotalParado; ?> <span style="font-size: 1.2rem; color: #94a3b8;">min</span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; max-width: 400px; margin-left: auto; margin-right: auto; font-size: 0.9rem; color: #94a3b8;">
                            <div><strong><?php echo $totalParadas; ?></strong> parada<?php echo $totalParadas !== 1 ? 's' : ''; ?></div>
                            <div><strong><?php echo formatarNumero($tempoMedioParado, 2); ?></strong> min (média)</div>
                        </div>
                    </div>
                </div>

                <!-- TABELA DE PARADAS -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list"></i> Paradas do Período
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; color: #e2e8f0;">
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,.1);">
                                        <th style="padding: 0.75rem; text-align: left;">Horário</th>
                                        <th style="padding: 0.75rem; text-align: left;">Código</th>
                                        <th style="padding: 0.75rem; text-align: left;">Descrição</th>
                                        <th style="padding: 0.75rem; text-align: left;">Motivo</th>
                                        <th style="padding: 0.75rem; text-align: center;">Tempo (min)</th>
                                        <th style="padding: 0.75rem; text-align: left;">Setor</th>
                                        <th style="padding: 0.75rem; text-align: left;">Máquina</th>
                                        <th style="padding: 0.75rem; text-align: left;">Operador</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($paradas)): ?>
                                        <?php foreach ($paradas as $parada): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,.05);">
                                            <td style="padding: 0.75rem;">
                                                <?php echo date('H:i', strtotime($parada['inicio'])); ?> - <?php echo date('H:i', strtotime($parada['fim'])); ?>
                                            </td>
                                            <td style="padding: 0.75rem; font-weight: bold; color: #3b82f6;">
                                                <?php echo htmlspecialchars($parada['codigo'] ?? '-'); ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php echo htmlspecialchars($parada['descricao'] ?? '-'); ?>
                                            </td>
                                            <td style="padding: 0.75rem; font-size: 0.9rem; color: #cbd5e1;">
                                                <small><?php echo htmlspecialchars($parada['motivo_parada'] ?? $parada['grupo_parada'] ?? '-'); ?></small>
                                            </td>
                                            <td style="padding: 0.75rem; text-align: center; font-weight: bold; color: #ef4444;">
                                                <?php echo $parada['tempo_parado']; ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php echo htmlspecialchars($parada['setor'] ?? '-'); ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php echo htmlspecialchars($parada['maquina'] ?? '-'); ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php echo htmlspecialchars($parada['operador'] ?? '-'); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" style="padding: 1rem; text-align: center; color: #94a3b8;">
                                                Nenhuma parada registrada para o período selecionado
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

// Seletor de data
document.getElementById('dataSelecionada').addEventListener('change', function() {
    const data = this.value;
    window.location.href = '?data=' + data;
});
</script>

</body>
</html>

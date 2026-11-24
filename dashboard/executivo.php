<?php
/**
 * =====================================================
 * DASHBOARD EXECUTIVO - Sistema Industrial JFM v3.0
 * =====================================================
 * Dashboard gerencial completo com análise de KPIs,
 * rankings, Pareto e drill-down interativo
 * 
 * Data: 2026-06-21
 * Autor: Equipe de Desenvolvimento
 */

// =====================================================
// INICIALIZAÇÃO
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';
require_once '../includes/Filtros.php';
require_once '../includes/QueryBuilder.php';

$titulo = 'Dashboard Executivo';

// Inicializar filtro
$filtro = new FiltroGlobal($pdo);
$filtro->aplicarFiltros($_GET);

// Inicializar query builder
$builder = new QueryBuilder($pdo, $filtro);

// Obter dados
$kpis = $builder->getKPIsGerais();
$topMotivos = $builder->getTopMotivos(10);
$topMaquinas = $builder->getTopMaquinas(10);
$topSetores = $builder->getTopSetores(10);
$topCodigos = $builder->getTopCodigos(10);
$pareto = $builder->getPareto();

// Preparar dados para gráficos
$paretoMotivos = array_map(function($item) { return $item['motivo'] ?? 'Sem motivo'; }, $pareto);
$paretoTempos = array_map(function($item) { return (float)$item['tempo_total']; }, $pareto);
$paretoPercentuais = array_map(function($item) { return (float)$item['percentual_acumulado']; }, $pareto);

$paretoMotivosJson = json_encode($paretoMotivos);
$paretoTemposJson = json_encode($paretoTempos);
$paretoPercentuaisJson = json_encode($paretoPercentuais);

// Verificar se há dados
$temDados = !empty($kpis['registros_processados']);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($titulo); ?> - Sistema JFM</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <!-- CSS Customizado -->
    <link href="../assets/css/main.css" rel="stylesheet">
    
    <style>
        :root {
            --color-primary: #3b82f6;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --color-info: #06b6d4;
            --bg-dark: #0f172a;
            --bg-darker: #0d1117;
            --text-light: #e2e8f0;
            --text-muted: #94a3b8;
            --border-color: #475569;
        }
        
        body {
            background: var(--bg-dark);
            color: var(--text-light);
        }
        
        .kpi-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .kpi-card:hover {
            transform: translateY(-2px);
            border-color: var(--color-primary);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.1);
        }
        
        .kpi-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }
        
        .kpi-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-light);
            margin: 0.5rem 0;
        }
        
        .kpi-icon {
            font-size: 2rem;
            opacity: 0.7;
            margin-bottom: 1rem;
        }
        
        .card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-light);
            padding: 1rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .table {
            color: var(--text-light);
        }
        
        .table thead th {
            border-bottom-color: var(--border-color);
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .table tbody tr {
            border-bottom-color: rgba(71, 85, 105, 0.3);
        }
        
        .table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        .table a {
            color: var(--color-primary);
            text-decoration: none;
        }
        
        .table a:hover {
            text-decoration: underline;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin: 1rem 0;
        }
        
        .badge-custom {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-critico {
            background-color: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        
        .badge-alto {
            background-color: rgba(245, 158, 11, 0.2);
            color: #fed7aa;
        }
        
        .badge-medio {
            background-color: rgba(59, 130, 246, 0.2);
            color: #bfdbfe;
        }
        
        .badge-baixo {
            background-color: rgba(16, 185, 129, 0.2);
            color: #a7f3d0;
        }
        
        .sem-dados {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }
        
        .sem-dados i {
            font-size: 3rem;
            opacity: 0.5;
            margin-bottom: 1rem;
        }
        
        .header-page {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .header-page h1 {
            margin: 0;
            color: var(--text-light);
            font-weight: 700;
        }
        
        .header-page small {
            color: var(--text-muted);
        }
    </style>
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
                <a href="/dashboard/index.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard Principal</span>
                </a>
                <a href="/dashboard/executivo.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>📊 Executivo (v3.0)</span>
                </a>
                <a href="/dashboard/oee.php" class="nav-item">
                    <i class="fas fa-star"></i>
                    <span>OEE Análise</span>
                </a>
                <a href="/dashboard/paradas.php" class="nav-item">
                    <i class="fas fa-pause-circle"></i>
                    <span>Paradas</span>
                </a>
                <a href="/dashboard/velocidade.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Velocidade</span>
                </a>
                <a href="/dashboard/eficiencia.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Eficiência</span>
                </a>
                
                <div class="nav-section">Sistema</div>
                <a href="/admin/usuarios.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Usuários</span>
                </a>
                <a href="/admin/auditoria.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>Auditoria</span>
                </a>
                <a href="/processos/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
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
            <!-- HEADER -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>
                        <i class="fas fa-chart-line" style="margin-right: 0.5rem;"></i> Dashboard Executivo
                    </h1>
                    <small style="color: #64748b;">
                        Análise gerencial completa • Período: <strong><?php echo formatarData($filtro->getFiltros()['data_inicio']); ?></strong> a <strong><?php echo formatarData($filtro->getFiltros()['data_fim']); ?></strong>
                    </small>
                </div>
                <div class="header-right">
                    <span class="sistema-status">
                        <i class="fas fa-circle" style="color: #10b981; margin-right: 0.5rem;"></i>
                        Sistema Ativo
                    </span>
                </div>
            </header>
    
            <!-- CONTEÚDO -->
            <div style="padding: 0 1.5rem; padding-bottom: 2rem;">
        
                <!-- FILTRO GLOBAL -->
                <?php include '../includes/filtro-componente.html'; ?>
        
                <!-- VERIFICAR SE HÁ DADOS -->
                <?php if (!$temDados): ?>
                    <div class="alert alert-info" style="background: rgba(6, 182, 212, 0.1); border-color: #06b6d4; color: #cffafe;">
                        <i class="fas fa-info-circle"></i> <strong>Nenhum dado encontrado</strong> para os filtros selecionados. Selecione um período ou filtro diferente.
                    </div>
                <?php else: ?>
        
                <!-- SEÇÃO: KPIs GERAIS (13 KPIs) -->
                <div style="margin-bottom: 3rem;">
                    <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; color: var(--text-light);">
                        <i class="fas fa-tachometer-alt" style="margin-right: 0.75rem;"></i> KPIs Gerais
                    </h2>
                    
                    <div class="row g-3">
                        <!-- OEE Geral -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #3b82f6;">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="kpi-label">OEE Geral</div>
                        <div class="kpi-value"><?php echo formatarPercentual($kpis['oee_geral'], 1); ?></div>
                        <small style="color: var(--text-muted);">Overall Equipment Effectiveness</small>
                    </div>
                </div>
                
                <!-- Disponibilidade -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #10b981;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="kpi-label">Disponibilidade</div>
                        <div class="kpi-value"><?php echo formatarPercentual($kpis['disponibilidade'], 1); ?></div>
                        <small style="color: var(--text-muted);">% tempo operacional</small>
                    </div>
                </div>
                
                <!-- Performance -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #f59e0b;">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="kpi-label">Performance</div>
                        <div class="kpi-value"><?php echo formatarPercentual($kpis['performance'], 1); ?></div>
                        <small style="color: var(--text-muted);">Velocidade atingida</small>
                    </div>
                </div>
                
                <!-- Qualidade -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #06b6d4;">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="kpi-label">Qualidade</div>
                        <div class="kpi-value"><?php echo formatarPercentual($kpis['qualidade'], 1); ?></div>
                        <small style="color: var(--text-muted);">Peças boas</small>
                    </div>
                </div>
                
                <!-- Eficiência Média -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #8b5cf6;">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="kpi-label">Eficiência Média</div>
                        <div class="kpi-value"><?php echo formatarPercentual($kpis['eficiencia_media'], 1); ?></div>
                        <small style="color: var(--text-muted);">Velocidade média</small>
                    </div>
                </div>
                
                <!-- Produção Total -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #ec4899;">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="kpi-label">Produção Total</div>
                        <div class="kpi-value"><?php echo formatarNumero($kpis['producao_total']); ?></div>
                        <small style="color: var(--text-muted);">unidades</small>
                    </div>
                </div>
            </div>
            
            <!-- Segunda linha de KPIs -->
            <div class="row g-3" style="margin-top: 1rem;">
                <!-- Produção Boa -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #10b981;">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="kpi-label">Produção Boa</div>
                        <div class="kpi-value"><?php echo formatarNumero($kpis['producao_boa']); ?></div>
                        <small style="color: var(--text-muted);">sem defeitos</small>
                    </div>
                </div>
                
                <!-- Perdas -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #ef4444;">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="kpi-label">Perdas Totais</div>
                        <div class="kpi-value"><?php echo formatarNumero($kpis['perdas_totais']); ?></div>
                        <small style="color: var(--text-muted);">unidades</small>
                    </div>
                </div>
                
                <!-- Tempo Parado -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #ef4444;">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                        <div class="kpi-label">Tempo Parado</div>
                        <div class="kpi-value"><?php echo $kpis['tempo_total_parado']; ?> <span style="font-size: 0.9rem;">min</span></div>
                        <small style="color: var(--text-muted);">minutos</small>
                    </div>
                </div>
                
                <!-- Quantidade de Paradas -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #f59e0b;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="kpi-label">Quantidade Paradas</div>
                        <div class="kpi-value"><?php echo $kpis['quantidade_paradas']; ?></div>
                        <small style="color: var(--text-muted);">ocorrências</small>
                    </div>
                </div>
                
                <!-- Linhas Ativas -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #06b6d4;">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="kpi-label">Linhas Ativas</div>
                        <div class="kpi-value"><?php echo $kpis['linhas_ativas']; ?></div>
                        <small style="color: var(--text-muted);">linhas</small>
                    </div>
                </div>
                
                <!-- Máquinas Ativas -->
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #8b5cf6;">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <div class="kpi-label">Máquinas Ativas</div>
                        <div class="kpi-value"><?php echo $kpis['maquinas_ativas']; ?></div>
                        <small style="color: var(--text-muted);">máquinas</small>
                    </div>
                </div>
            </div>
            
            <!-- Terceira linha - Registros -->
            <div class="row g-3" style="margin-top: 1rem;">
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="color: #3b82f6;">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="kpi-label">Registros</div>
                        <div class="kpi-value"><?php echo $kpis['registros_processados']; ?></div>
                        <small style="color: var(--text-muted);">processados</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SEÇÃO: PARETO -->
        <div class="card" style="margin-bottom: 3rem;">
            <div class="card-header">
                <i class="fas fa-chart-line" style="margin-right: 0.75rem;"></i> Pareto de Paradas (80/20)
                <small style="color: var(--text-muted); float: right;">Os 80% dos problemas vêm de 20% das causas</small>
            </div>
            <div class="card-body">
                <?php if (empty($pareto)): ?>
                    <div class="sem-dados">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhuma parada encontrada neste período</p>
                    </div>
                <?php else: ?>
                    <div class="chart-container">
                        <canvas id="chartPareto"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- SEÇÃO: RANKINGS -->
        <div class="row g-3" style="margin-bottom: 3rem;">
            
            <!-- Top 10 Motivos -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list" style="margin-right: 0.75rem;"></i> Top 10 Motivos de Parada
                    </div>
                    <div class="card-body">
                        <?php if (empty($topMotivos)): ?>
                            <div class="sem-dados">
                                <i class="fas fa-inbox"></i>
                                <p>Nenhuma parada encontrada</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="table table-sm" style="margin-bottom: 0;">
                                    <thead>
                                        <tr>
                                            <th>Motivo</th>
                                            <th style="text-align: center;">Tempo (min)</th>
                                            <th style="text-align: center;">Qtd</th>
                                            <th style="text-align: center;">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_tempo = array_sum(array_column($topMotivos, 'tempo_total'));
                                        foreach ($topMotivos as $idx => $motivo):
                                            $percentual = $total_tempo > 0 ? ($motivo['tempo_total'] / $total_tempo * 100) : 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <a href="?motivo_parada_id=<?php echo $motivo['id']; ?>&<?php echo $filtro->getUrlParams(); ?>">
                                                        <strong><?php echo htmlspecialchars($motivo['motivo']); ?></strong>
                                                        <br>
                                                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($motivo['grupo']); ?></small>
                                                    </a>
                                                </td>
                                                <td style="text-align: center;"><strong><?php echo formatarNumero($motivo['tempo_total']); ?></strong></td>
                                                <td style="text-align: center;"><?php echo $motivo['quantidade']; ?>x</td>
                                                <td style="text-align: center;">
                                                    <span class="badge-custom badge-<?php echo $percentual > 20 ? 'critico' : ($percentual > 10 ? 'alto' : 'medio'); ?>">
                                                        <?php echo formatarPercentual($percentual, 1); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Top 10 Máquinas -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-cogs" style="margin-right: 0.75rem;"></i> Top 10 Máquinas com Mais Paradas
                    </div>
                    <div class="card-body">
                        <?php if (empty($topMaquinas)): ?>
                            <div class="sem-dados">
                                <i class="fas fa-inbox"></i>
                                <p>Nenhuma máquina com paradas</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="table table-sm" style="margin-bottom: 0;">
                                    <thead>
                                        <tr>
                                            <th>Máquina</th>
                                            <th style="text-align: center;">Tempo (min)</th>
                                            <th style="text-align: center;">Paradas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topMaquinas as $maquina): ?>
                                            <tr>
                                                <td>
                                                    <a href="?maquina_id=<?php echo $maquina['id']; ?>&<?php echo $filtro->getUrlParams(); ?>">
                                                        <strong><?php echo htmlspecialchars($maquina['nome']); ?></strong>
                                                        <br>
                                                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($maquina['setor']); ?></small>
                                                    </a>
                                                </td>
                                                <td style="text-align: center;"><strong><?php echo formatarNumero($maquina['tempo_total']); ?></strong></td>
                                                <td style="text-align: center; background: rgba(59, 130, 246, 0.1); border-radius: 0.25rem;">
                                                    <span style="color: #3b82f6; font-weight: 600;"><?php echo $maquina['quantidade_paradas']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SEGUNDA LINHA DE RANKINGS -->
        <div class="row g-3" style="margin-bottom: 3rem;">
            
            <!-- Top 10 Setores -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-industry" style="margin-right: 0.75rem;"></i> Top 10 Setores com Mais Paradas
                    </div>
                    <div class="card-body">
                        <?php if (empty($topSetores)): ?>
                            <div class="sem-dados">
                                <i class="fas fa-inbox"></i>
                                <p>Nenhum setor com paradas</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="table table-sm" style="margin-bottom: 0;">
                                    <thead>
                                        <tr>
                                            <th>Setor</th>
                                            <th style="text-align: center;">Tempo (min)</th>
                                            <th style="text-align: center;">Paradas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topSetores as $setor): ?>
                                            <tr>
                                                <td>
                                                    <a href="?setor_id=<?php echo $setor['id']; ?>&<?php echo $filtro->getUrlParams(); ?>">
                                                        <strong><?php echo htmlspecialchars($setor['nome']); ?></strong>
                                                    </a>
                                                </td>
                                                <td style="text-align: center;"><strong><?php echo formatarNumero($setor['tempo_total']); ?></strong></td>
                                                <td style="text-align: center; background: rgba(239, 68, 68, 0.1); border-radius: 0.25rem;">
                                                    <span style="color: #ef4444; font-weight: 600;"><?php echo $setor['quantidade_paradas']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Top 10 Códigos -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-barcode" style="margin-right: 0.75rem;"></i> Top 10 Códigos de Parada
                    </div>
                    <div class="card-body">
                        <?php if (empty($topCodigos)): ?>
                            <div class="sem-dados">
                                <i class="fas fa-inbox"></i>
                                <p>Nenhum código registrado</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="table table-sm" style="margin-bottom: 0;">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Descrição</th>
                                            <th style="text-align: center;">Tempo (min)</th>
                                            <th style="text-align: center;">Qtd</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topCodigos as $codigo): ?>
                                            <tr>
                                                <td>
                                                    <strong style="color: var(--color-primary);">
                                                        <?php echo htmlspecialchars($codigo['codigo']); ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($codigo['descricao']); ?></small>
                                                </td>
                                                <td style="text-align: center; color: var(--text-muted);">
                                                    <?php echo formatarNumero($codigo['tempo_total']); ?>
                                                </td>
                                                <td style="text-align: center;">
                                                    <span style="background: rgba(16, 185, 129, 0.2); color: #86efac; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-weight: 600;">
                                                        <?php echo $codigo['quantidade']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($temDados && !empty($pareto)): ?>
<script>
// Gráfico Pareto
const motivos = <?php echo $paretoMotivosJson; ?>;
const tempos = <?php echo $paretoTemposJson; ?>;
const percentuais = <?php echo $paretoPercentuaisJson; ?>;

const ctx = document.getElementById('chartPareto').getContext('2d');

// Calcular cor para barras (vermelho se acumulado > 80%)
const cores = percentuais.map(p => p > 80 ? 'rgba(59, 130, 246, 0.7)' : 'rgba(16, 185, 129, 0.7)');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: motivos,
        datasets: [
            {
                label: 'Tempo Perdido (minutos)',
                data: tempos,
                backgroundColor: cores,
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'y'
            },
            {
                label: 'Percentual Acumulado (%)',
                data: percentuais,
                type: 'line',
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.3,
                pointRadius: 5,
                pointBackgroundColor: '#ef4444',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                labels: {
                    color: '#e2e8f0',
                    font: { size: 12 },
                    padding: 15
                }
            },
            tooltip: {
                backgroundColor: 'rgba(30, 41, 59, 0.9)',
                titleColor: '#e2e8f0',
                bodyColor: '#cbd5e1',
                borderColor: '#475569',
                borderWidth: 1
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                ticks: { color: '#94a3b8' },
                grid: { color: 'rgba(71, 85, 105, 0.2)', drawBorder: false },
                title: {
                    display: true,
                    text: 'Tempo Perdido (minutos)',
                    color: '#cbd5e1'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                ticks: { color: '#94a3b8' },
                grid: { drawOnChartArea: false },
                title: {
                    display: true,
                    text: 'Acumulado (%)',
                    color: '#cbd5e1'
                }
            },
            x: {
                ticks: { color: '#94a3b8' },
                grid: { color: 'rgba(71, 85, 105, 0.2)', drawBorder: false }
            }
        }
    }
});
</script>
<?php endif; ?>

            </div> <!-- fecha .main-content -->
        </div> <!-- fecha .dashboard-container -->
    </div>

</body>
</html>

<?php
/**
 * ======================================================
 * DASHBOARD PROFISSIONAL INDUSTRIAL - v2.0
 * ======================================================
 * Painel executivo e analítico para produção
 * Pronto para produção imediata
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';

$titulo = 'Dashboard Industrial - Sistema JFM';

// ======================================================
// FILTROS
// ======================================================
$periodo = $_GET['periodo'] ?? 'hoje';
$linhaFiltro = $_GET['linha'] ?? '';
$setorFiltro = $_GET['setor'] ?? '';
$turnoFiltro = $_GET['turno'] ?? '';

// Calcular datas
$dataInicio = date('Y-m-d');
$dataFim = date('Y-m-d');

if ($periodo === 'ontem') {
    $dataInicio = date('Y-m-d', strtotime('-1 day'));
    $dataFim = date('Y-m-d', strtotime('-1 day'));
} elseif ($periodo === '7dias') {
    $dataInicio = date('Y-m-d', strtotime('-7 days'));
} elseif ($periodo === '30dias') {
    $dataInicio = date('Y-m-d', strtotime('-30 days'));
} elseif ($periodo === 'customizado') {
    $dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
    $dataFim = $_GET['data_fim'] ?? date('Y-m-d');
}

try {
    // ======================================================
    // 1. DISPONIBILIDADE
    // ======================================================
    $tempoPadraoJornada = 16 * 60; // 960 minutos (16 horas)
    
    $sql = "
        SELECT 
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado_minutos
        FROM paradas p
        INNER JOIN registros r ON p.registro_id = r.id
        WHERE DATE(p.inicio) >= :data_inicio AND DATE(p.inicio) <= :data_fim
    ";
    
    $params = [':data_inicio' => $dataInicio, ':data_fim' => $dataFim];
    
    if (!empty($linhaFiltro)) {
        $sql .= " AND r.linha_id = :linha_id";
        $params[':linha_id'] = $linhaFiltro;
    }
    if (!empty($setorFiltro)) {
        $sql .= " AND l.setor = :setor";
        $params[':setor'] = $setorFiltro;
    }
    if (!empty($turnoFiltro)) {
        $sql .= " AND r.turno = :turno";
        $params[':turno'] = $turnoFiltro;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado_minutos
        FROM paradas p
        INNER JOIN registros r ON p.registro_id = r.id
        LEFT JOIN linhas l ON r.linha_id = l.id
        WHERE DATE(p.inicio) >= :data_inicio AND DATE(p.inicio) <= :data_fim
    " . (!empty($linhaFiltro) ? " AND r.linha_id = :linha_id" : "") .
        (!empty($setorFiltro) ? " AND l.setor = :setor" : "") .
        (!empty($turnoFiltro) ? " AND r.turno = :turno" : "")
    );
    
    $params = [':data_inicio' => $dataInicio, ':data_fim' => $dataFim];
    if (!empty($linhaFiltro)) $params[':linha_id'] = $linhaFiltro;
    if (!empty($setorFiltro)) $params[':setor'] = $setorFiltro;
    if (!empty($turnoFiltro)) $params[':turno'] = $turnoFiltro;
    
    $stmt->execute($params);
    $resultado = $stmt->fetch();
    $minutosParado = $resultado['tempo_parado_minutos'] ?? 0;
    $minutosProduzindo = ($tempoPadraoJornada * (($dataFim !== date('Y-m-d')) ? 1 : date('H') / 24)) - $minutosParado;
    $minutosProduzindo = max(0, $minutosProduzindo);
    
    $disponibilidade = calcularDisponibilidade($minutosProduzindo, $minutosParado);
    
    // ======================================================
    // 2. PRODUÇÃO REAL
    // ======================================================
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(p.producao), 0) as producao_real,
            COALESCE(SUM(p.perda_1 + p.perda_2 + p.perda_3 + p.perda_4), 0) as perdas_totais,
            COUNT(DISTINCT r.id) as registros
        FROM registros r
        LEFT JOIN producao p ON r.id = p.registro_id
        LEFT JOIN linhas l ON r.linha_id = l.id
        WHERE DATE(r.data_registro) >= :data_inicio AND DATE(r.data_registro) <= :data_fim
    " . (!empty($linhaFiltro) ? " AND r.linha_id = :linha_id" : "") .
        (!empty($setorFiltro) ? " AND l.setor = :setor" : "") .
        (!empty($turnoFiltro) ? " AND r.turno = :turno" : "")
    );
    
    $params = [':data_inicio' => $dataInicio, ':data_fim' => $dataFim];
    if (!empty($linhaFiltro)) $params[':linha_id'] = $linhaFiltro;
    if (!empty($setorFiltro)) $params[':setor'] = $setorFiltro;
    if (!empty($turnoFiltro)) $params[':turno'] = $turnoFiltro;
    
    $stmt->execute($params);
    $producao = $stmt->fetch();
    
    $producaoReal = $producao['producao_real'] ?? 0;
    $perdasTotais = $producao['perdas_totais'] ?? 0;
    $producaoTotal = $producaoReal + $perdasTotais;
    
    // ======================================================
    // 3. PERFORMANCE (usa planejado padrão)
    // ======================================================
    $producaoPlanejada = 400; // 400 unidades/dia (referência)
    $performance = calcularPerformance($producaoReal, $producaoPlanejada);
    
    // ======================================================
    // 4. QUALIDADE
    // ======================================================
    $producaoBoa = max(0, $producaoReal - $perdasTotais);
    $qualidade = calcularQualidade($producaoBoa, $producaoReal > 0 ? $producaoReal : 1);
    
    // ======================================================
    // 5. OEE
    // ======================================================
    $oee = calcularOEE($disponibilidade, $performance, $qualidade);
    
    // ======================================================
    // 6. VELOCIDADE MÉDIA
    // ======================================================
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(AVG(v.velocidade), 0) as velocidade_media,
            COALESCE(AVG(v.velocidade_planejada), 0) as velocidade_planejada_media
        FROM velocidades v
        INNER JOIN registros r ON v.registro_id = r.id
        LEFT JOIN linhas l ON r.linha_id = l.id
        WHERE DATE(r.data_registro) >= :data_inicio AND DATE(r.data_registro) <= :data_fim
    " . (!empty($linhaFiltro) ? " AND r.linha_id = :linha_id" : "") .
        (!empty($setorFiltro) ? " AND l.setor = :setor" : "") .
        (!empty($turnoFiltro) ? " AND r.turno = :turno" : "")
    );
    
    $stmt->execute($params);
    $velocidades = $stmt->fetch();
    $velocidadeMedia = $velocidades['velocidade_media'] ?? 0;
    $eficienciaMedia = ($velocidades['velocidade_planejada_media'] > 0) 
        ? ($velocidades['velocidade_media'] / $velocidades['velocidade_planejada_media']) * 100 
        : 0;
    
    // ======================================================
    // 7. TOP 5 PIORES LINHAS
    // ======================================================
    $stmt = $pdo->prepare("
        SELECT 
            l.id, l.linha, l.setor,
            COUNT(p.id) as quantidade_paradas,
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado
        FROM paradas p
        INNER JOIN registros r ON p.registro_id = r.id
        INNER JOIN linhas l ON r.linha_id = l.id
        WHERE DATE(p.inicio) >= :data_inicio AND DATE(p.inicio) <= :data_fim
        GROUP BY l.id, l.linha, l.setor
        ORDER BY tempo_parado DESC
        LIMIT 5
    ");
    $stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataFim]);
    $pioresLinhas = $stmt->fetchAll();
    
    // ======================================================
    // 8. PARETO DE PARADAS (TOP 5 MOTIVOS)
    // ======================================================
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(d.tipo, 'Não Informado') as tipo,
            COUNT(p.id) as quantidade,
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total
        FROM paradas p
        LEFT JOIN descricoes d ON p.codigo = d.codigo
        WHERE DATE(p.inicio) >= :data_inicio AND DATE(p.inicio) <= :data_fim
        GROUP BY COALESCE(d.tipo, 'Não Informado')
        ORDER BY tempo_total DESC
        LIMIT 5
    ");
    $stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataFim]);
    $paretoParadas = $stmt->fetchAll();
    
    // ======================================================
    // 9. PRODUÇÃO POR LINHA (TOP 5)
    // ======================================================
    $stmt = $pdo->prepare("
        SELECT 
            l.id, l.linha, l.setor,
            COALESCE(SUM(p.producao), 0) as producao
        FROM registros r
        INNER JOIN linhas l ON r.linha_id = l.id
        LEFT JOIN producao p ON r.id = p.registro_id
        WHERE DATE(r.data_registro) >= :data_inicio AND DATE(r.data_registro) <= :data_fim
        GROUP BY l.id, l.linha, l.setor
        ORDER BY producao DESC
        LIMIT 5
    ");
    $stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataFim]);
    $producaoPorLinha = $stmt->fetchAll();
    
    // ======================================================
    // 10. PRODUÇÃO POR TURNO
    // ======================================================
    $stmt = $pdo->prepare("
        SELECT 
            r.turno,
            COALESCE(SUM(p.producao), 0) as producao,
            COUNT(DISTINCT r.id) as registros
        FROM registros r
        LEFT JOIN producao p ON r.id = p.registro_id
        WHERE DATE(r.data_registro) >= :data_inicio AND DATE(r.data_registro) <= :data_fim
        GROUP BY r.turno
        ORDER BY r.turno
    ");
    $stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataFim]);
    $producaoPorTurno = $stmt->fetchAll();
    
    // ======================================================
    // 11. EVOLUÇÃO OEE (ÚLTIMOS 30 DIAS)
    // ======================================================
    $stmt = $pdo->prepare("
        SELECT 
            DATE(r.data_registro) as data,
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado,
            COALESCE(SUM(prod.producao), 0) as producao,
            COALESCE(SUM(prod.perda_1 + prod.perda_2 + prod.perda_3 + prod.perda_4), 0) as perdas
        FROM registros r
        LEFT JOIN paradas p ON r.id = p.registro_id
        LEFT JOIN producao prod ON r.id = prod.registro_id
        WHERE DATE(r.data_registro) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(r.data_registro)
        ORDER BY DATE(r.data_registro) DESC
    ");
    $stmt->execute();
    $evolucaoOEE = $stmt->fetchAll();
    
    // ======================================================
    // 12. LINHAS ATIVAS
    // ======================================================
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT linha_id) as linhas_ativas
        FROM registros
        WHERE DATE(data_registro) >= :data_inicio AND DATE(data_registro) <= :data_fim
    ");
    $stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataFim]);
    $linhasAtivas = $stmt->fetch()['linhas_ativas'] ?? 0;
    
    // ======================================================
    // STATUS OEE
    // ======================================================
    if ($oee >= 85) {
        $statusOEE = 'Excelente';
        $corOEE = '#10b981';
        $iconeOEE = 'fa-check-circle';
    } elseif ($oee >= 70) {
        $statusOEE = 'Bom';
        $corOEE = '#f59e0b';
        $iconeOEE = 'fa-exclamation-circle';
    } else {
        $statusOEE = 'Crítico';
        $corOEE = '#ef4444';
        $iconeOEE = 'fa-times-circle';
    }
    
} catch (Exception $e) {
    error_log('Erro ao carregar dashboard: ' . $e->getMessage());
    die('Erro ao carregar dados: ' . $e->getMessage());
}

?>

<?php include '../includes/header.php'; ?>

<style>
.kpi-card {
    background: rgba(15, 23, 42, .85);
    border: 1px solid rgba(255, 255, 255, .06);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.kpi-card:hover {
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.2);
    border-color: rgba(37, 99, 235, 0.3);
}

.kpi-valor {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0.5rem 0;
}

.kpi-label {
    font-size: 0.9rem;
    color: #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.kpi-icone {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.status-excelente {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.status-bom {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
}

.status-critico {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 1rem;
}
</style>

<!-- HEADER -->
<div class="row mb-4">
    <div class="col-md-12">
        <h1 style="color: #e2e8f0; margin-bottom: 1rem;">
            <i class="fas fa-tachometer-alt"></i> Dashboard Industrial
        </h1>
    </div>
</div>

<!-- FILTROS -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-auto">
                <select name="periodo" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="hoje" <?php echo ($periodo === 'hoje') ? 'selected' : ''; ?>>Hoje</option>
                    <option value="ontem" <?php echo ($periodo === 'ontem') ? 'selected' : ''; ?>>Ontem</option>
                    <option value="7dias" <?php echo ($periodo === '7dias') ? 'selected' : ''; ?>>Últimos 7 dias</option>
                    <option value="30dias" <?php echo ($periodo === '30dias') ? 'selected' : ''; ?>>Últimos 30 dias</option>
                    <option value="customizado" <?php echo ($periodo === 'customizado') ? 'selected' : ''; ?>>Período Customizado</option>
                </select>
            </div>
            
            <div class="col-auto">
                <select name="setor" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos os Setores</option>
                    <option value="PO" <?php echo ($setorFiltro === 'PO') ? 'selected' : ''; ?>>PO</option>
                    <option value="LIQ" <?php echo ($setorFiltro === 'LIQ') ? 'selected' : ''; ?>>LIQ</option>
                    <option value="BAR" <?php echo ($setorFiltro === 'BAR') ? 'selected' : ''; ?>>BARRA</option>
                </select>
            </div>
            
            <div class="col-auto">
                <select name="turno" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos os Turnos</option>
                    <option value="1" <?php echo ($turnoFiltro === '1') ? 'selected' : ''; ?>>Turno 1</option>
                    <option value="2" <?php echo ($turnoFiltro === '2') ? 'selected' : ''; ?>>Turno 2</option>
                    <option value="3" <?php echo ($turnoFiltro === '3') ? 'selected' : ''; ?>>Turno 3</option>
                </select>
            </div>
            
            <div class="col-auto">
                <span class="text-muted">
                    <?php 
                    if ($dataInicio !== $dataFim) {
                        echo "Período: " . formatarData($dataInicio) . " a " . formatarData($dataFim);
                    } else {
                        echo "Data: " . formatarData($dataInicio);
                    }
                    ?>
                </span>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     LINHA 1: KPIs EXECUTIVOS
     ========================================================= -->
<div class="row">
    <!-- OEE GERAL -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: <?php echo $corOEE; ?>;">
                <i class="fas <?php echo $iconeOEE; ?>"></i>
            </div>
            <div class="kpi-label">OEE Geral</div>
            <div class="kpi-valor" style="color: <?php echo $corOEE; ?>;">
                <?php echo formatarNumero($oee, 1); ?>%
            </div>
            <div class="status-badge status-<?php echo strtolower(str_replace('ç', 'c', str_replace('á', 'a', $statusOEE))); ?>" style="background-color: <?php echo $corOEE; ?>33; color: <?php echo $corOEE; ?>;">
                <?php echo $statusOEE; ?>
            </div>
        </div>
    </div>
    
    <!-- DISPONIBILIDADE -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #3b82f6;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="kpi-label">Disponibilidade</div>
            <div class="kpi-valor" style="color: #3b82f6;">
                <?php echo formatarNumero($disponibilidade, 1); ?>%
            </div>
            <small style="color: #94a3b8;"><?php echo $minutosProduzindo; ?> / <?php echo $tempoPadraoJornada; ?> min</small>
        </div>
    </div>
    
    <!-- PERFORMANCE -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #8b5cf6;">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <div class="kpi-label">Performance</div>
            <div class="kpi-valor" style="color: #8b5cf6;">
                <?php echo formatarNumero($performance, 1); ?>%
            </div>
            <small style="color: #94a3b8;"><?php echo (int)$producaoReal; ?> / <?php echo (int)$producaoPlanejada; ?> un</small>
        </div>
    </div>
    
    <!-- QUALIDADE -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #ec4899;">
                <i class="fas fa-check"></i>
            </div>
            <div class="kpi-label">Qualidade</div>
            <div class="kpi-valor" style="color: #ec4899;">
                <?php echo formatarNumero($qualidade, 1); ?>%
            </div>
            <small style="color: #94a3b8;"><?php echo (int)$producaoBoa; ?> / <?php echo (int)$producaoReal; ?> un</small>
        </div>
    </div>
    
    <!-- PRODUÇÃO REAL -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #10b981;">
                <i class="fas fa-box"></i>
            </div>
            <div class="kpi-label">Produção Real</div>
            <div class="kpi-valor" style="color: #10b981;">
                <?php echo formatarNumero($producaoReal, 0); ?>
            </div>
            <small style="color: #94a3b8;">unidades</small>
        </div>
    </div>
    
    <!-- LINHAS ATIVAS -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #f59e0b;">
                <i class="fas fa-cogs"></i>
            </div>
            <div class="kpi-label">Linhas Ativas</div>
            <div class="kpi-valor" style="color: #f59e0b;">
                <?php echo $linhasAtivas; ?>
            </div>
            <small style="color: #94a3b8;">em operação</small>
        </div>
    </div>
</div>

<!-- =========================================================
     LINHA 2: DADOS ADICIONAIS
     ========================================================= -->
<div class="row mt-4">
    <!-- TEMPO PARADO -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #ef4444;">
                <i class="fas fa-pause"></i>
            </div>
            <div class="kpi-label">Tempo Parado</div>
            <div class="kpi-valor" style="color: #ef4444;">
                <?php echo formatarNumero($minutosParado / 60, 1); ?>h
            </div>
            <small style="color: #94a3b8;"><?php echo (int)$minutosParado; ?> minutos</small>
        </div>
    </div>
    
    <!-- VELOCIDADE MÉDIA -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #06b6d4;">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="kpi-label">Velocidade Média</div>
            <div class="kpi-valor" style="color: #06b6d4;">
                <?php echo formatarNumero($velocidadeMedia, 1); ?>
            </div>
            <small style="color: #94a3b8;">un/min</small>
        </div>
    </div>
    
    <!-- EFICIÊNCIA MÉDIA -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #14b8a6;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="kpi-label">Eficiência Média</div>
            <div class="kpi-valor" style="color: #14b8a6;">
                <?php echo formatarNumero($eficienciaMedia, 1); ?>%
            </div>
            <small style="color: #94a3b8;">real vs planejado</small>
        </div>
    </div>
    
    <!-- PERDAS TOTAIS -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #f97316;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="kpi-label">Perdas Totais</div>
            <div class="kpi-valor" style="color: #f97316;">
                <?php echo formatarNumero($perdasTotais, 0); ?>
            </div>
            <small style="color: #94a3b8;">unidades</small>
        </div>
    </div>
    
    <!-- TAXA QUALIDADE -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #22c55e;">
                <i class="fas fa-star"></i>
            </div>
            <div class="kpi-label">Taxa Qualidade</div>
            <div class="kpi-valor" style="color: #22c55e;">
                <?php echo formatarNumero((($producaoReal - $perdasTotais) / max($producaoReal, 1)) * 100, 1); ?>%
            </div>
            <small style="color: #94a3b8;">bom vs total</small>
        </div>
    </div>
    
    <!-- REGISTROS -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-icone" style="color: #a78bfa;">
                <i class="fas fa-list"></i>
            </div>
            <div class="kpi-label">Registros</div>
            <div class="kpi-valor" style="color: #a78bfa;">
                <?php echo $producao['registros'] ?? 0; ?>
            </div>
            <small style="color: #94a3b8;">apontamentos</small>
        </div>
    </div>
</div>

<!-- =========================================================
     LINHA 3: GRÁFICOS PRINCIPAIS
     ========================================================= -->
<div class="row mt-4">
    <!-- GRÁFICO: OEE 30 DIAS -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Evolução OEE - Últimos 30 Dias
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartOEE30"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- GRÁFICO: TOP 5 PIORES LINHAS -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Top 5 Piores Linhas
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartPioresLinhas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     LINHA 4: ANÁLISE DE PERDAS
     ========================================================= -->
<div class="row mt-4">
    <!-- GRÁFICO: PARETO -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Pareto de Paradas
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartPareto"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- GRÁFICO: TOP 5 MOTIVOS -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bars"></i> Top 5 Motivos de Parada
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartMotivos"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     LINHA 5: DISTRIBUIÇÃO
     ========================================================= -->
<div class="row mt-4">
    <!-- GRÁFICO: PRODUÇÃO POR LINHA -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cogs"></i> Produção por Linha
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartProducaoLinha"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- GRÁFICO: PRODUÇÃO POR TURNO -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clock"></i> Produção por Turno
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartProducaoTurno"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- SCRIPTS DE GRÁFICOS -->
<script>
// Dados para gráficos (JSON)
const chartColors = {
    primary: '#2563eb',
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#06b6d4'
};

// =========================================================
// GRÁFICO 1: OEE 30 DIAS
// =========================================================
<?php 
$datasOEE = [];
$valoresOEE = [];
foreach (array_reverse($evolucaoOEE) as $item) {
    $datasOEE[] = "'" . formatarData($item['data']) . "'";
    $tempoParadoDia = $item['tempo_parado'];
    $minutosProduzindoDia = (16 * 60) - $tempoParadoDia;
    $disponibilidadeDia = calcularDisponibilidade($minutosProduzindoDia, $tempoParadoDia);
    $performanceDia = calcularPerformance($item['producao'], 400);
    $qualidadeDia = calcularQualidade(max(0, $item['producao'] - $item['perdas']), $item['producao'] > 0 ? $item['producao'] : 1);
    $oeeDia = calcularOEE($disponibilidadeDia, $performanceDia, $qualidadeDia);
    $valoresOEE[] = round($oeeDia, 2);
}
?>

const ctxOEE = document.getElementById('chartOEE30').getContext('2d');
new Chart(ctxOEE, {
    type: 'line',
    data: {
        labels: [<?php echo implode(',', $datasOEE); ?>],
        datasets: [{
            label: 'OEE %',
            data: [<?php echo implode(',', $valoresOEE); ?>],
            borderColor: chartColors.primary,
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: chartColors.primary,
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)' }
            },
            x: {
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)' }
            }
        }
    }
});

// =========================================================
// GRÁFICO 2: TOP 5 PIORES LINHAS
// =========================================================
const linhasNomes = [<?php foreach ($pioresLinhas as $linha) echo "'" . $linha['setor'] . "-" . str_pad($linha['linha'], 2, '0', STR_PAD_LEFT) . "',"; ?>];
const linhasTempos = [<?php foreach ($pioresLinhas as $linha) echo $linha['tempo_parado'] . ","; ?>];

const ctxPiores = document.getElementById('chartPioresLinhas').getContext('2d');
new Chart(ctxPiores, {
    type: 'bar',
    data: {
        labels: linhasNomes,
        datasets: [{
            label: 'Tempo Parado (min)',
            data: linhasTempos,
            backgroundColor: [
                'rgba(239, 68, 68, 0.7)',
                'rgba(249, 115, 22, 0.7)',
                'rgba(245, 158, 11, 0.7)',
                'rgba(59, 130, 246, 0.7)',
                'rgba(34, 197, 94, 0.7)'
            ],
            borderColor: [
                '#ef4444',
                '#f97316',
                '#f59e0b',
                '#3b82f6',
                '#22c55e'
            ],
            borderWidth: 2
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
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)' }
            },
            y: {
                ticks: { color: '#e2e8f0' },
                grid: { display: false }
            }
        }
    }
});

// =========================================================
// GRÁFICO 3: PARETO
// =========================================================
const paretoLabels = [<?php foreach ($paretoParadas as $item) echo "'" . htmlspecialchars($item['tipo']) . "',"; ?>];
const paretoValores = [<?php foreach ($paretoParadas as $item) echo $item['quantidade'] . ","; ?>];
const paretoColors = ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#dbeafe'];

const ctxPareto = document.getElementById('chartPareto').getContext('2d');
new Chart(ctxPareto, {
    type: 'pie',
    data: {
        labels: paretoLabels,
        datasets: [{
            data: paretoValores,
            backgroundColor: paretoColors,
            borderColor: 'rgba(15, 23, 42, 0.85)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: '#e2e8f0' }
            }
        }
    }
});

// =========================================================
// GRÁFICO 4: TOP 5 MOTIVOS (Horizontal Bar)
// =========================================================
const motivos = [<?php foreach ($paretoParadas as $item) echo "'" . htmlspecialchars($item['tipo']) . "',"; ?>];
const motivosTempos = [<?php foreach ($paretoParadas as $item) echo $item['tempo_total'] . ","; ?>];

const ctxMotivos = document.getElementById('chartMotivos').getContext('2d');
new Chart(ctxMotivos, {
    type: 'bar',
    data: {
        labels: motivos,
        datasets: [{
            label: 'Tempo (minutos)',
            data: motivosTempos,
            backgroundColor: 'rgba(239, 68, 68, 0.7)',
            borderColor: '#ef4444',
            borderWidth: 2
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
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)' }
            },
            y: {
                ticks: { color: '#e2e8f0' },
                grid: { display: false }
            }
        }
    }
});

// =========================================================
// GRÁFICO 5: PRODUÇÃO POR LINHA
// =========================================================
const linhasProducao = [<?php foreach ($producaoPorLinha as $linha) echo "'" . $linha['setor'] . "-" . str_pad($linha['linha'], 2, '0', STR_PAD_LEFT) . "',"; ?>];
const producaoLinhas = [<?php foreach ($producaoPorLinha as $linha) echo $linha['producao'] . ","; ?>];

const ctxProducaoLinha = document.getElementById('chartProducaoLinha').getContext('2d');
new Chart(ctxProducaoLinha, {
    type: 'bar',
    data: {
        labels: linhasProducao,
        datasets: [{
            label: 'Produção (unidades)',
            data: producaoLinhas,
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: '#10b981',
            borderWidth: 2
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
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)' }
            },
            x: {
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)' }
            }
        }
    }
});

// =========================================================
// GRÁFICO 6: PRODUÇÃO POR TURNO
// =========================================================
const turnos = [<?php foreach ($producaoPorTurno as $turno) echo "'Turno " . $turno['turno'] . "',"; ?>];
const producaoTurnos = [<?php foreach ($producaoPorTurno as $turno) echo $turno['producao'] . ","; ?>];

const ctxProducaoTurno = document.getElementById('chartProducaoTurno').getContext('2d');
new Chart(ctxProducaoTurno, {
    type: 'bar',
    data: {
        labels: turnos,
        datasets: [{
            label: 'Produção (unidades)',
            data: producaoTurnos,
            backgroundColor: 'rgba(245, 158, 11, 0.7)',
            borderColor: '#f59e0b',
            borderWidth: 2
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
                ticks: { color: '#e2e8f0' },
                grid: { color: 'rgba(255,255,255,.05)' }
            },
            x: {
                ticks: { color: '#e2e8f0' },
                grid: { display: false }
            }
        }
    }
});

// Auto-refresh a cada 5 minutos
setTimeout(() => { location.reload(); }, 5 * 60 * 1000);
</script>

<?php include '../includes/footer.php'; ?>

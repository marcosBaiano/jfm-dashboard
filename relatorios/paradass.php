<?php
/**
 * ======================================================
 * RELATÓRIO AVANÇADO DE PARADAS v2.0
 * ======================================================
 * Novo padrão com indicadores, filtros avançados e export
 * 
 * Usa as classes de queries:
 * - RelatorioParadas
 * - IndicadoresDashboard
 * - ExportadorCSV
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';
require_once '../includes/queries/autoload.php';

$titulo = 'Relatório Avançado de Paradas - Sistema Industrial JFM';

// ======================================================
// PROCESSAR EXPORTAÇÃO
// ======================================================
if ($_GET['acao'] === 'exportar' && $_GET['formato'] === 'csv') {
    $filtro = obterFiltros();
    $relatorio = new RelatorioParadas($pdo);
    $exportador = new ExportadorCSV();
    
    $dados = $relatorio->obterListagem($filtro);
    $indicadores = $relatorio->obterIndicadores($filtro);
    
    $exportador->paradas($dados['dados'], null, $indicadores);
}

// ======================================================
// OBTER FILTROS DA REQUISIÇÃO
// ======================================================
function obterFiltros() {
    $filtro = [];
    
    if (!empty($_GET['data_inicio'])) {
        $filtro['data_inicio'] = $_GET['data_inicio'];
    }
    
    if (!empty($_GET['data_fim'])) {
        $filtro['data_fim'] = $_GET['data_fim'];
    }
    
    if (!empty($_GET['linha_id'])) {
        $filtro['linha_id'] = (int)$_GET['linha_id'];
    }
    
    if (!empty($_GET['setor'])) {
        $filtro['setor'] = $_GET['setor'];
    }
    
    if (!empty($_GET['turno'])) {
        $filtro['turno'] = $_GET['turno'];
    }
    
    if (!empty($_GET['operador'])) {
        $filtro['operador'] = $_GET['operador'];
    }
    
    if (!empty($_GET['motivo'])) {
        $filtro['motivo'] = $_GET['motivo'];
    }
    
    // Padrões
    if (empty($filtro['data_inicio'])) {
        $filtro['data_inicio'] = date('Y-m-01');
    }
    
    if (empty($filtro['data_fim'])) {
        $filtro['data_fim'] = date('Y-m-d');
    }
    
    return $filtro;
}

// ======================================================
// OBTER DADOS
// ======================================================
$filtro = obterFiltros();
$pagina = (int)($_GET['pagina'] ?? 1);

try {
    $relatorioParadas = new RelatorioParadas($pdo);
    $indicadores = new IndicadoresDashboard($pdo);
    
    // Dados paginados
    $listagem = $relatorioParadas->obterListagem($filtro, $pagina, 50);
    $dados = $listagem['dados'];
    $total = $listagem['total'];
    $totalPaginas = $listagem['total_paginas'];
    
    // Indicadores
    $indGerai = $relatorioParadas->obterIndicadores($filtro);
    $indOEE = $indicadores->obterOEE($filtro);
    
    // Paretos
    $paretoMotivos = $relatorioParadas->obterParetoMotivos($filtro, 10);
    $paretoGrupos = $relatorioParadas->obterParetoGrupos($filtro, 10);
    
    // Por dimensão
    $porTurno = $relatorioParadas->obterPorTurno($filtro);
    $porOperador = $relatorioParadas->obterPorOperador($filtro);
    $porLinha = $relatorioParadas->obterPorLinha($filtro);
    $porSetor = $relatorioParadas->obterPorSetor($filtro);
    
    // Tendência
    $tendencia = $relatorioParadas->obterTendenciaDiaria($filtro);
    
} catch (Exception $e) {
    die('Erro ao processar dados: ' . $e->getMessage());
}

// ======================================================
// OBTER OPÇÕES DOS SELECT
// ======================================================
$setores = [];
$stmtSetores = $pdo->query("SELECT DISTINCT setor FROM linhas WHERE ativo = 1 ORDER BY setor");
$setores = $stmtSetores->fetchAll(PDO::FETCH_ASSOC);

$linhas = [];
$stmtLinhas = $pdo->query("SELECT id, codigo, linha, setor FROM linhas WHERE ativo = 1 ORDER BY setor, linha");
$linhas = $stmtLinhas->fetchAll(PDO::FETCH_ASSOC);

$operadores = [];
$stmtOperadores = $pdo->query("SELECT DISTINCT operador FROM registros ORDER BY operador");
$operadores = $stmtOperadores->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --primary: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        body { background: var(--bg-dark); color: var(--text-main); font-family: "Inter", -apple-system, sans-serif; }
        .container-fluid { padding: 2rem; }
        .card { background: var(--bg-card); border: 1px solid #334155; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-header { background: #334155; border-color: #334155; border-radius: 12px 12px 0 0; }
        .btn-export { background: var(--success); border: none; color: white; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-export:hover { background: #059669; }
        .badge-indicator { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 8px; background: #334155; margin: 0.5rem; text-align: center; }
        .badge-number { font-size: 1.5rem; font-weight: bold; color: var(--primary); }
        .table { color: var(--text-main); }
        table th { background: #334155; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
        table td { border-color: #334155; }
        table tbody tr:hover { background: rgba(37, 99, 235, 0.1); }
        .filtro-section { background: var(--bg-card); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; }
        .filtro-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .form-label { font-weight: 600; color: var(--text-muted); font-size: 0.875rem; }
        .form-control { background: #0f172a; border: 1px solid #334155; color: var(--text-main); border-radius: 6px; }
        .form-control:focus { background: #0f172a; border-color: var(--primary); color: var(--text-main); box-shadow: none; }
        .chart-container { position: relative; height: 400px; margin: 2rem 0; }
        .card-title { color: var(--text-main); font-weight: 700; margin-bottom: 1.5rem; }
        .icon-section { color: var(--primary); margin-right: 0.5rem; }
        .paginacao { display: flex; justify-content: center; gap: 0.5rem; margin: 2rem 0; }
        .paginacao a, .paginacao span { padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #334155; color: var(--text-main); text-decoration: none; }
        .paginacao a:hover { background: #334155; }
        .paginacao .ativo { background: var(--primary); color: white; border-color: var(--primary); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col">
                <h1><i class="fas fa-chart-bar icon-section"></i>Relatório Avançado de Paradas</h1>
                <p class="text-muted">Análise completa com indicadores, Pareto e tendências</p>
            </div>
            <div class="col text-end">
                <form method="GET" style="display: inline;">
                    <input type="hidden" name="acao" value="exportar">
                    <input type="hidden" name="formato" value="csv">
                    <?php foreach ($filtro as $k => $v): ?>
                        <input type="hidden" name="<?php echo $k; ?>" value="<?php echo $v; ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn-export"><i class="fas fa-download"></i> Exportar CSV</button>
                </form>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filtro-section">
            <form method="GET" class="filtro-grid">
                <div>
                    <label class="form-label">Data Início</label>
                    <input type="date" name="data_inicio" value="<?php echo $filtro['data_inicio']; ?>" class="form-control">
                </div>
                <div>
                    <label class="form-label">Data Fim</label>
                    <input type="date" name="data_fim" value="<?php echo $filtro['data_fim']; ?>" class="form-control">
                </div>
                <div>
                    <label class="form-label">Setor</label>
                    <select name="setor" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($setores as $setor): ?>
                            <option value="<?php echo $setor['setor']; ?>" <?php echo ($filtro['setor'] ?? '') === $setor['setor'] ? 'selected' : ''; ?>>
                                <?php echo $setor['setor']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Linha</label>
                    <select name="linha_id" class="form-control">
                        <option value="">Todas</option>
                        <?php foreach ($linhas as $linha): ?>
                            <option value="<?php echo $linha['id']; ?>" <?php echo ($filtro['linha_id'] ?? '') == $linha['id'] ? 'selected' : ''; ?>>
                                <?php echo $linha['setor'] . ' - ' . $linha['linha']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Turno</label>
                    <select name="turno" class="form-control">
                        <option value="">Todos</option>
                        <option value="1" <?php echo ($filtro['turno'] ?? '') === '1' ? 'selected' : ''; ?>>Turno 1</option>
                        <option value="2" <?php echo ($filtro['turno'] ?? '') === '2' ? 'selected' : ''; ?>>Turno 2</option>
                        <option value="3" <?php echo ($filtro['turno'] ?? '') === '3' ? 'selected' : ''; ?>>Turno 3</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Operador</label>
                    <select name="operador" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($operadores as $op): ?>
                            <option value="<?php echo $op['operador']; ?>" <?php echo ($filtro['operador'] ?? '') === $op['operador'] ? 'selected' : ''; ?>>
                                <?php echo $op['operador']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sync"></i> Atualizar</button>
                </div>
            </form>
        </div>

        <!-- INDICADORES -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="badge-indicator">
                    <div class="badge-number"><?php echo $indGerai['quantidade_paradas']; ?></div>
                    <div class="text-muted" style="font-size: 0.875rem;">Quantidade de Paradas</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="badge-indicator">
                    <div class="badge-number"><?php echo $indGerai['tempo_total_horas']; ?></div>
                    <div class="text-muted" style="font-size: 0.875rem;">Tempo Parado (h)</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="badge-indicator">
                    <div class="badge-number"><?php echo formatarNumero($indGerai['tempo_medio_minutos']); ?></div>
                    <div class="text-muted" style="font-size: 0.875rem;">Tempo Médio (min)</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="badge-indicator">
                    <div class="badge-number"><?php echo formatarPercentual($indOEE['oee']['disponibilidade']); ?></div>
                    <div class="text-muted" style="font-size: 0.875rem;">Disponibilidade</div>
                </div>
            </div>
        </div>

        <!-- PARADAS POR TURNO / SETOR -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-clock icon-section"></i>Paradas por Turno</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Turno</th>
                                    <th>Quantidade</th>
                                    <th>Tempo (h)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($porTurno as $item): ?>
                                    <tr>
                                        <td>Turno <?php echo $item['turno']; ?></td>
                                        <td><?php echo $item['quantidade']; ?></td>
                                        <td><?php echo formatarNumero($item['tempo_total_horas']); ?> h</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-building icon-section"></i>Paradas por Setor</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Setor</th>
                                    <th>Quantidade</th>
                                    <th>Tempo (h)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($porSetor as $item): ?>
                                    <tr>
                                        <td><?php echo $item['setor']; ?></td>
                                        <td><?php echo $item['quantidade']; ?></td>
                                        <td><?php echo formatarNumero($item['tempo_total_horas']); ?> h</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PARETO DE MOTIVOS -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-chart-pie icon-section"></i>Top 10 Motivos (Pareto)</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Motivo</th>
                            <th>Grupo</th>
                            <th>Quantidade</th>
                            <th>Tempo Total (h)</th>
                            <th>Tempo Médio (min)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paretoMotivos as $item): ?>
                            <tr>
                                <td><strong><?php echo $item['codigo']; ?></strong></td>
                                <td><?php echo $item['descricao']; ?></td>
                                <td><span class="badge" style="background: #334155; color: white;"><?php echo $item['grupo']; ?></span></td>
                                <td><?php echo $item['quantidade']; ?></td>
                                <td><?php echo formatarNumero($item['tempo_total_horas']); ?></td>
                                <td><?php echo formatarNumero($item['tempo_medio_minutos']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LISTAGEM COMPLETA -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-list icon-section"></i>Listagem Completa de Paradas</h5>
                <p class="text-muted mb-0">Total: <?php echo $total; ?> paradas | Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></p>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>Linha</th>
                                <th>Motivo</th>
                                <th>Grupo</th>
                                <th>Operador</th>
                                <th>Turno</th>
                                <th>Duração (h)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dados)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Nenhum registro encontrado</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dados as $item): ?>
                                    <tr>
                                        <td><?php echo formatarData($item['data_registro']); ?></td>
                                        <td><?php echo substr($item['inicio'], 11, 5); ?></td>
                                        <td><?php echo substr($item['fim'], 11, 5); ?></td>
                                        <td><?php echo $item['linha']; ?></td>
                                        <td><?php echo $item['descricao'] ?? $item['codigo']; ?></td>
                                        <td><?php echo $item['grupo'] ?? '-'; ?></td>
                                        <td><?php echo $item['operador']; ?></td>
                                        <td><?php echo 'Turno ' . $item['turno']; ?></td>
                                        <td><?php echo formatarNumero($item['duracao_horas']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($totalPaginas > 1): ?>
                    <div class="paginacao">
                        <?php if ($pagina > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>">Primeira</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">Anterior</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++): ?>
                            <?php if ($i == $pagina): ?>
                                <span class="ativo"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($pagina < $totalPaginas): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">Próxima</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $totalPaginas])); ?>">Última</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * ======================================================
 * RELATÓRIO DE PRODUÇÃO - VERSÃO MODERNA
 * ======================================================
 */
require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';

$titulo = 'Relatório de Produção - Sistema Industrial JFM';

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');
$formato = $_GET['formato'] ?? 'html';

try {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(r.data_registro) as data,
            l.setor,
            l.linha,
            r.operador,
            COUNT(p.id) as quantidade_produtos,
            COALESCE(SUM(p.producao), 0) as total_producao,
            COALESCE(SUM(p.perda_1 + p.perda_2 + p.perda_3 + p.perda_4), 0) as total_perdas,
            ROUND(100 - ((COALESCE(SUM(p.perda_1 + p.perda_2 + p.perda_3 + p.perda_4), 0) / NULLIF(COALESCE(SUM(p.producao), 0) + COALESCE(SUM(p.perda_1 + p.perda_2 + p.perda_3 + p.perda_4), 0), 0)) * 100), 2) as taxa_qualidade
        FROM registros r
        LEFT JOIN producao p ON r.id = p.registro_id
        LEFT JOIN linhas l ON r.linha_id = l.id
        WHERE DATE(r.data_registro) BETWEEN :data_inicio AND :data_fim
        GROUP BY DATE(r.data_registro), l.id
        ORDER BY DATE(r.data_registro) DESC, l.setor, l.linha
    ");
    
    $stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataFim]);
    $dados = $stmt->fetchAll();
    
    $totalProducao = 0;
    $totalPerdas = 0;
    foreach ($dados as $item) {
        $totalProducao += $item['total_producao'];
        $totalPerdas += $item['total_perdas'];
    }

    if ($formato == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_producao_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['DATA', 'SETOR', 'LINHA', 'OPERADOR', 'QUANTIDADE', 'PRODUÇÃO', 'PERDAS', 'QUALIDADE (%)'], ';');
        foreach ($dados as $item) {
            fputcsv($output, [
                formatarData($item['data']),
                $item['setor'],
                $item['linha'],
                $item['operador'],
                $item['quantidade_produtos'],
                number_format($item['total_producao'], 2, ',', '.'),
                number_format($item['total_perdas'], 2, ',', '.'),
                number_format($item['taxa_qualidade'], 2, ',', '.')
            ], ';');
        }
        fclose($output);
        exit();
    }

    echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $titulo . '</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
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
        body { background-color: var(--bg-dark); color: var(--text-main); font-family: "Inter", sans-serif; margin: 0; display: flex; }
        .sidebar { width: 260px; background: #1e293b; min-height: 100vh; position: fixed; transition: 0.3s; z-index: 1000; }
        .content { margin-left: 260px; flex: 1; padding: 2rem; transition: 0.3s; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: #1e293b; padding: 1rem 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .grid-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; background: #1e293b; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .form-control { background: #0f172a; border: 1px solid #334155; color: white; padding: 0.75rem; border-radius: 8px; outline: none; }
        .btn-modern { background: var(--primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; justify-content: center; }
        .btn-success-modern { background: var(--success); }
        .table-container { background: #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #334155; color: var(--text-muted); font-weight: 600; padding: 1rem; text-transform: uppercase; font-size: 0.75rem; }
        td { padding: 1rem; border-bottom: 1px solid #334155; }
        tr:hover { background: #33415555; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        @media (max-width: 1024px) { .sidebar { width: 0; overflow: hidden; } .content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div style="padding: 2rem; text-align: center;">
            <h2 style="color: var(--primary); margin: 0;">JFM Industrial</h2>
        </div>
        <nav style="padding: 1rem;">
            <a href="/dashboard/index.php" style="display: flex; align-items: center; gap: 1rem; color: #94a3b8; text-decoration: none; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem;"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="/relatorios/paradas.php" style="display: flex; align-items: center; gap: 1rem; color: #94a3b8; text-decoration: none; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Paradas</a>
            <a href="/relatorios/producao.php" style="display: flex; align-items: center; gap: 1rem; color: white; background: var(--primary); text-decoration: none; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem;"><i class="fas fa-industry"></i> Produção</a>
        </nav>
    </aside>

    <main class="content">
        <header class="header">
            <h1 style="margin: 0; font-size: 1.5rem;"><i class="fas fa-file-pdf"></i> RELATÓRIO DE PRODUÇÃO</h1>
            <div style="color: var(--text-muted); font-size: 0.875rem;">' . date('d/m/Y H:i') . '</div>
        </header>

        <form method="GET" class="grid-filters">
            <div class="form-group">
                <label>Início</label>
                <input type="date" name="data_inicio" value="' . htmlspecialchars($dataInicio) . '" class="form-control">
            </div>
            <div class="form-group">
                <label>Fim</label>
                <input type="date" name="data_fim" value="' . htmlspecialchars($dataFim) . '" class="form-control">
            </div>
            <div class="form-group" style="justify-content: flex-end;">
                <button type="submit" class="btn-modern"><i class="fas fa-sync"></i> Gerar</button>
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Setor</th>
                        <th>Linha</th>
                        <th>Operador</th>
                        <th>Produção</th>
                        <th>Perdas</th>
                        <th>Qualidade</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($dados as $item) {
        $q = $item['taxa_qualidade'] ?? 0;
        $badgeClass = ($q >= 95) ? 'badge-success' : (($q >= 90) ? 'badge-warning' : 'badge-danger');
        
        echo '<tr>
                <td>' . formatarData($item['data']) . '</td>
                <td><strong>' . htmlspecialchars($item['setor']) . '</strong></td>
                <td>' . str_pad($item['linha'], 2, '0', STR_PAD_LEFT) . '</td>
                <td>' . htmlspecialchars($item['operador']) . '</td>
                <td>' . number_format($item['total_producao'], 2, ',', '.') . '</td>
                <td>' . number_format($item['total_perdas'], 2, ',', '.') . '</td>
                <td><span class="badge ' . $badgeClass . '">' . number_format($q, 2, ',', '.') . '%</span></td>
            </tr>';
    }
    echo '      <tr style="background: #334155; font-weight: bold;">
                    <td colspan="4">TOTAL</td>
                    <td>' . number_format($totalProducao, 2, ',', '.') . '</td>
                    <td>' . number_format($totalPerdas, 2, ',', '.') . '</td>
                    <td>-</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 2rem;">
            <a href="?formato=csv&data_inicio=' . $dataInicio . '&data_fim=' . $dataFim . '" class="btn-modern btn-success-modern">
                <i class="fas fa-download"></i> Exportar CSV
            </a>
        </div>
    </main>

    <script>
        setTimeout(() => { window.location.reload(); }, 300000);
    </script>
</body>
</html>';

    include '../includes/footer.php';

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}

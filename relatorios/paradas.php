<?php
/**
 * ======================================================
 * RELATÓRIO DE PARADAS - VERSÃO MODERNA
 * ======================================================
 */
require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';

$titulo = 'Relatório de Paradas - Sistema Industrial JFM';

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');
$setor = $_GET['setor'] ?? '';
$formato = $_GET['formato'] ?? 'html';

try {
    $sql = "
    SELECT
        p.*,
        r.data_registro,
        l.setor,
        l.linha,
        mot.motivo AS motivo_nome,
        mot.impacto AS motivo_impacto
    FROM paradas p
    INNER JOIN registros r
        ON p.registro_id = r.id
    INNER JOIN linhas l
        ON r.linha_id = l.id
    LEFT JOIN motivos_parada mot
        ON p.motivo_id = mot.id
    WHERE DATE(r.data_registro) BETWEEN :data_inicio AND :data_fim
";
    
    $params = [':data_inicio' => $dataInicio, ':data_fim' => $dataFim];
    
    if (!empty($setor)) {
        $sql .= " AND l.setor = :setor";
        $params[':setor'] = $setor;
    }
    
    $sql .= " ORDER BY r.data_registro DESC, p.hora_inicio DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll();
    
    $totalMinutos = 0;
    foreach ($dados as $item) {
        $totalMinutos += $item['duracao'];
    }

    if ($formato == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_paradas_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['DATA', 'SETOR/LINHA', 'MOTIVO', 'INÍCIO', 'FIM', 'DURAÇÃO (MIN)'], ';');
        foreach ($dados as $item) {
            fputcsv($output, [
                formatarData($item['data_parada']),
                $item['setor'] . ' - ' . $item['linha'],
                $item['motivo_nome'],
                $item['hora_inicio'],
                $item['hora_fim'],
                $item['duracao']
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
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        @media (max-width: 1024px) { .sidebar { width: 0; overflow: hidden; } .content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <!-- Sidebar content similar to dashboard -->
        <div style="padding: 2rem; text-align: center;">
            <h2 style="color: var(--primary); margin: 0;">JFM Industrial</h2>
        </div>
        <nav style="padding: 1rem;">
            <a href="/dashboard/index.php" style="display: flex; align-items: center; gap: 1rem; color: #94a3b8; text-decoration: none; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem;"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="/relatorios/paradas.php" style="display: flex; align-items: center; gap: 1rem; color: white; background: var(--primary); text-decoration: none; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Paradas</a>
            <a href="/relatorios/producao.php" style="display: flex; align-items: center; gap: 1rem; color: #94a3b8; text-decoration: none; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem;"><i class="fas fa-industry"></i> Produção</a>
        </nav>
    </aside>

    <main class="content">
        <header class="header">
            <h1 style="margin: 0; font-size: 1.5rem;"><i class="fas fa-file-alt"></i> RELATÓRIO DE PARADAS</h1>
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
            <div class="form-group">
                <label>Setor</label>
                <select name="setor" class="form-control">
                    <option value="">Todos</option>
                    <option value="ESPUMACAO" ' . ($setor == 'ESPUMACAO' ? 'selected' : '') . '>ESPUMAÇÃO</option>
                    <option value="LAMINACAO" ' . ($setor == 'LAMINACAO' ? 'selected' : '') . '>LAMINAÇÃO</option>
                </select>
            </div>
            <div class="form-group" style="justify-content: flex-end;">
                <button type="submit" class="btn-modern"><i class="fas fa-sync"></i> Atualizar</button>
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Linha</th>
                        <th>Motivo</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Duração (Min)</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($dados as $item) {
        echo '<tr>
                <td>' . formatarData($item['data_parada']) . '</td>
                <td>' . htmlspecialchars($item['setor'] . ' - Line ' . $item['linha']) . '</td>
                <td>' . htmlspecialchars($item['motivo_nome']) . '</td>
                <td>' . $item['hora_inicio'] . '</td>
                <td>' . $item['hora_fim'] . '</td>
                <td><span class="badge badge-danger">' . $item['duracao'] . ' min</span></td>
            </tr>';
    }
    echo '      <tr style="background: #334155; font-weight: bold;">
                    <td colspan="5" style="text-align: right;">TOTAL:</td>
                    <td>' . $totalMinutos . ' min</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 2rem;">
            <a href="?format=csv&data_inicio=' . $dataInicio . '&data_fim=' . $dataFim . '&setor=' . $setor . '" class="btn-modern btn-success-modern">
                <i class="fas fa-file-excel"></i> Exportar CSV
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

<?php
/**
 * ======================================================
 * AUDITORIA - VISUALIZAÇÃO DE LOGS DO SISTEMA
 * ======================================================
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';

// Verificar permissão
if ($perfil != 'Administrador') {
    die('Acesso negado! Apenas administradores podem acessar auditoria.');
}

$titulo = 'Auditoria - Sistema Industrial JFM';

// Filtros
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');
$usuario = $_GET['usuario'] ?? '';
$acao = $_GET['acao'] ?? '';

try {
    $sql = "
        SELECT 
            id,
            usuario_id,
            usuario_nome,
            acao,
            tabela,
            registro_id,
            detalhes,
            ip,
            created_at
        FROM auditoria
        WHERE DATE(created_at) BETWEEN :data_inicio AND :data_fim
    ";
    
    $params = [':data_inicio' => $dataInicio, ':data_fim' => $dataFim];
    
    if (!empty($usuario)) {
        $sql .= " AND usuario_nome LIKE :usuario";
        $params[':usuario'] = '%' . $usuario . '%';
    }
    
    if (!empty($acao)) {
        $sql .= " AND acao = :acao";
        $params[':acao'] = $acao;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 500";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll();
    
} catch (Exception $e) {
    $registros = [];
    error_log('Erro ao carregar auditoria: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        table { width: 100%; border-collapse: collapse; }
        th { background: #334155; color: #94a3b8; font-weight: 600; padding: 1rem; text-align: left; text-transform: uppercase; font-size: 0.75rem; }
        td { padding: 1rem; border-bottom: 1px solid #334155; color: #e2e8f0; font-size: 0.875rem; }
        tr:hover { background: #33415520; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-insert { background: #10b98120; color: #10b981; }
        .badge-update { background: #2563eb20; color: #2563eb; }
        .badge-delete { background: #ef444420; color: #ef4444; }
        .badge-login { background: #f59e0b20; color: #f59e0b; }
        .form-control, .form-select { background: #0f172a; border: 1px solid #334155; color: #f8fafc; padding: 0.75rem; border-radius: 8px; font-size: 0.875rem; }
        .form-control:focus, .form-select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
    </style>
</head>
<body>
    <div class="dashboard-container">
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
                <a href="/dashboard/eficiencia.php" class="nav-item"><i class="fas fa-chart-bar"></i><span>Eficiência</span></a>
                <div class="nav-section">Sistema</div>
                <a href="/admin/usuarios.php" class="nav-item"><i class="fas fa-users"></i><span>Usuários</span></a>
                <a href="/admin/auditoria.php" class="nav-item active"><i class="fas fa-history"></i><span>Auditoria</span></a>
                <a href="/processos/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Sair</span></a>
            </nav>
            <div class="sidebar-footer">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($usuario_nome ?? 'User'); ?>&background=random" class="sidebar-user-avatar" alt="Avatar">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($usuario_nome ?? 'Usuário'); ?></div>
                    <div class="sidebar-user-role"><?php echo htmlspecialchars($perfil ?? 'Administrador'); ?></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="toggle-sidebar-btn" id="toggleSidebarBtn"><i class="fas fa-bars"></i></button>
                    <div class="header-title">Auditoria do Sistema</div>
                </div>
            </header>

            <div class="dashboard-content">
                <h2 style="margin: 0 0 1.5rem 0; font-size: 1.5rem;">
                    <i class="fas fa-history"></i> Registro de Atividades (<span style="color: #2563eb;"><?php echo count($registros); ?></span>)
                </h2>

                <div style="background: #1e293b; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
                    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: #e2e8f0; font-weight: 500; font-size: 0.875rem;">Data Início</label>
                            <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($dataInicio); ?>" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: #e2e8f0; font-weight: 500; font-size: 0.875rem;">Data Fim</label>
                            <input type="date" name="data_fim" value="<?php echo htmlspecialchars($dataFim); ?>" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: #e2e8f0; font-weight: 500; font-size: 0.875rem;">Usuário</label>
                            <input type="text" name="usuario" placeholder="Buscar por nome..." value="<?php echo htmlspecialchars($usuario); ?>" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: #e2e8f0; font-weight: 500; font-size: 0.875rem;">Ação</label>
                            <select name="acao" class="form-select">
                                <option value="">Todas</option>
                                <option value="INSERT" <?php echo ($acao == 'INSERT' ? 'selected' : ''); ?>>INSERT</option>
                                <option value="UPDATE" <?php echo ($acao == 'UPDATE' ? 'selected' : ''); ?>>UPDATE</option>
                                <option value="DELETE" <?php echo ($acao == 'DELETE' ? 'selected' : ''); ?>>DELETE</option>
                                <option value="LOGIN" <?php echo ($acao == 'LOGIN' ? 'selected' : ''); ?>>LOGIN</option>
                                <option value="LOGOUT" <?php echo ($acao == 'LOGOUT' ? 'selected' : ''); ?>>LOGOUT</option>
                            </select>
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <button type="submit" style="background: #2563eb; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <div style="background: #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Usuário</th>
                                    <th>Ação</th>
                                    <th>Tabela</th>
                                    <th>Detalhes</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $reg): ?>
                                <tr>
                                    <td><?php echo formatarDataHora($reg['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['usuario_nome']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($reg['acao']); ?>">
                                            <?php echo htmlspecialchars($reg['acao']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($reg['tabela'] ?? '-'); ?></td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($reg['detalhes'] ?? '', 0, 100)); ?>
                                    </td>
                                    <td style="font-family: monospace; font-size: 0.8rem;"><?php echo htmlspecialchars($reg['ip'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($registros)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                        Nenhum registro encontrado
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

    <script>
        document.getElementById('toggleSidebarBtn').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
    </script>
</body>
</html>

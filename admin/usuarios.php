<?php
/**
 * ======================================================
 * ADMINISTRAÇÃO DE USUÁRIOS - VERSÃO MODERNIZADA
 * ======================================================
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';

// Verificar permissão
if (!temPermissao('editar_usuarios') && $perfil != 'Administrador') {
    die('Acesso negado! Apenas administradores podem gerenciar usuários.');
}

$titulo = 'Administração de Usuários - Sistema Industrial JFM';
$mensagem = null;

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    try {
        if ($_POST['acao'] == 'criar') {
            $nome = limpar($_POST['nome']);
            $email = limpar($_POST['email']);
            $senha = $_POST['senha'];
            $perfil_novo = $_POST['perfil'];
            
            if (strlen($senha) < 6) {
                $mensagem = ['tipo' => 'danger', 'texto' => 'Senha deve ter no mínimo 6 caracteres'];
            } else {
                $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)");
                $stmt->execute([':nome' => $nome, ':email' => $email, ':senha' => $senhaHash, ':perfil' => $perfil_novo]);
                
                $mensagem = ['tipo' => 'success', 'texto' => 'Usuário criado com sucesso!'];
            }
        } elseif ($_POST['acao'] == 'editar') {
            $usuarioId = (int)$_POST['usuario_id'];
            $nome = limpar($_POST['nome']);
            $perfil_novo = $_POST['perfil'];
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, perfil = :perfil, ativo = :ativo WHERE id = :id");
            $stmt->execute([':nome' => $nome, ':perfil' => $perfil_novo, ':ativo' => $ativo, ':id' => $usuarioId]);
            
            $mensagem = ['tipo' => 'success', 'texto' => 'Usuário atualizado com sucesso!'];
        } elseif ($_POST['acao'] == 'alterar_senha') {
            $usuarioId = (int)$_POST['usuario_id'];
            $novaSenha = $_POST['nova_senha'];
            
            if (strlen($novaSenha) < 6) {
                $mensagem = ['tipo' => 'danger', 'texto' => 'Senha deve ter no mínimo 6 caracteres'];
            } else {
                $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
                $stmt->execute([':senha' => $senhaHash, ':id' => $usuarioId]);
                $mensagem = ['tipo' => 'success', 'texto' => 'Senha alterada com sucesso!'];
            }
        }
    } catch (Exception $e) {
        $mensagem = ['tipo' => 'danger', 'texto' => 'Erro: ' . $e->getMessage()];
    }
}

// Buscar usuários
$stmt = $pdo->prepare("SELECT * FROM usuarios ORDER BY created_at DESC");
$stmt->execute();
$usuarios = $stmt->fetchAll();

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
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 999;
            display: none;
        }
        .modal-overlay.show { display: block; }
        .modal-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: #1e293b;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            min-width: 420px;
            display: none;
        }
        .modal-box.show { display: block; }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 1.25rem; font-weight: 600; color: #f8fafc; margin: 0; }
        .modal-close { background: none; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer; padding: 0; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1.5rem; border-top: 1px solid #334155; display: flex; gap: 1rem; justify-content: flex-end; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: #e2e8f0; font-weight: 500; font-size: 0.875rem; }
        .form-control, .form-select { background: #0f172a; border: 1px solid #334155; color: #f8fafc; padding: 0.75rem; border-radius: 8px; width: 100%; font-size: 0.9rem; }
        .form-control:focus, .form-select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .btn { background: #2563eb; color: white; border: none; padding: 0.625rem 1.25rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn:hover { opacity: 0.9; }
        .btn-secondary { background: #64748b; }
        .btn-danger { background: #ef4444; }
        .btn-sm { padding: 0.5rem 0.75rem; font-size: 0.75rem; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #10b98120; color: #10b981; }
        .badge-secondary { background: #64748b20; color: #cbd5e1; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #334155; color: #94a3b8; font-weight: 600; padding: 1rem; text-align: left; text-transform: uppercase; font-size: 0.75rem; }
        td { padding: 1rem; border-bottom: 1px solid #334155; color: #e2e8f0; }
        tr:hover { background: #33415520; }
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
                <a href="/admin/usuarios.php" class="nav-item active"><i class="fas fa-users"></i><span>Usuários</span></a>
                <a href="/admin/auditoria.php" class="nav-item"><i class="fas fa-history"></i><span>Auditoria</span></a>
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
                    <div class="header-title">Administração de Usuários</div>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if (isset($mensagem)): ?>
                <div style="background: <?php echo ($mensagem['tipo'] === 'success' ? '#10b98120' : '#ef444420'); ?>; border-left: 4px solid <?php echo ($mensagem['tipo'] === 'success' ? '#10b981' : '#ef4444'); ?>; color: <?php echo ($mensagem['tipo'] === 'success' ? '#10b981' : '#ef4444'); ?>; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; justify-content: space-between;">
                    <span><?php echo htmlspecialchars($mensagem['texto']); ?></span>
                    <button onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: inherit; cursor: pointer;">✕</button>
                </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="margin: 0; font-size: 1.5rem;">
                        <i class="fas fa-users"></i> Usuários Cadastrados (<span style="color: #2563eb;"><?php echo count($usuarios); ?></span>)
                    </h2>
                    <button class="btn" onclick="abrirModal('novo')">
                        <i class="fas fa-plus"></i> Novo Usuário
                    </button>
                </div>

                <div style="background: #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Perfil</th>
                                    <th>Status</th>
                                    <th>Cadastro</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['nome']); ?></td>
                                    <td style="font-family: monospace; font-size: 0.85rem;"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="badge" style="background: #2563eb20; color: #2563eb;"><?php echo htmlspecialchars($u['perfil']); ?></span></td>
                                    <td><?php echo $u['ativo'] ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge badge-secondary">Inativo</span>'; ?></td>
                                    <td style="font-size: 0.85rem;"><?php echo formatarData($u['created_at']); ?></td>
                                    <td style="white-space: nowrap;">
                                        <button class="btn btn-sm" onclick="abrirModal('editar', <?php echo $u['id']; ?>)"><i class="fas fa-edit"></i> Editar</button>
                                        <button class="btn btn-sm" onclick="abrirModal('senha', <?php echo $u['id']; ?>)"><i class="fas fa-key"></i> Senha</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL NOVO USUÁRIO -->
    <div class="modal-overlay" id="overlayNovo"></div>
    <div class="modal-box" id="modalNovo">
        <div class="modal-header">
            <h3 class="modal-title">Novo Usuário</h3>
            <button class="modal-close" onclick="fecharModal('novo')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="acao" value="criar">
                <div class="form-group">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" name="nome" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Senha</label>
                    <input type="password" class="form-control" name="senha" required minlength="6" placeholder="Mínimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label class="form-label">Perfil</label>
                    <select class="form-select" name="perfil" required>
                        <option value="">Selecione...</option>
                        <option>Administrador</option>
                        <option>Supervisor</option>
                        <option>Líder</option>
                        <option>Operador</option>
                        <option>Visualizador</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModal('novo')">Cancelar</button>
                <button type="submit" class="btn">Criar</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('toggleSidebarBtn').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        function abrirModal(tipo, id) {
            document.getElementById('overlay' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).classList.add('show');
            document.getElementById('modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).classList.add('show');
        }

        function fecharModal(tipo) {
            const tipo2 = tipo.charAt(0).toUpperCase() + tipo.slice(1);
            document.getElementById('overlay' + tipo2).classList.remove('show');
            document.getElementById('modal' + tipo2).classList.remove('show');
        }

        document.querySelectorAll('[id^="overlay"]').forEach(el => {
            el.addEventListener('click', () => fecharModal(el.id.replace('overlay', '').toLowerCase()));
        });
    </script>
</body>
</html>

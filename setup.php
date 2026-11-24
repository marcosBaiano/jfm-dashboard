<?php
/**
 * ======================================================
 * SETUP INICIAL DO SISTEMA
 * ======================================================
 * Execute este arquivo APENAS uma vez para configurar
 * o sistema e criar o usuário administrador
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'banco/conexao.php';

try {
    // Conectar ao MySQL
    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8mb4',
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    $conexaoBemSucedida = true;
    
} catch (PDOException $e) {
    $conexaoBemSucedida = false;
    $erroConexao = $e->getMessage();
}

// Processar formulário de configuração
$mensagem = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    
    if (!$conexaoBemSucedida) {
        $mensagem = ['tipo' => 'danger', 'texto' => 'Erro de conexão com banco de dados'];
    } elseif ($_POST['acao'] == 'criar_tabelas') {
        try {
            // Ler e executar arquivo SQL
            $sql = file_get_contents('banco/tabelas_novas.sql');
            
            // Dividir em múltiplas queries
            $queries = array_filter(array_map('trim', preg_split('/;(?=\s|$)/', $sql)));
            
            foreach ($queries as $query) {
                if (!empty($query)) {
                    $pdo->exec($query);
                }
            }
            
            $mensagem = ['tipo' => 'success', 'texto' => 'Tabelas criadas com sucesso!'];
            
        } catch (Exception $e) {
            $mensagem = ['tipo' => 'danger', 'texto' => 'Erro ao criar tabelas: ' . $e->getMessage()];
        }
        
    } elseif ($_POST['acao'] == 'criar_admin') {
        try {
            $nome = trim($_POST['nome'] ?? 'Administrador');
            $email = trim($_POST['email'] ?? 'admin@jfm.com.br');
            $senha = $_POST['senha'] ?? 'admin123';
            
            if (strlen($senha) < 6) {
                $mensagem = ['tipo' => 'danger', 'texto' => 'Senha deve ter no mínimo 6 caracteres'];
            } else {
                $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                
                // Verificar se usuário já existe
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
                $stmt->execute([':email' => $email]);
                
                if ($stmt->rowCount() > 0) {
                    $mensagem = ['tipo' => 'warning', 'texto' => 'Usuário já existe. Pulando criação.'];
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios (nome, email, senha, perfil, ativo)
                        VALUES (:nome, :email, :senha, :perfil, 1)
                    ");
                    
                    $stmt->execute([
                        ':nome' => $nome,
                        ':email' => $email,
                        ':senha' => $senhaHash,
                        ':perfil' => 'Administrador'
                    ]);
                    
                    $mensagem = ['tipo' => 'success', 'texto' => 'Administrador criado com sucesso!'];
                }
            }
            
        } catch (Exception $e) {
            $mensagem = ['tipo' => 'danger', 'texto' => 'Erro ao criar admin: ' . $e->getMessage()];
        }
        
    } elseif ($_POST['acao'] == 'testar_login') {
        try {
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            
            if (empty($email) || empty($senha)) {
                $mensagem = ['tipo' => 'warning', 'texto' => 'Preencha email e senha'];
            } else {
                $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $usuario = $stmt->fetch();
                
                if ($usuario && password_verify($senha, $usuario['senha'])) {
                    $mensagem = ['tipo' => 'success', 'texto' => '✅ Login bem-sucedido! Usuário: ' . $usuario['nome']];
                } else {
                    $mensagem = ['tipo' => 'danger', 'texto' => '❌ Email ou senha incorretos'];
                }
            }
            
        } catch (Exception $e) {
            $mensagem = ['tipo' => 'danger', 'texto' => 'Erro ao testar: ' . $e->getMessage()];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Sistema Industrial JFM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .setup-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2rem;
            max-width: 600px;
            width: 100%;
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 1rem;
        }
        
        .setup-header h1 {
            color: #1e40af;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .setup-step {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #1e40af;
        }
        
        .setup-step h5 {
            color: #1e40af;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .btn-setup {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 6px;
            width: 100%;
            color: white;
            transition: transform 0.2s ease;
        }
        
        .btn-setup:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #163299 0%, #6d28d9 100%);
            color: white;
        }
        
        .status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .status.ok {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status.erro {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <!-- HEADER -->
        <div class="setup-header">
            <h1>
                <i class="fas fa-cogs"></i> Setup Inicial
            </h1>
            <p class="text-muted mb-0">Sistema Industrial JFM</p>
        </div>
        
        <!-- MENSAGEM -->
        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $mensagem['tipo']; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem['texto']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- STATUS CONEXÃO -->
        <div class="setup-step">
            <h5>
                <i class="fas fa-database"></i> Status da Conexão
            </h5>
            <?php if ($conexaoBemSucedida): ?>
                <div class="status ok">
                    <i class="fas fa-check-circle"></i> Conectado ao banco de dados
                </div>
                <p class="text-muted"><small>Host: <code><?php echo $host; ?></code></small></p>
                <p class="text-muted"><small>Banco: <code><?php echo $db; ?></code></small></p>
            <?php else: ?>
                <div class="status erro">
                    <i class="fas fa-times-circle"></i> Erro ao conectar
                </div>
                <p class="text-danger"><small><?php echo $erroConexao; ?></small></p>
                <p class="text-muted"><small>Verifique o arquivo <code>setup.php</code> e as configurações.</small></p>
            <?php endif; ?>
        </div>
        
        <!-- PASSO 1: CRIAR TABELAS -->
        <?php if ($conexaoBemSucedida): ?>
            <div class="setup-step">
                <h5>
                    <i class="fas fa-table"></i> Passo 1: Criar Tabelas
                </h5>
                <p class="text-muted">
                    Cria as tabelas necessárias para o sistema (usuarios, auditoria, historico_edicoes, etc).
                </p>
                <form method="POST">
                    <input type="hidden" name="acao" value="criar_tabelas">
                    <button type="submit" class="btn btn-setup" onclick="return confirm('Deseja criar as tabelas do sistema?')">
                        <i class="fas fa-arrow-right"></i> Criar Tabelas
                    </button>
                </form>
            </div>
            
            <!-- PASSO 2: CRIAR ADMIN -->
            <div class="setup-step">
                <h5>
                    <i class="fas fa-user-shield"></i> Passo 2: Criar Administrador
                </h5>
                <p class="text-muted">
                    Cria o usuário administrador do sistema.
                </p>
                <form method="POST">
                    <input type="hidden" name="acao" value="criar_admin">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="nome" value="Administrador" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="admin@jfm.com.br" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" class="form-control" name="senha" value="admin123" minlength="6" required>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    
                    <button type="submit" class="btn btn-setup">
                        <i class="fas fa-arrow-right"></i> Criar Administrador
                    </button>
                </form>
            </div>
            
            <!-- PASSO 3: TESTAR LOGIN -->
            <div class="setup-step">
                <h5>
                    <i class="fas fa-sign-in-alt"></i> Passo 3: Testar Login
                </h5>
                <p class="text-muted">
                    Teste as credenciais do administrador.
                </p>
                <form method="POST">
                    <input type="hidden" name="acao" value="testar_login">
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="admin@jfm.com.br" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" class="form-control" name="senha" placeholder="admin123" required>
                    </div>
                    
                    <button type="submit" class="btn btn-setup">
                        <i class="fas fa-check"></i> Testar Login
                    </button>
                </form>
            </div>
            
            <!-- PRÓXIMO PASSO -->
            <div class="setup-step" style="border-left-color: #16a34a; background: #f0fdf4;">
                <h5 style="color: #16a34a;">
                    <i class="fas fa-arrow-right"></i> Próximo Passo
                </h5>
                <p class="text-muted mb-2">
                    Após completar os passos acima, acesse o sistema:
                </p>
                <a href="/processos/login.php" class="btn btn-success w-100">
                    <i class="fas fa-sign-in-alt"></i> Ir para Login
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

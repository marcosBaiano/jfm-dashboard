<?php
/**
 * ======================================================
 * PÁGINA DE LOGIN
 * ======================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Sao_Paulo');

// Se já está logado, redirecionar para dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: /dashboard/index.php');
    exit();
}

// Incluir conexão

require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';

$erro = '';
$sucesso = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = limpar($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Email e senha são obrigatórios!';
    } else {
        try {
            // Buscar usuário
            $stmt = $pdo->prepare("
                SELECT id, nome, email, senha, perfil, ativo
                FROM usuarios
                WHERE email = :email AND ativo = 1
                LIMIT 1
            ");
            
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Autenticação bem-sucedida
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['perfil'] = $usuario['perfil'];
                $_SESSION['ultimo_acesso'] = time();
                
                // Registrar login em auditoria
                $ip = obterIP();
                $stmt = $pdo->prepare("
                    INSERT INTO auditoria (usuario_id, usuario_nome, acao, ip)
                    VALUES (:usuario_id, :usuario_nome, :acao, :ip)
                ");
                
                $stmt->execute([
                    ':usuario_id' => $usuario['id'],
                    ':usuario_nome' => $usuario['nome'],
                    ':acao' => 'LOGIN',
                    ':ip' => $ip
                ]);
                
                header('Location: /dashboard/index.php');
                exit();
            } else {
                $erro = 'Email ou senha incorretos!';
            }
            
        } catch (Exception $e) {
            $erro = 'Erro ao processar login: ' . $e->getMessage();
        }
    }
}

// Verificar mensagem de query string
if (isset($_GET['msg'])) {
    $sucesso = htmlspecialchars($_GET['msg']);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Industrial JFM</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2rem;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #1e40af;
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: #1e40af;
            box-shadow: 0 0 0 0.2rem rgba(30, 64, 175, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 6px;
            width: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 64, 175, 0.3);
            background: linear-gradient(135deg, #163299 0%, #6d28d9 100%);
        }
        
        .alert {
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .form-text {
            font-size: 0.85rem;
        }
        
        .demo-info {
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.85rem;
        }
        
        .demo-info strong {
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- HEADER -->
        <div class="login-header">
            <h1>
                <i class="fas fa-industry"></i>
                SISTEMA JFM
            </h1>
            <p>Gerenciamento de Produção</p>
        </div>
        
        <!-- MENSAGENS -->
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($sucesso)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- FORMULÁRIO -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    placeholder="seu.email@jfm.com.br"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="senha" class="form-label">
                    <i class="fas fa-lock"></i> Senha
                </label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="senha" 
                    name="senha" 
                    placeholder="Digite sua senha"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-login text-white">
                <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
            </button>
        </form>
        
        <!-- INFORMAÇÕES DE DEMONSTRAÇÃO -->
        <div class="demo-info">
            <strong>👤 Dados de Acesso (Demonstração):</strong>
            <br>
            <small>Email: <code>admin@jfm.com.br</code></small>
            <br>
            <small>Senha: <code>admin123</code></small>
            <p class="mt-2 mb-0 text-muted">
                <i class="fas fa-info-circle"></i>
                Entre em contato com o administrador para alterar sua senha.
            </p>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

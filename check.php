<?php
/**
 * ======================================================
 * VERIFICAÇÃO DE INSTALAÇÃO
 * ======================================================
 * Execute este arquivo para validar a instalação completa
 */

$checks = [];
$erro = false;

// ======================================================
// 1. Verificar PHP
// ======================================================
$checks['PHP Version'] = [
    'passou' => version_compare(PHP_VERSION, '7.4', '>='),
    'atual' => PHP_VERSION,
    'requerido' => '7.4 ou superior'
];

// ======================================================
// 2. Verificar Extensões
// ======================================================
$extensoes = ['pdo', 'pdo_mysql', 'json', 'curl'];
foreach ($extensoes as $ext) {
    $checks['Extensão: ' . $ext] = [
        'passou' => extension_loaded($ext),
        'mensagem' => extension_loaded($ext) ? 'Carregada' : 'Não encontrada'
    ];
}

// ======================================================
// 3. Verificar Arquivos
// ======================================================
$arquivos = [
    'banco/conexao.php',
    'banco/conexao_pdo.php',
    'includes/auth.php',
    'includes/header.php',
    'includes/footer.php',
    'includes/functions.php',
    'processos/login.php',
    'processos/logout.php',
    'dashboard/index.php',
    'dashboard/paradas.php',
    'dashboard/velocidade.php',
    'dashboard/eficiencia.php',
    'dashboard/oee.php',
    'relatorios/producao.php',
    'relatorios/paradas.php',
    'admin/usuarios.php',
    'admin/auditoria.php',
    'README.md',
    'MANUAL.md',
    'setup.php'
];

foreach ($arquivos as $arquivo) {
    $checks['Arquivo: ' . $arquivo] = [
        'passou' => file_exists($arquivo),
        'mensagem' => file_exists($arquivo) ? 'Encontrado' : 'NÃO ENCONTRADO'
    ];
    if (!file_exists($arquivo)) $erro = true;
}

// ======================================================
// 4. Verificar Banco de Dados
// ======================================================
$conexaoBD = false;
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=paradas;charset=utf8mb4',
        'root',
        '3512',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $conexaoBD = true;
    
    // Contar tabelas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = 'paradas'");
    $totalTabelas = $stmt->fetch()['total'];
    
    $checks['Conexão Banco'] = [
        'passou' => true,
        'mensagem' => 'Conectado (' . $totalTabelas . ' tabelas)'
    ];
    
    // Verificar tabelas necessárias
    $tabelasNecessarias = ['usuarios', 'auditoria', 'historico_edicoes', 'linhas', 'registros', 'paradas', 'producao', 'velocidades'];
    foreach ($tabelasNecessarias as $tabela) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = 'paradas' AND table_name = '$tabela'");
        $existe = $stmt->fetch()['total'] > 0;
        
        $checks['Tabela: ' . $tabela] = [
            'passou' => $existe,
            'mensagem' => $existe ? 'Existe' : 'NÃO ENCONTRADA'
        ];
        if (!$existe) $erro = true;
    }
    
    // Verificar usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $totalUsuarios = $stmt->fetch()['total'];
    
    $checks['Usuários no Banco'] = [
        'passou' => $totalUsuarios > 0,
        'mensagem' => $totalUsuarios . ' usuários'
    ];
    
} catch (Exception $e) {
    $checks['Conexão Banco'] = [
        'passou' => false,
        'mensagem' => 'Erro: ' . substr($e->getMessage(), 0, 50)
    ];
    $erro = true;
}

// ======================================================
// 5. Verificar Permissões
// ======================================================
$diretorios = ['assets', 'dashboard', 'banco', 'includes', 'processos', 'relatorios', 'admin'];
foreach ($diretorios as $dir) {
    $checks['Diretório: ' . $dir] = [
        'passou' => is_dir($dir),
        'mensagem' => is_dir($dir) ? 'Existe' : 'NÃO ENCONTRADO'
    ];
    if (!is_dir($dir)) $erro = true;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Instalação - JFM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container-max {
            max-width: 1000px;
        }
        
        .check-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .check-item {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .check-item:last-child {
            border-bottom: none;
        }
        
        .check-item.passed {
            background: #f0fdf4;
        }
        
        .check-item.failed {
            background: #fef2f2;
        }
        
        .badge-check {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <div class="container-max">
        <!-- HEADER -->
        <div class="text-white text-center mb-4">
            <h1>
                <i class="fas fa-check-circle"></i> Verificação de Instalação
            </h1>
            <p>Sistema Industrial JFM v2.0.0</p>
        </div>
        
        <!-- RESULTADO GERAL -->
        <div class="check-card">
            <?php if (!$erro): ?>
                <div class="alert alert-success alert-lg">
                    <h4 class="alert-heading">
                        <i class="fas fa-check-circle"></i> Instalação Completa!
                    </h4>
                    <p>Todos os componentes foram instalados corretamente.</p>
                    <hr>
                    <p class="mb-0">
                        <strong>Próximo passo:</strong> 
                        <a href="/processos/login.php" class="alert-link">Ir para o Login</a>
                    </p>
                </div>
            <?php else: ?>
                <div class="alert alert-danger alert-lg">
                    <h4 class="alert-heading">
                        <i class="fas fa-times-circle"></i> Problemas Encontrados
                    </h4>
                    <p>Por favor, corrija os itens marcados em vermelho antes de usar o sistema.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- VERIFICAÇÕES DETALHADAS -->
        <div class="check-card">
            <h4 class="mb-3">
                <i class="fas fa-list"></i> Detalhes das Verificações
            </h4>
            
            <?php foreach ($checks as $item => $resultado): ?>
                <div class="check-item <?php echo ($resultado['passou']) ? 'passed' : 'failed'; ?>">
                    <div>
                        <strong><?php echo htmlspecialchars($item); ?></strong>
                        <?php if (isset($resultado['atual'])): ?>
                            <br>
                            <small class="text-muted">
                                Atual: <code><?php echo htmlspecialchars($resultado['atual']); ?></code>
                                | Requerido: <code><?php echo htmlspecialchars($resultado['requerido']); ?></code>
                            </small>
                        <?php endif; ?>
                        <?php if (isset($resultado['mensagem'])): ?>
                            <br>
                            <small class="text-muted"><?php echo htmlspecialchars($resultado['mensagem']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($resultado['passou']): ?>
                            <span class="badge badge-check bg-success">
                                <i class="fas fa-check"></i> OK
                            </span>
                        <?php else: ?>
                            <span class="badge badge-check bg-danger">
                                <i class="fas fa-times"></i> ERRO
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- PRÓXIMOS PASSOS -->
        <div class="check-card">
            <h4 class="mb-3">
                <i class="fas fa-arrow-right"></i> Próximos Passos
            </h4>
            
            <ol class="large">
                <li class="mb-3">
                    <strong>Se ainda não executou o setup:</strong>
                    <br>
                    <a href="/setup.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-cogs"></i> Ir para Setup
                    </a>
                </li>
                
                <li class="mb-3">
                    <strong>Se a instalação passou:</strong>
                    <br>
                    <a href="/processos/login.php" class="btn btn-sm btn-success">
                        <i class="fas fa-sign-in-alt"></i> Ir para Login
                    </a>
                </li>
                
                <li class="mb-3">
                    <strong>Documentação:</strong>
                    <br>
                    <a href="/README.md" class="btn btn-sm btn-info">
                        <i class="fas fa-book"></i> Ler README
                    </a>
                    <a href="/MANUAL.md" class="btn btn-sm btn-info">
                        <i class="fas fa-graduation-cap"></i> Ler Manual
                    </a>
                </li>
            </ol>
        </div>
        
        <!-- INFORMAÇÕES DO SERVIDOR -->
        <div class="check-card">
            <h5 class="mb-3">
                <i class="fas fa-server"></i> Informações do Servidor
            </h5>
            
            <dl class="row">
                <dt class="col-sm-3">PHP:</dt>
                <dd class="col-sm-9"><code><?php echo PHP_VERSION; ?></code></dd>
                
                <dt class="col-sm-3">Sistema Operacional:</dt>
                <dd class="col-sm-9"><code><?php echo php_uname(); ?></code></dd>
                
                <dt class="col-sm-3">Diretório Atual:</dt>
                <dd class="col-sm-9"><code><?php echo getcwd(); ?></code></dd>
                
                <dt class="col-sm-3">Memória Disponível:</dt>
                <dd class="col-sm-9"><code><?php echo ini_get('memory_limit'); ?></code></dd>
                
                <dt class="col-sm-3">Max Upload:</dt>
                <dd class="col-sm-9"><code><?php echo ini_get('upload_max_filesize'); ?></code></dd>
            </dl>
        </div>
        
        <!-- FOOTER -->
        <div class="text-white text-center mt-5 mb-3">
            <p class="small">
                Sistema Industrial JFM v2.0.0 | 
                <a href="mailto:suporte@jfm.com.br" class="text-white">suporte@jfm.com.br</a>
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

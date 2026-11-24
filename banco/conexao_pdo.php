<?php
/**
 * ======================================================
 * CONEXÃO PDO - SISTEMA INDUSTRIAL
 * ======================================================
 * Este arquivo é responsável por gerenciar a conexão
 * com o banco de dados usando PDO (Prepared Statements)
 * 
 * Utilizar este arquivo para novas funcionalidades
 * Mantém compatibilidade com código existente (mysqli)
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '3512');
define('DB_NAME', 'paradas');

try {
    // Criar conexão PDO
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}

/**
 * Função auxiliar para registrar log em auditoria
 * 
 * @param int $usuario_id
 * @param string $acao (LOGIN, LOGOUT, INSERT, UPDATE, DELETE)
 * @param string $tabela
 * @param int $registro_id
 * @param string $detalhes
 */
function registrarAuditoria($usuario_id, $acao, $tabela = null, $registro_id = null, $detalhes = null) {
    global $pdo;
    
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $usuario_nome = $_SESSION['usuario_nome'] ?? 'Sistema';
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, usuario_nome, acao, tabela, registro_id, detalhes, ip)
            VALUES (:usuario_id, :usuario_nome, :acao, :tabela, :registro_id, :detalhes, :ip)
        ");
        
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':usuario_nome' => $usuario_nome,
            ':acao' => $acao,
            ':tabela' => $tabela,
            ':registro_id' => $registro_id,
            ':detalhes' => $detalhes,
            ':ip' => $ip
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao registrar auditoria: ' . $e->getMessage());
    }
}

/**
 * Função auxiliar para registrar histórico de edições
 * 
 * @param int $usuario_id
 * @param string $tabela
 * @param int $registro_id
 * @param string $campo
 * @param string $valor_antigo
 * @param string $valor_novo
 */
function registrarEdicao($usuario_id, $tabela, $registro_id, $campo, $valor_antigo, $valor_novo) {
    global $pdo;
    
    try {
        $usuario_nome = $_SESSION['usuario_nome'] ?? 'Sistema';
        
        $stmt = $pdo->prepare("
            INSERT INTO historico_edicoes (usuario_id, usuario_nome, tabela_afetada, registro_id, campo, valor_antigo, valor_novo)
            VALUES (:usuario_id, :usuario_nome, :tabela, :registro_id, :campo, :valor_antigo, :valor_novo)
        ");
        
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':usuario_nome' => $usuario_nome,
            ':tabela' => $tabela,
            ':registro_id' => $registro_id,
            ':campo' => $campo,
            ':valor_antigo' => $valor_antigo,
            ':valor_novo' => $valor_novo
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao registrar edição: ' . $e->getMessage());
    }
}

/**
 * Função para verificar permissão do usuário
 * 
 * @param string $permissao (exemplo: 'editar_paradas', 'visualizar_dashboard')
 * @return bool
 */
function temPermissao($permissao) {
    $perfil = $_SESSION['perfil'] ?? null;
    
    $permissoes = [
        'Administrador' => ['*'], // Acesso total
        'Supervisor' => ['editar_paradas', 'editar_velocidade', 'editar_producao', 'visualizar_dashboard', 'visualizar_relatorios'],
        'Lider' => ['registrar_paradas', 'editar_proprias_paradas', 'visualizar_paradas'],
        'Operador' => ['registrar_paradas', 'visualizar_proprias_paradas'],
        'Visualizador' => ['visualizar_dashboard', 'visualizar_relatorios']
    ];
    
    if (!isset($permissoes[$perfil])) {
        return false;
    }
    
    if (in_array('*', $permissoes[$perfil])) {
        return true;
    }
    
    return in_array($permissao, $permissoes[$perfil]);
}

?>

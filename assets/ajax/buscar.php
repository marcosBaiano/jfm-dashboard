<?php
/**
 * ======================================================
 * ENDPOINT GENÉRICO DE BUSCA - AJAX
 * ======================================================
 * Consolidação dos 3 endpoints anteriores em um único arquivo
 * Versão 2.0 - Refatorado com validação centralizada
 * 
 * Parâmetros suportados:
 * - tipo: 'descricao' | 'produto' | 'sugestao'
 * - termo: texto de busca
 * - codigo: código específico
 * - setor: setor para filtro
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../../banco/conexao.php';
require_once '../../includes/functions.php';

try {
    // Validar tipo de busca
    $tipo = validarParametro('tipo', 'string', null, true);
    if (is_array($tipo)) {
        erroValidacao($tipo['erro']);
    }
    
    $tipos_permitidos = ['descricao', 'produto', 'sugestao'];
    if (!in_array($tipo, $tipos_permitidos)) {
        erroValidacao("Tipo de busca inválido: '$tipo'");
    }
    
    $resposta = [];
    
    // ====== TIPO: Buscar Descrição por Código e Setor ======
    if ($tipo === 'descricao') {
        $codigo = validarParametro('codigo', 'string', null, true);
        $setor = validarParametro('setor', 'string', null, true);
        
        if (is_array($codigo)) erroValidacao($codigo['erro']);
        if (is_array($setor)) erroValidacao($setor['erro']);
        
        $sql = "SELECT descricao FROM descricoes 
                WHERE codigo = ? AND setor = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$codigo, $setor]);
        
        $resposta = ['descricao' => ''];
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resposta['descricao'] = $row['descricao'];
        }
    }
    
    // ====== TIPO: Buscar Produtos (Pai) ======
    else if ($tipo === 'produto') {
        $termo = validarParametro('termo', 'string');
        $termo = $termo ?: (validarParametro('term', 'string') ?: 
                            (validarParametro('query', 'string', '')));
        
        if (empty($termo) || strlen($termo) < 2) {
            echo json_encode([]);
            exit;
        }
        
        $termoLimpo = "%{$termo}%";
        $sql = "SELECT DISTINCT codigo_pai, descricao_pai FROM estrutura 
                WHERE descricao_pai LIKE ? LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$termoLimpo]);
        
        $resposta = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resposta[] = [
                'codigo' => htmlspecialchars($row['codigo_pai'], ENT_QUOTES, 'UTF-8'),
                'descricao' => htmlspecialchars($row['descricao_pai'], ENT_QUOTES, 'UTF-8')
            ];
        }
    }
    
    // ====== TIPO: Buscar Sugestões de Paradas ======
    else if ($tipo === 'sugestao') {
        $termo = validarParametro('termo', 'string');
        $termo = $termo ?: (validarParametro('term', 'string') ?: 
                            (validarParametro('query', 'string', '')));
        $setor = validarParametro('setor', 'string', '');
        
        if (empty($termo) || strlen($termo) < 2) {
            echo json_encode([]);
            exit;
        }
        
        if (empty($setor)) {
            erroValidacao("Setor é obrigatório para buscar sugestões");
        }
        
        $termoLimpo = "%{$termo}%";
        $sql = "SELECT DISTINCT codigo, descricao FROM descricoes 
                WHERE descricao LIKE ? AND setor = ? LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$termoLimpo, $setor]);
        
        $resposta = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resposta[] = [
                'codigo' => htmlspecialchars($row['codigo'], ENT_QUOTES, 'UTF-8'),
                'descricao' => htmlspecialchars($row['descricao'], ENT_QUOTES, 'UTF-8')
            ];
        }
    }
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro ao processar requisição',
        'mensagem' => 'Contate o administrador do sistema'
    ]);
}
?>

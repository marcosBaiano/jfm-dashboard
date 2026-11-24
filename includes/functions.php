<?php
/**
 * ======================================================
 * FUNÇÕES AUXILIARES DO SISTEMA
 * ======================================================
 */

/**
 * Converter valor decimal (US format -> BR format)
 */
function decimal($valor) {
    if ($valor === '' || $valor === null) {
        return null;
    }
    return str_replace(',', '.', $valor);
}

/**
 * Converter data/hora para formato MySQL
 */
function dataHora($valor) {
    if (!$valor) {
        return null;
    }
    return date('Y-m-d H:i:s', strtotime($valor));
}

/**
 * Converter data/hora MySQL para formato brasileiro
 */
function formatarDataHora($dataHora) {
    if (!$dataHora) {
        return '-';
    }
    return date('d/m/Y H:i:s', strtotime($dataHora));
}

/**
 * Converter data MySQL para formato brasileiro
 */
function formatarData($data) {
    if (!$data) {
        return '-';
    }
    return date('d/m/Y', strtotime($data));
}

/**
 * Formatar número para exibição
 */
function formatarNumero($numero, $decimais = 2) {
    if ($numero === null) {
        return '-';
    }
    return number_format((float)$numero, $decimais, ',', '.');
}

/**
 * Formatar percentual
 */
function formatarPercentual($valor, $decimais = 2) {
    if ($valor === null) {
        return '-';
    }
    return number_format((float)$valor, $decimais, ',', '.') . '%';
}

/**
 * Obter IP do cliente
 */
function obterIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Limpar entrada
 */
function limpar($string) {
    return trim(htmlspecialchars($string, ENT_QUOTES, 'UTF-8'));
}

/**
 * ======================================================
 * FUNÇÕES DE VALIDAÇÃO E SANITIZAÇÃO (v2.0)
 * ======================================================
 * Camada centralizada de validação para toda aplicação
 */

/**
 * Validar e sanitizar parâmetro GET/POST
 * @param string $chave Nome do parâmetro
 * @param string $tipo (string|int|float|bool)
 * @param mixed $padrao Valor padrão se não existir
 * @param bool $obrigatorio Se true, retorna erro se vazio
 * @return mixed Valor sanitizado ou null
 */
function validarParametro($chave, $tipo = 'string', $padrao = null, $obrigatorio = false) {
    $valor = $_GET[$chave] ?? $_POST[$chave] ?? null;
    
    if ($valor === null || $valor === '') {
        if ($obrigatorio) {
            return ['erro' => "Parâmetro '$chave' é obrigatório"];
        }
        return $padrao;
    }
    
    $valor = trim($valor);
    
    switch ($tipo) {
        case 'int':
            if (!is_numeric($valor) || strpos($valor, '.') !== false) {
                return ['erro' => "Parâmetro '$chave' deve ser um número inteiro"];
            }
            return (int)$valor;
            
        case 'float':
            if (!is_numeric($valor)) {
                return ['erro' => "Parâmetro '$chave' deve ser um número decimal"];
            }
            return (float)str_replace(',', '.', $valor);
            
        case 'bool':
            return in_array(strtolower($valor), ['true', '1', 'sim', 'yes', 'on']);
            
        case 'string':
        default:
            return limpar($valor);
    }
}

/**
 * Validar múltiplos parâmetros de uma vez
 * @param array $parametros Array com chave => [tipo, obrigatorio]
 * @return array|bool Array com dados sanitizados ou array com erros
 */
function validarParametros($parametros) {
    $validados = [];
    $erros = [];
    
    foreach ($parametros as $chave => $config) {
        $tipo = $config['tipo'] ?? 'string';
        $obrigatorio = $config['obrigatorio'] ?? false;
        
        $resultado = validarParametro($chave, $tipo, null, $obrigatorio);
        
        if (is_array($resultado) && isset($resultado['erro'])) {
            $erros[] = $resultado['erro'];
        } else {
            $validados[$chave] = $resultado;
        }
    }
    
    if (!empty($erros)) {
        return ['erros' => $erros];
    }
    
    return $validados;
}

/**
 * Validar email
 * @param string $email
 * @return bool
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar data em formato YYYY-MM-DD
 * @param string $data
 * @return bool
 */
function validarData($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

/**
 * Responder com erro de validação
 * @param array $erros
 */
function erroValidacao($erros) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro de validação',
        'erros' => is_array($erros) ? $erros : [$erros]
    ]);
    exit;
}

/**
 * Gerar resposta JSON
 */
function respostaJSON($sucesso, $mensagem, $dados = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
        'dados' => $dados
    ]);
    exit();
}

/**
 * Calcular OEE
 * OEE = Disponibilidade × Performance × Qualidade
 */
function calcularOEE($disponibilidade, $performance, $qualidade) {
    if ($disponibilidade <= 0 || $performance <= 0 || $qualidade <= 0) {
        return 0;
    }
    
    $oee = ($disponibilidade / 100) * ($performance / 100) * ($qualidade / 100) * 100;
    return round($oee, 2);
}

/**
 * Calcular disponibilidade
 * Disponibilidade = Tempo Produzindo / (Tempo Produzindo + Tempo Parado)
 */
function calcularDisponibilidade($tempoProduzindo, $tempoParado) {
    $total = $tempoProduzindo + $tempoParado;
    
    if ($total == 0) {
        return 0;
    }
    
    $disponibilidade = ($tempoProduzindo / $total) * 100;
    return round($disponibilidade, 2);
}

/**
 * Calcular performance
 * Performance = Produção Real / Produção Planejada
 */
function calcularPerformance($producaoReal, $producaoPlanejada) {
    if ($producaoPlanejada <= 0) {
        return 0;
    }
    
    $performance = ($producaoReal / $producaoPlanejada) * 100;
    return round($performance, 2);
}

/**
 * Calcular qualidade
 * Qualidade = Produção Boa / Produção Total
 */
function calcularQualidade($producaoBoa, $producaoTotal) {
    if ($producaoTotal <= 0) {
        return 0;
    }
    
    $qualidade = ($producaoBoa / $producaoTotal) * 100;
    return round($qualidade, 2);
}

/**
 * Calcular eficiência
 * Eficiência = (Velocidade Real / Velocidade Planejada) * 100
 */
function calcularEficiencia($velocidadeReal, $velocidadePlanejada) {
    if ($velocidadePlanejada <= 0) {
        return 0;
    }
    
    $eficiencia = ($velocidadeReal / $velocidadePlanejada) * 100;
    return round($eficiencia, 2);
}

?>

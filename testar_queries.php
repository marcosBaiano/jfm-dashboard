<?php
require_once 'banco/conexao_pdo.php';

echo "=== TESTANDO QUERIES DOS DASHBOARDS ===\n\n";

// Data para teste
$dataTeste = '2026-06-21';

echo "Data de teste: $dataTeste\n\n";

// ========== OEE.php ==========
echo "=== OEE.php ===\n";

// Paradas
echo "\n1. Paradas (tempo parado):\n";
try {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado
        FROM paradas p
        INNER JOIN registros r ON p.registro_id = r.id
        WHERE DATE(r.data_registro) = :data_selecionada
    ");
    $stmt->execute([':data_selecionada' => $dataTeste]);
    $result = $stmt->fetch();
    echo "   Resultado: " . $result['tempo_parado'] . " minutos\n";
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
}

// Produção
echo "\n2. Produção (total e boa):\n";
try {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(pr.producao), 0) as total,
            COALESCE(SUM(pr.producao - (pr.perda_1 + pr.perda_2 + pr.perda_3 + pr.perda_4)), 0) as boa
        FROM producao pr
        INNER JOIN registros r ON pr.registro_id = r.id
        WHERE DATE(r.data_registro) = :data_selecionada
    ");
    $stmt->execute([':data_selecionada' => $dataTeste]);
    $result = $stmt->fetch();
    echo "   Total: " . $result['total'] . ", Boa: " . $result['boa'] . "\n";
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
}

// ========== VELOCIDADE.php ==========
echo "\n=== VELOCIDADE.php ===\n";

echo "\n1. Velocidades da data:\n";
try {
    $stmt = $pdo->prepare("
        SELECT 
            v.id,
            v.maquina,
            v.velocidade_planejada,
            v.velocidade_real
        FROM velocidades v
        WHERE DATE(v.data_registro) = :data_selecionada
    ");
    $stmt->execute([':data_selecionada' => $dataTeste]);
    $results = $stmt->fetchAll();
    echo "   ERRO: Colunas 'maquina' e 'velocidade_real' não existem na tabela!\n";
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
}

// Mostrar colunas reais
echo "\n2. Colunas reais da tabela velocidades:\n";
$stmt = $pdo->query("SELECT * FROM velocidades LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Colunas: " . implode(", ", array_keys($row)) . "\n";

// Query corrigida
echo "\n3. Query CORRIGIDA para velocidades:\n";
try {
    $stmt = $pdo->prepare("
        SELECT 
            v.id,
            v.registro_id,
            v.hora,
            v.velocidade as velocidade_real,
            v.velocidade_planejada,
            ROUND((v.velocidade / NULLIF(v.velocidade_planejada, 0)) * 100, 2) as eficiencia,
            r.data_registro
        FROM velocidades v
        LEFT JOIN registros r ON v.registro_id = r.id
        WHERE DATE(r.data_registro) = :data_selecionada
    ");
    $stmt->execute([':data_selecionada' => $dataTeste]);
    $results = $stmt->fetchAll();
    echo "   Registros encontrados: " . count($results) . "\n";
    if (!empty($results)) {
        foreach ($results as $row) {
            echo "     - Data: " . $row['data_registro'] . ", Hora: " . $row['hora'] . ", Eficiência: " . $row['eficiencia'] . "%\n";
        }
    }
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
}

// ========== PARADAS.php ==========
echo "\n=== PARADAS.php ===\n";
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.inicio,
            p.fim,
            p.codigo,
            p.descricao,
            COALESCE(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim), 0) as tempo_parado,
            l.linha,
            r.operador
        FROM paradas p
        LEFT JOIN registros r ON p.registro_id = r.id
        LEFT JOIN linhas l ON r.linha_id = l.id
        WHERE DATE(p.inicio) = :data_selecionada
    ");
    $stmt->execute([':data_selecionada' => $dataTeste]);
    $results = $stmt->fetchAll();
    echo "Paradas encontradas: " . count($results) . "\n";
    if (!empty($results)) {
        foreach ($results as $row) {
            echo "  - Código: " . $row['codigo'] . ", Tempo: " . $row['tempo_parado'] . " min, Operador: " . $row['operador'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

// ========== EFICIENCIA.php ==========
echo "\n=== EFICIENCIA.php ===\n";
try {
    $stmt = $pdo->prepare("
        SELECT 
            l.linha,
            ROUND(AVG((v.velocidade / NULLIF(v.velocidade_planejada, 0)) * 100), 2) as eficiencia_media,
            COUNT(v.id) as quantidade_registros
        FROM velocidades v
        LEFT JOIN registros r ON v.registro_id = r.id
        LEFT JOIN linhas l ON r.linha_id = l.id
        WHERE DATE(r.data_registro) = :data_selecionada
        AND v.velocidade_planejada > 0
        GROUP BY l.id, l.linha
    ");
    $stmt->execute([':data_selecionada' => $dataTeste]);
    $results = $stmt->fetchAll();
    echo "Linhas com dados: " . count($results) . "\n";
    if (!empty($results)) {
        foreach ($results as $row) {
            echo "  - Linha: " . $row['linha'] . ", Eficiência: " . $row['eficiencia_media'] . "%, Registros: " . $row['quantidade_registros'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>

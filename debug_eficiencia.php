<?php
require_once 'banco/conexao_pdo.php';

$dataSelecionada = '2026-06-21';

echo "=== DEBUG EFICIÊNCIA ===\n\n";

// Verificar dados na tabela velocidades com data_registro
echo "1. Velocidades com registro_id > 0 para 2026-06-21:\n";
$stmt = $pdo->prepare("
    SELECT v.id, v.registro_id, v.velocidade, v.velocidade_planejada, r.data_registro, r.linha_id
    FROM velocidades v
    LEFT JOIN registros r ON v.registro_id = r.id
    WHERE DATE(r.data_registro) = :data
    LIMIT 5
");
$stmt->execute([':data' => $dataSelecionada]);
$vel = $stmt->fetchAll();
foreach ($vel as $v) {
    echo "  ID: {$v['id']}, registro_id: {$v['registro_id']}, velocidade: {$v['velocidade']}, planejada: {$v['velocidade_planejada']}, linha_id: {$v['linha_id']}\n";
}

echo "\n2. Linhas disponíveis na tabela linhas:\n";
$stmt = $pdo->query("SELECT id, linha FROM linhas LIMIT 10");
$linhas = $stmt->fetchAll();
foreach ($linhas as $l) {
    echo "  ID: {$l['id']}, Linha: {$l['linha']}\n";
}

echo "\n3. Query de eficiência por linha:\n";
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(l.linha, 'Sem Linha') as linha,
        ROUND(AVG((v.velocidade / NULLIF(v.velocidade_planejada, 0)) * 100), 2) as eficiencia_media,
        COUNT(v.id) as quantidade_registros
    FROM velocidades v
    LEFT JOIN registros r ON v.registro_id = r.id
    LEFT JOIN linhas l ON r.linha_id = l.id
    WHERE DATE(r.data_registro) = :data
    AND v.velocidade_planejada > 0
    GROUP BY l.id
    ORDER BY eficiencia_media DESC
");
$stmt->execute([':data' => $dataSelecionada]);
$result = $stmt->fetchAll();

if (empty($result)) {
    echo "  Nenhum resultado encontrado!\n";
    echo "\n  Testando query SEM o filtro velocidade_planejada > 0:\n";
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(l.linha, 'Sem Linha') as linha,
            COUNT(v.id) as quantidade_registros
        FROM velocidades v
        LEFT JOIN registros r ON v.registro_id = r.id
        LEFT JOIN linhas l ON r.linha_id = l.id
        WHERE DATE(r.data_registro) = :data
        GROUP BY l.id
    ");
    $stmt->execute([':data' => $dataSelecionada]);
    $result2 = $stmt->fetchAll();
    if (!empty($result2)) {
        foreach ($result2 as $row) {
            echo "    Linha: {$row['linha']}, Registros: {$row['quantidade_registros']}\n";
        }
    }
} else {
    foreach ($result as $row) {
        echo "  Linha: {$row['linha']}, Eficiência: {$row['eficiencia_media']}%, Registros: {$row['quantidade_registros']}\n";
    }
}

echo "\n4. Valores de velocidade_planejada para a data:\n";
$stmt = $pdo->prepare("
    SELECT DISTINCT v.velocidade_planejada, COUNT(*) as qtd
    FROM velocidades v
    LEFT JOIN registros r ON v.registro_id = r.id
    WHERE DATE(r.data_registro) = :data
    GROUP BY v.velocidade_planejada
");
$stmt->execute([':data' => $dataSelecionada]);
$planejadas = $stmt->fetchAll();
foreach ($planejadas as $p) {
    echo "  Planejada: {$p['velocidade_planejada']}, Quantidade: {$p['qtd']}\n";
}
?>

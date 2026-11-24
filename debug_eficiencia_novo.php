<?php
require_once 'banco/conexao_pdo.php';

$dataSelecionada = '2026-06-21';

echo "=== DEBUG EFICIÊNCIA (QUERY CORRIGIDA) ===\n\n";

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

echo "\n2. Velocidades totais para a data:\n";
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM velocidades v
    LEFT JOIN registros r ON v.registro_id = r.id
    WHERE DATE(r.data_registro) = :data
");
$stmt->execute([':data' => $dataSelecionada]);
$total = $stmt->fetch();
echo "  Total de velocidades: {$total['total']}\n";

echo "\n3. Query de eficiência por linha (CORRIGIDA - usando velocidade média):\n";
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(l.linha, 'Sem Linha') as linha,
        ROUND(AVG(COALESCE(v.velocidade, 0)), 2) as velocidade_media,
        COUNT(v.id) as quantidade_registros
    FROM velocidades v
    LEFT JOIN registros r ON v.registro_id = r.id
    LEFT JOIN linhas l ON r.linha_id = l.id
    WHERE DATE(r.data_registro) = :data
    GROUP BY l.id
    ORDER BY velocidade_media DESC
");
$stmt->execute([':data' => $dataSelecionada]);
$result = $stmt->fetchAll();

if (empty($result)) {
    echo "  Nenhum resultado encontrado!\n";
} else {
    foreach ($result as $row) {
        echo "  Linha: {$row['linha']}, Velocidade Média: {$row['velocidade_media']}, Registros: {$row['quantidade_registros']}\n";
    }
}

echo "\n4. Tendência dos últimos 30 dias:\n";
$dataInicio = date('Y-m-d', strtotime('-29 days', strtotime($dataSelecionada)));
$stmt = $pdo->prepare("
    SELECT 
        DATE(r.data_registro) as data,
        ROUND(AVG(COALESCE(v.velocidade, 0)), 2) as velocidade_media
    FROM velocidades v
    LEFT JOIN registros r ON v.registro_id = r.id
    WHERE DATE(r.data_registro) >= :data_inicio
    AND DATE(r.data_registro) <= :data_fim
    GROUP BY DATE(r.data_registro)
    ORDER BY DATE(r.data_registro) DESC
    LIMIT 5
");
$stmt->execute([':data_inicio' => $dataInicio, ':data_fim' => $dataSelecionada]);
$tendencia = $stmt->fetchAll();
foreach ($tendencia as $t) {
    echo "  Data: {$t['data']}, Velocidade Média: {$t['velocidade_media']}\n";
}
?>

<?php
require_once 'banco/conexao_pdo.php';

echo "=== ESTRUTURA DAS TABELAS ===\n\n";

// Tabela registros
echo "REGISTROS:\n";
$stmt = $pdo->query('DESCRIBE registros');
foreach ($stmt->fetchAll() as $row) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Tabela paradas
echo "\nPARADAS:\n";
$stmt = $pdo->query('DESCRIBE paradas');
foreach ($stmt->fetchAll() as $row) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Tabela producao
echo "\nPRODUCAO:\n";
$stmt = $pdo->query('DESCRIBE producao');
foreach ($stmt->fetchAll() as $row) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Tabela velocidades
echo "\nVELOCIDADES:\n";
$stmt = $pdo->query('DESCRIBE velocidades');
foreach ($stmt->fetchAll() as $row) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== QUANTIDADE DE REGISTROS ===\n\n";

// Contar registros
$tables = ['registros', 'paradas', 'producao', 'velocidades', 'linhas'];
foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetch()[0];
    echo "$table: $count registros\n";
}

echo "\n=== AMOSTRA DE DADOS ===\n\n";

// Amostra de registros
echo "REGISTROS (últimos 5):\n";
$stmt = $pdo->query('SELECT * FROM registros ORDER BY id DESC LIMIT 5');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "  ID: " . $row['id'] . ", Data: " . $row['data_registro'] . ", Operador: " . $row['operador'] . "\n";
}

// Amostra de paradas
echo "\nPARADAS (últimas 5):\n";
$stmt = $pdo->query('SELECT p.*, r.data_registro FROM paradas p LEFT JOIN registros r ON p.registro_id = r.id ORDER BY p.id DESC LIMIT 5');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "  ID: " . $row['id'] . ", Data: " . ($row['data_registro'] ?? 'N/A') . ", Código: " . $row['codigo'] . "\n";
}

// Amostra de producao
echo "\nPRODUCAO (últimas 5):\n";
$stmt = $pdo->query('SELECT p.*, r.data_registro FROM producao p LEFT JOIN registros r ON p.registro_id = r.id ORDER BY p.id DESC LIMIT 5');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "  ID: " . $row['id'] . ", Data: " . ($row['data_registro'] ?? 'N/A') . ", Produção: " . $row['producao'] . "\n";
}

// Amostra de velocidades
echo "\nVELOCIDADES (últimas 5):\n";
$stmt = $pdo->query('SELECT * FROM velocidades ORDER BY id DESC LIMIT 5');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "  ID: " . $row['id'] . ", Data: " . $row['data_registro'] . ", Máquina: " . $row['maquina'] . ", Eficiência: " . round(($row['velocidade_real'] / $row['velocidade_planejada']) * 100, 2) . "%\n";
}
?>
